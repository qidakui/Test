<?php
/**
 * Created by PhpStorm.
 * User: wenlh
 * Date: 2016/5/12
 * Time: 18:11
 */
/**
 * This is the model class for table "{{study_document}}".
 *
 * The followings are the available columns in table '{{study_document}}':
 * @property string $id
 * @property string $study_document_name
 * @property integer $branch_id
 * @property integer $create_user_id
 * @property string $detail
 * @property string $document_name
 * @property integer $document_type
 * @property string $document_img
 * @property string $document_src
 * @property string $document_swf_src
 * @property integer $software_id
 * @property integer $business_id
 * @property integer $area
 * @property integer $open_count
 * @property integer $up_count
 * @property integer $down_count
 * @property integer $favourite_count
 * @property integer $share_count
 * @property integer $status
 * @property integer $is_delete
 * @property string $create_time
 * @property string $update_time
 * @property string $page_count
 * @property string $service_auth_check
 */
namespace application\models\OnlineStudy;
use application\models\Admin\Admin;
use application\models\ServiceRegion;
class StudyDocument extends \CActiveRecord
{
    public $status_name;
    private $statusKey = array(
        0 => '关闭',
        1 => '在线',
    );

    public $documentTypeKey = array(
        1 => 'PDF',
        2 => 'WORD',
        3 => 'PPT'
    );

    public $positionTypeIdKey = array(
        0 => '',
        1 => '更多图文资料',
    );

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{study_document}}';
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
        return array();
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'id',
            'study_document_name' => '列表展示名称',
            'branch_id' => '分支ID',
            'create_user_id' => '创建人ID',
            'create_user_name' => '创建人名称',
            'sh_user_name' => '审核人名称',
            'teacher_name' => '讲师名称',
            'detail' => '描述',
            'document_name' => '文档名称',
            'document_type' => '文档类型',
            'document_img' => '文档图片',
            'document_src' => '文档下载地址',
            'document_swf_src' => '文档观看地址',
            'page_count' => '页数',
            'software_id' => '软件ID',
            'business_id' => '业务ID',
            'province_id' => '省ID',
            'city_id' => '城市ID',
            'position_type_id' => '位置',
            'position_num' => '位置编号',
            'price' => '价格',
            'area' => '地区',
            'open_count' => '打开次数',
            'up_count' => '点赞次数',
            'down_count' => '点踩次数',
            'favourite_count' => '收藏次数',
            'reply_count' => '回复次数',
            'share_count' => '分享次数',
            'comment' => '备注',
            'status' => '状态',
            'is_delete' => '是否删除',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'service_auth_check' => '服务授权验证',
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
     * @return StudyDocument the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    //自定义的保存方法,处理了create_time及update_time
    public function studyDocumentSave($data)
    {
        $branch_id = \Yii::app()->user->branch_id;
        $area = 0;
        if(isset(\Yii::app()->params['online_study_filter']['area'][$branch_id])){
            $area = \Yii::app()->params['online_study_filter']['area'][$branch_id]['id'];
        }
        $model = new self();
        $model->study_document_name = $data['study_document_name'];
        $model->branch_id = $branch_id;
        $model->create_user_id =  \Yii::app()->user->user_id;
        $model->detail = $data['detail'];
        $model->document_name = $data['document_name'];
        $model->document_type = $data['document_type'];
        $model->document_img = $data['document_img'];
        $model->document_src = $data['document_src'];
        $model->document_swf_src = $data['document_swf_src'];
        $model->software_id = $data['software_id'];
        $model->business_id = $data['business_id'];
        $model->province_id = $data['province_id'];
        $model->city_id = $data['city_id'];
        $model->service_auth_check = $data['service_auth_check'];
        $model->comment = $data['comment'];
        $model->price = $data['price'];
        $model->teacher_name = $data['teacher_name'];
        $model->create_user_name = $data['create_user_name'];
        $model->sh_user_name = $data['sh_user_name'];
        $model->area = $area;
        $model->open_count = isset($data['open_count']) ? $data['open_count'] : 0;
        $model->up_count = isset($data['up_count']) ? $data['up_count'] : 0;
        $model->down_count = isset($data['down_count']) ? $data['down_count'] : 0;
        $model->reply_count = isset($data['reply_count']) ? $data['reply_count'] : 0;
        $model->favourite_count = isset($data['favourite_count']) ? $data['favourite_count'] : 0;
        $model->share_count = isset($data['share_count']) ? $data['share_count'] : 0;
        $model->status = 1;
        $model->is_delete = false;
        $model->create_time = date('Y-m-d H:i:s');
        $model->update_time = date('Y-m-d H:i:s');
        $model->save();
        $id = $model->primaryKey;
        return $id;
    }


    public function copyStudyDocument($id){
        $source_document = self::model()->findByPk($id);
        $source_document->setIsNewRecord(true);
        $source_document->__unset('id');
        $source_document->create_user_id = \Yii::app()->user->user_id;
        $source_document->status = 0;
        $source_document->open_count = 0;
        $source_document->up_count = 0;
        $source_document->down_count = 0;
        $source_document->reply_count = 0;
        $source_document->favourite_count = 0;
        $source_document->share_count = 0;
        $source_document->create_time = date('Y-m-d H:i:s');
        $source_document->update_time = date('Y-m-d H:i:s');
        $source_document->save();
        $id = $source_document->primaryKey;
        return $id;
    }

    //自定义的update方法,不修改create_time,修改update_time为当前时间
    public function studyDocumentUpdate($data)
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
        $model->study_document_name = $data['study_document_name'];
        $model->branch_id = $branch_id;
        $model->create_user_id = \Yii::app()->user->user_id;
        $model->detail = $data['detail'];
        if(!empty($data['document_name'])){
            $model->document_name = $data['document_name'];
        }
        $model->document_type = $data['document_type'];
        $model->document_img = $data['document_img'];
        $model->document_src = $data['document_src'];
        $model->document_swf_src = $data['document_swf_src'];
        $model->software_id = $data['software_id'];
        $model->business_id = $data['business_id'];
        $model->province_id = $data['province_id'];
        $model->city_id = $data['city_id'];
        $model->comment = $data['comment'];
        $model->price = $data['price'];
        $model->teacher_name = $data['teacher_name'];
        $model->create_user_name = $data['create_user_name'];
        $model->sh_user_name = $data['sh_user_name'];
        $model->service_auth_check = $data['service_auth_check'];
        $model->area = $area;
        $model->status = 1;
        $model->is_delete = false;
        $model->update_time = date('Y-m-d H:i:s');
        $model->save();
        $id = $model->primaryKey;
        return $id;
    }

    //设置资料显示位置
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

    //自定义修改status字段的方法
    public function studyDocumentUpdateStatus($id, $status)
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
    public function studyDocumentDelete($id)
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
            $result[$v->id] = $v->study_document_name;
        }
        return $result;
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
        $count = self::model()->count($criteria);
        $business = get_all_business_category(true);
        $software = get_software_category();
        $province_info = $this->with_other_model_for_has_one($ret,'application\models\ServiceRegion','province_id', 'region_id');
        $city_info = $this->with_other_model_for_has_one($ret,'application\models\ServiceRegion','city_id', 'region_id');

        foreach ($ret as $k => $v) {
            $data[$k] = $v->attributes;
            $data[$k]['status_name'] = isset($this->statusKey[$v->status]) ? $this->statusKey[$v->status] : '';
            $data[$k]['document_type_name'] = isset($this->documentTypeKey[$v->document_type]) ? $this->documentTypeKey[$v->document_type] : '';
            $data[$k]['software_name'] = isset($software[$v->software_id]) ? $software[$v->software_id] : '';
            $data[$k]['business_name'] = isset($business[$v->business_id]) ? $business[$v->business_id] : '';
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
}
