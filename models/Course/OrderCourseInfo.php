<?php

/**
 * This is the model class for table "{{order_course_info}}".
 *
 * The followings are the available columns in table '{{order_course_info}}':
 * @property integer $id
 * @property string $order_sn
 * @property string $global_tradeno
 * @property string $global_order_id
 * @property string $course_order_id
 * @property string $member_id
 * @property string $global_id
 * @property integer $order_status
 * @property string $order_amount
 * @property string $coupon_amount
 * @property string $amount
 * @property string $course_id
 * @property string $course_name
 * @property integer $app_type
 * @property string $add_time
 * @property string $pay_time
 * @property integer $is_sync
 * @property integer $status
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Course;
class OrderCourseInfo extends \CActiveRecord
{

	public $orderStatusName = array(
		10 => '未支付',
		12 => '支付成功'
	);

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{order_course_info}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			
		);
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
		// @todo Please modify the following code to remove attributes that should not be searched.

		
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return OrderCourseInfo the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getList($con=array(),$order='id desc',$limit=-1,$offset=0){
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
		if(!empty($index))
			$criteria->index = $index;
		$criteria->order = $order;
		$criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
		$criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

		$ret = self::model()->findAll($criteria);
		$count = self::model()->count($criteria);
		foreach($ret as $k => $v){
			$data[$k] = $v->attributes;
			$data[$k]['global_order_id'] = empty($data[$k]['global_order_id']) ? '' : $data[$k]['global_order_id'];
			$data[$k]['course_order_id'] = empty($data[$k]['course_order_id']) ? '' : $data[$k]['course_order_id'];
			$data[$k]['is_sync'] = empty($data[$k]['is_sync']) ? '否' : '是';
			$data[$k]['course_amount'] =  number_format($data[$k]['course_amount']/100,2);
			$data[$k]['coupon_amount'] =  number_format($data[$k]['coupon_amount']/100,2);
			$data[$k]['amount'] =  number_format($data[$k]['amount']/100,2);
			$data[$k]['order_status'] = $v->getOrderStatusName() ;

		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

	public function getOrderStatusName(){
		return $this->orderStatusName[$this->order_status];
	}
}
