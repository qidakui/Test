<?php

/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/17
 * Time: 8:44
 */
namespace application\models\Common;
use application\models\User\UserPoint;
use application\models\User\UserBrief;
use application\models\User\Ssoinfos;
use application\models\Common\CommonSsoByIdentity;
class CommonMember extends \CActiveRecord {

    /**
     * @return string the associated database table name
     */
    private $_model;

    public function tableName() {
        return '{{common_member}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
           
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
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
    public function search() {
        
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CommonMember the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * 用户注册
     */
    public function register($addUserinfo) {
        $redisLock = \FwUtility::lock($addUserinfo['global_id']->value,10);//redis锁
        if($redisLock === false){
            return $redisLock;
        }        
        $newarray = array();
        $model = new self();
        writeIntoLog('register', "----------\r\n" . date("H:i:s") . "\r\n" . $addUserinfo['UserID']->value . '登录了平台:' . json_encode(
                        array(
                            'number_user_id' => isset($addUserinfo['UserID']->value)?$addUserinfo['UserID']->value:'',
                            'number_global_id' => isset($addUserinfo['global_id']->value)?$addUserinfo['global_id']->value:'',
                            'number_user_name' => isset($addUserinfo['username']->value)?$addUserinfo['username']->value:'',
                            'number_nick' => isset($addUserinfo['nick']->value)?$addUserinfo['nick']->value:'',
                )) . "\r\n--------------\r\n");
        $model->member_user_id  =  $addUserinfo['UserID']->value;
        $model->member_user_name = $addUserinfo['username']->value? urldecode($addUserinfo['username']->value) : '';
        $model->member_nick_name = $addUserinfo['nick']->value?$addUserinfo['nick']->value:'';
        $model->global_id = $addUserinfo['global_id']->value;
        $model->status = 0;
        $model->_login_time = time();
        $model->is_login= 1;
        $model->_create_time = date('Y-m-d H:i:s');
        $model->_update_time = date('Y-m-d H:i:s');
        $verifyMember = $this->find("global_id=:global_id and _delete=:_delete", array(':global_id' => $addUserinfo['global_id']->value,':_delete'=>0));
        writeIntoLog('register/repeat', "----------\r\n" . date("H:i:s") . "\r\n" . '效验重复记录:' .$addUserinfo['global_id']->value."----".var_export($verifyMember,true)."\r\n--------------\r\n");
        if(empty($verifyMember) && !isset($verifyMember->global_id)){
            writeIntoLog('register', "----------\r\n" . date("H:i:s") . "\r\n" . 'web平台入库集合:' . json_encode($model->attributes) . "\r\n--------------\r\n");
            if($model->save()){
                \FwUtility::unlock($addUserinfo['global_id']->value);
                $id  = $model->primaryKey;
                return $id;               
            }else{
                 \FwUtility::unlock($addUserinfo['global_id']->value);
                return false;
            }
        }else{
            return true;
        }
    }

    /**
     * 用户注册
     */
    public function extRegister($addUserinfo) {
        $redisLock = \FwUtility::lock($addUserinfo['global_id'],10);//redis锁
        if($redisLock === false){
            return $redisLock;
        }
        $model = new self();
        writeIntoLog('extRegister', "----------\r\n" . date("H:i:s") . "\r\n" . $addUserinfo['UserID'] . '接收信息:' . json_encode(
                array(
                    'number_user_id' => $addUserinfo['UserID'],
                    'number_global_id' => $addUserinfo['global_id'],
                    'number_user_name' => $addUserinfo['username'],
                    'number_nick' => $addUserinfo['nick'],
                )) . "\r\n--------------\r\n");

        $model->member_user_id      = $addUserinfo['UserID'];
        $model->member_user_name    = $addUserinfo['username']? urldecode($addUserinfo['username']) : '';
        $model->member_nick_name    = $addUserinfo['nick']?$addUserinfo['nick']:'';
        $model->global_id           = $addUserinfo['global_id'];
        $model->status              = 0;
        $model->_login_time         = time();
        $model->_create_time        = date('Y-m-d H:i:s');
        $model->_update_time        = date('Y-m-d H:i:s');
        $verifyMember = $this->find("global_id=:global_id and _delete=:_delete", array(':global_id' => $addUserinfo['global_id'],':_delete'=>0));
        writeIntoLog('register/extrepeat', "----------\r\n" . date("H:i:s") . "\r\n" . '效验重复记录:' .$addUserinfo['global_id']."----".var_export($verifyMember,true)."\r\n--------------\r\n");
        if (empty($verifyMember) && !isset($verifyMember->global_id)){
            $flag = $model->save();
            if($flag){
                writeIntoLog('extRegister', "----------\r\n" . date("H:i:s") . "\r\n" . '平台入库集合成功:' . json_encode($model->attributes) . "\r\n--------------\r\n");
                \FwUtility::unlock($addUserinfo['global_id']);
                return true;
            } else {
                writeIntoLog('extRegister', "----------\r\n" . date("H:i:s") . "\r\n" . '平台入库集合失败:' . json_encode($model->attributes) . "\r\n--------------\r\n");
                \FwUtility::unlock($addUserinfo['global_id']);
                return false;
            }
        }
        return false;
        // }
    }


    /**
     * 校验用户唯一性
     */
    function checkMember($member_user_id = null, $global_id = null) {
        $user = self::find("global_id=:global_id and _delete=:_delete", array(':global_id' => $global_id,'_delete'=>0));
        if (empty($user)) {
            return true;
        }
    }

    /**
     * 效验用户名唯一性
     */
    function checkMemberIdentity($member_user_name){
        $user = $this->find("member_user_name=:member_user_name and status=:status", array(':member_user_name' => $member_user_name,':status'=>0));
        if(!empty($user)){
            return $user;
        }else{
            $getUserBrief = UserBrief::model()->find("UserName=:UserName",array(':UserName'=>$member_user_name));
            if(!empty($getUserBrief)){
                return $getUserBrief;
            }else{
                return array();
            }
        }
    }
    /**
     * 返回数据模型基于用户主键的变量.
     * If the data model is not found, an HTTP exception will be raised.
     */
    public function loadModel($member_user_id) {
        if ($this->_model === null) {
            if (isset($member_user_id)) {
                $this->_model = $this->find("member_user_id=:member_user_id and _delete=:_delete", array(':member_user_id' => $member_user_id,':_delete'=>0));
            }
        }
        return $this->_model;
    }
    
    /**
     * desc:返回列表
     * author:besttaowenjing@163.com
     * date:2016-07-13
     * select参数为字段名字
     */
    public function getlist($con, $orderBy, $order, $limit, $offset, $select){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        if ($select) {
            $criteria->select = $select;
        }
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
       
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        $data = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
	return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);
    }
    
    /**
     * desc:根据id修改该记录
     * author:besttaowenjing@163.com
     * date:2016-07-13
     */
    public function updateInfoGroupById ($id, $param) {
        return self::updateByPk($id, $param);
    }

    /**
     * 根据统计设定是否为专家
     *
     */
    public function setUserInfo($user_id){
        $criteria = new \CDbCriteria;
        $criteria->compare('member_user_id',$user_id);
        $commonMemberObj = self::model()->findAll($criteria);
        if($commonMemberObj){
            CommonMember::model()->updateAll(array('is_question_expert'=> 1),'member_user_id=:member_user_id and _delete=:_delete',array(':member_user_id'=>$user_id,':_delete'=>0));
        }else{
//            $commonMemberObj = new CommonMember();
//            $commonMemberObj->member_user_id = $user_id;
//            $commonMemberObj->is_question_expert = 1;
//            $commonMemberObj->save();
        }
    }
    /*
     * 修改本月已经不是专家的用户状态
     * **/
    public function editUserIsExpertState($userIds){
        $criteria = new \CDbCriteria;
        $criteria->addNotInCondition('member_user_id',$userIds);
        $commonMemberObj = self::model()->findAll($criteria);
        if($commonMemberObj){
            CommonMember::model()->updateAll(array('is_question_expert'=> 0),$criteria);
        }else{
            return true;
        }
    }

    /**
     * 获取个人中心经验值、积分排名、勋章
     */
    public function getRankingList(){
        $result = \Yii::app()->db->createCommand()
            ->select('max(credits) credits, max(experience) experience ')
             ->from('e_common_member')
            ->where('status=:status and _delete=:_delete', array(':status'=>0,':_delete'=>0))
             ->queryRow();
        $medalres = \Yii::app()->db->createCommand('select max(cc.total) total from (SELECT COUNT(member_user_id) as total FROM e_common_member_medal GROUP BY member_user_id) cc')->queryRow();
        $result['medaltotal'] = !empty($medalres['total'])?$medalres['total']:'0';
        return $result;
    }

    /**
     * 增加消息数量
     * @param $data
     * @return int
     */
    public function updateMessageNumber($data){
        $id    = !empty($data['member_user_id']) ? $data['member_user_id'] : 0;
        $model = self::model()->find('member_user_id=:member_user_id and _delete=:_delete', array(':member_user_id' => $id,':_delete'=>0));
        if(empty($model)){
            return false;
        }
        $model->message_number = $model->message_number + 1;
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    /**
     * 修改积分
     * @param $data
     * @return int
     */
    public function updateCredits($data){
        $id    = !empty($data['member_user_id']) ? $data['member_user_id'] : 0;
        $model = self::model()->find('member_user_id=:member_user_id and _delete=:_delete', array(':member_user_id' => $id,':_delete'=>0));
        if($data['credits_type'] == 1){
            $model->experience  = $model->experience - $data['credits'];
        } elseif($data['credits_type'] == 2){
            $model->credits     = $model->credits - $data['credits'];
        } elseif($data['credits_type'] == 3){
            $model->money       = $model->money - $data['credits'];
        }
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }
    /**
     * 获取用户信息
     */
    public function getMemberRes($member_id = null){
         $result = $this->find(array(
             'select' => array('id','member_user_id','member_user_name','member_nick_name','money','random','pay_password'),
             'condition' =>'_delete=:_delete and status=:status and member_user_id=:member_user_id', //占位符
             'params' =>  array(':_delete'=>'0',':status'=>0,':member_user_id'=>$member_id),
         ));
         return $result;
    }


    /**
     * 设置支付密码
     * @param $data
     * @return int
     */
    public function setPayPassword($data){
        $id    = !empty($data['member_user_id']) ? $data['member_user_id'] : 0;
        if(empty($id)){
            return false;
        }
        $model = self::model()->find('member_user_id=:member_user_id and _delete=:_delete', array('member_user_id' => $id,':_delete'=>0));
        $model->pay_password = $data['pay_password'];
        $model->random       = $data['random'];
        $model->_update_time = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    /**
     * 修改广币金额
     */
    public function editMemberMoney($memberInfo = NULL,$money = null){
        if(!empty($memberInfo)){
            $residueMoney = $memberInfo->money - $money;
            $editnot = $this->updateByPk($memberInfo->id,array('money'=>$residueMoney));
            return $editnot;
        }else{
            return false;
        }
    }

    /**
     * 根据memberUserId查找用户信息
     */
    public function findMemberUserId($memberUserId){
        if(empty($memberUserId)){
            return false;
        }
        $userArr = self::model()->find('member_user_id=:member_user_id and _delete=:_delete', array(':member_user_id' => $memberUserId,':_delete'=>0));
        return !empty($userArr) ? $userArr : array();
    }
	
	/**
     * 根据memberUserId获取用户头像
     */
    public function getAvatar($memberUserId){
		$avatar = \Yii::app()->params['defaultHeadIcon']; //本地默认头像
        try {
            if(empty($memberUserId)){
                throw new \Exception($avatar);
            }
            $Ssoinfos = Ssoinfos::model()->find("userid=:userid",array(':userid'=>$memberUserId));
            $UserBrief = UserBrief::model()->findByPk($memberUserId);
            if( empty($Ssoinfos) ){
                throw new \Exception($avatar);
            }
            if( empty($UserBrief) ){
                $flag = CommonSsoByIdentity::model()->synch_user_from_sso_by_identity($Ssoinfos->global_id);
                $UserBrief = UserBrief::model()->findByPk($memberUserId);
            }
            if( strstr($UserBrief->IconUrl,'https://account.glodon.com/avatar/show/') ){
                $avatar = $UserBrief->IconUrl;
                throw new \Exception($avatar);
            }

            $UserYun = \UserYun::getUserInfo($Ssoinfos->global_id);
            if( isset($UserYun['data']) && !empty($UserYun['data']) && $UserYun['data']['defaultAvatar']!=1 ){
                $avatar = $UserYun['data']['avatarPath'][2];
                $UserBrief->IconUrl = $avatar;
                $UserBrief->save();
                throw new \Exception($avatar);
            }
            if(!empty($UserBrief->IconUrl)){
                $avatar = getHeadIcon($memberUserId, $UserBrief->IconUrl);
                throw new \Exception($avatar);
            }
            
        } catch (\Exception $e) {
            $avatar = $e->getMessage();
        }
		return $avatar;
    }

    /**$UserYun = \UserYun::getUserInfo($member->global_id);
				if( isset($UserYun['data']) && !empty($UserYun['data']) && $UserYun['data']['defaultAvatar']!=1 ){
					$avatar = $UserYun['data']['avatarPath'][2];
                    $member->avatar = $avatar;
                    $member->save();
				}else{
					$UserBrief = UserBrief::model()->findByPk($memberUserId);
					$avatar = getHeadIcon($memberUserId, $UserBrief['IconUrl']);
				}
     * 根据GlobalId查找用户信息
     */
    public function findGlobalId($GlobalId){
        if(empty($GlobalId)){
            return false;
        }
        $userArr = self::model()->find('global_id=:global_id and _delete=:_delete', array(':global_id' => $GlobalId,':_delete'=>0));
        return !empty($userArr) ? $userArr : array();
    }

    /**
     * 同步用户积分
     * @param $memberUserId
     * @return string
     */
    public function syncFwxgxCredits($memberUserId){
        try{
            //判断是否同步过
            $isSyncFwxgx = CommonMemberCreditLog::model()->isSyncFwxgx($memberUserId);
            if(!empty($isSyncFwxgx)){
                writeIntoLog('isSyncFwxgx', "----------" . date("H:i:s")  . " -- actionSyncFwxgxPoint isSyncFwxgx userid 已同步数据 ： {$memberUserId} --- count 数量: {$isSyncFwxgx} \r\n");
                throw new \Exception('已同步数据');
            }
            $userPointObj = UserPoint::model()->find('UserID=:UserID', array('UserID' => $memberUserId));
            if(empty($userPointObj)){
                throw new \Exception('用户数据不存在');
            } elseif(!isset($userPointObj->ContributePoint) || $userPointObj->ContributePoint <= 0){
                throw new \Exception('新干线积分为空');
            }

            $memberUserArr = Ssoinfos::model()->find('userid=:userid', array('userid' => $memberUserId));
            if(empty($memberUserArr)){
                writeIntoLog('SyncFwxgxCredits', "----------" . date("H:i:s")  . " -- actionSyncFwxgxPoint UserBrief::model()->getUserInfo 查询 userId ： {$memberUserId} 为空  \r\n");
                throw new \Exception('新干线积分为空');
            }

            $type 		= 'sync';
            $relatedId 	= 0;
            $creditsType= 2;
            $credits 	= $userPointObj->ContributePoint > 0 ? $userPointObj->ContributePoint : 0;
            $title   	= '服务新干线同步积分';
            $appKey  	= '78c39f1dbaf509e84d18c96bc2bf40b1';
            $action 	= 'fwxgx';
            $data = array(
                'globalId'      => $memberUserArr->global_id,
                'type'          => $type,
                'creditsType'   => $creditsType,
                'credits'       => $credits,
                'title'         => $title,
                'appKey'        => $appKey,
            );
            if(!empty($relatedId)){
                $data['relatedId'] = $relatedId;
            }
            //writeIntoLog('SyncFwxgxCredits', "----------" . date("H:i:s")  . "接收参数： ".json_encode($data)." \r\n");

            $flag = \CreditLog::addExtCreditLog($action, $type, $relatedId, $creditsType, $credits, 'add', $title, $memberUserId, $appKey);
            if($flag !== true){
                writeIntoLog('SyncFwxgxCredits', "----------" . date("H:i:s")  . "同步失败： ".json_encode($data)."  $flag \r\n");
            } else {
                return true;
            }
        } catch(\Exception $e){
            return $e->getMessage();
        }
    }

    public function getExpertByNick($nick){
        $criteria = new \CDbCriteria;
        $criteria->compare('member_nick_name',$nick);
        $criteria->compare('is_question_expert',1);
        return self::model()->find($criteria);
    }
}
