<?php

/**
 * This is the model class for table "{{customer_service}}".
 *
 * The followings are the available columns in table '{{customer_service}}':
 * @property string $id
 * @property string $service_qq
 * @property string $filiale_id
 * @property string $user_id
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Customer;
use application\models\Admin\Admin;
use application\models\ServiceRegion;
class CustomerService extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{customer_service}}';
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
	 * @return CustomerService the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        public function ger_Service_list($con, $orderBy, $order, $limit, $offset){
            $criteria = new \CDbCriteria;
            if(!empty($con)){
                 foreach($con as $key => $val){
                     $criteria->compare($key, $val);
                 }
            }
            if(!empty($orderBy) && !empty($order)){
                $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
            }
            $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
            $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
            $ret = self::model()->findAll($criteria);
            $count = self::model()->count($criteria);
            
            $userIds = columnToArr($ret, 'user_id');
            $filialIds = columnToArr($ret, 'filiale_id');
            
            $criteria = new \CDbCriteria;
            $criteria->addInCondition('id', $userIds);
            $adminObj = Admin::model()->findAll($criteria);   
            $adminArr           = objectToKeywordArr($adminObj, 'id', 'user_name');
            $serviceRegionObj   = ServiceRegion::model()->getBranchToCity($filialIds);
            foreach($serviceRegionObj as $region){
                $branch_id = !empty($region->filiale_id) ? substr($region->filiale_id,0 , 2) : 0;
                $regionArr[$branch_id] = $region->region_name;
            }            
            foreach($ret as $k => $v){
                 $data[$k] = $v->attributes; 
                 $data[$k]['city_name']   = !empty($regionArr[$data[$k]['filiale_id']]) ? $regionArr[$data[$k]['filiale_id']] : '全国';
                 $data[$k]['user_name']   = !empty($adminArr[$data[$k]['user_id']]) ? $adminArr[$data[$k]['user_id']] : '';                 
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        }
        /**
         * 保存客服QQ
         */
        public function CustomerServiceSave($data = null){
            if(!empty($data)){
                 $model = new self();
                 $model->service_qq     = $data['service_qq'];
                 $model->filiale_id     = $data['filiale_id'];
                 $model->setting_config = $data['setting_config'];
                 $model->user_id        = $data['user_id'];
                 $model->_create_time   = date('Y-m-d H:i:s');
                 $model->_update_time   = date('Y-m-d H:i:s');
                 $model->save();
                 $addId  = $model->primaryKey;
                 if($addId){
                     return $addId;
                 }else{
                     return false;
                 }
            }else{
                return false;
            }
        }
        /**
         * 修改客服QQ
         */
        public function editsave($data = null){
            if(!empty($data)){
                $editInfo = $this->updateByPk($data['id'], $data);
                return !empty($editInfo)?$editInfo:false;
            }else{
                return false;
            }
        }
        /**
         * 效验记录唯一性
         */
        public function checkInfo($data = null,$mark){
            if(!empty($data)){
                if($mark == 'service'){
                    $result = $this->find('service_qq=:service_qq and _delete=:_delete',$data);
                }else{
                    $result = $this->find('filiale_id=:filiale_id and _delete=:_delete',$data);
                }        
                return $result;
            }else{
                return array();
            }
        }
}
