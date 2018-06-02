<?php

/**
 * This is the model class for table "{{question_user_operate_analysis}}".
 *
 * The followings are the available columns in table '{{question_user_operate_analysis}}':
 * @property integer $id
 * @property string $analysis_date
 * @property string $member_user_id
 * @property string $search_count
 * @property string $question_count
 * @property integer $answer_count
 * @property string $show_question_count
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Question\Analysis;
use application\models\Question\Analysis\QuestionUserLevelAnalysis;
class QuestionUserOperateAnalysis extends \CActiveRecord
{
	public $user_count;
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{question_user_operate_analysis}}';
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
			'member_user_id' => '用户ID',
			'search_count' => '搜索次数',
			'question_count' => '提问次数',
			'answer_count' => '回答次数',
			'show_question_count' => '查看问题次数',
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
	 * @return QuestionUserOperateAnalysis the static model class
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
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

	public function generate_user_board_data($begin_date,$end_date){
		$criteria = new \CDbCriteria;
		$criteria->select = 'count(distinct member_user_id) user_count';
		if(!empty($begin_date) && !empty($end_date))
			$criteria->addBetweenCondition('analysis_date',$begin_date,$end_date);
		return self::model()->find($criteria);
	}


	public function get_r_level_user($begin_date,$end_date,$level){
		$mix_count_name = 'level_r' . $level;
		$max_count_name = 'level_r' . ($level + 1);
		$mix_count = QuestionUserLevelAnalysis::model()->$mix_count_name;

		$criteria = new \CDbCriteria;
		$criteria->select = 'member_user_id';
		$criteria->addBetweenCondition('analysis_date', $begin_date,$end_date);
		$criteria->group = 'member_user_id';
		if($level < 5){
			$max_count = QuestionUserLevelAnalysis::model()->$max_count_name;
			$criteria->having = "count(member_user_id) > $mix_count and count(member_user_id) <= $max_count";
		}else{
			$criteria->having = "count(member_user_id) > $mix_count";
		}

		$criteria->index = 'member_user_id';
		return self::model()->findAll($criteria);
	}


	public function get_m_level_user($begin_date,$end_date,$level){
		$mix_count_name = 'level_m' . $level;
		$max_count_name = 'level_m' . ($level + 1);
		$mix_count = QuestionUserLevelAnalysis::model()->$mix_count_name;

		$criteria = new \CDbCriteria;
		$criteria->select = 'member_user_id';
		$criteria->addBetweenCondition('analysis_date', $begin_date,$end_date);
		$criteria->group = 'member_user_id';
		if($level < 5){
			$max_count = QuestionUserLevelAnalysis::model()->$max_count_name;
			$criteria->having = "sum(total_count) >= $mix_count and sum(total_count) < $max_count";
		}else{
			$criteria->having = "sum(total_count) >= $mix_count";
		}

		$criteria->index = 'member_user_id';
		return self::model()->findAll($criteria);
	}
}
