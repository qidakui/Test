<?php

/**
 * desc:产品
 * author:besttaowenjing@163.com
 * date:2016-10-26
 */
namespace application\models\Prize;
use application\models\ServiceRegion;
class PrizeWinningLog extends \CActiveRecord {
 
    /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{prize_winning_log}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
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
	 * @return Category the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    //查询奖品列表
    public function get_list($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='title'){
					$criteria->addSearchCondition('title', $val);
				}elseif($key=='filiale_id' && is_array($val) ){
                    $criteria->addInCondition('filiale_id',$val);
                }else{
					$criteria->compare($key, $val);
				}
            }
        }
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
       
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        foreach($ret as $k => $v){
            $v = $v->attributes;
            if( !empty($v['province_code']) && !empty($v['city_code']) ){
                $province_name = ServiceRegion::model()->getRedisCityList($v['province_code']);
                $city_name = ServiceRegion::model()->getRedisCityList($v['city_code']);
                $v['address'] = $province_name.$city_name.$v['address'];
            }
            $data[] = $v;
        }
        $data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
    
    
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return intval($count);
    }
    

}
