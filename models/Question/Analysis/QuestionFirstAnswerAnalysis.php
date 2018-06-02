<?php

/**
 * This is the model class for table "{{question_first_answer_analysis}}".
 *
 * The followings are the available columns in table '{{question_first_answer_analysis}}':
 * @property integer $id
 * @property string $analysis_date
 * @property string $question_count
 * @property string $minute_5_answer
 * @property string $minute_15_answer
 * @property string $minute_30_answer
 * @property string $hour_1_answer
 * @property string $hour_24_answer
 * @property string $hour_48_answer
 * @property integer $hour_72_answer
 * @property string $hour_72_no_answer
 * @property string $average_answer_time
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Question\Analysis;
use application\models\Question\Question;
class QuestionFirstAnswerAnalysis extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{question_first_answer_analysis}}';
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
			'question_count' => '提问总数',
			'analysis_question_count' => '24小时内响应问题总数',
			'analysis_answer_time' => '24小时内响应总时间',
			'minute_5_answer' => '5分钟响应数',
			'minute_15_answer' => '15分钟响应数',
			'minute_30_answer' => '三十分钟响应数',
			'hour_1_answer' => '一小时响应数',
			'hour_24_answer' => '24小时响应数',
			'hour_48_answer' => '48小时内回复',
			'hour_72_answer' => '72小时回复',
			'hour_72_no_answer' => '72小时无回复',
			'average_answer_time' => '24小时内平均响应时间',
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
	 * @return QuestionFirstAnswerAnalysis the static model class
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

	// QuestionFirstAnswerAnalysis::generate_data_everyday();
	public static function generate_data_everyday(){
		$all_date = array(date('Y-m-d',strtotime('-1 day')),date('Y-m-d',strtotime('-2 day')),date('Y-m-d',strtotime('-3 day')),date('Y-m-d',strtotime('-4 day')));
		foreach($all_date as $date){
			self::generate_data($date,date('Y-m-d',strtotime('+1 day',strtotime($date))));
		}
	}


	public static function generate_data($begin_date,$end_date){
		$sql = <<<sql
				SELECT
					count(
						CASE
						WHEN analysis.answer_time <= 300 THEN
							1
						ELSE
							NULL
						END
					) minute_5_answer,
					count(
						CASE
						WHEN analysis.answer_time > 300
						AND analysis.answer_time <= 900 THEN
							1
						ELSE
							NULL
						END
					) minute_15_answer,
					count(
						CASE
						WHEN analysis.answer_time > 900
						AND analysis.answer_time <= 1800 THEN
							1
						ELSE
							NULL
						END
					) minute_30_answer,
					count(
						CASE
						WHEN analysis.answer_time > 1800
						AND analysis.answer_time <= 3600 THEN
							1
						ELSE
							NULL
						END
					) hour_1_answer,
					count(
						CASE
						WHEN analysis.answer_time > 3600
						AND analysis.answer_time <= 24 * 3600 THEN
							1
						ELSE
							NULL
						END
					) hour_24_answer,
					count(
						CASE
						WHEN analysis.answer_time > 24 * 3600
						AND analysis.answer_time <= 48 * 3600 THEN
							1
						ELSE
							NULL
						END
					) hour_48_answer,
					count(
						CASE
						WHEN analysis.answer_time > 48 * 3600
						AND analysis.answer_time <= 72 * 3600 THEN
							1
						ELSE
							NULL
						END
					) hour_72_answer,
					count(
						CASE
						WHEN analysis.answer_time IS NULL
						OR analysis.answer_time > 72 * 3600 THEN
							1
						ELSE
							NULL
						END
					) hour_72_no_answer,
					count(
						CASE
						WHEN analysis.answer_time <= 24 * 3600 THEN
							1
						ELSE
							NULL
						END
					) analysis_question_count,
					count(id) question_count,
					sum(
						CASE
						WHEN analysis.answer_time <= 24 * 3600 THEN
							analysis.answer_time
						ELSE
							0
						END
					) AS analysis_answer_time
				FROM
					(
						SELECT
							id,
							TIMESTAMPDIFF(
								SECOND,
								_create_time,
								first_answer_time
							) answer_time
						FROM
							e_question
						WHERE
							_create_time >= '$begin_date'
						AND _create_time < '$end_date'
					) AS analysis
sql;
	$result = Question::model()->findBySql($sql);
	if(!empty($result)){
		$model = self::model()->findByAttributes(array('analysis_date' => $begin_date));
		if(empty($model))
			$model = new self();
		$model->analysis_date = $begin_date;
		$model->minute_5_answer = $result->minute_5_answer;
		$model->minute_15_answer = $result->minute_15_answer;
		$model->minute_30_answer = $result->minute_30_answer;
		$model->hour_1_answer = $result->hour_1_answer;
		$model->hour_24_answer = $result->hour_24_answer;
		$model->hour_48_answer = $result->hour_48_answer;
		$model->hour_72_answer = $result->hour_72_answer;
		$model->hour_72_no_answer = $result->hour_72_no_answer;
		$model->question_count = $result->question_count;
		$model->analysis_question_count = $result->analysis_question_count;
		$model->analysis_answer_time = $result->analysis_answer_time;
		$model->average_answer_time = empty($result->analysis_question_count) ? 0 : intval($result->analysis_answer_time/$result->analysis_question_count);
		$model->save();
	}
	}

	public function generate_board_data($begin_date,$end_date){
		$sql = <<<sql
SELECT
	 COALESCE(sum(analysis_answer_time),0) analysis_answer_time,
	 COALESCE(sum(analysis_question_count),0) analysis_question_count
FROM
	e_question_first_answer_analysis
sql;
		if(!empty($begin_date) && !empty($end_date)){
			$sql = $sql . " WHERE analysis_date >= '$begin_date' AND analysis_date <= '$end_date'";
		}
		return self::model()->findBySql($sql);
	}

	public function get_average_answer_time(){
		return intval(( $this->average_answer_time * 100 ) / 60)/100;
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
			$data[$k]['average_answer_time'] = $v->get_average_answer_time();
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}
}
