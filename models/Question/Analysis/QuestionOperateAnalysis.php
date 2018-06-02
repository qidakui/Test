<?php

/**
 * This is the model class for table "{{question_operate_analysis}}".
 *
 * The followings are the available columns in table '{{question_operate_analysis}}':
 * @property integer $id
 * @property string $analysis_date
 * @property string $search_count
 * @property string $question_count
 * @property string $answer_count
 * @property string $show_question_count
 * @property string $adopt_count
 * @property string $upcount
 * @property string $no_answer_count
 * @property string $search_question_ratio
 * @property string $adopt_ratio
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Question\Analysis;
use application\models\Question\Analysis\QuestionFirstAnswerAnalysis;
use application\models\Question\Analysis\QuestionUserOperateAnalysis;
use application\models\Question\Analysis\QuestionUserLevelAnalysis;
class QuestionOperateAnalysis extends \CActiveRecord
{
	public $board_key = 'board_data';
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{question_operate_analysis}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array();
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'analysis_date' => '统计日期',
			'search_count' => '查询总数',
			'question_count' => '提问总数',
			'answer_count' => '回答总数',
			'show_question_count' => '查看问题总数',
			'adopt_count' => '采纳总数',
			'upcount' => '点赞总数',
			'no_answer_count' => '0回复总数',
			'search_question_ratio' => '搜问比(100倍)',
			'adopt_ratio' => '采纳率(100倍)',
			'_create_time' => '创建时间',
			'_update_time' => '更新时间',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return QuestionOperateAnalysis the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				$this->_update_time = date('y-m-d H:m:s');
			}
			return true;
		}else{
			return false;
		}
	}

	public function get_search_question_ratio($date=0){
		if(empty($date))
			$date = empty($this->search_question_ratio) ? 0 : $this->search_question_ratio;
		return ($date/100) . '%';
	}

	public function get_adopt_ratio($date=0){
		if(empty($date))
			$date = empty($this->adopt_ratio) ? 0 : $this->adopt_ratio;
		return ($date/100) . '%';
	}

	public function generate_board_data($begin_date,$end_date){
		$sql = <<<sql
SELECT
	 COALESCE(sum(question_count),0) question_count,
	 COALESCE(sum(answer_count),0) answer_count,
	 COALESCE(sum(adopt_count),0) adopt_count,
	 COALESCE(sum(search_count),0) search_count
FROM
	e_question_operate_analysis
sql;
		if(!empty($begin_date) && !empty($end_date)){
			$sql = $sql . " WHERE analysis_date >= '$begin_date' AND analysis_date <= '$end_date'";
		}
		return self::model()->findBySql($sql);
	}

	public function get_board_data(){
		$result = \Yii::app()->redis->getClient()->get($this->board_key);
		if(empty($result)){
			self::model()->generate_all_board_data(time());
			$result = \Yii::app()->redis->getClient()->get($this->board_key);
		}
		return unserialize($result);
	}

	//生成全部看板数据
	public function generate_all_board_data($date){
		//处理日期写入array进行遍历,包含昨日,前日,本周,上周,本月,上月,本年,上年,全部,
		$all_date = $this->get_analysis_date_for_board($date);
		foreach($all_date as $k=>$v){
			$begin_date = $v['begin_date'];
			$end_date = $v['end_date'];
			//获取指定日期范围内的搜索次数,答案次数,提问次数,采纳次数
			$operate_result = self::model()->generate_board_data($begin_date, $end_date);
			$all_date[$k]['question_count'] = $operate_result->question_count;
			$all_date[$k]['answer_count'] = $operate_result->answer_count;
			//处理搜问比及采纳率
			$all_date[$k]['search_question_ratio'] = $operate_result->get_search_question_ratio($operate_result->set_search_question_ratio());
			$all_date[$k]['adopt_ratio'] = $operate_result->get_adopt_ratio($operate_result->set_adopt_ratio());
			//获取指定日期范围内的响应时间
			$first_answer_result = QuestionFirstAnswerAnalysis::model()->generate_board_data($begin_date, $end_date);
			$first_answer_result->average_answer_time = empty($first_answer_result->analysis_question_count) ? 0 : intval($first_answer_result->analysis_answer_time/$first_answer_result->analysis_question_count);
			$all_date[$k]['first_answer_time'] = $first_answer_result->get_average_answer_time();
			//获取指定日期范围内的答疑解惑用户数
			$user_count_result = QuestionUserOperateAnalysis::model()->generate_user_board_data($begin_date, $end_date);
			$all_date[$k]['user_count'] = $user_count_result->user_count;
			//如果是获取本月或上月用户级别,直接从用户等级统计表(QuestionUserLevelAnalysis)获取
			if($k == 'month' || $k == 'before_month'){
				$user_level_result = QuestionUserLevelAnalysis::model()->findAll("analysis_month BETWEEN '$begin_date' AND '$end_date'");
				if(empty($user_level_result)){
					$all_date[$k][1] = array(
							'level_1_count' => 0,
							'level_2_count' => 0,
							'level_3_count' => 0,
							'level_4_count' => 0,
							'level_5_count' => 0,
					);
					$all_date[$k][2] = $all_date[$k][1];
				}else{
					foreach($user_level_result as $v){
						$all_date[$k][$v->level_type] = array(
								'level_1_count' => $v->level_1_count,
								'level_2_count' => $v->level_2_count,
								'level_3_count' => $v->level_3_count,
								'level_4_count' => $v->level_4_count,
								'level_5_count' => $v->level_5_count,
						);
					}
				}
			}
			if($k == 'year' || $k == 'before_year'){
				//如果统计类型为本年,开始日期为当年一月一日,结束日期为上个自然月最后一日
				if($k == 'year'){
					$year_level_begin_date = date("Y-m-d H:i:s",mktime(0, 0 , 0,1,1,date("Y",$date)));
					$year_level_end_date = date("Y-m-d H:i:s",mktime(23,59,59,date("m",$date),0,date("Y",$date)));
				}else{
					$year_level_begin_date = $begin_date;
					$year_level_end_date = $end_date;
				}
				//等级系数等于结束日期的月份
				$coefficient = date("m",strtotime($year_level_end_date));
				$user_level_result = QuestionUserLevelAnalysis::model()->generate_board_data($year_level_begin_date, $year_level_end_date,$coefficient);
				//1为M级,2为R级
				$all_date[$k][1] = array(
							'level_1_count' => $user_level_result->m1,
							'level_2_count' => $user_level_result->m2,
							'level_3_count' => $user_level_result->m3,
							'level_4_count' => $user_level_result->m4,
							'level_5_count' => $user_level_result->m5,
				);
				$all_date[$k][2] = array(
							'level_1_count' => $user_level_result->r1,
							'level_2_count' => $user_level_result->r2,
							'level_3_count' => $user_level_result->r3,
							'level_4_count' => $user_level_result->r4,
							'level_5_count' => $user_level_result->r5,
				);
			}
		}
		$all_date['update_time'] = date("Y-m-d H:i:s");
		return \Yii::app()->redis->getClient()->set($this->board_key ,serialize($all_date));
	}

	//生成看板需要统计的全部日期段,包含昨日,前日,本周,上周,本月,上月,本年,上年,全部,
	public function get_analysis_date_for_board($date){
		$this_date = array('m' => date("m",$date),'d' => date("d",$date),'y' => date("Y",$date),'w' => date("w",$date),'t'=> date("t",$date));
		if($this_date['w'] == 0)
			$this_date['w'] = 7;
		$all_date = array();
		$all_date['yesterday']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,$this_date['m'],$this_date['d']-1,$this_date['y']));
		$all_date['yesterday']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,$this_date['m'],$this_date['d']-1,$this_date['y']));
		$all_date['before_yesterday']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,$this_date['m'],$this_date['d']-2,$this_date['y']));
		$all_date['before_yesterday']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,$this_date['m'],$this_date['d']-2,$this_date['y']));
		$all_date['week']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,$this_date['m'],$this_date['d']-$this_date['w']+1,$this_date['y']));
		$all_date['week']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,$this_date['m'],$this_date['d']-$this_date['w']+7,$this_date['y']));
		$all_date['before_week']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,$this_date['m'],$this_date['d']-$this_date['w']+1-7,$this_date['y']));
		$all_date['before_week']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,$this_date['m'],$this_date['d']-$this_date['w']+7-7,$this_date['y']));
		$all_date['month']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,$this_date['m'],1,$this_date['y']));
		$all_date['month']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,$this_date['m'],$this_date['t'],$this_date['y']));
		$all_date['before_month']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,$this_date['m']-1,1,$this_date['y']));
		$all_date['before_month']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,$this_date['m'] ,0,$this_date['y']));
		$all_date['year']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,1,1,$this_date['y']));
		$all_date['year']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,1,0,$this_date['y'] +1));
		$all_date['before_year']['begin_date'] = date("Y-m-d H:i:s",mktime(0, 0 , 0,1,1,$this_date['y']-1));
		$all_date['before_year']['end_date'] = date("Y-m-d H:i:s",mktime(23,59,59,1 ,0,$this_date['y']));
		$all_date['total']['begin_date']= '';
		$all_date['total']['end_date']= '';
		return $all_date;
	}

	//处理搜问比
	public function set_search_question_ratio(){
		if(!empty($this->search_count) && !empty($this->question_count)){
			return intval(($this->search_count * 10000)/$this->question_count);
		}else{
			return 0;
		}
	}
	//处理采纳率
	public function set_adopt_ratio(){
		if(!empty($this->question_count) && !empty($this->adopt_count)){
			return intval(($this->adopt_count * 10000)/$this->question_count);
		}else{
			return 0;
		}
	}

	public function get_list($con, $order, $limit=-1, $offset=0){
		$criteria = new \CDbCriteria;
		if(!empty($con)){
			foreach ($con as $key => $val) {
				if(is_array($val) && isset($val[0])){
					switch($val[0]){
						case 'search_like':
							$criteria->compare($key, $val[1],true);
							break;
						case 'between':
							$criteria->addBetweenCondition($key,$val[1],$val[2]);
							break;
						case 'not_in':
							$criteria->addNotInCondition($key,$val[1]);
							break;
						default:
							$criteria->compare($key, $val);
					}
				}else{
					$criteria->compare($key, $val);
				}
			}
		}

		$criteria->order = $order;
		$criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
		$criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

		$ret = self::model()->findAll($criteria);
		$count = self::model()->count($criteria);

		foreach($ret as $k => $v){
			$data[$k] = $v->attributes;
			$data[$k]['search_question_ratio'] = $v->get_search_question_ratio();
			$data[$k]['adopt_ratio'] = $v->get_adopt_ratio();

		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

}
