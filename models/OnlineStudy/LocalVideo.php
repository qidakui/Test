<?php
/**
 * Created by PhpStorm.
 * User: wenlh
 * Date: 2016/5/12
 * Time: 18:11
 */
/**
 * This is the model class for table "{{local_video}}".
 *
 * The followings are the available columns in table '{{local_video}}':
 * @property integer $id
 * @property string $video_name
 * @property integer $branch_id
 * @property integer $create_user_id
 * @property string $detail
 * @property integer $video_type
 * @property string $video_img
 * @property string $video_src
 * @property integer $software_id
 * @property integer $business_id
 * @property integer $price
 * @property integer $open_count
 * @property integer $up_count
 * @property integer $down_count
 * @property integer $favourite_count
 * @property integer $status
 * @property integer $is_live
 * @property integer $is_delete
 * @property string $create_time
 * @property string $update_time
 * @property string $area
 * @property string $live_begin_time
 * @property string $live_end_time
 * @property string $jzkt_id
 * @property string $teacher_name
 */
namespace application\models\OnlineStudy;
use application\models\Admin\Admin;
use application\models\ServiceRegion;
class LocalVideo extends \CActiveRecord
{
    public $status_name;
    private $statusKey = array(
        0 => '关闭',
        1 => '在线',
    );


    public $videoTypeKey = array(
        1 => '优酷视频',
        2 => '土豆视频',
        5 => '腾讯视频',
        6 => '腾讯课堂',
        3 => '建筑课堂视频',
        4 => '同步建筑课堂视频',
    );

    public $isLiveKey = array(
        1 => '直播',
        0 => '录播',
    );

    public $positionTypeIdKey = array(
        0 => '',
        1 => '分支精彩视频',
        2 => '更多精彩视频',
    );

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{local_video}}';
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
            'author'=>array(self::BELONGS_TO, 'Admin', 'create_user_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'id',
            'video_name' => '视频名称',
            'branch_id' => '分支ID',
            'create_user_id' => '创建人ID',
            'create_user_name' => '创建人名称',
            'sh_user_name' => '审核人名称',
            'detail' => '描述',
            'video_type' => '视频类型',
            'video_img' => '视频图片',
            'video_src' => '视频地址',
            'teacher_name' => '讲师名称',
            'software_id' => '软件ID',
            'business_id' => '业务ID',
            'province_id' => '省ID',
            'city_id' => '城市ID',
            'area' => '地区',
            'is_live' => '是否直播',
            'live_begin_time' => '直播开始时间',
            'live_end_time' => '直播结束时间',
            'jzkt_id' => '建筑课堂ID',
            'price' => '价格',
            'position_type_id' => '位置ID',
            'position_num' => '位置序号',
            'open_count' => '打开次数',
            'up_count' => '点赞次数',
            'down_count' => '点踩次数',
            'favourite_count' => '收藏次数',
            'reply_count' => '回复数',
            'share_count' => '分享次数',
            'comment' => '备注',
            'status' => '状态',
            'is_delete' => '是否删除',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
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
     * @return LocalVideo the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function get_video_type(){
        $res = $this->model()->videoTypeKey;
        unset($res[4]);
        return $res;
    }

    //自定义的保存方法,处理了create_time及update_time
    public function localVideoSave($data)
    {
        $branch_id = \Yii::app()->user->branch_id;
        $area = 0;
        if(isset(\Yii::app()->params['online_study_filter']['area'][$branch_id])){
            $area = \Yii::app()->params['online_study_filter']['area'][$branch_id]['id'];
        }

        $model = new self();
        $model->video_name = $data['video_name'];
        $model->branch_id = $branch_id;
        $model->create_user_id = \Yii::app()->user->user_id;
        $model->detail = $data['detail'];
        $model->video_type = $data['video_type'];
        $model->video_img = $data['video_img'];
        $model->video_src = $data['video_src'];
        $model->teacher_name = $data['teacher_name'];
        $model->create_user_name = $data['create_user_name'];
        $model->sh_user_name = $data['sh_user_name'];
        $model->software_id = $data['software_id'];
        $model->business_id = $data['business_id'];
        if(empty($data['live_begin_time']) || empty($data['live_end_time'])){
            $model->live_begin_time = null;
            $model->live_end_time = null;
        }else{
            $model->live_begin_time = $data['live_begin_time'];
            $model->live_end_time = $data['live_end_time'];
        }
        $model->province_id = $data['province_id'];
        $model->city_id = $data['city_id'];
        $model->service_auth_check = $data['service_auth_check'];
        $model->comment = $data['comment'];
        $model->price = $data['price'];
        $model->area = $area;
        $model->open_count = isset($data['open_count']) ? $data['open_count'] : 0;
        $model->up_count = isset($data['up_count']) ? $data['up_count'] : 0;
        $model->down_count = isset($data['down_count']) ? $data['down_count'] : 0;
        $model->reply_count = isset($data['reply_count']) ? $data['reply_count'] : 0;
        $model->favourite_count = isset($data['favourite_count']) ? $data['favourite_count'] : 0;
        $model->share_count = isset($data['share_count']) ? $data['share_count'] : 0;
        $model->status = 1;
        $model->is_live = !(empty($data['live_begin_time']) || empty($data['live_end_time']));
        $model->is_delete = false;
        $model->create_time = date('Y-m-d H:i:s');
        $model->update_time = date('Y-m-d H:i:s');
        $model->save();
        $id = $model->primaryKey;
        return $id;
    }

    public function copyLocalVideo($id){
        $source_video = self::model()->findByPk($id);
        $source_video->setIsNewRecord(true);
        $source_video->__unset('id');
        $source_video->create_user_id = \Yii::app()->user->user_id;
        $source_video->status = 0;
        $source_video->open_count = 0;
        $source_video->up_count = 0;
        $source_video->down_count = 0;
        $source_video->reply_count = 0;
        $source_video->favourite_count = 0;
        $source_video->share_count = 0;
        $source_video->create_time = date('Y-m-d H:i:s');
        $source_video->update_time = date('Y-m-d H:i:s');
        $source_video->save();
        $id = $source_video->primaryKey;
        return $id;
    }

    //自定义的update方法,不修改create_time,修改update_time为当前时间
    public function localVideoUpdate($data)
    {
        $id = $data['id'];
        if (empty($id)) {
            return false;
        }
        $branch_id = \Yii::app()->user->branch_id;
        $area = 0;
        if(isset(\Yii::app()->params['online_study_filter']['area'][$branch_id])){
            $area = \Yii::app()->params['online_study_filter']['area'][$branch_id]['id'];
        }
        $model = self::model()->findbypk($id);
        $model->video_name = $data['video_name'];
        $model->branch_id = $branch_id;
        $model->create_user_id = \Yii::app()->user->user_id;
        $model->detail = $data['detail'];
        $model->video_type = $data['video_type'];
        $model->video_img = $data['video_img'];
        $model->video_src = $data['video_src'];
        $model->teacher_name = $data['teacher_name'];
        $model->create_user_name = $data['create_user_name'];
        $model->sh_user_name = $data['sh_user_name'];
        $model->software_id = $data['software_id'];
        $model->business_id = $data['business_id'];
        $model->price = $data['price'];
        if(empty($data['live_begin_time']) || empty($data['live_end_time'])){
            $model->live_begin_time = null;
            $model->live_end_time = null;
        }else{
            $model->live_begin_time = $data['live_begin_time'];
            $model->live_end_time = $data['live_end_time'];
        }
        $model->province_id = $data['province_id'];
        $model->city_id = $data['city_id'];
        $model->service_auth_check = $data['service_auth_check'];
        $model->comment = $data['comment'];
        $model->area = $area;
        $model->status = 1;
        $model->is_live = !empty($data['live_begin_time']) && !empty($data['live_end_time']);
        $model->is_delete = false;
        $model->update_time = date('Y-m-d H:i:s');
        $model->save();
        $id = $model->primaryKey;
        return $id;
    }

    //自定义修改status字段的方法
    public function localVideoUpdateStatus($id, $status)
    {
        if (empty($id)) {
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->status = $status;
        $flag = $model->save();
        return $flag;
    }

    //自定义修改is_delete字段为true的方法,软删除
    public function localVideoDelete($id)
    {
        $model = self::model()->findbypk($id);
        $model->is_delete = true;
        $flag = $model->save();
        return $flag;
    }

    //拼入查询条件branch_id
    public function by_branch_id($branch_id)
    {
        $this->getDbCriteria()->compare('branch_id',$branch_id);
        return $this;
    }

    //拼入查询条件not in $ids
    public function without_in_id($ids){
        $this->getDbCriteria()->addNotInCondition('id',$ids);
        return $this;
    }

    //拼入查询条件建筑课堂ID
    public function by_jzkt_id($jzkt_id){
        $this->getDbCriteria()->compare('jzkt_id',$jzkt_id);
        return $this;
    }

    //拼入查询条件is_delete=0
    public function activity(){
        $this->getDbCriteria()->compare('is_delete',0);
        return $this;
    }

    //拼入查询条件status=1
    public function online(){
        $this->getDbCriteria()->compare('status',1);
        return $this;
    }

    //根据branch_id获取视频列表
    public function get_list_for_select(){
        $result = [];
        $branch_id = \Yii::app()->user->branch_id;
        $ret = $this->model()->by_branch_id($branch_id)->activity()->findAll();
        foreach ($ret as $k => $v){
            $result[$v->id] = $v->video_name;
        }
        return $result;
    }

    //设置位置
    public function set_position($information_id, $position_type_id, $position_num){
        $branch_id = \Yii::app()->user->branch_id;
        $province_id = ServiceRegion::model()->getRegionIdByBranch($branch_id);
        $information = $this::model()->findByPk($information_id);
        if(empty($information)){
            return 0;
        }
        if($information->province_id == QG_BRANCH_ID){
            $position_num = $position_num + QG_OFFSET;
        }
        $old_information = $this->model()->findByAttributes(array('position_type_id' => $position_type_id, 'position_num' => $position_num, 'province_id' => $province_id));
        if(!empty($old_information)){
            $old_information->position_type_id = null;
            $old_information->position_num = null;
            $old_information->update_time = date('Y-m-d H:i:s');
            $old_information->save();
        }
        $attributes = array('position_type_id'=>$position_type_id, 'position_num' => $position_num, 'update_time' => date('Y-m-d H:i:s'));
        return $this::model()->updateByPk($information_id,$attributes);
    }
    //自定义获取单表list的方法
    public function getlist($con, $orderBy, $order, $limit, $offset)
    {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                if(is_array($val) && isset($val[0])){
                    switch($val[0]){
                        case 'search_like':
                            $criteria->compare($key, $val[1],true);
                            break;
                        case 'between':
                            $criteria->addBetweenCondition('create_time',$val[1],$val[2]);
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
        if (!empty($orderBy) && !empty($order)) {
            $criteria->order = sprintf('%s %s', $order, $orderBy);//排序条件
        }
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        $ret = self::model()->findAll($criteria);
        $province_info = $this->with_other_model_for_has_one($ret,'application\models\ServiceRegion','province_id', 'region_id');
        $city_info = $this->with_other_model_for_has_one($ret,'application\models\ServiceRegion','city_id', 'region_id');
        $count = self::model()->count($criteria);
        $business = get_all_business_category(true);
        $software = get_software_category();
        foreach ($ret as $k => $v) {
            $data[$k] = $v->attributes;
            $data[$k]['status_name'] = isset($this->statusKey[$v->status]) ? $this->statusKey[$v->status] : '';
            $data[$k]['video_type_name'] = isset($this->videoTypeKey[$v->video_type]) ? $this->videoTypeKey[$v->video_type] : '';
            $data[$k]['software_name'] = isset($software[$v->software_id]) ? $software[$v->software_id] : '';
            $data[$k]['business_name'] = isset($business[$v->business_id]) ? $business[$v->business_id] : '';
            $data[$k]['is_live_name'] = $this->isLiveKey[$v->is_live];
            $data[$k]['position_name'] = isset($this->positionTypeIdKey[$v->position_type_id]) ? $this->positionTypeIdKey[$v->position_type_id] : '';
            $data[$k]['provice_name'] = isset($province_info[$v->province_id]) ? $province_info[$v->province_id]->region_name : '';
            $data[$k]['city_name'] = (isset($city_info[$v->city_id])) ? $city_info[$v->city_id]->region_name : '全部';
            $data[$k]['service_auth_check_name'] = empty($v->service_auth_check) ? '否' : '是';
            if(empty($data[$k]['provice_name'])){
                $data[$k]['city_name'] = '';
            }
            if(!empty($v->position_num) && $v->province_id == QG_BRANCH_ID){
                $data[$k]['position_num'] = $v->position_num - QG_OFFSET;
            }
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

    //关联查询
    public function with_other_model_for_has_one($data,$model_name,$key,$other_key){
        $result = [];
        $key_values = columnToArr($data, $key);
        $criteria = new \CDbCriteria;
        $criteria->addInCondition($other_key, $key_values);
        $other_model_result = $model_name::model()->findAll($criteria);
        foreach($other_model_result as $val){
            $result[$val->$other_key] = $val;
        }
        return $result;
    }

    //同步建筑课堂课程
    public function sync_jzkt_course(){
        $url = 'http://jzkt.glodon.com/webapp/appalltypecourselist?currentPage=1&pageSize=1500&order=new';
        $ret = \CJSON::decode(file_get_contents($url));
        if($ret['success'] == 'true'){
            $jzkt_course = $ret['entity']['courseList'];
            foreach($jzkt_course as $k){
                $this->create_or_update_jzkt_data($k);
            }
            $this->updateAll(array('is_delete'=>true),'update_time < :update_time and create_user_id = :create_user_id',
                array(':update_time'=>date('Y-m-d'), ':create_user_id'=>JZKT_ADMIN_ID));
        }

    }

    //创建或更新建筑课堂课程
    public function create_or_update_jzkt_data($data){
        date_default_timezone_set("UTC");
        if(isset($data['area']) && $data['area'] == '全国'){
            $subject_info = \Yii::app()->params['jzkt_ehome_subject'];
            $software_id = 0;
            $business_id = 0;
            if(isset($subject_info[$data['subjectList'][0]])){
                $subject_id = $subject_info[$data['subjectList'][0]];
                if($subject_id < 1000){
                    $software_id = $subject_id;
                }else{
                    $business_id = $subject_id;
                }
            }
            $ret = $this->by_jzkt_id($data['courseId'])->find();
            $record = empty($ret) ? new self() : $ret;
            $record->video_name = $data['name'];
            $record->branch_id = JZKT_ID;
            $record->create_user_id = JZKT_ADMIN_ID;
            $record->detail = $data['title'];
            $record->video_type = 4;
            $record->video_img = 'http://jzktst.glodon.com/'.$data['logo'];
            $record->video_src = 'http://jzkt.glodon.com/front/couinfo/'.$data['courseId'];
            $record->software_id = $software_id;
            $record->business_id = $business_id;
            $record->area = $data['area'];
            $record->teacher_name = $data['teacherList'][0];
            $record->create_user_name = '建筑课堂';
            $record->sh_user_name = '建筑课堂';
            $record->is_live = $data['sellType'] == 'LIVE';
            if(isset($data['liveBeginTime'])) $record->live_begin_time = $data['liveBeginTime'];
            if(isset($data['liveEndTime'])) $record->live_end_time = $data['liveEndTime'];
            $record->jzkt_id = $data['courseId'];
            $record->province_id = QG_BRANCH_ID;
            $record->city_id = 0;
            $record->price = $data['buyPrice'];
            $record->open_count = $data['viewcount'];
            $record->up_count = 0;
            $record->down_count = 0;
            $record->favourite_count = 0;
            $record->reply_count = 0;
            $record->share_count = 0;
            $record->status = 1;
            $record->is_delete = false;
            if(empty($ret)) $record->create_time = date('Y-m-d H:i:s');
            $record->update_time = date('Y-m-d H:i:s');
            $record->save();
            echo('创建建筑课堂课程'.$data['courseId'].$data['subjectList'][0].$software_id.$business_id.$data['area']."\n");
        }else{
            echo('地方课程未创建'.$data['courseId']."\n");
        }
    }
}
