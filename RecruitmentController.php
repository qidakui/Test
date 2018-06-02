<?php

/**
 * 同城招聘管理
 * @author hd
 */
use application\models\Recruitment\RecruitmentCompany;
use application\models\Recruitment\RecruitmentUser;
use application\models\ServiceRegion;
class RecruitmentController extends Controller {

    private $msg = array(
        'Y' => '操作成功',
        1 => '数据非法，请正常输入',
        2 => '操作数据库错误',
    );
    public $user_id;
    public $user_name;
    public $filiale_id;

    public function init(){
        parent::init();
        $this->user_id = Yii::app()->user->user_id;
        $this->user_name = Yii::app()->user->user_name;
        $this->filiale_id = Yii::app()->user->branch_id;
    }
    public function actionindex() {
        $getCityList = ServiceRegion::model()->getCityList();
        $this->render('index',array('getCityList'=>$getCityList));
    }

    public function actionRecruitmenList() {
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'asc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $starttime = trim(Yii::app()->request->getParam('starttime'));
        $endtime = trim(Yii::app()->request->getParam('endtime'));
        $province_code = intval(Yii::app()->request->getParam('province_code')); //分支id前两位
        $company_content = trim(Yii::app()->request->getParam('company_content'));
        if ($starttime) {
            $con['_update_time>'] = $starttime;
            $con['_update_time<'] = $endtime ? $endtime . ' 23:59:59' : $starttime . ' 23:59:59';
        }
        if(!empty($province_code)){
             $con['province'] = $province_code;
        }
        if (!empty($company_content)) {
            $con['company_name'] = $company_content;
        }
        $con['_delete'] = 0;
        $list = RecruitmentUser::model()->get_recruitment_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionRuditdatum() {
        try {
            $company_id = intval(Yii::app()->request->getParam('company_id'));
            if (empty($company_id))
                    throw new Exception('1');
                $con['_delete'] = 0;
                $con['id'] = $company_id;
                $companyInfo = RecruitmentUser::model()->findCompanyInfo($con);
                if (!empty($companyInfo)) {
                    $msgNo = 'Y';
                } else {
                    throw new Exception('2');
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            if ($msgNo == 'Y') {
                $this->render('ruditdatum', array('data' => $companyInfo));
            } else {
                $msg = $this->msg[$msgNo];
                echo $this->encode($msgNo, $msg);
            }
    }

    public function actionaudit() {
        try {
            $company_id = intval(Yii::app()->request->getParam('company_id'));
            $oper = trim(Yii::app()->request->getParam('oper'));
            $audit_rejected = trim(Yii::app()->request->getParam('audit_rejected'));
            $operate_status = trim(Yii::app()->request->getParam('operate_status'));
            if (empty($company_id))
                throw new Exception('1');
            if (empty($oper))
                throw new Exception('1');
            if ($oper == 'nothrough') {
                if (empty($audit_rejected))
                    throw new Exception('1');
            }
            $editarray['audit_id'] = $this->user_id;
            switch ($oper) {
                case 'through':
                    $editarray['audit_status'] = 3;
                    break;
                case 'blacklist':
                    $editarray['status'] = $operate_status;
                    break;
                default :
                   $editarray['audit_status'] = 2;
                   $editarray['rejected'] = $audit_rejected;
            }
            $flag = RecruitmentUser::model()->auditdatum($company_id,$oper,$editarray);
            if ($flag) {
                OperationLog::addLog(OperationLog::$operationRecruitment, 'edit', '资料管理', $company_id, array(), array());
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

}
