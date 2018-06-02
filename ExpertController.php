<?php

/**
 * Created by PhpStorm.
 * User: xinggx
 * Date: 2016/5/31
 * Time: 15:07
 */
use application\models\Question\UserQuestion;
use application\models\Question\Expert;
use application\models\Question\ExpertApply;
use application\models\Member\CommonMember;
use application\models\ServiceRegion;
use application\models\Admin\AdminRole;
use application\models\User\UserBrief;
use application\models\Common\Message;

class ExpertController extends Controller {

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '参数错误',
        5 => '时间格式错误',
        6 => '推荐数量超限',
        1001 => '授予等级小于现有等级',
    );
    public $majorCate = array(
        1 => '土建',
        2 => '安装',
        3 => '装饰',
        4 => '市政',
        5 => '园林',
        6 => '水利',
        7 => '公路',
        8 => '机电',
        9 => '电力',
        10 => '其他',
    );

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionExpert_index() {
        $res = ServiceRegion::model()->getCityList();
        $option = '<option value="' . QG_BRANCH_ID . '">全国</option>';
        foreach ($res as $val) {
            $option .= '<option value="' . $val['region_id'] . '">' . $val['region_name'] . '</option>';
        }
        $this->render('expert_index', array('provice_option' => $option));
    }

    public function actionExpert_list() {
        $search_arr = $this->_get_search_content();
        $list = Expert::model()->getExpertList($search_arr[0], $search_arr[1], $search_arr[2], $search_arr[3], $search_arr[4]);
        echo CJSON::encode($list);
    }

    public function actionSet_expert_level() {
        $user_id = trim(Yii::app()->request->getParam('user_id'));
        $expertObj = Expert::model()->find('user_id=:user_id', array('user_id' => $user_id));
        $this->render('set_expert_level', array('user_id' => $user_id, 'expertObj' => $expertObj));
    }

    public function actionEdit_expert() {
        $major_info = ['土建', '安装', '装饰', '市政', '园林', '水利', '公路', '机电', '电力', '其他'];
        $user_id = trim(Yii::app()->request->getParam('user_id'));
        $expert_info = Expert::model()->find('user_id=:user_id', array('user_id' => $user_id));
        if (!empty($expert_info->mobile)) {
            $mobile = $expert_info->mobile;
        } else {
            $user = UserBrief::model()->getUserBriefObjByUIds(array($user_id));
            $mobile = (isset($user[0]) && !empty($user[0]) && !empty($user[0]->sMobile)) ? $user[0]->sMobile : '';
        }
        $this->render('edit_expert', array('user_id' => $user_id, 'expert_info' => $expert_info, 'major_info' => $major_info, 'mobile' => $mobile));
    }

    public function actionUpdate_expert() {
        $msgNo = '2';
        try {
            $expert_id = trim(Yii::app()->request->getParam('expert_id'));
            $real_name = trim(Yii::app()->request->getParam('real_name'));
            $mobile = trim(Yii::app()->request->getParam('mobile'));
            $job_year = trim(Yii::app()->request->getParam('job_year'));
            $major = Yii::app()->request->getParam('major');
            $remark = trim(Yii::app()->request->getParam('remark'));
            $resume_name = trim(Yii::app()->request->getParam('resume_name'));
            $resume_src = trim(Yii::app()->request->getParam('resume_src'));
            $expert = Expert::model()->findByPk($expert_id);
            if (empty($expert)) {
                throw new Exception('2');
            }
            $expert->real_name = $real_name;
            $expert->mobile = $mobile;
            $expert->job_year = $job_year;
            if (!empty($major)) {
                $expert->major = join(',', $major);
            } else {
                $expert->major = '';
            }
            $expert->remark = $remark;
            $expert->resume_name = $resume_name;
            $expert->resume_src = $resume_src;
            if ($expert->save()) {
                $msgNo = 'Y';
            };
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionSave_expert_resume() {
        if (isset($_FILES['resume_file'])) {
            $name = $_FILES['resume_file']['name'];
            $upload = new Upload();
            $upload->set('allowtype', array('doc', 'docx'));
            $upload->set('maxsize', 10 * 1024 * 1024);
            $flag = $upload->uploadFile('resume_file');
            if (empty($upload->getErrorMsg())) {
                //保存数据库及返回前台结果
                echo CJSON::encode(array('status' => 'Y', 'resume_name' => $name, 'resume_src' => $flag, 'link_href' => UPLOADURL . $flag));
                exit;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());
                exit;
            }
        }
    }

    public function actionUpdate_expert_level() {
        $flag = true;
        try {
            $user_id = trim(Yii::app()->request->getParam('user_id'));
            $question_expert_level = trim(Yii::app()->request->getParam('question_expert_level'));
            $expertObj = Expert::model()->find('user_id=:user_id', array('user_id' => $user_id));
            $oldData = array(
                'expert_level' => $expertObj->expert_level,
                'user_id' => $user_id,
            );
            if (isset($expertObj->expert_level) && ($question_expert_level < $expertObj->expert_level)) {
                throw new Exception('1001');
            }
            $questionData = array(
                'expert_level' => $question_expert_level,
                'user_id' => $user_id,
            );
            $expertFlag = Expert::model()->UpdateExpertLevel($questionData);
            if (!$expertFlag) {
                $flag = false;
            }
            if ($flag) {
                $msgNo = 'Y';
                if($oldData['expert_level']!=$questionData['expert_level']){
                   FwUtility::informCredit('creditexpert','expert_upgrade',$expertFlag,'add','答疑专家等级升级',$user_id);
                   
                }
                 OperationLog::addLog(OperationLog::$operationExpert, 'edit', '修改专家等级', '', $oldData, $questionData);
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionExpert_del() {
        try {
            $id = trim(Yii::app()->request->getParam('id'));
            $user_id = trim(Yii::app()->request->getParam('user_id'));
            if (empty($id)) {
                throw new Exception('4');
            }
            $flag = Expert::model()->delExpert($id);
            if ($flag) {
                OperationLog::addLog(OperationLog::$operationExpert, 'del', '删除专家', '', array(), $id);
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionExpert_leave() {
        try {
            $id = trim(Yii::app()->request->getParam('id'));
            if (empty($id)) {
                throw new Exception('4');
            }
            $flag = Expert::model()->leaveExpert($id);
            if ($flag) {
                OperationLog::addLog(OperationLog::$operationExpert, 'del', '卸任专家', '', array(), $id);
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 导出专家列表,导出方式为同步导出
     */
    public function actionExport_information() {
        $expert_search_arr = $this->_get_search_content();
        $expert_list = Expert::model()->getExpertList($expert_search_arr[0], $expert_search_arr[1], $expert_search_arr[2], -1, 0)['data'];
        $header = array('专家账号', '专家昵称', '真实姓名', '手机号', '状态', '地区', '等级', '专业', '邮箱', '从业年限', '备注', '创建时间');
        $data = array();
        //写入专家信息
        foreach ($expert_list as $k => $v) {
            switch (intval($v['expert_state'])) {
                case 0:
                    $expert_state = '申请中';
                    break;
                case 1:
                    $expert_state = '在职';
                    break;
                case 2:
                    $expert_state = '卸任';
                    break;
                case 3:
                    $expert_state = '删除';
                    break;
                case 4:
                    $expert_state = '不批准';
                    break;
                default:
                    $expert_state = $v['expert_state'];
            }
            $data[] = array($v['UserName'], $v['Nick'], $v['real_name'], $v['mobile'], $expert_state, $v['RegionName'],
                $v['expert_level'], $v['major'], $v['email'], $v['job_year'], $v['remark'],
                $v['_create_time'],
            );
        }
        FwUtility::exportExcel($data, $header, '专家列表', '答疑专家资料导出' . date('Ymd'));
    }

    private function _get_search_content() {
        $con = array();
        $expert_state = trim(Yii::app()->request->getParam('expert_state'));
        $province_code = trim(Yii::app()->request->getParam('province_code'));
        $search_type = trim(Yii::app()->request->getParam('search_type'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));
        $start_date = trim(Yii::app()->request->getParam('start_date'));
        $end_date = trim(Yii::app()->request->getParam('end_date'));
        $search_recomm = trim(Yii::app()->request->getParam('search_recomm'));
        $limit = trim(Yii::app()->request->getParam('length'));
        $offset = trim(Yii::app()->request->getParam('start'));
        $indexArr = Yii::app()->request->getParam('order');
        $index = $indexArr[0]['column'];
        $ord = $indexArr[0]['dir'];
        //$field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $columns = Yii::app()->request->getParam('columns');

        $ord = !empty($ord) ? $ord : 'desc';
        $field = isset($columns[$index]['data']) ? $columns[$index]['data'] : 'id';
//        $ord = 'desc';
//        $field = 'id';
        $offset = !empty($offset) ? $offset : 0;
        $limit = !empty($limit) ? $limit : 20;

        if (!empty($search_content) && !empty($search_type)) {
            switch ($search_type) {
                case 1:
                    $con['UserName'] = array('search_like', $search_content);
                    break;
                case 2:
                    $con['Nick'] = array('search_like', $search_content);
                    break;
            }
        }
        if (isset($expert_state) && $expert_state != '_all_') {
            $con['expert_state'] = $expert_state;
        }

        if (isset($province_code) && $province_code != QG_BRANCH_ID && $province_code != "") {
            $con['province_code'] = $province_code;
        }

        if (!empty($start_date) && !empty($end_date)) {
            $con['time'] = array('start_time' => $start_date, 'end_time' => $end_date);
        }
        if (!empty($search_recomm)) {
            $con['is_recommend'] = array('is_recommend' => $search_recomm);
        }
        return array($con, $ord, $field, $limit, $offset);
    }

    public function actionExpert_apply_index() {
        $this->render('expert_apply_index');
    }

    public function actionexpert_apply_list() {
        $con = array();
        $member_user_name = trim(Yii::app()->request->getParam('member_user_name'));
        $mobile = trim(Yii::app()->request->getParam('mobile'));
        $limit = trim(Yii::app()->request->getParam('length'));
        $page = trim(Yii::app()->request->getParam('start'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'desc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        if (Yii::app()->user->branch_id != BRANCH_ID) {
            $branchId = Yii::app()->user->branch_id;
            $con['filiale_id'] = $branchId;
        }
        if (!empty($member_user_name)) {
            $con['member_user_name'] = $member_user_name;
        }

        if (!empty($mobile)) {
            $con['mobile'] = $mobile;
        }

        $list = ExpertApply::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionExpert_apply_apdate() {
        try {
            $id = intval(Yii::app()->request->getParam('id'));
            $status = intval(Yii::app()->request->getParam('status'));
            if (empty($id))
                throw new Exception('4');
            $data = array(
                'id' => $id,
                'status' => $status,
            );
            $exper_apply = ExpertApply::model()->find('id = :id', array('id' => $id));
            if (empty($exper_apply)) {
                throw new Exception('3');
            } else {
                $member_user_id = $exper_apply->member_user_id;
            }
            if ($status == 1) {
                $str = '通过专家账户';
                //新增专家，默认一级；同时在Expert、CommonMember、UserQuestion中创建数据
                Expert::model()->addExpert($member_user_id);
                $expert = Expert::model()->find('user_id=:user_id', array('user_id' => $member_user_id));
                $major_info = CJSON::decode($exper_apply->major);
                if(!empty($major_info)){
                    foreach($major_info as $v){
                        $info[]=$this->majorCate[$v];
                        $expert->major = join(',', $info);
                    }
                }
                $expert->real_name = $exper_apply->apply_user_name;
                $expert->mobile = $exper_apply->mobile;
                $expert->job_year = $exper_apply->agelimit;
                $expert->save();

                $message_data = array(
                    'user_id' => $member_user_id,
                    'subject' => '系统消息',
                    'message' => "恭喜您通过了审核，成为答疑专家。请您仔细阅读《2017年答疑专家管理及考核规则》，有疑问请与管理员联系。",
                    'config_id' => 1,
                );

                Message::model()->messageSave($message_data);
            } else {
                $str = '不通过专家账户';
                $message_data = array(
                    'user_id' => $member_user_id,
                    'subject' => '系统消息',
                    'message' => "很抱歉，您没有通过专家审核，请您再接再厉哦！如有疑问，请联系管理员。",
                    'config_id' => 1,
                );

                Message::model()->messageSave($message_data);
            }
            $flag = ExpertApply::model()->expertUpdate($data);
            if ($flag) {
                $msgNo = 'Y';
                FwUtility::informCredit('creditexpert','expert_apply',$id,'add','答疑专家申请',$member_user_id);
                OperationLog::addLog(OperationLog::$operationExpert, 'edit', $str, '', array(), $data);
            } else {
                throw new Exception('1');
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 设置用户推荐
     */
    public function actionset_expert_recommend() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $user_id = Yii::app()->request->getParam('user_id');
                $is_all = Yii::app()->request->getParam('is_all');
                //非空检查
                $countMun = Expert::model()->count("filiale_id=:filiale_id and is_recommend=:is_recommend", array('filiale_id' => Yii::app()->user->branch_id, ':is_recommend' => 1));
                if ($countMun >= 2)
                    throw new Exception('6');
                $editconfig['filiale_id'] = Yii::app()->user->branch_id;
                $editconfig['is_recommend'] = 1;
                $editconfig['is_all'] = $is_all ? $is_all : '0';
                $editinfo = Expert::model()->edit_expert_mes($editconfig, $user_id);
                if ($editinfo) {
                    OperationLog::addLog(OperationLog::$operationExpert, 'edit', '专家推荐', '', array(), $editconfig);
                    $msgNo = 'Y';
                } else {
                    throw new Exception('1');
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        } else {
            //判断是否为超级管理员
            $user_id = Yii::app()->request->getParam('user_id');
            $this->render('set_expert_recommend', array('branch_id' => Yii::app()->user->branch_id, 'user_id' => $user_id));
        }
    }

    /**
     * 关闭推荐
     */
    public function actionExpert_close() {
        try {
            $id = Yii::app()->request->getParam('id');
            if (empty($id))
                throw new Exception('4');
            $flag = Expert::model()->edit_recomm($id);
            if ($flag) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationExpert, 'del', '关闭推荐', '', array(), $id);
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
