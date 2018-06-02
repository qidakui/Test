<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/13
 * Time: 11:12
 */
use application\models\Member\Message;
use application\models\ServiceRegion;
use application\models\Member\MessageConfig;
use application\models\Member\CommonMember;

class MessageController extends Controller{

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
        1006 => '图片不能大于1M'
    );

    public function actionIndex(){
        $this->render('index');
    }

    public function actionMessage_list(){
        $member_nick_name      = trim(Yii::app()->request->getParam( 'member_nick_name' ));
        $member_user_name      = trim(Yii::app()->request->getParam( 'member_user_name' ));
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
            $commonMemberArr = CommonMember::model()->find('member_nick_name=:member_nick_name', array('member_nick_name' => $member_nick_name));
            $memberUserIds   = !empty($commonMemberArr->member_user_id) ? $commonMemberArr->member_user_id : 0;
            $con['msg_to_id']= $memberUserIds;
        }

        if(!empty($member_user_name)){
            $commonMemberArr = CommonMember::model()->find('member_user_name=:member_user_name', array('member_user_name' => $member_user_name));
            $memberUserIds   = !empty($commonMemberArr->member_user_id) ? $commonMemberArr->member_user_id : 0;
            $con['msg_to_id']= $memberUserIds;
        }

        /*if(Yii::app()->user->branch_id != BRANCH_ID){
            $branchId = Yii::app()->user->branch_id;
            $con['branch_id'] = $branchId;
        }*/

        $con['_delete'] = 0;
        $list = Message::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }


    public function actionMember(){
        $this->render('member_list');
    }

    public function actionAdd(){
        $CityList = ServiceRegion::model()->getCityList();
        $this->render('message_add', array('citylist' => $CityList));
    }

    public function actionAdd_op(){
        try{
            $config       = Yii::app()->request->getParam( 'config' );
            $subject      = trim(Yii::app()->request->getParam( 'subject' ));
            $message      = trim(Yii::app()->request->getParam( 'content' ));
            $data = array(
                'subject' => $subject,
                'message' => CHtml::encode($message),
                'config' => serialize($config),
            );
            $flag = MessageConfig::model()->messageConfigSave($data);
            if($flag){
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationMessage, 'add', '消息添加', $flag, array(), $data);
            } else {
                throw new Exception('1');
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    //培训讲座选择讲师账号
    public function actionTraining_Member(){
        $this->render('training_member_list');
    }
}