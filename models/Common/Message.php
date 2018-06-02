<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/18
 * Time: 14:06
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
namespace application\models\Common;
class Message extends \CActiveRecord
{
    private $newKey = array(
        '0' => '新消息',
        '1' => '已读',
    );

    private $msg = array(
        1 => '发件人用户错误',
        2 => '收件人用户错误',
    );

    private static $action = array(
        'activity'      => '同城活动',
        'question'      => '在线答疑',
        'home'          => '首页',
        'onlineStudy'   => '在线学习',
        'local_video'   => '在线视频',
        'study_document' => '在线资料',
        'prize' => '抽奖',
    );

    public static $link = array(
        'activity' => 'index.php?r=activity/activity&id=',
        'question' => 'index.php?r=question/question_show&question_id=',
        'local_video' => 'index.php?r=Onlinestudy/video_view&video_id=',
        'study_document' => '/index.php?r=Onlinestudy/document_view&document_id=',
        'research'       => '/index.php?r=research/preview&from=Message&research_id=',
        'prize' => '/index.php?r=prize/index&id=',
        'goods' => '/index.php?r=usc/goods&type=goods&orderid='
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
     * @return Message the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param $action $action 动作 如 答疑 在线学习
     * @param $msgFrom 发送人用户名
     * @param $msgFromId 发送人用户ID
     * @param $msgToId 接收人用户ID
     * @param $subject 主题
     * @param $message 消息内容
     * @param $relatedId 关联id
     * @return bool
     */
    public function creditMessage($action, $msgFrom, $msgFromId, $msgToId, $subject, $message, $relatedId){
        $transaction=self::model()->dbConnection->beginTransaction();
        try{
            $msgToMemberArr = CommonMember::model()->find('member_user_id=:member_user_id', array('member_user_id' => $msgToId));
            if(empty($msgToMemberArr)){
                throw new \CException('2');
            }
            $model = new self();
            $model->source              = !empty($action) ? $action : '';
            $model->msg_from            = !empty($msgFrom) ? $msgFrom : '';
            $model->msg_from_id         = !empty($msgFromId) ? $msgFromId : 0;
            $model->msg_to_id           = !empty($msgToId) ? $msgToId : 0;
            $model->subject             = !empty($subject) ? $subject : '';
            $model->message             = !empty($message) ? $message : '';
            $model->related_id          = !empty($relatedId) ? $relatedId : 0;
            $model->date_line           = date('Y-m-d H:i:s');
            $model->save();
            $msgId =$model->primaryKey;
            $CommonMemberFlag = CommonMember::model()->updateMessageNumber(array('member_user_id' => $msgToId));

            if($msgId && $CommonMemberFlag){
                $msgNo = 0;
                $counter = new \ARedisHash("totalMessageMemberUser:".$msgToId);
                $counter->add($msgId, 1);
                $transaction->commit();
            }
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
            $transaction->rollBack();
        }
        if($msgNo == 0){
            return true;
        } else {
            return $this->msg[$msgNo];
        }
    }

    //查询我的消息列表
    public function findMessage($con, $orderBy, $order, $limit){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }

        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }

        $count = self::model()->count($criteria);
        $pages = new \CPagination( $count );//实例化分页类
        $pages->pageSize = $limit;
        $criteria->limit = $pages->pageSize;
        $criteria->offset = $pages->currentPage * $pages->pageSize;

        $ret = self::model()->findAll($criteria);

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['action_name'] = !empty(self::$action[$data[$k]['source']]) ? self::$action[$data[$k]['source']] : '';
            $data[$k]['action_link'] = !empty(self::$link[$data[$k]['source']]) ? self::$link[$data[$k]['source']] : '';
            $data[$k]['short_title'] = !empty($data[$k]['subject']) ? cutstr($data[$k]['subject'], 70) : '';
            $data[$k]['new_name']    = isset($this->newKey[$data[$k]['new']]) ? $this->newKey[$data[$k]['new']] : '';
        }

        $data = !empty($data) ? $data : array();
        //return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        return array('data' => $data, 'count' => $count, 'pages'=>$pages);
    }

    public function UpdateMessage($data){
        $id    = !empty($data['id']) ? $data['id'] : 0;
        $model = self::model()->findByPk($id);
        foreach($data as $key => $val){
            $model->$key = $val;
        }
        $model->read_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    //查询统计
    public function findMessageCount($con){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);

        return !empty($count) ? $count : 0;
    }

    /**
     * 系统发送短消息
     * @param $data
     * @return mixed
     */
    public function messageSave($data){
        $model = new self();
        $model->msg_from    = '系统管理员';
        $model->msg_from_id = 0;
        $model->msg_to_id   = !empty($data['user_id']) ? $data['user_id'] : 0;
        $model->subject     = !empty($data['subject']) ? $data['subject'] : '';
        $model->date_line   = date('Y-m-d H:i:s');
        $model->message     = !empty($data['message']) ? $data['message'] : '';
        $model->config_id   = !empty($data['config_id']) ? $data['config_id'] : 0;
        $model->save();
        $id  = $model->primaryKey;
        $CommonMemberFlag = CommonMember::model()->updateMessageNumber(array('member_user_id' => $data['user_id']));

        if($id && $CommonMemberFlag){
            $counter = new \ARedisHash("totalMessageMemberUser:".$data['user_id']);
            $counter->add($id, 1);
        }
        return $id;
    }
}