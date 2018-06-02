<?php

/**
 * 调研管理
 * @author hd
 */
use application\models\Activity\Activity;
use application\models\Training\Training;
use application\models\ServiceRegion;
use application\models\Research\Research;
use application\models\Research\ResearchIssue;
use application\models\Research\ResearchOption;
use application\models\Research\UserResearch;
use application\models\Research\ResearchTemplate;

class ResearchController extends Controller {

    private $msg = array(
        'Y' => '操作成功',
        1 => '数据非法，请正常输入',
        2 => '栏目类型不能为空',
        3 => '栏目ID不能为空',
        4 => '栏目名称不能为空',
        5 => '触发设置不能为空',
        6 => '调研名称不能为空',
        7 => '调研说明不能为空',
        8 => '时间不合法',
        9 => '同一个栏目只能存在一个在线调研问卷',
        10 => '请选择问题类型',
        11 => '请选择答题要求',
        12 => '标题名称不能为空',
        13 => '问题选择项不能为空',
        14 => '字数超出限制',
        15 => '问题选择项不合法',
        17 => '标题名称字数超出限制，字数在15个字以内',
        18 => '调研说明字数超出限制，字数在80个字以内',
        20 => 'Access Denied',
        21 => '关联的信息尚未发布。',
        22 => '此信息已下线。',
        23 => '网络直播，不能触发签到调研。',
        24 => '分支信息错误',
        25 => '结束时间不能小于当前时间',
        26 => '请选择终端',
        27 => '默认问题不可操作',
        28 => '模板标题不可为空',
        29 => '模板名称不可为空',
        30 => '模板类型不可为空',
        31 => '模板说明不可为空',       
        32 => '模板已存在 请换个模板名称',       
        33 => '模板已绑定，不可删除',       
        34 => '信息错误、请联系管理员',       
        35 => '已结束的记录 不可添加调研',       
    );
    private $column = array(
        '1' => '同城活动',
        '2' => '培训报名',
        '3' => '无关联栏目',
    );
    private $triggerConfig = array(
        '1' => '查看内容时',
        '2' => '签到完成后',
        '3' => '报名完成后',
        '4' => '无',
    );
    private $issue_type_list = array(
        '1' => '多选',
        '2' => '单选',
        '3' => '输入型',
    );
    private $claim_type_list = array(
        '1' => '必答',
        '2' => '非必答'
    );
    private $terminal_type = array(
        1 => 'PC端',
        2 => '手机端',
        3 => '两端共存'
    );
    private $login_type = array(
        1=>'广联云登录',
        2=>'手机号登录'
    );
    private $template_type = array(
        1=>'培训模板',
        2=>'活动模板'
    );
    public $branch_id = 11;
    public function actionindex() {
        $getBranchList = ServiceRegion::model()->getBranchList();
        $this->render('index', array('getBranchList' => $getBranchList));
    }

    public function actionResearchList() {
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
        $search_type = trim(Yii::app()->request->getParam('search_type'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));
        if ($starttime) {
            $con['start_time>'] = $starttime;
            $con['end_time<'] = $endtime ? $endtime . ' 23:59:59' : $starttime . ' 23:59:59';
        }
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            if ($province_code) {
                $con['filiale_id'] = $province_code;
            }
        } else {
            $con['filiale_id'] = Yii::app()->user->branch_id;
        }

        if ($search_type == 'title') {
            $con['research_title'] = $search_content;
        }elseif ($search_type == 'research_id') {
            $con['id'] = $search_content;
        }
        $con['_delete'] = 0;
        $list = Research::model()->get_research_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    /**
     * 问题页面d
     */
    public function actionIssueIndex() {
        $research_id = trim(Yii::app()->request->getParam('research_id'));
        $template_id = trim(Yii::app()->request->getParam('template_id'));
        $param = !empty($research_id)?array('research_id'=>$research_id,'_delete'=>0):array('template_id'=>$template_id,'_delete'=>0);
        $issueinfo = ResearchIssue::model()->getdata($param,'all');
        $this->render('research_issue_list', array('research_id' => $research_id, 'template_id'=>$template_id,'pageUrl' => EHOME . '?r=Research/Preview','issueinfo'=>$issueinfo));
    }

    /**
     * 问题列表
     */
    public function actionIssueList() {
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'asc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $template_id = trim(Yii::app()->request->getParam('template_id'));
        $research_id = trim(Yii::app()->request->getParam('research_id'));
        $con['_delete'] = 0;
        if(!empty($research_id)){
            $con['research_id'] = $research_id;
        }else{
            $con['is_template'] = 1;
            $con['template_id'] = $template_id;
        }
        $list = ResearchIssue::model()->ger_Issue_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    /**
     * 添加调研
     */
    public function actionaddResearch() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $column_type = trim(Yii::app()->request->getParam('column_type'));
                $column_id = intval(Yii::app()->request->getParam('column_id'));
                $column_title = trim(Yii::app()->request->getParam('column_title'));
                $terminal_type = trim(Yii::app()->request->getParam('terminal_type'));
                $trigger_config = trim(Yii::app()->request->getParam('trigger_config'));
                $research_title = trim(Yii::app()->request->getParam('research_title'));
                $start_time = trim(Yii::app()->request->getParam('start_time'));
                $end_time = trim(Yii::app()->request->getParam('end_time'));
                $explain = trim(Yii::app()->request->getParam('explain'));
                $research_id = intval(Yii::app()->request->getParam('research_id'));
                $login_type = intval(Yii::app()->request->getParam('login_type'));
                //非空检查
                if (empty($column_type))
                    throw new Exception('2');
                if ($column_type != 3) {
                    if (empty($column_id))
                        throw new Exception('3');
                    if (empty($column_title))
                        throw new Exception('4');
                    if($login_type<=1){
                         if (empty($trigger_config))
                          throw new Exception('5'); 
                     if ($trigger_config > 3) {
                            throw new Exception('1');
                        }     
                    }
                }
                if (empty($terminal_type))
                    throw new Exception('26');
                if (empty($research_title))
                    throw new Exception('6');
                if (empty($explain))
                    throw new Exception('7');
                if (empty(Yii::app()->user->branch_id))
                    throw new Exception('24');
                if (getStringLength($research_title) > 15) {
                    throw new Exception('17');
                }
                if (getStringLength($explain) > 80) {
                    throw new Exception('18');
                }
                if (empty($start_time) && empty($end_time))
                    throw new Exception('8');
                if (strtotime($end_time) < time()) {
                    throw new Exception('25');
                }
                if ($column_type != 3) {
                    $checkinfo = $this->checkColumn(array('column_id' => $column_id, 'column_type' => $column_type));
                    if (empty($checkinfo)) {
                        throw new Exception('21');
                    }
                }
                if ($column_type == 2) {  //网络签到拦截
                    if ($trigger_config == 2) {
                        if ($checkinfo->way == 2) {
                            throw new Exception('23');
                        }
                    }
                }
                if (!empty($research_id)) {   //效验同一个地区同一个时间段不能存在多个在线调研
                    $this->msg['Y'] = array('research_id' => $research_id);
                    $editResearch = Research::model()->enditResearch($research_id, $_POST);
                    if ($editResearch) {
                        //新增默认问题 培训讲座，签到完成后增加默认问题。
                        if(($column_type == 2 && $trigger_config == 2 && $login_type<=1) || $column_type == 2 && $trigger_config == 4 && $login_type>1){
                            $checkinfo = ResearchIssue::model()->findIssue($research_id,1); 
                            if(empty($checkinfo)){
                                $this->loaddefaultissue($research_id);
                            }
                        }else{ //如果修改了不是配置项，需将绑定的问题删除
                            if($login_type<=1|| $column_type != 2 && $trigger_config == 4 && $login_type>1){ //删除默认项
                                $checkinfo = ResearchIssue::model()->findIssue($research_id,1);
                                if(!empty($checkinfo)){
                                   $delissue = ResearchIssue::model()->updateAll(array('_delete'=>1),'research_id=:research_id and is_default=:is_default',array(':research_id'=>$research_id,':is_default'=>1));
                                   if($delissue){
                                       foreach ($checkinfo as $key=>$row){
                                           $deloption = ResearchOption::model()->updateAll(array('_delete'=>1),'issue_id=:issue_id',array(':issue_id'=>$row->id));
                                       }
                                   }
                                }
                            }
                        }                        
                        $loldata = Research::model()->findByPk($research_id);
                        OperationLog::addLog(OperationLog::$operationResearch, 'edit', '调研管理', $research_id, $loldata->attributes, $_POST);
                        throw new Exception('Y');
                    } else {
                        throw new Exception('Y');
                    }
                } else {
                    $saveResearch = Research::model()->researchSave($_POST);
                    $this->msg['Y'] = array('research_id' => $saveResearch);
                    if ($saveResearch) {
                        //新增默认问题
                        if(($column_type == 2 && $trigger_config == 2 && $login_type<=1)||($login_type >1 && $column_type == 2 && $trigger_config == 4)){
                            $checkinfo = ResearchIssue::model()->findIssue($saveResearch,1);
                            if(empty($checkinfo)){
                                $this->loaddefaultissue($saveResearch);
                            }
                        }
                        OperationLog::addLog(OperationLog::$operationResearch, 'add', '调研管理', $saveResearch, array(), $_POST);
                        throw new Exception('Y');
                    } else {
                        throw new Exception('1');
                    }
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        } else {
            $areaName = ServiceRegion::model()->getBranchToCity(Yii::app()->user->branch_id);
            $research_id = intval(Yii::app()->request->getParam('research_id'));
            $column_id = intval(Yii::app()->request->getParam('column_id'));
            $column_type = intval(Yii::app()->request->getParam('column_type'));
            $column_title = trim(Yii::app()->request->getParam('column_title'));
            $from = trim(Yii::app()->request->getParam('from'));
            if (!empty($research_id)) {
                switch ($from) {
                    case 'myself':
                        $researchInfo = Research::model()->findByPk($research_id);
                        break;
                    case 'shortcut':
                        $researchInfo = array();
                        break;
                    default :
                        $researchInfo = Research::model()->find("column_id=:column_id and column_type=:column_type", array('column_id' => $column_id, 'column_type' => $column_type));
                }
                if (!empty($researchInfo)) {
                    switch ($researchInfo->column_type) {
                        case 1:
                            $columnName = Activity::model()->findByPk($researchInfo->column_id);
                            break;
                        default :
                            $columnName = Training::model()->findByPk($researchInfo->column_id);
                    }
                } else {
                    $columnName = array();
                }
            } else {
                $researchInfo = array();
                $columnName = array();
            }
            $this->render('research_basic_info', array(
                'column' => $this->column,
                'trigger_config' => $this->triggerConfig,
                'terminal' => $this->terminal_type,
                'login_type'=> $this->login_type,
                'areaName' => !empty($areaName[0]['region_name']) ? $areaName[0]['region_name'] : '全国',
                'researchInfo' => $researchInfo,
                'columnName' => $columnName,
                'shortcutinfo' => array('column_id' => $column_id, 'column_type' => $column_type, 'column_title' => urldecode($column_title)),
                'from' => $from
            ));
        }
    }

    /**
     * 编辑问题
     */
    public function actioneditIssue() {
        $issue_id    = trim(Yii::app()->request->getParam('issue_id'));
        $research_id = trim(Yii::app()->request->getParam('research_id'));
        $template_id = trim(Yii::app()->request->getParam('template_id'));
        $issueinfo = ResearchIssue::model()->getIssueInfo($issue_id);
        $this->render('research_issue_info', array(
            'issue_id' => $issue_id,
            'research_id'=>$research_id,
            'template_id'=>$template_id,
            'issueinfo' => $issueinfo,
            'issue_type_list' => $this->issue_type_list,
            'claim_type_list' => $this->claim_type_list));
    }

    /**
     * 新增问题
     */
    public function actionaddIssue() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $issue_type = intval(Yii::app()->request->getParam('issue_type'));
                $claim_type = intval(Yii::app()->request->getParam('claim_type'));
                $issue_name = trim(Yii::app()->request->getParam('issue_name'));
                $issue_option = Yii::app()->request->getParam('issue_option');
                $research_id = intval(Yii::app()->request->getParam('research_id'));
                $template_id = intval(Yii::app()->request->getParam('template_id'));
                $issue_id = intval(Yii::app()->request->getParam('issue_id'));
                //非空检查
                if (empty($issue_type))
                    throw new Exception('10');
                if (empty($claim_type))
                    throw new Exception('11');
                if (empty($issue_name))
                    throw new Exception('12');
                if ($issue_type != 3) {
                    if (empty($issue_option))
                        throw new Exception('15');
                }
                if (getStringLength($issue_name) > 30) {
                    throw new Exception('14');
                }
                if ($issue_type != 3) {
                    foreach ($issue_option as $key => $item) {
                        if (getStringLength($item) >= 200) {
                            throw new Exception('14');
                            exit();
                        }
                    }
                }
                $templatedata = ResearchIssue::model()->getdata(array('issue_name'=>$issue_name,'research_id'=>$research_id,'template_id'=>$template_id,'_delete'=>0));
                if(!empty($templatedata)){
                    if(in_array($templatedata->is_default, array(1)))
                        throw new Exception('27');
                }else{
                    $researchdata = ResearchIssue::model()->getdata(array('issue_name'=>$issue_name,'research_id'=>$research_id,'_delete'=>0));
                    if(!empty($researchdata)){
                        if(in_array($researchdata->is_default, array(1)))
                         throw new Exception('27');        
                    }       
                }
                if (!empty($issue_id)) { 
                        $loldata = ResearchIssue::model()->findByPk($issue_id);
                        $loloptiondata = ResearchOption::model()->getOne(array('issue_id'=>$issue_id,'_delete'=>0),'all');
                        $endIssue = ResearchIssue::model()->enditIssue($issue_id, $_POST);
                    if ($endIssue){
                        if ($issue_type != 3) {
                            $editIssueOption = ResearchOption::model()->editIssueOption($issue_id, $_POST['issue_option']);
                            if ($editIssueOption) {
                                $msgNo = 'Y';
                                OperationLog::addLog(OperationLog::$operationResearch, 'edit', '调研问题选择项', $issue_id, objectToArr($loloptiondata), $_POST['issue_option']);
                            }
                        } else {
                            $issue_option = ResearchOption::model()->findAll('issue_id=:issue_id and _delete=:_delete', array('issue_id' => $issue_id, '_delete' => 0));
                            if (!empty($issue_option)) {
                                foreach ($issue_option as $key => $item) {
                                    $editarray[] = $item['issue_id'];
                                }
                                $delOption = ResearchOption::model()->deleteOption($editarray);
                                if($delOption){
                                    OperationLog::addLog(OperationLog::$operationResearch, 'edit', '调研问题选择项', $issue_id, $loloptiondata, $editarray);
                                }
                            }
                            $msgNo = 'Y';
                        }
                        OperationLog::addLog(OperationLog::$operationResearch, 'edit', '调研问题管理', $issue_id, $loldata->attributes, $_POST);
                    }
                }else {
                    $saveIssue = ResearchIssue::model()->issueSave($research_id, $_POST);
                    if ($saveIssue) {
                         OperationLog::addLog(OperationLog::$operationResearch, 'add', '调研问题管理', $saveIssue, array(), $_POST);
                        if (!empty($_POST['issue_option'])) {
                            $addIssueOption = ResearchOption::model()->addIssueOption($saveIssue, $_POST['issue_option']);
                            if ($addIssueOption) {
                                OperationLog::addLog(OperationLog::$operationResearch, 'add', '调研问题选择项', $saveIssue, array(), $addIssueOption);
                                $msgNo = 'Y';
                            }
                        } else {
                            $msgNo = 'Y';
                        }
                    }
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        } else {
            $research_id = trim(Yii::app()->request->getParam('research_id'));
            $template_id = trim(Yii::app()->request->getParam('template_id'));
            $oper        = trim(Yii::app()->request->getParam('oper'));
            if($oper == 'template'){ //添加模板操作
                if(empty($template_id)){
                    echo CJSON::encode(array('status' => 'kill', 'msg' => 'Access Denied'));
                    exit;
                }
            }else{
                if(empty($research_id)){
                  echo CJSON::encode(array('status' => 'kill', 'msg' => 'Access Denied'));
                }
            }
            $this->render('research_issue_info', array('research_id' => $research_id, 'template_id'=>$template_id,'issue_type_list' => $this->issue_type_list, 'claim_type_list' => $this->claim_type_list));
        }
    }

    /**
     * 选择栏目
     */
    public function actionselectColumn() {
        $column_type = intval(Yii::app()->request->getParam('column_type'));
        if ($column_type == 3) {
            echo CJSON::encode(array('status' => 'kill', 'msg' => 'Access Denied'));
            exit;
        };
        $column_name = $column_type == 1 ? '活动' : '培训';
        $this->render('research_select_column', array('column_name' => $column_name, 'column_type' => $column_type));
    }

    /**
     * 栏目列表
     */
    public function actionColumnList() {
        $column_type = intval(Yii::app()->request->getParam('column_type'));
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'desc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $column_name = trim(Yii::app()->request->getParam('column_name'));
        $con = array('status!' => 0);
        $con['title'] = $column_name;
        if (Yii::app()->user->branch_id != BRANCH_ID) {
            $con['filiale_id'] = Yii::app()->user->branch_id;
        }
        switch ($column_type) {
            case 1:
                $list = Activity::model()->getlist($con, $ord, $field, $limit, $page);
                break;
            default :
                $list = Training::model()->getlist($con, $ord, $field, $limit, $page);
        }
        echo CJSON::encode($list);
    }

    /**
     * ajax获取栏目名称
     * 
     */
    public function actiongetColumnNmame() {
        try {
            $newarray = array();
            $column_type = intval(Yii::app()->request->getParam('column_type'));
            $column_id = intval(Yii::app()->request->getParam('column_id'));
            //非空检查
            if (empty($column_type))
                throw new Exception('2');
            if (empty($column_id))
                throw new Exception('3');
            if (!is_numeric($column_id))
                throw new Exception('1');
            if ($column_type == 3)
                throw new Exception('1');
            switch ($column_type) {
                case 1:
                    $result = Activity::model()->find("id=:id and status=:status", array('id' => $column_id, 'status' => 1));
                    break;
                default :
                    $result = Training::model()->find("id=:id and status=:status", array('id' => $column_id, 'status' => 1));
            }
            if (!empty($result)) {
                $this->msg['Y'] = $result['title'];
                throw new Exception('Y');
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
     * 删除问题、调研
     */
    public function actiondel_data() {
        try {
            $del_id = intval(Yii::app()->request->getParam('del_id'));
            $del_type = trim(Yii::app()->request->getParam('del_type'));
            if (empty($del_id))
                throw new Exception('1');
            switch ($del_type) {
                case 'issue':
                    $model = ResearchIssue::model()->findbypk($del_id);
                    break;
                case 'template':
                    $model = ResearchTemplate::model()->findbypk($del_id);
                    break;
                default :
                    $model = Research::model()->findbypk($del_id);
            }
            if($del_type == 'issue'){
                if(in_array($model->is_default, array(1)))
                    throw new Exception('27');     
            }
            $model->_delete = 1;
            $flag = $model->save();
            if ($flag) {
                if ($del_type == 'issue') {
                    $delissueOption = ResearchOption::model()->updateAll(array('_delete' => 1), "issue_id=:issue_id", array("issue_id" => $del_id));
                    if ($delissueOption) {
                        OperationLog::addLog(OperationLog::$operationResearch, 'del', '问题管理', $del_id, array(), array());
                        $msgNo = 'Y';
                    }
                } else {
                    if($del_type == 'template'){
                        $delIssue = ResearchIssue::model()->updateAll(array('_delete' => 1), 'template_id=:template_id and is_template=:is_template', array('template_id' => $del_id,'is_template'=>1));
                        $getIssueId = ResearchIssue::model()->getdata(array('template_id'=>$del_id,'is_template'=>1),'all');
                    }else{
                        $delIssue = ResearchIssue::model()->updateAll(array('_delete' => 1), 'research_id=:research_id', array('research_id' => $del_id));
                        $getIssueId = ResearchIssue::model()->getdata(array('research_id'=>$del_id),'all');
                    }
                    if ($delIssue) {
                        foreach ($getIssueId as $key => $item) {
                            $editarray[] = $item->id;
                        }
                        $delissueOption = ResearchOption::model()->deleteOption($editarray);
                        $msgNo = 'Y';
                        OperationLog::addLog(OperationLog::$operationResearch, 'del', '调研管理', $del_id, array(), array());
                    }
                }
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

    /**
     * 调研发布
     */
    public function actionresearchRelease() {
        try {
            $research_id = intval(Yii::app()->request->getParam('research_id'));
            $status = intval(Yii::app()->request->getParam('status'));
            if (empty($research_id))
                throw new Exception('1');
            if (empty($status))
                throw new Exception('1');
            $researchInfo = Research::model()->findByPk($research_id);
            if ($status == 1 && $researchInfo->column_type != 3) {
                $checkRes = Research::model()->find('column_id=:column_id and column_type=:column_type and status=:status and _delete=:_delete', array('column_id' => $researchInfo->column_id, 'column_type' => $researchInfo->column_type, 'status' => 1, '_delete' => 0));
                if (!empty($checkRes))
                    throw new Exception('9');
            }
            if (!empty($researchInfo)) {
                $from = $researchInfo->column_type != 3 ? 'lawful' : 'touch';
                $hostInfo = EHOME . '?r=research/preview' . '&research_id=' . $research_id . '&column_id=' . $researchInfo->column_id . '&column_type=' . $researchInfo->column_type . '&from=' . $from;
                $newarray['research_url'] = $status == 1 ? $hostInfo : '';
                $newarray['status'] = $status;
                $newarray['qrcodeurl'] = $status == 1 ? FwUtility::generateQrcodeCode($hostInfo) : '';
                $release = Research::model()->updateByPk($research_id, $newarray);
                if ($release) {
                    $msgNo = 'Y';
                    OperationLog::addLog(OperationLog::$operationResearch, 'lawful', '发布调研', $research_id, array(), array());
                } else {
                    $msgNo = 'Y';
                }
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
     * 复制调研信息
     */
    public function actionCopyResearch() {
        try {
            $addResearch = array();
            $addIssue = array();
            $addIssueOptioen = array();
            $research_id = intval(Yii::app()->request->getParam('research_id'));
            if (empty($research_id))
                throw new Exception('1');
            $getIssueInfo = ResearchIssue::model()->findAll('research_id=:research_id and _delete=:_delete', array('research_id' => $research_id, '_delete' => 0));
            if (!empty($getIssueInfo)) {
                $areaName = ServiceRegion::model()->getBranchToCity(Yii::app()->user->branch_id);
                $addResearch['_delete'] = 0;
                $addResearch['start_time'] = '';
                $addResearch['end_time'] = '';
                $addResearch['filiale_id'] = Yii::app()->user->branch_id;
                $addResearch['area_name'] = !empty($areaName[0]['region_name']) ? $areaName[0]['region_name'] : '';
                $saveResearch = Research::model()->researchSave($addResearch);
                if ($saveResearch) {
                    OperationLog::addLog(OperationLog::$operationResearch, 'add', '添加调研', $saveResearch, array(), array());
                    foreach ($getIssueInfo as $key => $val) {
                        $addIssue['issue_name'] = $val->issue_name;
                        $addIssue['issue_type'] = $val->issue_type;
                        $addIssue['claim_type'] = $val->claim_type;
                        $addIssue['is_default'] = $val->is_default;
                        $SaveIssue = ResearchIssue::model()->issueSave($saveResearch, $addIssue, 'copy');
                        if (!empty($SaveIssue)) {
                            $getIssueOption = ResearchOption::model()->findAll("issue_id=:issue_id and _delete=:_delete", array("issue_id" => $val->id,'_delete'=>0));
                            foreach ($getIssueOption as $key => $row) {
                                $addIssueOptioen['answer_name'] = $row->answer_name;
                                $addOptioen = ResearchOption::model()->addIssueOption($SaveIssue[0], $addIssueOptioen);
                            }
                        }
                    }
                    $msgNo = 'Y';
                }
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 其它栏目入口地址
     */
    public function actionResearchEntrance() {
        $research_id = intval(trim(Yii::app()->request->getParam('research_id')));
        $column_type = intval(trim(Yii::app()->request->getParam('column_type')));
        $column_id = intval(trim(Yii::app()->request->getParam('column_id')));
        $column_title = trim(Yii::app()->request->getParam('column_title'));
        $way = trim(Yii::app()->request->getParam('way'));
        $areaName = ServiceRegion::model()->getBranchToCity(Yii::app()->user->branch_id);
        $template_type = $column_type == 1?2:1;
        $provinces = objectToArr(ResearchTemplate::model()->getdata(array('filiale_id'=>Yii::app()->user->branch_id,'_delete'=>0,'template_type'=>$template_type),'all')); 
        $this->render('research_entrance_info', array('shortcutinfo' => array('column_type' => $column_type, 'column_id' => $column_id, 'column_title' => urldecode($column_title)),'areaName'=>$areaName,'way'=>$way,'provinces'=>$provinces));
    }
    /**
     * 
     */
    public function actiongetTemplate(){
        $filiale_id= is_numeric(Yii::app()->request->getParam('filiale_id')) ? Yii::app()->request->getParam('filiale_id') : '0';
        $column_type = is_numeric(Yii::app()->request->getParam('column_type')) ? Yii::app()->request->getParam('column_type') : '0';
        if(!empty($filiale_id)){
            $template_type = $column_type == 1?2:1;
            $provinces = objectToArr(ResearchTemplate::model()->getdata(array('filiale_id'=>$filiale_id,'_delete'=>0,'template_type'=>$template_type),'all'));
        }else{
            $provinces = objectToArr(ServiceRegion::model()->getBranchList());
            if(Yii::app()->user->branch_id ==QG_BRANCH_ID){              
                array_unshift($provinces,array('filiale_id'=>QG_BRANCH_ID,'region_name'=>'全国'));    
            }

        }
        exit(CJSON::encode($provinces));        
    }
    /**
     * 获取调研信息
     */
    public function actiongetResearchRes() {
        $actitrainfo = array();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $research_id = intval(trim(Yii::app()->request->getParam('research_id')));
                $research_safe = trim(Yii::app()->request->getParam('research_safe'));
                $column_id = intval(trim(Yii::app()->request->getParam('column_id')));
                $column_type = intval(trim(Yii::app()->request->getParam('column_type')));
                $template_id = intval(trim(Yii::app()->request->getParam('template_id')));
                $from = trim(Yii::app()->request->getParam('from'));
                if(empty($column_id))
                    throw new Exception('3');
                switch ($column_type){
                        case 1:
                            $actitrainfo = Activity::model()->findByPk($column_id);
                            break;
                        default :
                            $actitrainfo = Training::model()->findByPk($column_id);
                }
                if(empty($actitrainfo))
                    throw new Exception('34');

                if(strtotime($actitrainfo->endtime)<=time())
                    throw new Exception('35');
                if ($research_safe == 'safe') {
                    $addResearch['column_id'] = $column_id;
                    $addResearch['column_type'] = $column_type;
                    $addResearch['start_time'] = '';
                    $addResearch['end_time'] = '';
                    $saveResearch = Research::model()->researchSave($addResearch);
                    $msgNo = 'Y';
                    $msg = $saveResearch;
                    echo $this->encode($msgNo, array('research_id' => $msg));
                    exit;
                } else {
                    if($research_safe != 'template'){
                        if (empty($research_id))
                           throw new Exception('1'); 
                    }
                }
                if($research_safe == 'template'){
                    $findTemplateInfo = ResearchTemplate::model()->findByPk($template_id);
                    if(!empty($findTemplateInfo)){
                        $addResearch['column_id'] = $column_id;
                        $addResearch['research_title'] = !empty($findTemplateInfo->research_title)?$findTemplateInfo->research_title:'';
                        $addResearch['column_type'] = $column_type;
                        $addResearch['trigger_config'] = 4;
                        $addResearch['terminal_type'] = 3;
                        $addResearch['login_type'] = 2;
                        $addResearch['explain'] = !empty($findTemplateInfo->explain)?$findTemplateInfo->explain:'';
                        $addResearch['start_time'] = !empty($actitrainfo->starttime)?$actitrainfo->starttime:'';
                        $addResearch['end_time'] = !empty($actitrainfo->endtime)?$actitrainfo->endtime:'';
                        $saveResearch = Research::model()->researchSave($addResearch);
                        if($saveResearch){
                            $this->msg['Y'] = array('research_id' => $saveResearch);
                            OperationLog::addLog(OperationLog::$operationResearch, 'add', '添加调研', $saveResearch, array(), $addResearch);
                            $getIssueInfo = ResearchIssue::model()->getdata(array('template_id'=>$template_id,'is_template'=>1,'_delete'=>0),'all');
                            if(!empty($getIssueInfo)){
                                /*模板绑定*/
                                $tempbinding = ResearchIssue::model()->updateAll(array('research_id'=>$saveResearch,'is_template'=>2), "template_id=:template_id and is_template=:is_template",array(':template_id'=>$template_id,'is_template'=>1));
                                if($tempbinding){
                                    $addissue = $this->addIssue($getIssueInfo,$research_id,$template_id);
                                    if($addissue){
                                        $msgNo = 'Y';
                                    }else{
                                        throw new Exception('20');
                                    }
                                }
                            }else{
                                $msgNo = 'Y';
                            }
                        }
                    }else{
                        throw new Exception('1');
                    }
                }else{
                    $getResearch = Research::model()->find("id=:id and _delete=:_delete", array(':id' => $research_id, ':_delete' => 0));
                    if (!empty($getResearch)) {
                        if ($from == 'submit') { //快捷方式传入的
                            $checkColumn = $this->checkColumn(array('column_id' => $column_id, 'column_type' => $column_type));
                            if (empty($checkColumn)) {
                                throw new Exception('21');
                            }
                            $getIssueInfo = ResearchIssue::model()->findAll('research_id=:research_id and _delete=:_delete', array('research_id' => $research_id, '_delete' => 0));
                            if (!empty($getIssueInfo)) {
                                $addResearch['column_id'] = $column_id;
                                $addResearch['column_type'] = $column_type;
                                $addResearch['start_time'] = '';
                                $addResearch['end_time'] = '';
                                $saveResearch = Research::model()->researchSave($addResearch);
                                if ($saveResearch) {
                                    $this->msg['Y'] = array('research_id' => $saveResearch);
                                    OperationLog::addLog(OperationLog::$operationResearch, 'add', '添加调研', $saveResearch, array(), $addResearch);
                                    $addissue = $this->addIssue($getIssueInfo, $saveResearch);
                                    if($addissue){
                                        $msgNo = 'Y';
                                    }else{
                                        throw new Exception('20');
                                    }
                                }
                            } else {
                                $addResearch['column_id'] = $column_id;
                                $addResearch['column_type'] = $column_type;
                                $addResearch['start_time'] = '';
                                $addResearch['end_time'] = '';
                                $saveResearch = Research::model()->researchSave($addResearch);
                                if($saveResearch){
                                    $this->msg['Y'] = array('research_id' => $saveResearch);
                                    $msgNo = 'Y';                                    
                                }else{
                                    throw new Exception('20');
                                }
                            }
                        } else {
                            $this->msg['Y'] = array('research_title' => $getResearch->research_title, 'id' => $getResearch->id);
                            $msgNo = 'Y';
                        }
                    } else {
                        $msgNo = '22';
                    }
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        } else {
            $this->render('research_entrance_info');
        }
    }

    /**
     * 效验数据合法性
     */
    public function checkColumn($data) {
        if (!empty($data)) {
            switch ($data['column_type']) {
                case 1:
                    $result = Activity::model()->find("id=:id and status=:status", array('id' => $data['column_id'], 'status' => 1));
                    break;
                default :
                    $result = Training::model()->find("id=:id and status=:status", array('id' => $data['column_id'], 'status' => 1));
            }
            return $result;
        } else {
            return array();
        }
    }

    /**
     * 统计分析
     */
    public function actionstatisticIndex() {
        $research_id = intval(trim(Yii::app()->request->getParam('research_id')));
        $exportNumber = UserResearch::model()->count(array(
            'group' => 'member_id',
            'condition' => 'research_id=:research_id', //占位符，
            'params' => array(':research_id' => $research_id)
        ));
        $this->render('research_statistic_index', array('research_id' => $research_id, 'exportNumber' => $exportNumber));
    }

    /**
     * 统计list
     */
    public function actionStatisticList() {
        $research_id = intval(trim(Yii::app()->request->getParam('research_id')));
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'asc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $con['_delete'] = 0;
        $con['research_id'] = $research_id;
        $list = ResearchIssue::model()->ger_Issue_list($con, $ord, $field, $limit, $page);
        foreach ($list['data'] as $key => $item) {
            $list['data'][$key]['issue_option_list'] = $this->get_issue_option_list($item['id'], $item['issue_type']);
        }
        echo CJSON::encode($list);
    }

    /**
     * 获取统计问题百分比
     */
    public function get_issue_option_list($issue_id, $issue_type, $i = 0) {
        $optionName = '';
        if ($issue_type == 3) {
            $getimportVal = UserResearch::model()->findAll("issue_id=:issue_id ", array('issue_id' => $issue_id));
            if (!empty($getimportVal)) {
                $optionName = "<span style='width:170px;display:block;text-align: right;'>" . count($getimportVal) . "人次</span>";
            }
        } else {
            $getOptionList = ResearchOption::model()->findAll("issue_id=:issue_id and _delete=:_delete", array('issue_id' => $issue_id,':_delete'=>0));
            if (!empty($getOptionList)) {
                foreach ($getOptionList as $key => $item) {
                    if ($item->issue_id == $issue_id) {
                        $opt_number_sum = ResearchOption::model()->opt_number_sum(array('issue_id' => $issue_id));
                        $percent = !empty($item->opt_number) ? number_format($item->opt_number / $opt_number_sum[0]['total'], 2) * 100 : '0';
                        $optionName .= "<span style='width:140px;display::block;float:left;height:20px;overflow:hidden'>" . $item->answer_name . "</span>" . "<span style='width:60px;display::block;float:left;height:20px;overflow:hidden'>" . $item->opt_number . "人次</span>" . "<span style='width:100px;display::block;float:left;height:20px;overflow:hidden'>" . $percent . "%" . "</span><br>";
                    }
                }
            }
        }
        return $optionName;
    }

    public function actionexportExcel() {
        try {
            $research_id = intval(trim(Yii::app()->request->getParam('research_id')));
            if (empty($research_id))
                throw new Exception('1');
            $getExcelInfo = Research::model()->GetExcelInfo($research_id);
            if (!empty($getExcelInfo)) {
                FwUtility::exportExcel($getExcelInfo['data'], $getExcelInfo['header'], '调研明细', '调研列表_' . date('Y-m-d'));
                exit;
            } else {
                $msgNo = '1';
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 默认问题满意度
     */
    private function loaddefaultissue($research_id = 0,$template_id = 0) {
        $default_issue   = Yii::app()->params['default_issue'];
        $default_option  =  Yii::app()->params['default_option'];
        foreach ($default_issue as $key => $item) {
            $addIssue['issue_name'] = $item['issue_name'];
            $addIssue['issue_type'] = 2;
            $addIssue['claim_type'] = 1;
            $addIssue['is_default'] = 1;
            $addIssue['template_id'] = $template_id;
            $SaveIssue = ResearchIssue::model()->issueSave($research_id, $addIssue, 'copy');
            if (!empty($SaveIssue)) {
                foreach ($default_option[$key] as $key => $row) {
                    $addIssueOptioen['answer_name'] = $row;
                    $addOptioen = ResearchOption::model()->addIssueOption($SaveIssue[0], $addIssueOptioen);
                }
            }
        }
    }
    /**
     * 下载二维码
     */
    public function actiongetqrcode(){
        $qrcodeurl = Yii::app()->request->getParam( 'qrcodeurl' );
        $download = Yii::app()->request->getParam( 'download' );
        $researchid = Yii::app()->request->getParam( 'researchid' );
        if ( $download ) {
            $filename =  $researchid . '.png';
            $file = Yii::getPathOfAlias('system.') . '/../' . $qrcodeurl;
            header("Content-type: octet/stream");
            header("Content-disposition:attachment;filename=" . $filename);
            header("Content-Length:" . filesize($file));
            readfile($file);
            exit;
        }       
    }
    /**
     * 调研模板
     */
    public function  actiontemplate(){
        if(!isset($_GET['iDisplayLength'])){
            $getBranchList = ServiceRegion::model()->getBranchList();
            $this->render('research_template',array('getBranchList'=>$getBranchList));exit;
        }
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
        $search_type = trim(Yii::app()->request->getParam('search_type'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));
        if ($starttime) {
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime . ' 23:59:59' : $starttime . ' 23:59:59';
        }
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            if ($province_code) {
                $con['filiale_id'] = $province_code;
            }
        } else {
            $con['filiale_id'] = Yii::app()->user->branch_id;
        }

        if ($search_type == 'title') {
            $con['template_name'] = $search_content;
        }elseif ($search_type == 'template_id') {
            $con['id'] = $search_content;
        }
        $con['_delete'] = 0;
        $list = ResearchTemplate::model()->get_template_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);        
    }
    /**
     * 添加模板
     */
    public function actionaddtemplate(){
        $template_id = trim(Yii::app()->request->getParam('template_id'));
        $from = trim(Yii::app()->request->getParam('from'));
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $research_title = trim(Yii::app()->request->getParam('research_title'));
                $template_name = trim(Yii::app()->request->getParam('template_name'));
                $template_type = trim(Yii::app()->request->getParam('template_type'));
                $explain = trim(Yii::app()->request->getParam('explain'));
                $params = get_defined_vars();
                //非空检查
                if (empty($research_title))
                    throw new Exception('28');
                if (empty($template_name))
                    throw new Exception('29');
                if (empty($template_type))
                    throw new Exception('30');
                if (empty($explain))
                    throw new Exception('31');          
                if (!empty($template_id)) { 
                    $this->msg['Y'] = array('research_id'=>0,'template_id' => $template_id);
                    $loldata = ResearchTemplate::model()->findByPk($template_id);
                    $editResearch = ResearchTemplate::model()->create_or_update_record($params);
                    if($editResearch){
                        $checkinfo = ResearchIssue::model()->getdata(array('template_id'=>$template_id,'is_default'=>1,'_delete'=>0),'all');
                       if(!empty($checkinfo)){
                           foreach ($checkinfo as $key=>$item){
                               $newarray[] = $item->is_default;
                           }
                           if(in_array(1, $newarray) && !in_array($template_type, array(1))){ //历史选择了有满意度、删除
                               $delissue = ResearchIssue::model()->updateAll(array('_delete'=>1),'template_id=:template_id and is_default=:is_default',array(':template_id'=>$template_id,':is_default'=>1));
                               if($delissue){
                                   foreach ($checkinfo as $key=>$row){
                                        $deloption = ResearchOption::model()->updateAll(array('_delete'=>1),'issue_id=:issue_id',array(':issue_id'=>$row->id));
                                    } 
                               }else{
                                   throw new Exception('1'); 
                               }                              
                           }
                       }else{
                            if(empty($checkinfo)&& in_array($template_type, array(1))){
                                $this->loaddefaultissue(0,$template_id);
                            }  
                       }
                        OperationLog::addLog(OperationLog::$operationResearch, 'edit', '调研管理修改模板', $template_id, $loldata->attributes, $params);
                        throw new Exception('Y');
                    }else{
                         throw new Exception('Y');
                    }
                }else{
                    $saveTemplate = ResearchTemplate::model()->create_or_update_record($params);
                    if($saveTemplate){
                        $this->msg['Y'] = array('research_id'=>0,'template_id' => $saveTemplate);
                        if(in_array($template_type, array(1))){
                            $checkinfo = ResearchIssue::model()->getdata(array('template_id'=>$saveTemplate,'is_default'=>1,'_delete'=>0));
                            if(empty($checkinfo)){
                                $this->loaddefaultissue(0,$saveTemplate);
                                throw new Exception('Y');
                            }                       
                      }
                       throw new Exception('Y');
                       OperationLog::addLog(OperationLog::$operationResearch, 'add', '调研模板添加', $saveTemplate, array(), $params);
                   }
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);            
        }else{
            if (!empty($template_id)) {
                switch ($from) {
                    case 'myself':
                        $templateInfo = ResearchTemplate::model()->findByPk($template_id);
                        break;
                    default :
                        $templateInfo = array();
                }
            } else {
                $templateInfo = array();
            }            
            $this->render('research_template_basic_info',array('template_type'=>$this->template_type,'templateInfo'=>$templateInfo));            
        }
    }
    /**
     * 添加问题
     */
        public function addIssue($resources, $researchid = 0,$template_id = 0) {
            $return = false;
            if (!empty($resources)) {
                foreach ($resources as $key => $val) {
                    $addIssue['issue_name']  = $val->issue_name;
                    $addIssue['issue_type']  = $val->issue_type;
                    $addIssue['claim_type']  = $val->claim_type;
                    $addIssue['is_default']  = $val->is_default;
                    $addIssue['template_id'] = $template_id;
                    $addIssue['is_template']     = !empty($template_id)?1:2;
                    $addIssue = ResearchIssue::model()->issueSave($researchid, $addIssue, 'copy');
                    OperationLog::addLog(OperationLog::$operationResearch, 'add', '添加问题', $addIssue[0], array() , $addIssue);
                    if (!empty($addIssue)) {
                        $getIssueOption = ResearchOption::model()->findAll("issue_id=:issue_id", array(
                            "issue_id" => $val->id
                        ));
                        foreach ($getIssueOption as $key => $row) {
                            $addIssueOptioen['answer_name'] = $row->answer_name;
                            $addOptioen = ResearchOption::model()->addIssueOption($addIssue[0], $addIssueOptioen);
                            OperationLog::addLog(OperationLog::$operationResearch, 'add', '添加问题选择项', $addOptioen['answer_name']['inserid'], array() , $addOptioen);
                        }
                    }
                }
                return true;
            }else{
                return $reutrn;
            }
        }
}
