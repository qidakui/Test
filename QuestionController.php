<?php
use application\models\ServiceRegion;
use application\models\Question\Question;
use application\models\Question\Answer;
use application\models\Question\SetExpertAnswer;
use application\models\Member\CommonMember;
use application\models\Question\MemberReported;
use application\models\Question\Analysis\QuestionFirstAnswerAnalysis;
use application\models\Question\Analysis\QuestionOperateAnalysis;
use application\models\Question\Analysis\QuestionUserLevelAnalysis;
use application\models\Question\Analysis\QuestionUserOperateAnalysis;
use application\models\Question\QuestionUser;
class QuestionController extends Controller
{
    /*
Yii::app()->user->isGuest 是否登录
Yii::app()->user->user_id 获取用户id
Yii::app()->user->user_name 获取用户名
Yii::app()->user->branch_id 分之id
后端 获取用户信息
    */
    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '开始时间不能为空',
        5 => '结束时间不能为空',
        6 => '批量导出上限为10万条记录。',
        7 => '问题ID不存在',
        8 => '答案ID不存在',
        9 => '举报内容不存在'
    );

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if($error=Yii::app()->errorHandler->error)
        {
            if(Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    public function actionQuestion_index()
    {
        $option = '';
        $res = ServiceRegion::model()->getCityList();
        $getCateName = Question::model()->getCateName();
        $option = '<option value="'. QG_BRANCH_ID.'">全国</option>';
        foreach ($res as $val) {
            $option .= '<option value="' . $val['region_id'] . '">' . $val['region_name'] . '</option>';
        }
        $this->render('question_index', array('provice_option' => $option,'getCateName'=>$getCateName));
    }

    public function actionGet_question_list(){
        $search_arr = $this->_get_search_arr();
        $list = Question::model()->getQuestionList($search_arr[0], $search_arr[1], $search_arr[2], $search_arr[3], $search_arr[4]);
        echo CJSON::encode($list);
    }


    public function actionQuestion_export_execl(){
        try {
            $start_time = trim(Yii::app()->request->getParam('start_time'));
            $end_time = trim(Yii::app()->request->getParam('end_time'));
            $province_id = intval(Yii::app()->request->getParam('province'));
            $category_id = intval(Yii::app()->request->getParam('category_id'));
            $title = trim(Yii::app()->request->getParam('title'));
            $exportTitle = trim(Yii::app()->request->getParam('exportTitle'));
            $file_name = trim(Yii::app()->request->getParam('file_name'));
            $status = trim(Yii::app()->request->getParam('status'));
            if(empty($start_time))
                throw new Exception('4');
            if(empty($end_time))
                throw new Exception('5');
            $ord = 'desc';
            $field = '_create_time';
            if (!empty($start_time) && !empty($end_time)) {
                $end_time = date("Y-m-d", strtotime("$end_time   +1   day"));
                $con['_create_time'] = array('between', $start_time, $end_time);
            }
            if (!empty($title)) {
                 $con['title'] = array('search_like', $title);
            }
            if(!empty($category_id)){
                $con['category_id'] = $category_id;
            }
            if($province_id != QG_BRANCH_ID){
                $con['left(area_code,2)'] = $province_id;
            }
            if(!empty($status)){
                $con['status'] = $status;
            }
            $con['is_valid'] = 1;
            $con['_deleted'] =0;
            $headerarray= array('ID','标题','分类','用户名','手机号','邮箱','浏览数量','回答数量','得赞数量','收藏数量','省份','地区','状态','创建时间','最后回答时间');
            $total = Question::model()->getExportTotal($con);
            if($total >=100000){
                throw new Exception('6');
            }
            $list = ExportTasks::addRecord(ExportTasks::$creditQuestion,$con,$headerarray,$exportTitle,$file_name);
            if($list){
                $msgNo = 'Y';
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage(); 
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);   
    }

    public function actionQuestion_detail(){
        $id = Yii::app()->request->getParam( 'id' );
        $ret = Question::model()->find('id=:id', array('id' => $id));
        $this->render('question_detail', array('question' => $ret));
    }

    public function actionQuestion_show(){
        $id = Yii::app()->request->getParam( 'id' );
        $ret = Question::model()->find('id=:id', array('id' => $id));
        $this->render('question_show', array('question' => $ret));
    }

    public function actionSet_expert_answer(){
        $this->render('expert_answer_index');
    }

    public function actionSet_expert_answer_list()
    {
        $sSearch   	= trim(Yii::app()->request->getParam( 'sSearch' ));
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        if(Yii::app()->user->branch_id != BRANCH_ID){
            $branchId = Yii::app()->user->branch_id;
            $con['filiale_id'] = $branchId;
        }
        $con['_delete'] = 0;

        $list = SetExpertAnswer::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionExpert_answer_add(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            try {
                $member_user_name = trim(Yii::app()->request->getParam( 'member_user_name' ));
                $id= trim(Yii::app()->request->getParam('id'));
                //非空检查
                if(empty($member_user_name))
                    throw new Exception('3');

                if(!empty($id)){
                    $checkres = CommonMember::model()->find('member_user_name=:member_user_name',array('member_user_name'=>$member_user_name));
                    if(empty($checkres))
                        throw new Exception('3');
                    $olddata = SetExpertAnswer::model()->findByPk($id);
                    $data['user_id'] =  Yii::app()->user->user_id;
                    $data['filiale_id'] = Yii::app()->user->branch_id;
                    $data['member_user_id'] = $checkres->member_user_id;
                    $editmenu = $olddata->editsave($data);
                    if($editmenu){
                        OperationLog::addLog(OperationLog::$operationQuestion , 'edit', '新增答疑服务账户', $editmenu, $olddata->attributes, $data);
                        $msgNo = 'Y';
                    }
                }else{
                    $checkres = CommonMember::model()->find('member_user_name=:member_user_name',array('member_user_name'=>$member_user_name));
                    if(empty($checkres))
                        throw new Exception('3');
                    $setExpertAnswerObj = SetExpertAnswer::model()->find('member_user_id=:member_user_id and _delete=0', array('member_user_id' => $checkres->member_user_id));
                    if(!empty($setExpertAnswerObj)){
                        throw new Exception('2');
                    }
                    $data['user_id'] =  Yii::app()->user->user_id;
                    $data['filiale_id'] = Yii::app()->user->branch_id;
                    $data['member_user_id'] = $checkres->member_user_id;
                    $savemenu = SetExpertAnswer::model()->addSave($data);
                    if($savemenu){
                        OperationLog::addLog(OperationLog::$operationQuestion , 'add', '设置答疑服务账户', $savemenu, array(), $data);
                        $msgNo = 'Y';
                    }
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        }else{
            $commonMember = array();
            $id= trim(Yii::app()->request->getParam('id'));
            $editinfo = SetExpertAnswer::model()->findByPk($id);
            if(!empty($editinfo)){
                $commonMember = CommonMember::model()->find('member_user_id=:member_user_id', array('member_user_id' => $editinfo->member_user_id));
            }
            $this->render('expert_answer_add',array('data'=>$editinfo, 'commonMember' => $commonMember,'id'=>$id));
        }
    }

    public function actionExpert_answer_del(){
        try {
            $id = trim(Yii::app()->request->getParam( 'id' ));
            if(empty($id))
                throw new Exception('1');
            $model = SetExpertAnswer::model()->findbypk($id);
            $model->_delete = 1;
            $flag = $model->save();
            if ($flag) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationQuestion , 'add', '删除答疑服务账户', $id, array(), array());
            } else {
                throw new Exception('1');
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    /*
    *删除问题
     */
    public function actionQuestion_del(){
        $id = Yii::app()->request->getParam( 'id' );
        $flag = Question::model()->delQuestion($id);
        if($flag['data']){
            OperationLog::addLog(OperationLog::$operationQuestion, 'del', '删除问题', $id, '',''); //删除
            $msgNo = 'Y';
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        } else {
            $msgNo = 1;
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        }
    }

    /**
     * 举报内容管理
     */
    public function actionMember_reported_index(){
        $this->render('member_reported_index');
    }

    public function actionMember_reported_list()
    {
        $statr_time = trim(Yii::app()->request->getParam( 'statr_time' ));
        $end_time 	= trim(Yii::app()->request->getParam( 'end_time' ));
        $sSearch   	= trim(Yii::app()->request->getParam( 'sSearch' ));
        $limit   	= trim(Yii::app()->request->getParam( 'length' ));
        $page       = trim(Yii::app()->request->getParam('start'));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $con['_delete'] = 0;
        $list = MemberReported::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

     /*
     * 撤销举报内容
     */
    public function actionMember_reported_reset(){
        $id = Yii::app()->request->getParam( 'id' );
        $id = is_numeric($id) ? intval($id) : 0;
        $memberReportedObj = MemberReported::model()->findByPk($id);
        if(empty($memberReportedObj)){
            $msgNo = 1;
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
            exit;
        }
        if($memberReportedObj->type == 1){
            MemberReported::model()->updateByPk($id, array('_delete' => 1));
            $question = Question::model()->getQuestion($memberReportedObj->reported_id);
            $question->is_valid = 1;
            $flag = $question->save();
        } elseif($memberReportedObj->type == 2){
            MemberReported::model()->updateByPk($id, array('_delete' => 1));
            $flag = Answer::model()->updateByPk($memberReportedObj->reported_id, array('is_valid' => 1));
        }
        if($flag){
            OperationLog::addLog(OperationLog::$operationQuestion, 'add', '撤销举报内容', $id, '',''); //删除
            $msgNo = 'Y';
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        } else {
            $msgNo = 1;
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        }
    }


    /*
     * 查看举报内容
     */
    public function actionMember_reported_view(){
        $id = Yii::app()->request->getParam( 'id' );
        $id = is_numeric($id) ? intval($id) : 0;
        $question_id = '';
        $memberReportedObj = MemberReported::model()->findByPk($id);
        if(empty($memberReportedObj)){
            $msgNo = 1;
            $msg = $this->msg[$msgNo];
            echo '未找到举报内容';
            exit;
        }
        if($memberReportedObj->type == 1){
            $question_id = $memberReportedObj->reported_id;
        } elseif($memberReportedObj->type == 2){
            $answerId = !empty($memberReportedObj->reported_id) ? $memberReportedObj->reported_id : 0;
            $answerArr = Answer::model()->findByPk($answerId);
            $question_id = !empty($answerArr->question_id) ? $answerArr->question_id : 0;
        }
        $this->redirect(EHOME . "/index.php?r=question/question_show&question_id=" . $question_id);
    }

    public function actionDel_reported_info(){
        try{
            $id = Yii::app()->request->getParam( 'id' );
            $id = is_numeric($id) ? intval($id) : 0;
            $msgNo = 'Y';
            $memberReportedObj = MemberReported::model()->findByPk($id);
            if(empty($memberReportedObj)){
                throw new Exception(9);
            }      
            if($memberReportedObj->type == 1){
                $question_id = $memberReportedObj->reported_id;
                if(empty($question_id)){
                    throw new Exception(7);
                }
                $flag = Question::model()->delQuestion($question_id);
                if($flag['data']){
                    //举报奖励惩罚积分操作
                    FwUtility::backreport(CreditLog::$creditQuestion,CreditLog::$typeKey[18],CreditLog::$typeKey[19], $question_id,$memberReportedObj->content,$memberReportedObj->member_user_id,$memberReportedObj->reported_user_id,$memberReportedObj->type);                       
                    OperationLog::addLog(OperationLog::$operationQuestion, 'del', '删除问题', $question_id, '',''); //删除
                } else {
                    throw new Exception(1);
                }
            } elseif($memberReportedObj->type == 2){
                $answerId = $memberReportedObj->reported_id;
                if(empty($answerId)){
                    throw new Exception(8);
                }
                //举报奖励惩罚积分操作
                FwUtility::backreport(CreditLog::$creditQuestion,CreditLog::$typeKey[20],CreditLog::$typeKey[21], $answerId,$memberReportedObj->content,$memberReportedObj->member_user_id,$memberReportedObj->reported_user_id,$memberReportedObj->type);                 
                $data = Answer::model()->delAnswer($answerId);
                if($data['data']){                
                    $msgNo = 'Y';
                } else {
                    throw new Exception(1);
                }
            }
            //补充举报加分和被举报减分
            $memberReportedObj->_delete = 1;
            $memberReportedObj->save();
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);

    }

    private function _get_search_arr()
    {
        $start_time = trim(Yii::app()->request->getParam('start_time'));
        $end_time = trim(Yii::app()->request->getParam('end_time'));
        $province_id = intval(Yii::app()->request->getParam('province'));
        $category_id = intval(Yii::app()->request->getParam('category_id'));
        $title = trim(Yii::app()->request->getParam('title'));
        $limit      = trim(Yii::app()->request->getParam( 'length' ));
        $page       = trim(Yii::app()->request->getParam('start'));
        $index      = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : '_create_time';
        $page       = !empty($page) ? $page : 0;
        $limit      = !empty($limit) ? $limit : 20;

        if (!empty($start_time) && !empty($end_time)) {
            $end_time = date("Y-m-d", strtotime("$end_time   +1   day"));
            $con['_create_time'] = array('between', $start_time, $end_time);
        }
        if (!empty($title)) {
            $con['title'] = array('search_like', $title);
        }

        if($province_id != QG_BRANCH_ID){
            $con['left(area_code,2)'] = $province_id;
        }
        if(!empty($category_id)){
             $con['category_id'] = $category_id;
        }
        if(!empty($status)){
            $con['status'] = $status;
        }
        $con['is_valid'] = 1;
        $con['_deleted'] =0;
        return array($con, $ord, $field, $limit, $page);
    }


    //数据统计及看板相关方法
//    public function actionTest(){
//        QuestionFirstAnswerAnalysis::generate_data_everyday();
//    }

    public function actionFirst_answer_index(){
        $this->render('analysis/first_answer_index');
    }

    public function actionGet_first_answer(){
        $con = array();
        $start_time = trim(Yii::app()->request->getParam( 'start_time' ));
        $end_time = trim(Yii::app()->request->getParam( 'end_time' ));
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('start'));
        $is_export = trim(Yii::app()->request->getParam('is_export'));
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $order = 'analysis_date desc';
        if(!empty($start_time) && !empty($end_time)){
            $con['analysis_date'] = array('between', $start_time, $end_time);
        }
        if($is_export == 'true'){
            $header = array('日期','提问数量','5分钟内响应数量','15分钟内响应数量','30分钟内响应数量','1小时内响应数量',
                '24小时内响应数量','48小时内响应数量','72小时内响应数量','72小时无响应数量','响应平均时长');
            $data = array();
            $ret = QuestionFirstAnswerAnalysis::model()->get_list($con,$order);
            foreach($ret['data'] as $k=>$v){
                $data[]= array($v['analysis_date'], $v['question_count'], $v['minute_5_answer'], $v['minute_15_answer'],
                    $v['minute_30_answer'], $v['hour_1_answer'], $v['hour_24_answer'], $v['hour_48_answer'],
                    $v['hour_72_answer'], $v['hour_72_no_answer'], $v['average_answer_time']);
            }
            FwUtility::exportExcel($data, $header, '响应时长统计','响应时长统计'.date('Ymd'));
        }else{
            $ret = QuestionFirstAnswerAnalysis::model()->get_list($con,$order,$limit,$page);
            echo CJSON::encode($ret);
        }
    }


    public function actionQuestion_operate_index(){
        $this->render('analysis/question_operate_index');
    }

    public function actionGet_question_operate(){
        $con = array();
        $start_time = trim(Yii::app()->request->getParam( 'start_time' ));
        $end_time = trim(Yii::app()->request->getParam( 'end_time' ));
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('start'));
        $is_export = trim(Yii::app()->request->getParam('is_export'));
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $order = 'analysis_date desc';
        if(!empty($start_time) && !empty($end_time)){
            $con['analysis_date'] = array('between', $start_time, $end_time);
        }
        if($is_export == 'true'){
            $header = array('日期','搜索问题次数','提问次数','答题次数','查看次数','采纳数',
                '点赞数','0回复数','搜问比','采纳率');
            $data = array();
            $ret = QuestionOperateAnalysis::model()->get_list($con,$order);
            foreach($ret['data'] as $k=>$v){
                $data[]= array($v['analysis_date'], $v['search_count'], $v['question_count'], $v['answer_count'],
                    $v['show_question_count'], $v['adopt_count'], $v['upcount'], $v['no_answer_count'],
                    $v['search_question_ratio'], $v['adopt_ratio']);
            }
            FwUtility::exportExcel($data, $header, '站内搜问比统计','站内搜问比统计'.date('Ymd'));
        }else{
            $ret = QuestionOperateAnalysis::model()->get_list($con,$order,$limit,$page);
            echo CJSON::encode($ret);
        }
    }

    public function actionQuestion_m_level_analysis_index(){
        $this->render('analysis/question_m_level_analysis_index');
    }

    public function actionQuestion_r_level_analysis_index(){
        $this->render('analysis/question_r_level_analysis_index');
    }

    public function actionQuestion_user_index(){
        $this->render('analysis/question_user_index');
    }


    public function actionGet_question_user(){
        $con = array();
        $start_time = trim(Yii::app()->request->getParam( 'start_time' ));
        $end_time = trim(Yii::app()->request->getParam( 'end_time' ));
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('start'));
        $is_export = trim(Yii::app()->request->getParam('is_export'));
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        if(!empty($start_time) && !empty($end_time)){
            $con['_create_time'] = array('between', $start_time, $end_time);
        }
        if($is_export == 'true'){
            $header = array('ID','账户昵称','用户名','积分','手机号','邮箱','注册新干线时间','注册时长','登陆天数',
                '搜索次数','提问次数','回答次数','最近一次进入答疑解惑时间','R级别','M级别');
            $data = array();
            $ret = QuestionUser::model()->get_list($con);
            foreach($ret['data'] as $k=>$v){
                $data[]= array($v["member_user_id"],$v["Nick"],$v["UserName"],$v["credits"],$v["sMobile"],$v["email"],
                    $v["nRegisterTime"],$v["register_day_count"],$v["onlinetime"],$v["search_count"],$v["question_count"],
                    $v["answer_count"],$v["last_operate_time"],$v["r_level"],$v["m_level"]);
            }
            FwUtility::exportExcel($data, $header, '答疑解惑用户','答疑解惑用户'.date('Ymd'));
        }else{
            $ret = QuestionUser::model()->get_list($con,'',$limit,$page);
            echo CJSON::encode($ret);
        }
    }

    public function actionGet_question_user_level_count(){
        $start_time = trim(Yii::app()->request->getParam( 'start_time' ));
        $end_time = trim(Yii::app()->request->getParam( 'end_time' ));
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('start'));
        $is_export = trim(Yii::app()->request->getParam('is_export'));
        $level_type = trim(Yii::app()->request->getParam('level_type'));
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $level_type = !empty($level_type) ? $level_type : 1;
        $order = 'analysis_month desc';
        $con = array('level_type' => $level_type);
        if(!empty($start_time) && !empty($end_time)){
            $con['analysis_month'] = array('between', date('Y-m-d',strtotime($start_time)), date('Y-m-31',strtotime($end_time)));
        }
        if($is_export == 'true'){
            $data = array();
            $ret = QuestionUserLevelAnalysis::model()->get_list($con,$order);
            foreach($ret['data'] as $k=>$v){
                $data[]= array($v['analysis_month'], $v['level_1_count'], $v['level_2_count'], $v['level_3_count'],
                    $v['level_4_count'], $v['level_5_count']);
            }
            if($level_type == 1){
                $header = array('月份','M1','M2','M3','M4','M5');
                FwUtility::exportExcel($data, $header, 'M级用户统计','M级用户统计'.date('Ymd'));
            }else{
                $header = array('月份','R1','R2','R3','R4','R5');
                FwUtility::exportExcel($data, $header, 'R级用户统计','R级用户统计'.date('Ymd'));
            }
        }else{
            $ret = QuestionUserLevelAnalysis::model()->get_list($con,$order,$limit,$page);
            echo CJSON::encode($ret);
        }
    }

    public function actionBoard_index(){
        $board_data = QuestionOperateAnalysis::model()->get_board_data();
        if(empty($board_data)){
            echo '获取看板数据失败';
        }else{
            $this->render('analysis/board',array('board_data' => $board_data));
        }
    }


}