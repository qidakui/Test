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
class Product extends \CActiveRecord
{
    public $templateTypeKey = array(
        0 => '默认模板',
        1 => 'P5模板',
    );

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{product}}';
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
        $Category = array();
        if($count){
            $Category = $this->getAllCategory();
        }
        foreach($ret as $k => $v){
            $v = $v->attributes;
            if($v['filiale_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
            }
            $v['region_name'] = $city[0]['region_name']; //分支
              //适用地
            if($v['apply_province_code']==BRANCH_ID){
                $v['apply_province_name'] = '全国';
            }else{
                $v['apply_province_name'] = ServiceRegion::model()->getRedisCityList($v['apply_province_code']);
            }
            $v['category_parent'] = isset($Category[$v['category_parent_id']]) ? $Category[$v['category_parent_id']] : '未知';
            $v['category'] = isset($Category[$v['category_id']]) ? $Category[$v['category_id']] : '';
            $v['_update_time'] = $v['_update_time']==0 ? '' : $v['_update_time'];
            $v['status_txt'] = $v['status']==1?'已发布':'未发布';
            $data[] = $v;
        }
        $data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

	//保存/修改
    public function ProductSave($data, $extdata=array()){
        if(isset($data['id'])){
            $model = self::model()->findbypk($data['id']);
            $oldData = $model->attributes;
            $model->_update_time = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
        }
		
        foreach($data as $k=>$v){
            if($k == 'custom_video'){
                $model->$k = $v;
            }else{
                $model->$k = \CHtml::encode($v);
            }
        }
        $Connection = $model->dbConnection->beginTransaction();
        if( $model->save() ){
            $id  = intval($model->primaryKey);
            $extdata['id'] = $id;
            $eid = ProductExtension::model()->productExtensionSave($extdata);
			
            if($eid){
                $Connection->commit();
            }else{
                $id = 0;
                $Connection->rollBack();
            }
        }else{
            $id = 0;
            $Connection->rollBack();
        }
        
        if($id){
            /*if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationActivity, 'edit', '修改活动', $data['id'], $oldData, $data);
            }else{
                \OperationLog::addLog(\OperationLog::$operationActivity, 'add', '新建活动', $id, array(), $data);
            }*/
        }
        return $id;
    }
    
    //查询单条
    public function getProduct($id){
        $data = self::model()->findByPk($id);
        $extdata = ProductExtension::model()->findByPk($id);
        if( empty($extdata) ) {
            ProductExtension::model()->productExtensionSave(array('id'=>$id));
            $extdata = ProductExtension::model()->findByPk($id);
        }
        $data = $data->attributes;
        $extdata = $extdata->attributes;
        if( !empty($extdata['keywords']) ){
            $extdata['keywords'] = unserialize($extdata['keywords']);
        }
        unset($extdata['id'],$extdata['_create_time'], $extdata['_update_time']);
        $res = array_merge($data, $extdata);
        return $res;
    }
    
    function getAllCategory(){
        $arr = array();
        $list = Category::model()->get_list();
        foreach ($list as $k=>$v){
            $arr[$v['id']] = $v['category_name'];
        }
        return $arr;
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
        /**
         * 获取资源信息
         * @param type $data
         * @return type
         */
        public function findInfo($data = NULL,$all = null ){
            $criteria = new \CDbCriteria;
            if(!empty($data)){
               foreach($data as $key => $val){
                   $criteria->compare($key, $val);
               }
                $results = empty($all)?self::model()->find($criteria):self::model()->findAll($criteria);
            }
            return !empty($results)?$results:array(); 
        }     
    
}
