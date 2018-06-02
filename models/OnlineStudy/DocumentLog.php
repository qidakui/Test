<?php

/**
 * This is the model class for table "{{document_log}}".
 *
 * The followings are the available columns in table '{{document_log}}':
 * @property integer $id
 * @property integer $user_id
 * @property integer $document_id
 * @property integer $document_name
 * @property integer $operate_type
 * @property integer $is_delete
 * @property string $create_time
 * @property string $update_time
 */
namespace application\models\OnlineStudy;
use application\models\User\UserBrief;
use application\models\ServiceRegion;

class DocumentLog extends \CActiveRecord
{
    /**
     * 操作类型
     * by: wenlh
     */
    public $operateTypeKey = array(
        1 => '在线阅读',
        2 => '发表评论',
        3 => '购买资料',
        4 => '下载资料',
        5 => '点赞资料',
        6 => '收藏资料',
        7 => '分享资料',
    );

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{document_log}}';
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
            'user_info' => array(self::BELONGS_TO,get_class(UserBrief::model()), 'user_id', 'join_with_in' => true),
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
     * @return DocumentLog the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    //自定义的保存方法,处理了create_time及update_time
    public function documentLogSave($data)
    {
        $model = new self();
        $model->user_id = $data['user_id'];
        $model->document_id = $data['document_id'];
        $model->document_name = $data['document_name'];
        $model->operate_type = $data['operate_type'];
        $model->is_delete = false;
        $model->create_time = date('Y-m-d H:i:s');
        $model->update_time = date('Y-m-d H:i:s');
        $model->save();
        return $model;
    }

    /**
     * 保存观看资料日志
     * by: wenlh
     */
    public function saveViewDocumentLog($data){
        $data['operate_type'] = 1;
        $log = $this->documentLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->document_name,$log->create_time, $log->document_id, 2);
    }

    /**
     * 保存点赞资料日志
     * by: wenlh
     */
    public function saveUpDocumentLog($data){
        $data['operate_type'] = 5;
        $log = $this->documentLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->document_name,$log->create_time, $log->document_id, 2);
        return $log->id;
    }

    /**
     * 保存收藏资料日志
     * by: wenlh
     */
    public function saveFavouriteDocumentLog($data){
        $data['operate_type'] = 6;
        $log = $this->documentLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->document_name,$log->create_time, $log->document_id, 2);
        return $log->id;
    }

    /**
     * 检查是否分享过
     * by: wenlh
     */
    public function hasShare($document_id){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        $criteria->compare('document_id', $document_id);
        $criteria->compare('operate_type', '7');
        return self::model()->exists($criteria);
    }

    /**
     * 检查是否点赞过
     * by: wenlh
     */
    public function hasUp($document_id){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        $criteria->compare('document_id', $document_id);
        $criteria->compare('operate_type', '5');
        return self::model()->exists($criteria);
    }

    /**
     * 检查是否分享过
     * by: wenlh
     */
    public function hasFavourite($document_id, $all=false){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        $criteria->compare('document_id', $document_id);
        $criteria->compare('operate_type', '6');
        if(!$all){
            $criteria->compare('is_delete', '0');
        }
        return self::model()->exists($criteria);
    }

    /**
     * 获取我的收藏,
     * by: wenlh
     */
    public function getMyFavourite($user_id){
        $result = array();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        $criteria->compare('operate_type', '6');
        $criteria->compare('is_delete','0');
        $sql_result = self::model()->findAll($criteria);
        foreach($sql_result as $k=>$v){
            $result[] = array('name' => $v->document_name, 'log_time' => date('Y-m-d',strtotime($v->create_time)),
                'url' => \Yii::app()->createUrl('onlinestudy/document_view',array('document_id' => $v->document_id)));
        }
        return $result;
    }

    /**
     * 保存分享日志
     * by: wenlh
     */
    public function saveShareDocumentLog($data){
        $data['operate_type'] = 7;
        $log = $this->documentLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->document_name,$log->create_time, $log->document_id, 2);
        return $log->id;
    }

    /**
     * 获取当前用户观看图文资料总数
     * by: wenlh
     */
    public function get_my_view_count($date = ''){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        if(!empty($date)){
            $criteria->compare('create_time>', $date);
        }
        $criteria->group='document_id';
        return self::model()->count($criteria);
    }

    //自定义获取单表list的方法
    public function getlist($con, $orderBy, $order, $limit, $offset)
    {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
        }
        if (!empty($orderBy) && !empty($order)) {
            $criteria->order = sprintf('%s %s', $order, $orderBy);//排序条件
        }
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);

        foreach ($ret as $k => $v) {
            $data[$k] = $v->attributes;
            $data[$k]['operate_type_name'] = $v->get_operate_type_name;
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'count' => $count);
    }

    public function get_operate_type_name(){
        return isset($this->operateTypeKey[$this->operate_type]) ? $this->operateTypeKey[$this->operate_type] : '';
    }

    public function with_other_model_for_has_one($data, $model_name, $key, $other_key)
    {
        $result = [];
        $key_values = columnToArr($data, $key);
        $criteria = new \CDbCriteria;
        $criteria->addInCondition($other_key, $key_values);
        $other_model_result = $model_name::model()->findAll($criteria);
        foreach ($other_model_result as $val) {
            $result[$val->$other_key] = $val;
        }
        return $result;
    }

    public function get_info_with_user($document_id,$limit,$offset){
        $criteria = new \CDbCriteria;
        $criteria->compare('operate_type',array(1,2));
        $criteria->compare('document_id',$document_id);
        $criteria->compare('is_delete',0);
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        $criteria->with = 'user_info';
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);

        $criteria = new \CDbCriteria;
        $criteria->compare('is_parent', 0);
        $ServiceRegionObj = ServiceRegion::model()->findAll($criteria);
        foreach($ServiceRegionObj as $serviceRegion){
            if(!empty($serviceRegion)){
                $serviceRegionArr[$serviceRegion->region_id] = $serviceRegion->region_name;
            }
        }
        foreach($ret as $k => $v){
            if(empty($v->user_info)){
                $v->user_info = new UserBrief();
            }
            $region_id = !empty($v->user_info->regionID) ? substr($v->user_info->regionID,0 , 2) : '';
            $data[$k]['city_name']   = !empty($serviceRegionArr[$region_id]) ? $serviceRegionArr[$region_id] : '';
            $data[$k]['member_user_id'] = $v->user_id;
            $data[$k]['member_nick_name'] = $v->user_info->Nick;
            $data[$k]['member_user_name'] = $v->user_info->UserName;
            $data[$k]['mobile'] = $v->user_info->sMobile;
            $data[$k]['mail'] = $v->user_info->email;
            $data[$k]['create_time'] = $v->create_time;
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
}
