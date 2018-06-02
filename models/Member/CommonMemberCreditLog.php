<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/16
 * Time: 14:53
 */

namespace application\models\Member;
use application\models\Member\CommonMember;
class CommonMemberCreditLog extends \CActiveRecord
{
    public $sum;
    private $msg = array(
        1 => '数据错误',
    );
    protected $_memberUserId;
    private $tableName;

    private static $typeKey = array(
        'share_website'     => '分享',
        'submit_questions'  => '提交回答',
        'accept'            => '被提问者采纳',
        'mone'              => '提问给赏金',
        'remark'            => '网友评论',
        'sign'              => '现场签到',
        'collect'           => '收藏',
        'give'              => '点赞',
        'share_activity'    => '分享活动',
        'share_data'        => '分享资料',
        'del_remark'        => '删除评论', //删除评论
        'del_answer'        => '删除答案', //删除答案
        'reward'            => '系统奖赏',
        'charge_money'      => '充值', //充值
        'exchange'          => '兑换', //兑换
        'consume'           => '消费', //消费
        'refund'            => '退款', //退款
    );

    private static $action = array(
        'activity'      => '同城活动',
        'question'      => '在线答疑',
        'home'          => '首页',
        'onlineStudy'   => '在线学习',
        'local_video'   => '在线视频',
        'study_document'=> '在线资料',
        'system'        => '系统',
        'training'      => '培训讲座',
        'goods'         => '商城',
    );

    private static $creditsType = array(
        '1' => '经验值',
        '2' => '积分',
        '3' => '广币',
    );

    private static $link = array(
        'activity'  => 'index.php?r=activity/activity&id=',
        'question'  => 'index.php?r=question/question_show&question_id=',
        'local_video' => 'index.php?r=Onlinestudy/video_view&video_id=',
        'study_document' => '/index.php?r=Onlinestudy/document_view&document_id=',
        'training'  => 'index.php?r=training/detail&id=',
        'product'   => 'index.php?r=product/detail&id=',
        'research'  => '/index.php?r=research/preview&from=Message&research_id=',
        'reward'    => '/index.php?r=usc/message&type=view&params='
    );

    /**
     * @return string the associated database table name
     */
    public function tableName($name = null)
    {
        if($name == null){
            $suffix =  '_'.substr($this->_memberUserId, -2);
            $this->tableName = '{{common_member_credit_log'.$suffix.'}}';
        } else {
            $this->tableName = $name;
        }
        $this->refreshMetaData();
        return $this->tableName;
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

    public function getDbConnection() {
        return \Yii::app()->creditsdb;
//          return \Yii::app()->srvdb;
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CommonMemberCreditLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function setMemberUserId($memberUserId = null)
    {
        $this->_memberUserId   =  empty($memberUserId) ? getUserId() : $memberUserId;
    }

    public function getMemberUserId(){
        return $this->_memberUserId;
    }

    //查询我的积分列表
    public function findCredits($con, $orderBy, $order, $limit, $offset){
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
        $commonMemberArr = CommonMember::model()->loadModel($con['member_user_id']);

        //$provinceArr = \FwUtility::get_cache('province','area');
        //$provinceArr = $this->_getProvince($provinceArr);
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['type_name'] = !empty(self::$typeKey[$data[$k]['type']]) ? self::$typeKey[$data[$k]['type']].'：' : '';
           // $data[$k]['branch_name'] = !empty($provinceArr[$data[$k]['branch_id']]) ? $provinceArr[$data[$k]['branch_id']] : '';
            $data[$k]['action_name'] = !empty(self::$action[$data[$k]['source']]) ? self::$action[$data[$k]['source']] : '';
            $data[$k]['operation_name'] = $data[$k]['operation'] == 'add' ? '+' : '-';
            $data[$k]['credits_type_name'] = isset(self::$creditsType[$data[$k]['credits_type']]) ? self::$creditsType[$data[$k]['credits_type']] : '-';
            if($data[$k]['source'] == 'system' && $data[$k]['type'] == 'reward'){
                $data[$k]['action_link'] = self::$link[$data[$k]['type']];
            } else {
                $data[$k]['action_link'] = !empty(self::$link[$data[$k]['source']]) ? self::$link[$data[$k]['source']] : '';
            }
        }

        $data = !empty($data) ? $data : array();
        //return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);
    }

    //查询今日新增经验值
    public function findCreditsCount($con){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $ret = self::model()->findAll($criteria);
        $count = 0;
        foreach($ret as $val){
            if($val['operation'] == 'add'){
                $count += $val['credits'];
            } elseif ($val['operation'] == 'subtract'){
                $count -= $val['credits'];
            }
        }

        return !empty($count) ? $count : 0;
    }

    private function _getProvince($provinceArr){
        foreach($provinceArr as $val){
            $filiale_id = $val['filiale_id'] != QG_BRANCH_ID ? substr($val['filiale_id'], 0, 2) : QG_BRANCH_ID;
            $data[$filiale_id] = $val['region_name'];
        }
        return !empty($data) ? $data : array();
    }

    /**
     * @param $action 动作 如 答疑 在线学习
     * @param $memberUserId 用户id
     * @param $type 如 分享 点赞
     * @param $relatedId 关联id
     * @param $creditsType 1.经验值2.积分3.广币...
     * @param $credits 分数
     * @param $operation 操作 add 加积分 subtract 减去积分
     * @return mixed
     */
    public function creditOperate($action, $memberUserId, $type, $relatedId, $creditsType, $credits, $operation = 'add', $title, $branch_id = null){
        $transaction=self::model()->dbConnection->beginTransaction();
        try{
            $model = new self();
            $model->setMemberUserId($memberUserId);
            $model->tableName();
            $model->source              = !empty($action) ? $action : '';
            $model->member_user_id      = !empty($memberUserId) ? $memberUserId : 0;
            $model->type                = !empty($type) ? $type : '';
            $model->related_id          = !empty($relatedId) ? $relatedId : 0;
            $model->credits_type        = !empty($creditsType) ? $creditsType : 0;
            $model->credits             = $credits;
            $model->title               = !empty($title) ? $title : '';
            $model->operation           = $operation;
            //$model->branch_id           = getBranchId();
            $model->branch_id            = $branch_id;
            $model->lose_time           = date('Y-m-d H:i:s', strtotime('+2 year'));
            $model->_create_time        = date('Y-m-d H:i:s');
            $model->_update_time        = date('Y-m-d H:i:s');
            $creditLogId = $model->save();
            $commonMemberArr = CommonMember::model()->find('member_user_id=:member_user_id', array('member_user_id' => $memberUserId));
            if(empty($commonMemberArr)){
                throw new \CException('1');
            }
            if($operation == 'add'){
                if($creditsType == 1){
                    $commonMemberArr->experience = $commonMemberArr->experience  + $credits;
                } elseif ($creditsType == 2){
                    $commonMemberArr->credits = $commonMemberArr->credits + $credits;
                } elseif ($creditsType == 3){
                    $commonMemberArr->money = $commonMemberArr->money + $credits;
                }
            } elseif($operation == 'subtract'){
                if($creditsType == 1){
                    $commonMemberArr->experience = $commonMemberArr->experience - $credits;
                } elseif ($creditsType == 2){
                    $commonMemberArr->credits = $commonMemberArr->credits - $credits;
                } elseif ($creditsType == 3){
                    $commonMemberArr->money = $commonMemberArr->money - $credits;
                }
            }

            $memberId = $commonMemberArr->save();

            if($creditLogId && $memberId){
                $msgNo = 0;
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

}