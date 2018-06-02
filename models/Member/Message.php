<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/25
 * Time: 14:32
 */
/**
 * This is the model class for table "{{message}}".
 *
 * The followings are the available columns in table '{{message}}':
 * @property string $id
 * @property string $msg_from
 * @property string $msg_from_id
 * @property string $msg_to_id
 * @property string $folder
 * @property integer $new
 * @property string $subject
 * @property string $read_time
 * @property string $date_line
 * @property string $message
 * @property integer $_delete
 * @property integer $relevance_id
 * @property string $source
 * @property string $related_id
 */
namespace application\models\Member;
use application\models\Member\CommonMember;
use application\models\User\UserBrief;
use application\models\ServiceRegion;
class Message extends \CActiveRecord
{
    private $newKey = array(
        0 => '未读',
        1 => '已读',
    );

    public $number=0;

    private $action = array(
        'activity'      => '同城活动',
        'question'      => '在线答疑',
        'home'          => '首页',
        'onlineStudy'   => '在线学习',
        'local_video'   => '在线视频',
        'study_document' => '在线资料',
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{message}}';
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
            'msg_from' => '发送人用户名',
            'msg_from_id' => '发送人用户ID',
            'msg_to_id' => '接收人用户ID',
            'folder' => '收件箱/发件箱-- 暂停',
            'new' => '是否为新消息0新消息1已读',
            'subject' => '消息主题',
            'read_time' => '读取时间',
            'date_line' => '消息发送时间',
            'message' => '消息内容',
            '_delete' => '是否删除0正常1删除',
            'relevance_id' => '关联的消息ID--暂停',
            'source' => '来源 如 答疑 在线学习',
            'related_id' => '关联来源id',
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
     * @return Message the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function messageSave($data){
        $model = new self();
        $model->msg_from    = '系统管理员';
        $model->msg_from_id = 0;
        $model->msg_to_id   = !empty($data['user_id']) ? $data['user_id'] : 0;
        $model->subject     = !empty($data['subject']) ? $data['subject'] : '';
        $model->date_line   = date('Y-m-d H:i:s');
        $model->message     = !empty($data['message']) ? $data['message'] : '';
        $model->config_id   = !empty($data['config_id']) ? $data['config_id'] : 0;
        $model->is_execute  = 1;
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function getlist($con, $orderBy, $order, $limit = null, $offset = null){
        $regionIds = $userArr = array();
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
        if($limit != null || $offset != null){
            $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
            $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        }

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);

        $msgToIds = columnToArr($ret, 'msg_to_id');

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('UIN', $msgToIds);
        $userObj = UserBrief::model()->findAll($criteria);
        if(!empty($userObj)){
            foreach($userObj as $user){
                $userArr[$user->UIN] = $user->attributes;
            }

            foreach($userObj as $user){
                $regionIds[] = !empty($user->regionID) ? substr($user->regionID, 0 ,2) : '';
            }
        }
        if(!empty($regionIds)){
            $criteria = new \CDbCriteria;
            $criteria->addInCondition('left(region_id,2)', $regionIds);
            $criteria->compare('is_parent', 0);
            $ServiceRegionObj = ServiceRegion::model()->findAll($criteria);
            foreach($ServiceRegionObj as $serviceRegion){
                if(!empty($serviceRegion)){
                    $serviceRegionArr[$serviceRegion->region_id] = $serviceRegion->region_name;
                }
            }
        }

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('member_user_id', $msgToIds);
        $criteria->compare('_delete', 0);
        $commonMemberObj = CommonMember::model()->findAll($criteria);

        $commonMemberArr = objectToKeywordArr($commonMemberObj, 'member_user_id', 'member_user_name');

        foreach($ret as $k => $v){
            $data[$k]  = $v->attributes;
            $region_id = isset($userArr[$data[$k]['msg_to_id']]['regionID']) && !empty($userArr[$data[$k]['msg_to_id']]['regionID']) ? substr($userArr[$data[$k]['msg_to_id']]['regionID'],0 , 2) : '';
            $data[$k]['city_name']     = isset($serviceRegionArr[$region_id]) ? $serviceRegionArr[$region_id] : '';
            $data[$k]['msg_to_name']   = isset($commonMemberArr[$data[$k]['msg_to_id']]) ? $commonMemberArr[$data[$k]['msg_to_id']] : '';
            $data[$k]['new_name']      = isset($this->newKey[$data[$k]['new']]) ? $this->newKey[$data[$k]['new']] : '';
            $data[$k]['source_name']      = isset($this->action[$data[$k]['source']]) ? $this->action[$data[$k]['source']] : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }


    //查询短消息
    public function findMessageAll($con){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            $criteria->select = "count(*) as number,msg_to_id";
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $criteria->group = "msg_to_id";
        $ret = self::model()->findAll($criteria);
        return !empty($ret) ? $ret : array();
    }
}