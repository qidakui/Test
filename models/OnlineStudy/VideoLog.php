<?php

/**
 * This is the model class for table "{{video_log}}".
 *
 * The followings are the available columns in table '{{video_log}}':
 * @property integer $id
 * @property integer $user_id
 * @property integer $video_type
 * @property integer $video_id
 * @property string $video_name
 * @property string $video_src
 * @property integer $is_live
 * @property integer $is_delete
 * @property string $create_time
 * @property string $update_time
 * @property string $operate_type
 */
namespace application\models\OnlineStudy;
use application\models\User\UserBrief;
use application\models\ServiceRegion;

class VideoLog extends \CActiveRecord
{

    public $operateTypeKey = array(
        1 => '进入直播',
        2 => '在线观看',
        3 => '购买视频',
        4 => '下载视频',
        5 => '点赞视频',
        6 => '收藏视频',
        7 => '分享视频',
    );

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{video_log}}';
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
     * @return VideoLog the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    //自定义的保存方法,处理了create_time及update_time
    public function videoLogSave($data)
    {
        $model = new self();
        $model->user_id = $data['user_id'];
        $model->video_type = $data['video_type'];
        $model->video_id = $data['video_id'];
        $model->video_name = $data['video_name'];
        $model->video_src = $data['video_src'];
        $model->is_live = $data['is_live'];
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
    public function saveViewVideoLog($data){
        if($data['is_live'] == '1'){
            $data['operate_type'] = 1;
        }else{
            $data['operate_type'] = 2;
        }
        $log = $this->videoLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->video_name,$log->create_time, $log->video_id, 1);

    }

    public function get_operate_type_name(){
        return isset($this->operateTypeKey[$this->operate_type]) ? $this->operateTypeKey[$this->operate_type] : '';
    }

    /**
     * 保存点赞资料日志
     * by: wenlh
     */
    public function saveUpVideoLog($data){
        $data['operate_type'] = 5;
        $log = $this->videoLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->video_name,$log->create_time, $log->video_id, 1);
        return $log->id;
    }

    /**
     * 保存收藏资料日志
     * by: wenlh
     */
    public function saveFavouriteVideoLog($data){
        $data['operate_type'] = 6;
        $log = $this->videoLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->video_name,$log->create_time, $log->video_id, 1);
        return $log->id;
    }

    /**
     * 保存分享资料日志
     * by: wenlh
     */
    public function saveShareVideoLog($data){
        $data['operate_type'] = 7;
        $log = $this->videoLogSave($data);
        $operate_type_name = $log->get_operate_type_name();
        add_student_dynamic($operate_type_name, $log->video_name,$log->create_time, $log->video_id, 1);
        return $log->id;
    }

    /**
     * 获取当前用户观看视频资料总数
     * by: wenlh
     */
    public function get_my_view_count($date = ''){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        if(!empty($date)){
            $criteria->compare('create_time>', $date);
        }
        $criteria->group='video_id';
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
            $data[$k]['operate_type_name'] = $this->get_operate_type_name();
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'count' => $count);
    }

    /**
     * 获取当前用户最新观看视频及图文资料
     * by: wenlh
     */
    public function get_video_and_document_new_log($user_id,$limit = 7,$offset = 0){
        $result = array();
        $sql = "select * from (select video_name as video_name,create_time,user_id,video_id,is_live from e_video_log where user_id = :user_id and (operate_type=2 or operate_type = 1) union select document_name as video_name,create_time,user_id,document_id,2 is_live from e_document_log where user_id = :user_id and operate_type = 1 ) as a ORDER BY create_time desc limit {$offset},{$limit}";
        $sql_result = VideoLog::model()->findAllBySql($sql,array('user_id' => $user_id));
        foreach($sql_result as $k=>$v){
            if($v->is_live == 2){
                $url = \Yii::app()->createUrl('onlinestudy/document_view',array('document_id' => $v->video_id));
            }else{
                $url = \Yii::app()->createUrl('onlinestudy/video_view',array('video_id' => $v->video_id));
            }
            $result[] = array('video_id'=>$v->video_id,'is_live'=>$v->is_live,'name' => $v->video_name,
                'log_time' => date('Y-m-d',strtotime($v->create_time)), 'url' => $url);
        }
        return $result;
    }

    /**
     * 获取当前用户收藏的视频及图文资料
     * by: wenlh
     */
    public function get_favourite_video_and_document($user_id,$limit = 7,$offset = 0){
        $result = array();
        $sql = "select * from ((select video_name as video_name,create_time,user_id,video_id,is_live,id from e_video_log where user_id = :user_id and operate_type=6 and is_delete = 0) union (select document_name as video_name,create_time,user_id,document_id,2 is_live,id from e_document_log where user_id = :user_id and operate_type = 6 and is_delete = 0)) as a ORDER BY create_time desc limit {$offset},{$limit}";
        $sql_result = VideoLog::model()->findAllBySql($sql,array('user_id' => $user_id));
        foreach($sql_result as $k=>$v){
            if($v->is_live == 2){
                $url = \Yii::app()->createUrl('onlinestudy/document_view',array('document_id' => $v->video_id));
            }else{
                $url = \Yii::app()->createUrl('onlinestudy/video_view',array('video_id' => $v->video_id));
            }
            $result[] = array('id'=>$v->id,'video_id'=>$v->video_id,'is_live'=>$v->is_live,'name' => $v->video_name,
                'log_time' => date('Y-m-d',strtotime($v->create_time)), 'url' => $url);
        }
        return $result;
    }
    /**
     * 获取我的总的学习记录(暂且) add by hd 
     * @param type $user_id 用户id
     */
    public function get_total_study_number($user_id){
        $sql = "select count(*) from (select video_name as video_name,create_time,user_id,video_id,is_live from e_video_log where user_id = :user_id and (operate_type=2 or operate_type = 1) union select document_name as video_name,create_time,user_id,document_id,2 is_live from e_document_log where user_id = :user_id and operate_type = 1) as a ORDER BY create_time desc";
        $total = VideoLog::model()->countBySql($sql,array('user_id' => $user_id));
        return $total;
    }
    /**
     * 获取我的收藏总的记录条数
     * @param type $video_id
     * @return type
     */
    public function get_total_collect_munber($user_id){
         $sql = "select count(*) from ((select video_name as video_name,create_time,user_id,video_id,is_live from e_video_log where user_id = :user_id and operate_type=6 and is_delete = 0) union (select document_name as video_name,create_time,user_id,document_id,2 is_live from e_document_log where user_id = :user_id and operate_type = 6 and is_delete = 0)) as a ORDER BY create_time desc";
         $total = VideoLog::model()->countBySql($sql,array('user_id' => $user_id));
         return $total;
    }
    /**
     * 获取个人中心学习达人勋章
     * @author hd;
     * 备选:
     * example:
     * 1、select user_id,COUNT(DISTINCT video_id) t from e_video_log where (operate_type = 1 or operate_type = 2) group by user_id;
     *    select user_id,COUNT(DISTINCT document_id) t from e_document_log where (operate_type = 1) group by user_id;
     * 2、
     * select user_id,COUNT(DISTINCT information_id) from ((SELECT user_id,video_id as information_id from e_video_log where (operate_type = 1 or operate_type = 2)) union (select user_id,document_id as information_id from e_document_log where (operate_type = 1))) b group by user_id
     */
    public function get_sync_talent_medal(){
      $sql = "SELECT user_id,sum(t) is_live from ((select user_id,COUNT(DISTINCT video_id) t from e_video_log where (operate_type = 1 or operate_type = 2) group by user_id) union (select user_id,COUNT(DISTINCT document_id) t from e_document_log where (operate_type = 1) group by user_id)) b group by user_id";
      $sql_result = VideoLog::model()->findAllBySql($sql);
      return $sql_result;
    }

    /**
     * 获取当前用户是否分享过
     * by: wenlh
     */
    public function hasShare($video_id){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        $criteria->compare('video_id', $video_id);
        $criteria->compare('operate_type', '7');
        return self::model()->exists($criteria);
    }

    /**
     * 获取当前用户是否点赞过
     * by: wenlh
     */
    public function hasUp($video_id){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        $criteria->compare('video_id', $video_id);
        $criteria->compare('operate_type', '5');
        return self::model()->exists($criteria);
    }

    /**
     * 获取当前用户是否收藏过
     * by: wenlh
     */
    public function hasFavourite($video_id,$all=false){
        $user_id = getUserId();
        $criteria = new \CDbCriteria;
        $criteria->compare('user_id', $user_id);
        $criteria->compare('video_id', $video_id);
        $criteria->compare('operate_type', '6');
        if(!$all) {
            $criteria->compare('is_delete', '0');
        }
        return self::model()->exists($criteria);
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

    /**
     * 获取当前用户学习总数及网站资料总数(包含视频及文档)
     * by: wenlh
     */
    public function get_study_count(){
        $all_video_num = VideoLog::model()->get_my_view_count();
        $all_document_num = DocumentLog::model()->get_my_view_count();
        $today_video_num = VideoLog::model()->get_my_view_count(date('Y-m-d'));
        $today_document_num = DocumentLog::model()->get_my_view_count(date('Y-m-d'));
        $result = array('my_count' => ($today_video_num + $today_document_num), 'total_count' => ($all_video_num + $all_document_num));
        return $result;
    }

    public function get_info_with_user($video_id,$limit,$offset){
        $criteria = new \CDbCriteria;
        $criteria->compare('operate_type',array(1,2));
        $criteria->compare('video_id',$video_id);
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
