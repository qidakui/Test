<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/13
 * Time: 11:12
 */
use application\models\Member\CommonMember;
use application\models\Member\CommonLoginLog;
use application\models\Question\Expert;
use application\models\Member\CommonMemberCreditLog;
use application\models\Question\UserQuestion;
use application\models\Member\Message;
use application\models\AdminOperationLog;
use application\models\Admin\Admin;
use application\models\ServiceRegion;
use application\models\User\UserBrief;
use application\models\Admin\AdminRole;
class MemberController extends Controller{

    protected $mockUrl = 'http://e.fwxgx.com/index.php?r=usc/Add_message_total';
    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '参数错误',
        1001 => '授予等级小于现有等级',
        1002 => '类型错误',
        1003 => '请选择分支',
        1004 => '图片上传失败',
        1005 => '背景颜色错误',
        1006 => '图片不能大于1M',
        1007 => '积分不能为空',
        1008 => '请选择奖惩类型',
        1009 => '积分不足，不够扣减',
        1010 => '冻结失败',
    );

    public function actionIndex(){
        $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id'=>Yii::app()->user->user_id));
        $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
        $role_id = $adminrole['role_id'];
        $this->render('index',array('role_id'=>$role_id));    
    }

    public function actionMember_list(){
        $member_nick_name      = trim(Yii::app()->request->getParam( 'member_nick_name' ));
        $member_user_name      = trim(Yii::app()->request->getParam( 'member_user_name' ));
        $member_user_id        = trim(Yii::app()->request->getParam( 'member_user_id' ));
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        if(!empty($statr_time) && !empty($end_time)){
            $con['time'] = array('statr_time' => $statr_time, 'end_time' => $end_time);
        }
        if(!empty($member_nick_name)){
            $con['member_nick_name'] = $member_nick_name;
        }

        if(!empty($member_user_name)){
            $con['member_user_name'] = $member_user_name;
        }

        if(!empty($member_user_id)){
            $con['member_user_id'] = $member_user_id;
        }

        /*if(Yii::app()->user->branch_id != BRANCH_ID){
            $branchId = Yii::app()->user->branch_id;
            $con['branch_id'] = $branchId;
        }*/

        $con['_delete'] = 0;
        $list = CommonMember::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionMember_excel(){
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';

        $con['_delete'] = 0;
        $list = CommonMember::model()->getlist($con, $ord, $field);

        foreach($list['data'] as $k=>$v){
            $tmp['member_user_id'] = $v['member_user_id'];
            $tmp['member_nick_name'] = $v['member_nick_name'];
            $tmp['member_user_name'] = $v['member_user_name'];
            $tmp['mobile'] = $v['mobile'];
            $tmp['mail'] = $v['mail'];
            $tmp['city_name'] = $v['city_name'];
            $tmp['is_question_contribution_expert_name'] = $v['is_question_contribution_expert_name'];
            $tmp['is_lecturer_contribution_expert_name'] = $v['is_lecturer_contribution_expert_name'];
            $tmp['Address'] = $v['Address'];
            $data[] = $tmp;
        }
        $header = array('ID','用户昵称','用户名','手机号','邮编','地区','特约专家','特约讲师','地址');

        FwUtility::exportExcel($data, $header,'用户列表','用户列表_'.date('Y-m-d'));
    }

    public function actionCredits(){
        $member_user_id = trim(Yii::app()->request->getParam( 'member_user_id' ));
        $commonMember = CommonMember::model()->find('member_user_id=:member_user_id', array('member_user_id' => $member_user_id));
        $this->render('credits', array('member_user_id' => $member_user_id, 'commonMember' => $commonMember));
    }

    public function actionCredits_list(){
        $member_user_id = trim(Yii::app()->request->getParam( 'member_user_id' ));
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        if(!empty($statr_time) && !empty($end_time)){
            $con['time'] = array('statr_time' => $statr_time, 'end_time' => $end_time);
        }

        $con['member_user_id'] = $member_user_id;
        $con['_delete'] = 0;
        CommonMemberCreditLog::model()->setMemberUserId($member_user_id);
        $list = CommonMemberCreditLog::model()->findCredits($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionAssign_level(){
        $member_id      = trim(Yii::app()->request->getParam( 'id' ));
        $member_user_id = trim(Yii::app()->request->getParam( 'member_user_id' ));
        $expertObj = Expert::model()->find('user_id=:user_id', array('user_id' => $member_user_id));
        $memberObj  = CommonMember::model()->findByPk($member_id);
        $this->render('assign_level', array('member_id' => $member_id, 'member_user_id' => $member_user_id, 'expertObj' => $expertObj, 'memberObj' => $memberObj));
    }

    public function actionAssign_level_op(){
        $flag = true;
        try{
            $member_id      = trim(Yii::app()->request->getParam( 'member_id' ));
            $member_user_id = trim(Yii::app()->request->getParam( 'member_user_id' ));
            $question_expert_level = trim(Yii::app()->request->getParam( 'question_expert_level' ));
            $lecturer_expert_level = trim(Yii::app()->request->getParam( 'lecturer_expert_level' ));
            $is_question_contribution_expert      = trim(Yii::app()->request->getParam( 'is_question_contribution_expert' ));
            $is_lecturer_contribution_expert      = trim(Yii::app()->request->getParam( 'is_lecturer_contribution_expert' ));
            if(isset($is_question_contribution_expert) && $is_question_contribution_expert == 1){
                $expertObj = Expert::model()->find('user_id=:user_id', array('user_id' => $member_user_id));
                if(isset($expertObj->expert_level) && ($question_expert_level < $expertObj->expert_level)){
                    throw new Exception('1001');
                }
                $questionData = array(
                    'expert_level'  => $question_expert_level,
                    'user_id'       => $member_user_id,
                );
                $data = array(
                    'is_question_contribution_expert' => 1,
                    'is_question_expert'              => 1,
                    'id'                              => $member_id,
                );
                $expertFlag = Expert::model()->UpdateExpertLevel($questionData);
                if(!$expertFlag){
                    $flag = false;
                }
            }

            if(isset($is_lecturer_contribution_expert) && $is_lecturer_contribution_expert == 1){
                $memberObj  = CommonMember::model()->findByPk($member_id);
                if(isset($memberObj->lecturer_expert_level) && ($lecturer_expert_level < $memberObj->lecturer_expert_level)){
                    throw new Exception('1001');
                }
                $data = array(
                    'is_lecturer_contribution_expert' => 1,
                    'is_lecturer_expert'              => 1,
                    'lecturer_expert_level'           => $lecturer_expert_level,
                    'id'                              => $member_id,
                );

                $memberFlag = CommonMember::model()->MemberUpdateExpert($data);
                if(!$memberFlag){
                    $flag = false;
                }
            }
            if($flag){
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationMember, 'edit', '指定业务等级', '', array(), $data);
            } else {
                throw new Exception('1');
            }

        } catch (Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    public function actionUpdate_user_op(){
        try{
            $member_id          = intval(Yii::app()->request->getParam( 'member_id' ));
            $member_user_id     = trim(Yii::app()->request->getParam( 'member_user_id' ));
            $member_nick_name   = trim(Yii::app()->request->getParam( 'member_nick_name' ));
            $data = array(
                'member_nick_name' => $member_nick_name,
                'id'               => $member_id,
            );
            $expertData = array(
                'nick'             => $member_nick_name,
                'user_id'          => $member_user_id,
            );
            $flag = CommonMember::model()->MemberUpdateExpert($data);
            $expertFlag = UserQuestion::model()->expertUpdateCommon($expertData);
            if($flag && $expertFlag){
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationMember, 'edit', '编辑用户信息', '', array(), $data);
            } else {
                throw new Exception('1');
            }

        } catch (Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionLock_user(){
        try{
            $member_id      = trim(Yii::app()->request->getParam( 'member_id' ));
            $status         = trim(Yii::app()->request->getParam( 'status' ));
            $content        = trim(Yii::app()->request->getParam( 'content'));
            $data = array(
                'ids'       => $member_id,
                'status'    => $status,
                'content'   => $content
            );
            $memberFlag = CommonMember::model()->MemberUpdateExpert($data);
            if($memberFlag == 'Y'){
                OperationLog::addLog(OperationLog::$operationMember, 'edit', '开启或关闭用户', '', array(), $data);
                $msgNo = 'Y';
            } else {
                throw new Exception('1010');
            }

        } catch (Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    public function actionReward(){
        $member_id      = intval(Yii::app()->request->getParam( 'id' ));
        $member_user_id = intval(Yii::app()->request->getParam( 'member_user_id' ));
        $this->render('reward', array('member_id' => $member_id, 'member_user_id' => $member_user_id));
    }
    public function actionLock(){
        $member_id      = intval(Yii::app()->request->getParam('member_id'));
        $status         = trim(Yii::app()->request->getParam( 'status' ));
        $this->render('lock', array('member_id' => $member_id, 'status' => $status));
    }
    public function actionReward_op(){
        try{
            $member_id      = intval(Yii::app()->request->getParam( 'member_id' ));
            $member_user_id = intval(Yii::app()->request->getParam( 'member_user_id' ));
            $operation      = trim(Yii::app()->request->getParam( 'operation' ));
            $credits        = intval(Yii::app()->request->getParam( 'credits' ));
            $remark         = trim(Yii::app()->request->getParam( 'remark' ));
            $branchId       = Yii::app()->user->branch_id;
            $CommonMemberArr = CommonMember::model()->findByPk($member_id);

            if(empty($credits)){
                throw new Exception('1007');
            }
            if(empty($operation)){
                throw new Exception('1008');
            }
            if($operation == 'add'){
                $title = '系统奖励积分';
            } else {
                if($CommonMemberArr->credits < $credits){
                    throw new Exception('1009');
                }
                $title = '系统惩罚积分';
            }
            CommonMemberCreditLog::model()->setMemberUserId($member_user_id);
            $subject = !empty($remark) ? $remark : $title.$credits;
            //增加消息日志
            $data = array(
                'user_id' => $member_user_id,
                'subject' => $subject,
                'message' => $subject,
                'is_execute' => 1,
                'config_id' => 1,
            );
            $messageId = Message::model()->messageSave($data);
            $commonMemberFlag = CommonMember::model()->updateMessageNumber(array('member_user_id' => $member_user_id));
            $commonMemberCreditLogFlag = CommonMemberCreditLog::model()->creditOperate('system', $member_user_id, 'reward', $messageId, 2, $credits, $operation, $subject, $branchId);
            if($commonMemberCreditLogFlag){
                $msgNo = 'Y';
                if(!$messageId && !$commonMemberFlag){
                    throw new Exception('1');
                } else {
                    $params = "&msgToId={$member_user_id}&messageId={$messageId}";
                    Yii::app()->curl->get($this->mockUrl.$params);
                }
                OperationLog::addLog(OperationLog::$operationMember, 'add', $title, '', array(), array('member_user_id' => $member_user_id, 'credits' => $credits, 'operation' => $operation));
            } else {
                throw new Exception('1');
            }

        } catch (Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    /**
     * 登录明细
     */
    public function actionlogindetail(){
        if(!isset($_GET['iDisplayLength'])){
            $this->render('logindetail');exit;
        } 
        $username           = trim(Yii::app()->request->getParam( 'username' ));
        $limit              = trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page               = trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index              = trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord                = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field              = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord                = !empty($ord) ? $ord : 'desc';
        $field              = !empty($field) ? $field : 'id';
        $page		    = !empty($page) ? $page : 0;
        $limit		    = !empty($limit) ? $limit : 20;
        if(!empty($username)){
            $con['member_user_name'] = $username;
        }else{
            $con = array();   
        }
        $list = CommonLoginLog::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);       
    }
    public function actionintegralList(){
        if(!isset($_GET['iDisplayLength'])){
            $getBranchList = ServiceRegion::model()->getBranchList();
            $this->render('integrallist',array('getBranchList' => $getBranchList));exit;
        }
        $limit              = trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page               = trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index              = trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord                = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field              = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord                = !empty($ord) ? $ord : 'desc';
        $field              = !empty($field) ? $field : 'id';
        $page		    = !empty($page) ? $page : 0;
        $limit		    = !empty($limit) ? $limit : 20;
        $starttime      = trim(Yii::app()->request->getParam('starttime'));
        $endtime        = trim(Yii::app()->request->getParam('endtime'));
        $province_code  = trim(Yii::app()->request->getParam('province_code'));
        $search_content  = trim(Yii::app()->request->getParam('search_content'));
        $export = trim(Yii::app()->request->getParam('export'));
        if ($starttime) {
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime . ' 23:59:59' : $starttime . ' 23:59:59';
        }
        $admincon['status'] = 0;
        $admincon['_delete'] = 0;
        if($province_code){
            $admincon['branch_id'] = $province_code;
        }
        if($search_content){
            $admincon['user_name'] = $search_content;
        }
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            $adminid = Admin::model()->getAdminId($admincon);
            if(!empty($adminid)){
                $con['user_id'] = $adminid['ids'];
            }else{
                $con['user_id'] = BRANCH_ID;
            }
        }
        $con['source'] = 'member';
        $con['operation'] = 'add';
        $con['`column`'] = array('系统奖励积分','系统惩罚积分');
        
        $list = AdminOperationLog::model()->findLog($con, $ord, $field, $limit, $page);
        if(empty($export)){
            echo CJSON::encode($list);exit;
        }else{
            $header = array('分支','用户名','栏目','奖励积分','扣除积分','创建时间');
            $data = array();
            foreach ($list['data'] as $value) {
                $data[]= array($value['branchName'],$value['user_name'],$value['column'],
                    $award = $value['column'] == '系统奖励积分'?$value['new_data']['credits']:'0',
                    $punishment = $value['column'] == '系统惩罚积分'?$value['new_data']['credits']:'0',$value['_create_time']);
            }
            FwUtility::exportExcel($data, $header, '明细列表','明细列表'.date('Ymd'));exit;
        }        
    }
    /**
     * 老答疑用户
     */
    public function actionoldmember(){
        $con = array();
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('oldmember',array('getCityList' => $getCityList));exit;
        }
        $limit              = trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page               = trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index              = trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord                = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field              = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord                = !empty($ord) ? $ord : 'desc';
        $field              = !empty($field) ? $field : 'UIN';
        $page		    = !empty($page) ? $page : 0;
        $limit		    = !empty($limit) ? $limit : 20;
        $starttime      = trim(Yii::app()->request->getParam('starttime'));
        $endtime        = trim(Yii::app()->request->getParam('endtime'));
        $province_code  = trim(Yii::app()->request->getParam('province_code'));
        $search_type = trim(Yii::app()->request->getParam('search_type'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));
        if ($starttime) {
            $con['nRegisterTime>'] = $starttime;
            $con['nRegisterTime<'] = $endtime ? $endtime . ' 23:59:59' : $starttime . ' 23:59:59';
        }
        if($province_code){
            $con['regionID'] = $province_code;
        }
        if ($search_type == 'username') {
            $con['UserName'] = $search_content;
        }elseif ($search_type == 'member_id') {
            $con['UIN'] = $search_content;
        }      
        $list = UserBrief::model()->getList($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    /**
     * 重置密码
     * //设置重置密码，用户身份 用户名 邮箱 手机号 
     * 因为这三个字段可为空，需轮询判断
     */
    public function actionresetpass(){
       if ($_SERVER['REQUEST_METHOD'] == 'POST') {
           try {
               $global_id = trim(Yii::app()->request->getParam('global_id'));
               $identity= trim(Yii::app()->request->getParam('identity'));
               $reset_password = trim(Yii::app()->request->getParam('reset_password'));
               if(empty($global_id))
                   throw new Exception('4');
               if(empty($reset_password))
                   throw new Exception('4');
               if(empty($identity))
                   throw new Exception('4');
              $flag= UserYun::resetpassword($identity,$reset_password); 
              if($flag['code'] == 200){
                    $data = array(
                            'global_id'     =>$global_id,
                            'filiale_id'    =>Yii::app()->user->branch_id,
                            'manager_id'    =>Yii::app()->user->user_id,
                            'reset_password'=>$reset_password
                      ); 
                 OperationLog::addLog(OperationLog::$operationMember, 'reseet', '重置密码', '', array(), $data);
                 $msgNo = 'Y';
              }else{
                  $this->msg[$flag['code']] = $flag['message'];
                  throw new Exception($flag['code']);
              }
           } catch (Exception $ex) {
               $msgNo = $ex->getMessage();
           }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);           
       }else{
           $member_id = trim(Yii::app()->request->getParam('member_id'));
           $memberArr = CommonMember::model()->loadModel($member_id);
           $UserInfo = UserYun::getUserInfo($memberArr->global_id);
           if($UserInfo['code'] == 0){ 
                    if(!empty($UserInfo['data']['email'])){
                       $identity = $UserInfo['data']['email'];
                   }else if(!empty($UserInfo['data']['mobile'])){
                       $identity = $UserInfo['data']['mobile'];
                   }else if(!empty($UserInfo['data']['accountName'])){
                       $identity = $UserInfo['data']['accountName'];
                   }               
           }
           $this->render('resetpass', array('memberArr'=>$memberArr,'identity'=>$identity));
       }
    }
}