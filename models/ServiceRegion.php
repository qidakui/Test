<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/25
 * Time: 16:58
 */
namespace application\models;
class ServiceRegion extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{service_region}}';
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
        return array();
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
     * @return ServiceRegion the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获取城市
     * @return array|mixed|null
     */
    public function getCityList(){
        $criteria = new \CDbCriteria;
        $criteria->compare('is_parent', 0);
        $ret = self::model()->findAll($criteria);
        return !empty($ret) ? $ret : array();
    }
    
    public function getRedisCityList($id){
        if(empty($id)){
            return false;
        }
        $list = new \ARedisHash("city_all_key");
        if(!empty($list->count())){
            $data = $list->getData();
            return !empty($data[$id]) ? $data[$id] : '';
        } else {
            $ret = self::model()->findAll();
            foreach($ret as $val){
                $list->add($val->region_id, $val->region_name);
            }
            return !empty($list[$id]) ? $list[$id] : '';
        }
    }

    /**
     * 获取分支list
     * @return array|mixed|null
     */
    public function getBranchList(){
        $criteria = new \CDbCriteria;
        $criteria->compare('is_sub', 1);
       // $criteria->compare('_delete', 0);
        $ret = self::model()->findAll($criteria);
        return !empty($ret) ? $ret : array();
    }

    public function getRegionIdByBranch($branchId){
        $branch_info = $this->getBranchInfo($branchId);
        if(empty($branch_info)){
            return 0;
        }
        if($branchId == QG_BRANCH_ID){
            return $branch_info[0]->region_id;
        }else{
            return substr($branch_info[0]->region_id,0,2);
        }
    }

    /**
     * 获取分支信息
     * @return array|mixed|null
     */
    public function getBranchInfo($branchId){
        $criteria = new \CDbCriteria;
        $criteria->compare('filiale_id', $branchId);
        //$criteria->compare('_delete', 0);
        $ret = self::model()->findAll($criteria);
        return !empty($ret) ? $ret : array();
    }

    /**
     * 根据分支获取省份
     * @return array|mixed|null
     */
    public function getBranchToCity($branchId){
        $criteria = new \CDbCriteria;
        $criteria->compare('filiale_id', $branchId);
        //$criteria->compare('_delete', 0);
        $ret = self::model()->findAll($criteria);
        foreach($ret as $val){
            $data[] = !empty($val->region_code) ? substr($val->region_code, 0, 2) : 0;
        }
        if(empty($data)){
            return false;
        }
        $criteria = new \CDbCriteria;
        $criteria->compare('region_id', $data);
        //$criteria->compare('_delete', 0);
        $ret = self::model()->findAll($criteria);

        return !empty($ret) ? $ret : array();
    }
    
    /**
     * 根据省份id获取分支id(filiale_id前两位)
     * @return array|mixed|null
     */
    public function getProvinceToFiliale($provinceId){
        if($provinceId==37){ //山东特殊处理
            return array(77,78);
        }
        $criteria = new \CDbCriteria;
        $criteria->select = 'filiale_id';
        $criteria->compare('region_id', $provinceId);
        $ret = self::model()->find($criteria);
        return empty($ret) ? 0 : substr($ret->filiale_id,0,2);
    }
    
    /**
     * 获取省份
     * @return array|mixed|null
     */
    public function getProvinceList(){
        $criteria = new \CDbCriteria;
        $criteria->compare('is_parent', 0);
        $criteria->compare('_delete', 0);
        $ret = self::model()->findAll($criteria);
        return !empty($ret) ? $ret : array();
    }

    /**
     *根据省code获取对应的市
     *
     */
    public function getCityByProvince($province){
        if(empty($province)){
            return array();
        }
        $con = array();
        if(is_numeric($province)){
            $region_id = $province;
        }else{
            $region_name = $province;
            $criteria = new \CDbCriteria;
            $criteria->compare('region_name', $region_name);
            //$criteria->compare('_delete', 0);
            if(!empty($region_name)){
                $model = self::model()->find($criteria);
                $region_id = $model->region_id;
            }
        }
        $con['left(region_id,2)'] = $region_id;
        $con['is_parent'] = 1;
        //$con['_delete'] = 0;
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $ret = self::model()->findAll($criteria);
        foreach($ret as $key=>$val){
            $data[$val['region_id']] = $val['region_name'];
        }
        return !empty($data)?$data:array();
    }
    public function getProvinceArr(){
        $province =self::model()->getProvinceList();
        foreach($province as $key=>$val){
            $data[$val['region_id']] = $val['region_name'];
        }
        return !empty($data)?$data:array();
    }

   /*
    * 根据地区id获取地区名
    * */
    public function getRegionName($region_id){
        $criteria = new \CDbCriteria;
        $criteria->compare('region_id', $region_id);
        $ret = self::model()->find($criteria);
        if(!empty($ret)){
            $region_name = $ret->region_name;
        }
        return !empty($region_name) ? $region_name : '';
    }
    /**
     * 修改开通地区信息
     */
    public function edit_Open_Area($region_id = null,$attributes){
	$editres = $this->updateByPk($region_id,$attributes);
	return true;
    }
    /**
     * 开通地区列表
     * @param type $con
     * @param type $orderBy
     * @param type $order
     * @param type $limit
     * @param type $offset
     * @return type
     */
    public function openArealist($con, $orderBy, $order, $limit, $offset){
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
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
}