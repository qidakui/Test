<?php

/**
 * This is the model class for table "{{question_user_level_analysis}}".
 *
 * The followings are the available columns in table '{{question_user_level_analysis}}':
 * @property string $id
 * @property string $analysis_month
 * @property string $level_type
 * @property string $level_1_count
 * @property string $level_2_count
 * @property string $level_3_count
 * @property string $level_4_count
 * @property string $level_5_count
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Question\Analysis;
class QuestionUserLevelAnalysis extends \CActiveRecord
{
	public $m1,$m2,$m3,$m4,$m5,$r1,$r2,$r3,$r4,$r5;

	public $level_m1 = 0;
	public $level_m2 = 30;
	public $level_m3 = 75;
	public $level_m4 = 150;
	public $level_m5 = 300;
	public $level_r1 = 0;
	public $level_r2 = 5;
	public $level_r3 = 10;
	public $level_r4 = 15;
	public $level_r5 = 20;

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{question_user_level_analysis}}';
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
			'analysis_month' => '统计月份',
			'level_type' => '级别类型(1:M级,2:R级)',
			'level_1_count' => '级别1人数',
			'level_2_count' => '级别2人数',
			'level_3_count' => '级别3人数',
			'level_4_count' => '级别4人数',
			'level_5_count' => '级别5人数',
			'_create_time' => 'Create Time',
			'_update_time' => 'Update Time',
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
	 * @return QuestionUserLevelAnalysis the static model class
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
			$data[$k]['analysis_month'] = date('Y-m-d',strtotime($v->analysis_month));

		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

	public function generate_board_data($begin_date,$end_date,$coefficient=1){
		$sql = <<<sql
SELECT
	count( CASE WHEN day_count <= $this->level_r2*$coefficient THEN 1 ELSE NULL END ) as r1,
	count( CASE WHEN day_count > $this->level_r2*$coefficient AND day_count <= $this->level_r3*$coefficient THEN 1 ELSE NULL END ) as r2,
	count( CASE WHEN day_count > $this->level_r3*$coefficient AND day_count <= $this->level_r4*$coefficient THEN 1 ELSE NULL END ) as r3,
	count( CASE WHEN day_count > $this->level_r4*$coefficient AND day_count <= $this->level_r5*$coefficient THEN 1 ELSE NULL END ) as r4,
	count( CASE WHEN day_count > $this->level_r5*$coefficient THEN 1 ELSE NULL END ) as r5,
	count( CASE WHEN all_count < $this->level_m2*$coefficient THEN 1 ELSE NULL END ) as m1,
	count( CASE WHEN all_count >= $this->level_m2*$coefficient AND all_count < $this->level_m3*$coefficient THEN 1 ELSE NULL END ) as m2,
	count( CASE WHEN all_count >= $this->level_m3*$coefficient AND all_count < $this->level_m4*$coefficient THEN 1 ELSE NULL END ) as m3,
	count( CASE WHEN all_count >= $this->level_m4*$coefficient AND all_count < $this->level_m5*$coefficient THEN 1 ELSE NULL END ) as m4,
	count( CASE WHEN all_count >= $this->level_m5*$coefficient THEN 1 ELSE NULL END ) as m5
FROM
	(
		SELECT
			member_user_id,
			count(member_user_id) day_count,
			sum(total_count) all_count
		FROM
			e_question_user_operate_analysis
		WHERE
			analysis_date BETWEEN '$begin_date' AND '$end_date'
		GROUP BY
			member_user_id
	) AS info
sql;
		return self::model()->findBySql($sql);
	}

}
