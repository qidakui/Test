<?php

/**
 * This is the model class for table "{{category}}".
 *
 * The followings are the available columns in table '{{category}}':
 * @property string $id
 * @property string $category_name
 * @property string $parent_id
 * @property integer $sort_order
 * @property string $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Product;
use application\models\ServiceRegion;
class ProductFeedback extends \CActiveRecord
{

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{product_feedback}}';
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


	public function get_list($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='realname' || $key=='member_user_name'){
					$criteria->addSearchCondition($key, $val);
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
            $product = Product::model()->findByPk($v['product_id'],array('select'=>array('title')));
             //适用地
            if($v['apply_province_code']==BRANCH_ID){
                $v['apply_province_name'] = '全国';
            }else{
                $v['apply_province_name'] = ServiceRegion::model()->getRedisCityList($v['apply_province_code']);
                $v['apply_city_name'] = !empty(ServiceRegion::model()->getRedisCityList($v['apply_city']))?ServiceRegion::model()->getRedisCityList($v['apply_city']):'';
            }
            $v['title'] = $product['title'];
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
