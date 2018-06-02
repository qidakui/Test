<?php
use application\models\Training\Training;
use application\models\Training\TrainingExtension;
use application\models\Activity\ActivityCollect;
use application\models\Activity\ActivityCz;
use application\models\Activity\ActivityShare;
use application\models\Activity\ActivityComment;
use application\models\Activity\ActivityLunbotu;
use application\models\Training\TrainingParticipate;
use application\models\Training\TrainingParticipateSigninLog;
use application\models\Activity\ActivitySetDrawLots;
use application\models\Training\TrainingLecturer;
use application\models\Training\TrainingDemandFeedback;
use application\models\Member\CommonMember;
use application\models\Admin\AdminRole;
use application\models\ServiceRegion;
use application\models\SmsTask;
use application\models\Meetingmodel\Selectmodel;
use application\models\Meetingmodel\Softname;
use application\models\Training\GenseeApi;
use application\models\Research\Research;
use application\models\Training\TrainingGenseePlan;
class TrainingController extends Controller
{

    private $msg = array(
            'Y' => '成功',
            1 => '操作数据库错误',
            2 => '数据已经存在',
            3 => '数据不存在',
            4 => '不可为空',
            5 => '不可过长',
            6 => '本地环境无法发送短信',
            1001 => '手机号错误',
            1002 => '邮箱错误',
            1003 => '管理员不能为空',
            1004 => '密码不能为空',
            1005 => '请选择培训方式',
            1006 => '参数错误',
            1007 => '参加人数不能为0',
            1008 => '标题不能为空',
            1009 => '开始时间不能为空',
            1010 => '结束时间不能为空',
            1011 => '培训图片不能为空',
            1012 => '详细地址不能为空',
            1013 => '详细内容不能为空',
            1014 => '图片上传错误，请重试',
            1015 => '上传文件过大',
            1016 => '地区不能不选',
            1017 => '此手机号已存在',
            1018 => '没有文件被上传',
            1019 => '图片格式不支持',
            1020 => '图片宽高过小，请上传合适宽高的图片',
            1021 => '培训已结束',
            1022 => '此活动已被管理员删除，请刷新页面重试',
            1023 => '此活动尚未完善，请先补充活动内容再进行复制',
            1024 => '所选人员均不符合签到条件，请重新选择',
            1025 => '签到时间不可超出今天',
			1026 => '短信接口请求失败,请联系管理员',
            1027 => '签到时间不可晚于今天',
            1028 => '签到时间不可早于报名时间',
            1029 => '讲师账号非本系统用户',
            1030 => '此讲师账号已添加过，不可重复添加',
            1031 => '请正确选择培训时间',
            1032 => '视频地址不可为空',
            1033 => '视频地址必须以http:// 或者 https://开头',
            1034 => '发送失败，只能向状态为“审核中”的学员发送邀请函',
            1035 => '培训已结束',
            1036 => '回放开始时间不得小于当前时间',
            1037 => '人数限制不得低于已经报名的人数',
			1038 => '当前培训存在尚未发送完成的短信任务，请稍后再编辑',
            1039 => '请先到第三步设置邀请函并上传邀请函模板',
            1040 => '发送邀请函必须设置具体的起始时间',
            1041 => '发送邀请函必须填写详细地址',
            1042 => '请填写自定义直播平台',
            1043 => '此培训非正常状态，无法取消',
            1044 => '此培训已结束，无法取消',
            1045 => '此培训已开始，无法取消',
            1046 => '培训开始开始时间不可晚于当前时间',
            1047 => '当前分支已存在相同排序位',
            1048 => '学员已经取消，不可重复取消，请重新勾选',
            1049 => '大括号“{}”及其中内容不可修改',
            1050 => '开始时间已过，不可再修改配置',
            1051 => '请按要求上传邀请函模板图片',
            1052 => '此处报名仅限于线下培训',
            1053 => '培训开始时间已过，不可报名',
            1054 => '当前培训已被取消，不可报名',
            1055 => '当前培训处于非正常状态，不可增加报名',
            1056 => '单位名称不可多于50个中文',
            1057 => '课程发布成功，新干线大讲堂创建失败，可到列表页重新创建新干线大讲堂',
            1058 => '不存在回放课件',
            1059 => '未创建新干线大讲堂课程',
            1060 => '软件名称不可为空',
            1061 => '培训类别不可为空',
            1062 => '培训类型不可为空',
            1063 => '超过最大人数限制',
    );

    //培训方式
    public $training_way = array(
        0 => '线下培训+网络直播',
        1 => '线下培训',
        2 => '网络直播'
    );
    //定义收集信息项
    public $shoujixnxi = array(
        'realname' => '真实姓名',
        'mobile' => '手机号码',
        'advice' => '培训内容建议',
        'qq' => 'QQ号码',
        'email' => '邮箱',
        'dongle' => '加密锁号',
        'company' => '单位全称',
        'company_type' => '单位性质',
        'position' => '职位',
        'major' => '专业',
        'work_num' => '从业年限',	
        'text1' => '',
        'text2' => '',
        'text3' => '',
        'text4' => '',
        'text5' => ''			
    );



    //定义报名花销
    public $baominghuaxiao = array(
            0 => '无需花销',
            1 => '广币',
            2 => '积分',
            3 => '经验值',
            4 => '人民币'
    );

    public $user_id;
    public $user_name;
    public $branch_id;
    public $filiale_id;

    public function init(){
        parent::init();
        $this->user_id = Yii::app()->user->user_id;
        $this->user_name = Yii::app()->user->user_name;
        $this->filiale_id = Yii::app()->user->branch_id;
    }

    //列表
    public function actionIndex(){
        if(!isset($_GET['iDisplayLength'])){
            //GenseeApi::model()->getHistory();
            $getBranchList = ServiceRegion::model()->getCityList();
            $cityList = array();
            if($this->filiale_id!=BRANCH_ID){
                $provinceId = ServiceRegion::model()->getBranchToCity($this->filiale_id);
                $provinceId = isset($provinceId[0]['region_id']) ? $provinceId[0]['region_id'] : 0;
                $cityList = ServiceRegion::model()->getCityByProvince($provinceId);
            }
            //判断是否为超级管理员
            $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id'=>$this->user_id));
            $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
            $role_id = $adminrole['role_id'];
            $this->render('list',array('getBranchList'=>$getBranchList,'cityList'=>$cityList, 'role_id'=>$role_id, ));exit;
        }

        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); //分支id前两位
        $city_code = intval(Yii::app()->request->getParam( 'city_code' )); 
        $search_type = trim(Yii::app()->request->getParam( 'search_type' ));
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con = array('status!'=>0);
        if($starttime){
            $con['starttime>'] = $starttime;
            $con['starttime<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }

        if($this->filiale_id==BRANCH_ID){
            if($province_code){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
        if($city_code){
            $con['city_code'] = $city_code;
        }
        if($search_type==='title'){
            $con['title'] = $search_content;
        }elseif($search_type==='id'){
            $con['id'] = intval($search_content);
        }elseif($search_type==='num'){
            $con['num'] = $search_content;
        }elseif($search_type==='lecturer'){ //九月
            $con['lecturer'] = $search_content;
        }
		if($search_content=='#最近操作#'){
			$con = [];
			$user_id = 'training_'.Yii::app()->user->user_id;
			$happenlately = new ARedisHash("happenlately");
			$con['id'] = isset($happenlately->data[$user_id]) ? intval($happenlately->data[$user_id]) : 0;
		}
        $list = Training::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    
    //导出培训列表
    public function actionTraining_Excel(){
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); //分支id前两位
        $city_code = intval(Yii::app()->request->getParam( 'city_code' )); 
        $search_type = trim(Yii::app()->request->getParam( 'search_type' ));
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con = array('status!'=>0);
        if($starttime && $endtime){
            $con['starttime>'] = $starttime;
            $con['starttime<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        
        if($this->filiale_id==BRANCH_ID){
            if($province_code){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
        if($city_code){
            $con['city_code'] = $city_code;
        }
        
        if($search_type==='title'){
            $con['title'] = $search_content;
        }elseif($search_type==='id'){
            $con['id'] = intval($search_content);
        }elseif($search_type==='num'){
            $con['num'] = $search_content;
        }elseif($search_type==='lecturer'){ //九月
            $con['lecturer'] = $search_content;
        }

        $list = Training::model()->getlist($con, 'desc', 'id', 50000, 0);
        if(empty($list['data'])){
            exit('没有符合条件的数据');
        }
        $softname_list = Softname::model()->getSoftKeyVal();
        $data = array();
        foreach($list['data'] as $k=>$v){
           $tmp['id'] = $v['id'];
           $tmp['num'] = $v['num'];
           $tmp['filiale_name'] = $v['filiale_name'];
           $tmp['title'] = $v['title'];
           $tmp['category_id'] = isset(Yii::app()->params['training_category'][$v['category_id']]) ? Yii::app()->params['training_category'][$v['category_id']] : $v['category_id'];
           $tmp['way'] = $this->training_way[$v['way']];
           $tmp['video_form'] = isset(Yii::app()->params['training_video_platform'][$v['video_form']]) ? Yii::app()->params['training_video_platform'][$v['video_form']] : $v['video_form'];
           $tmp['training_time'] = $v['starttime'].' 至 '.$v['endtime'];
           $tmp['alladdress'] = $v['alladdress'];
           $tmp['software'] = is_numeric($v['software']) && isset($softname_list[$v['software']]) ? $softname_list[$v['software']] : $v['software'];
           $tmp['lecturer'] = $v['lecturer'];
           $tmp['lecturer_account'] = $v['lecturer_account'];
           $tmp['user_name'] = $v['user_name'];
           $tmp['training_head'] = $v['training_head'];
           $tmp['training_head_tel'] = $v['training_head_tel'];
           $tmp['limit_number'] = $v['limit_number'];
           $tmp['cost'] = strpos($v['cost'],'<br>') ? str_replace('<br>', ' ; ', $v['cost']) : $v['cost'];
           $tmp['venue_pnum'] = $v['venue_pnum'];
           $tmp['venue_cost'] = $v['venue_cost'];
           $tmp['venue_head'] = $v['venue_head'];
           $tmp['venue_head_tel'] = $v['venue_head_tel'];
           $tmp['note'] = $v['note'];
           $tmp['_create_time'] = $v['_create_time'];
           $tmp['status_txt'] = $v['status_txt'];
           $tmp['浏览量'] = $v['views']; 
           $tmp['评论量'] = ActivityComment::model()->getCount(array('activity_id'=>$v['id'], 'status'=>2) );
           $tmp['转发量'] = ActivityShare::model()->getCount(array('activity_id'=>$v['id'], 'source'=>1) );
           $tmp['搜藏量'] = ActivityCollect::model()->getCount(array('activity_id'=>$v['id'], 'source'=>1) );
           $tmp['点赞量'] = ActivityCz::model()->getCount( array('czid'=>$v['id'], 'type'=>3) );
           $tmp['评分人数'] = ActivityComment::model()->getCount(array('activity_id'=>$v['id'], 'score!'=>0, 'status'=>2) );
           $tmp['当前分值'] = ActivityComment::model()->getScore($v['id']);
           $tmp['打赏人数'] = 0;
           $tmp['打赏总积分'] = 0;
           $tmp['报名人数'] = TrainingParticipate::model()->getRequirementCount( array('training_id'=>$v['id'], 'status'=>1) );
           $tmp['听课人数'] = TrainingParticipateSigninLog::model()->getListenNum( array('training_id'=>$v['id']) ); 
           $tmp['抽奖次数'] = TrainingParticipate::model()->getRequirementCount( array('training_id'=>$v['id'], 'status'=>1, 'is_prize_winning'=>1) );
           $tmp['报道图片量'] = $v['report_img_num'];
           $tmp['视频量'] = $v['report_video_num'];
           
           $data[] = $tmp;
        }

        $headerstr = 'ID,培训编号,分支,培训名称,培训类型,培训方式,直播渠道,培训时间,培训地点,软件名称,讲师,讲师广联达账号,创建人,负责人,负责人电话,线下培训限定人数,线下培训报名费用,场地容纳人数,场地费用,场地联系人,联系人电话,备注,创建时间,状态,浏览量,评论量,转发量,收藏量,点赞量,评分人数,当前分值,打赏人数,打赏总积分,报名人数,听课人数,抽奖次数,报道图片量,视频量';
        $header = explode(',',$headerstr);
       //print_r($data);die;
        FwUtility::exportExcel($data, $header,'培训明细','培训列表_'.date('Y-m-d'));
    }
    //新建
    public function actionAdd(){
        $getCityList = ServiceRegion::model()->getCityList();
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $region_name = '';
        $region_id = '';
        if( $this->filiale_id!=BRANCH_ID ){
            $filiale_arr = ServiceRegion::model()->getBranchToCity($this->filiale_id);
            $region_id = $filiale_arr[0]->region_id;
            $region_name = $filiale_arr[0]->region_name;
        }
        
        $softCon = array();
        if($id){
            $data = Training::model()->getTraining($id);
			$is_copy = $id && $data['starttime']==0 && $data['endtime']==0;
			if(!$is_copy){
				$softCon['id'] = $data['software'];
			}
            $views = $data['way']==0 ? 'add1' : 'add1_new';
        }else{
            $data = array('id' => 0, 'num' => '', 'filiale_id' => '', 'training_type'=>'','custom_video_form'=>'', 'is_online_send_sms'=>0 , 'apply_province_code' => '', 'apply_city_code' => '', 'category_id' => '', 'province_code' => '', 'city_code' => '', 'address' => '', 'title' => '', 'image' => '', 'starttime' => '', 'endtime' => '', 'tosign_endtime'=>'', 'is_show_week' => '', 'lecturer' => '', 'way' => 1, 'software' => '', 'limit_number' => '', 'online_cost' => '', 'offline_cost' => '', 'note' => '', '_create_time' => '', '_update_time' => '', 'status' => '', 'user_id' => '', 'user_name' => '', 'video_form' => '', 'video_url' => '', 'lecturer_id'=>0, 'lecturer_account' => '', 'venue_head' => '', 'venue_head_tel' => '', 'venue_cost' => '', 'venue_pnum' => '', 'training_head' => '', 'training_head_tel' => '', 'requirement' => '', 'isset_invitation' => '', 'invitation_image' => '', 'invitation_note' => '', 'qr_code_image' => '', 'content' => '', 'reports' => '' , 'is_show_list'=>1);
            $views = 'add1_new';
        }
        $softnamelist = Softname::model()->getList($softCon, 'desc', 'sort', 500);
        $softnamelist = isset($softnamelist['data']) && !empty($softnamelist['data']) ? $softnamelist['data'] : array();
        
        $this->render($views,
            array(
                'getCityList'=>$getCityList, 
                'region_id'=>$region_id, 
                'region_name'=>$region_name, 
                'softnamelist' => $softnamelist,
                'id'=>$id, 
                'data'=>$data));
    }
    
    //保存基信息
    public function actionAdd_Op(){
        $msgNo = 0;
        $data = array();
        $extdata = array();
        try{
            $data['status'] = 2; //未发布状态
            $extdata['status'] = 2; 
            $data['title']		= trim(Yii::app()->request->getParam( 'title' )); //标题
            $data['category_id'] = Yii::app()->request->getParam( 'category_id' );//类别
            if(!is_numeric($data['category_id'])){
                throw new Exception('1061');
            }
            $data['training_type'] = intval(Yii::app()->request->getParam( 'training_type' )); 
            if(empty($data['training_type'])){
                throw new Exception('1062');
            }
            //软件名称id
            $data['software'] = trim(Yii::app()->request->getParam( 'software' )); 
            if(is_numeric($data['software'])){
                $Selectmodel = Selectmodel::model()->getSoftIdToModel($data['category_id'], $data['software']);
				if(isset($Selectmodel['logo']) && !empty($Selectmodel['logo'])){
                    $data['logo_image'] = $Selectmodel['logo'];
                }
                if(isset($Selectmodel['content']) && !empty($Selectmodel['content'])){
                    $extdata['content'] = CHtml::decode($Selectmodel['content']);
                }
            }else{
               throw new Exception('1060');
            }
            //培训方式
            $way = intval(Yii::app()->request->getParam( 'training_way' )); 
            if( !in_array($way,array(1,2)) ){
                throw new Exception('1005');
            }
            $data['province_code'] = intval(Yii::app()->request->getParam( 'province_code' ));
            $data['city_code'] = intval(Yii::app()->request->getParam( 'city_code' ));
            $data['address'] = trim(Yii::app()->request->getParam( 'address' ));
            
            $data['way'] = $way;
            if($way===2){  
                $extdata['video_url'] = trim(Yii::app()->request->getParam( 'video_url' )); //视频url
                $extdata['video_form'] = intval(Yii::app()->request->getParam( 'video_form' ));//视频来源
                if( $extdata['video_form']==0 ){
                    $extdata['custom_video_form'] = trim(Yii::app()->request->getParam( 'custom_video_form' ));
                    if( empty($extdata['custom_video_form']) ){
                        throw new Exception('1042');//请填写自定义直播平台
                    }
                }
                //适用范围
                $data['apply_province_code'] = intval(Yii::app()->request->getParam( 'apply_province_code' ));
                $data['apply_city_code'] = intval(Yii::app()->request->getParam( 'apply_city_code' ));
                
                if( !empty($extdata['video_url']) ){
                    if( strpos($extdata['video_url'], 'http://')!==0 && strpos($extdata['video_url'], 'https://')!==0 ){
                        throw new Exception('1033'); //视频地址必须以http:// 或者 https://开头
                    }
                }
            }
            
            //时间
            $starttime	= trim(Yii::app()->request->getParam( 'starttime' ));
            $endtime	= trim(Yii::app()->request->getParam( 'endtime' ));
            if( !empty($starttime) && !empty($endtime) ){
                $data['starttime']	= trim(Yii::app()->request->getParam( 'starttime' ));
                $data['endtime']	= trim(Yii::app()->request->getParam( 'endtime' ));
            }else{
                throw new Exception('1031');
            }

			//开始就不可晚于当前时间
			if( $data['starttime'] <= date('Y-m-d H:i:s') ){
				throw new Exception('1046');
			}

            //培训地点
            if($way===1){  
                $extdata['venue_head'] = trim(Yii::app()->request->getParam( 'venue_head' ));  // 场地负责人
                $extdata['venue_head_tel'] = trim(Yii::app()->request->getParam( 'venue_head_tel' ));   //场地负责人电话
                $extdata['venue_cost'] = trim(Yii::app()->request->getParam( 'venue_cost' ));   //场地费用
                $extdata['venue_pnum'] = intval(Yii::app()->request->getParam( 'venue_pnum' )); //容纳人数
                $data['tosign_endtime'] = $data['starttime']; //报名截止时间就是培训开始时间
                if( !empty($starttime) && empty($data['address'])){
                    throw new Exception('1012');
                }
            }
            
            if( $way===1 ){ //线下培训适用地等于举办地
                $data['apply_province_code'] = $data['province_code'];
                $data['apply_city_code'] = $data['city_code'];
            }elseif( $way===2 ){
                $data['province_code'] = $data['apply_province_code'];
                $data['city_code'] = $data['apply_city_code'];
				$data['tosign_endtime'] = $data['endtime']; //报名截止时间就是培训结束时间
            }

            //封面图片
            $data['image'] = trim(Yii::app()->request->getParam( 'image_path' )); 
            if( strstr(strtolower(php_uname('s')), 'windows') ){
                $data['image'] = '/uploads/2017/12/20171226144723_838.png';
            }
            if( empty($data['image']) ){
                throw new Exception('1011');
            }
            //培训讲师
            $data['lecturer'] = trim(Yii::app()->request->getParam( 'lecturer' )); 
            //讲师账号
            $extdata['lecturer_account'] = trim(Yii::app()->request->getParam( 'lecturer_account' )); 
            $extdata['lecturer_id'] = intval(Yii::app()->request->getParam( 'lecturer_id' )); 
                
            //创建人uid
            $extdata['user_id'] = $this->user_id;
            //创建人姓名
            $extdata['user_name'] = trim(Yii::app()->request->getParam( 'user_name' )); 
            //培训负责人
            $extdata['training_head'] = trim(Yii::app()->request->getParam( 'training_head' )); 
            //联系电话
            $extdata['training_head_tel'] = trim(Yii::app()->request->getParam( 'training_head_tel' )); 
            //人数限制
            $data['limit_number'] = trim(Yii::app()->request->getParam( 'limit_number' ));
            
            //备注
            $data['note'] = trim(Yii::app()->request->getParam( 'note' )); 
            $data['filiale_id'] = $this->filiale_id;
            //配置默认短信
            if($way==1 && !empty($starttime) && !empty($endtime) ){
                $extdata['isset_tosign_sms'] = 1;
                $extdata['start_before_hour'] = 24;
            }
            
            //是否在前台显示
            $data['is_show_list'] = Yii::app()->request->getParam( 'is_show_list' ) ? 0 : 1; 
            //大讲堂验证允许人数
            if($way==2 && $extdata['video_form']==3){
                $GenseePlan_total = TrainingGenseePlan::model()->getResidual($starttime, $endtime);
                if( $data['limit_number'] > $GenseePlan_total ){
                    throw new Exception(1063);
                }
            }
            
            $ins_id = Training::model()->trainingSave($data, $extdata);
            if($ins_id){
                //初始化短信配置模板
                if( isset($extdata['isset_tosign_sms']) ){
                    SmsTask::model()->taskSaveDefault('training', $ins_id);
                }
                $msgNo = 'Y';
                echo $this->encode($msgNo, $ins_id);die;
            } else {
                throw new Exception('1');
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    //修改基本信息
    public function actionEdit_Op(){
        $msgNo = 0;
        $data = array();
        $extdata = array();
        try{
			$id = intval(Yii::app()->request->getParam( 'id' ));
            $training = Training::model()->findByPk($id);
            
            //是否已开始
            $is_beginning = false;
			//是否为新复制的  ,新复制的一定是未开始的
            $is_copy = $id && $training['starttime']==0 && $training['endtime']==0;
            if( !$is_copy ){
                $is_beginning = ($training['starttime'] < date('Y-m-d H:i:s')); 
            }
            
            $data['id'] = $id;
            $data['title']		= trim(Yii::app()->request->getParam( 'title' )); //标题
			if($is_copy){
				$data['category_id'] = Yii::app()->request->getParam( 'category_id' );//类别
				if(!is_numeric($data['category_id'])){
					throw new Exception('1061');
				}
				$data['training_type'] = intval(Yii::app()->request->getParam( 'training_type' )); 
				if(empty($data['training_type'])){
					throw new Exception('1062');
				}
				//软件名称id
				$data['software'] = trim(Yii::app()->request->getParam( 'software' )); 
				if(is_numeric($data['software'])){
					$Selectmodel = Selectmodel::model()->getSoftIdToModel($data['category_id'], $data['software']);
					if(isset($Selectmodel['logo']) && !empty($Selectmodel['logo'])){
						$data['logo_image'] = $Selectmodel['logo'];
					}
					if(isset($Selectmodel['content']) && !empty($Selectmodel['content'])){
						$extdata['content'] = CHtml::decode($Selectmodel['content']);
					}
				}else{
				   throw new Exception('1060');
				}
                //培训方式
                $way = intval(Yii::app()->request->getParam( 'training_way' )); 
                if( !in_array($way,array(1,2)) ){
                    throw new Exception('1005');
                }
            }else{
                $way = intval($training['way']);
            }
            
            if( !$is_beginning ){
                $data['province_code'] = intval(Yii::app()->request->getParam( 'province_code' ));
                $data['city_code'] = intval(Yii::app()->request->getParam( 'city_code' ));
                $data['address'] = trim(Yii::app()->request->getParam( 'address' ));
            }
            
            if($way===2 || $way===0){  
                $extdata['video_url'] = trim(Yii::app()->request->getParam( 'video_url' )); //视频url
                $extdata['video_form'] = intval(Yii::app()->request->getParam( 'video_form' ));//视频来源
                if( $extdata['video_form']==0 ){
                    $extdata['custom_video_form'] = trim(Yii::app()->request->getParam( 'custom_video_form' ));
                    if( empty($extdata['custom_video_form']) ){
                        throw new Exception('1042');//请填写自定义直播平台
                    }
                }
               
                //适用范围
                $data['apply_province_code'] = intval(Yii::app()->request->getParam( 'apply_province_code' ));
                $data['apply_city_code'] = intval(Yii::app()->request->getParam( 'apply_city_code' ));
                
                if( !empty($extdata['video_url']) ){
                    if( strpos($extdata['video_url'], 'http://')!==0 && strpos($extdata['video_url'], 'https://')!==0 ){
                        throw new Exception('1033'); //视频地址必须以http:// 或者 https://开头
                    }
                }

                if( $way===2 ){ //网络培训清空邀请函
                    $extdata['isset_invitation'] = 0;
                    $extdata['invitation_image'] = '';
                }
            }
            
            $starttime	= trim(Yii::app()->request->getParam( 'starttime' ));
            $endtime	= trim(Yii::app()->request->getParam( 'endtime' ));

            //时间
            if( !$is_beginning ){
                if( !empty($starttime) && !empty($endtime) ){
                    $data['starttime']	= $starttime;
                    $data['endtime']	= $endtime;
                    $extdata['sms_status'] = 0;
                    
                    //确定型 修改了时间或地点(时间修改超过30分钟)  发短信
                    $isedit_starttime = (abs(strtotime($training['starttime']) - strtotime($data['starttime'])) < 1800);
                    $isedit_endtime = (abs(strtotime($training['endtime']) - strtotime($data['endtime'])) < 1800);
                    if( $way==2 ){
                        $isedit_province_code = $isedit_city_code = $isedit_address = true;
                    }else{
                        $isedit_province_code = $data['province_code']==intval($training['province_code']);
                        $isedit_city_code = $data['city_code']==intval($training['city_code']);
                        $isedit_address = $data['address']==$training['address'];
                    }
                    $isedit = ($isedit_starttime && $isedit_endtime && $isedit_province_code && $isedit_city_code && $isedit_city_code && $isedit_address);
					
                    if( !$isedit ){
                        //检查是否处于任务未完成状态
                        $TrainingExtension = TrainingExtension::model()->findByPk($id, array('select'=>array('sms_status')) );
                        if( $TrainingExtension['sms_status']!=0 && $TrainingExtension['sms_status']!=5 ){
                            throw new Exception('1038');
                        }
                    
                        $extdata['sms_status'] = $extdata['sms_status']==1 ? 1 : 2; //培训开始前仅更改了时间或地址
                        //保存老地址
                        $training['province_code'] = ServiceRegion::model()->getRegionName($training['province_code']);
                        $training['city_code'] = ServiceRegion::model()->getRegionName($training['city_code']);
                        $extdata['old_time_and_address'] = array(
                            'starttime' => $training['starttime'],
                            'endtime' => $training['endtime'],
                            'address' => $training['province_code'].$training['city_code'].$training['address']
                        );
                        $extdata['old_time_and_address'] = serialize($extdata['old_time_and_address']);
                    }else{
						unset($extdata['sms_status']);
					}
                    
                    //有人报名才产生任务
                    $yibaoming = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$id));
                    if( $yibaoming==0 ){
                        $extdata['sms_status'] = 0;
                    }
                    
                }else{
					throw new Exception('1031');
                }
                $data['tosign_endtime'] = $data['starttime']; //报名截止时间就是培训开始时间
                
				//开始就不可晚于当前时间
				if( $data['starttime'] <= date('Y-m-d H:i:s') ){
					throw new Exception('1046');
				}
            }

            //培训地点
            if($way===1 || $way===0){  
                $extdata['venue_head'] = trim(Yii::app()->request->getParam( 'venue_head' ));  // 场地负责人
                $extdata['venue_head_tel'] = trim(Yii::app()->request->getParam( 'venue_head_tel' ));   //场地负责人电话
                $extdata['venue_cost'] = trim(Yii::app()->request->getParam( 'venue_cost' ));   //场地费用
                $extdata['venue_pnum'] = intval(Yii::app()->request->getParam( 'venue_pnum' )); //容纳人数
                if( !$is_beginning && !empty($starttime) && empty($data['address'])){
                    throw new Exception('1012');
                }
                //修改开始时间时需判断是否存在未执行的定时短信任务 存在则要相应同步执行时间
                if($id){
                    if( !empty($starttime) && strtotime($training['starttime'])!=strtotime($starttime) ){
                        SmsTask::model()->upSmsTask_send_time('training', $id, $starttime);
                    }
                }
            }
            
            if( $way===1 ){ //线下培训适用地等于举办地
                if(isset($data['province_code'])){
                    $data['apply_province_code'] = $data['province_code'];
                    $data['apply_city_code'] = $data['city_code'];
                }
            }elseif( $way===2 ){
                $data['province_code'] = $data['apply_province_code'];
                $data['city_code'] = $data['apply_city_code'];
            }

            //软件名称id
            //$data['software'] = trim(Yii::app()->request->getParam( 'software' )); 
            //封面图片
            $data['image'] = trim(Yii::app()->request->getParam( 'image_path' )); 
            //培训讲师
            $data['lecturer'] = trim(Yii::app()->request->getParam( 'lecturer' )); 
            //讲师账号
            $extdata['lecturer_account'] = trim(Yii::app()->request->getParam( 'lecturer_account' ));
            $extdata['lecturer_id'] = intval(Yii::app()->request->getParam( 'lecturer_id' )); 

            //创建人姓名
            $extdata['user_name'] = trim(Yii::app()->request->getParam( 'user_name' )); 
            //培训负责人
            $extdata['training_head'] = trim(Yii::app()->request->getParam( 'training_head' )); 
            //联系电话
            $extdata['training_head_tel'] = trim(Yii::app()->request->getParam( 'training_head_tel' )); 
            //人数限制
            $data['limit_number'] = trim(Yii::app()->request->getParam( 'limit_number' )); 
            $data['way'] = $way;
            
            if(!$is_copy){
                $yibaoming = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$id, 'participate_way'=>1, 'status!'=>2));
                if( $yibaoming > intval($data['limit_number']) ){
                   throw new Exception('1037'); //人数限制不得低于已经报名的人数
                }
            }
            
            //备注
            $data['note'] = trim(Yii::app()->request->getParam( 'note' ));
            //新复制的改为确定型时配置默认短信
            if($is_copy && $way!=2 && !empty($starttime) && !empty($endtime) ){
                $extdata['isset_tosign_sms'] = 1;
                $extdata['start_before_hour'] = 24;
            }elseif( !empty($training['is_show_week']) && !empty($starttime) && !empty($endtime)  ){
                $extdata['isset_tosign_sms'] = 1;
                $extdata['start_before_hour'] = 24;
            }
 
            //是否在前台显示
            $data['is_show_list'] = Yii::app()->request->getParam( 'is_show_list' ) ? 0 : 1; 
            //大讲堂验证允许人数
            if($way==2 && $extdata['video_form']==3){
                $GenseePlan_total = TrainingGenseePlan::model()->getResidual($starttime, $endtime, $id); 
                if( $data['limit_number'] > $GenseePlan_total ){
                    throw new Exception(1063);
                }
            }
 
            $ins_id = Training::model()->trainingSave($data, $extdata);
            if($ins_id){
                //配置默认短信模板
                if( isset($extdata['isset_tosign_sms']) ){
                    SmsTask::model()->taskSaveDefault('training', $ins_id);
                }
                //修改大讲堂
                $code = GenseeApi::model()->updateGensee($id);
                //更新大讲堂计划表
                if( $way==2 && $extdata['video_form']==3 && $training['status']==1 && strtotime($training['endtime'])>time() && $training['cancel']==0 && (substr($training['starttime'],0,16)!=$starttime || substr($training['endtime'],0,16)!=$endtime || $training['limit_number']!=$data['limit_number']) ){
                    $training->starttime = $starttime;
                    $training->endtime = $endtime;
                    $training->limit_number = $data['limit_number'];
                    TrainingGenseePlan::model()->setPlan($training);
                }
                $msgNo = 'Y';
                echo $this->encode($msgNo, $ins_id);die;
            } else {
                throw new Exception('1');
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }

        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    //actionContent(); 保存详情方法
    
    //报名
    public function actionParticipate(){

        $id = intval(Yii::app()->request->getParam( 'id' ));
        
        if(isset($_POST['save'])){
            $save = Yii::app()->request->getParam( 'save' );
            $keybox = Yii::app()->request->getParam( 'keybox' );
            $is_check = Yii::app()->request->getParam( 'is_check' );
            $is_dongle = Yii::app()->request->getParam( 'is_dongle' );
			$is_dongle = $is_dongle ? 1 : 0;
            if( $save=='requirement_online' ){
                $requirement = array();
                $requirement_key = 'requirement_online';
            }else{
                $keybox['realname'] = 'on';
                $keybox['mobile'] = 'on';
                $requirement = array('realname'=>1,'mobile'=>1);
                $requirement_key = 'requirement';
            }
            if( empty($keybox) ){
                $requirement = array();
            }else{
                foreach($keybox as $k=>$v){
                    if(in_array($k, array('text1','text2','text3','text4','text5'))){
                        $requirement[$k] = $is_check[$k].'|'.$_POST[$k];
                    }else{
                        $requirement[$k] = $is_check[$k];
                    }
                }
            }
            $data['id'] = $id;
            $data['is_dongle'] = $is_dongle;
            $data[$requirement_key] = serialize($requirement);
            $id = TrainingExtension::model()->trainingExtensionSave($data);
            if($id){
                //OperationLog::addLog(OperationLog::$operationActivity, 'edit', '活动报名设置', $activity_id, array(), array());
                echo $this->encode('Y', $this->msg['Y']);
            }
            die;
        }else{
            $data = array();
            $training = Training::model()->findByPk($id,array('select'=>array('id','way')) )->attributes;
            
            $TrainingExtension = TrainingExtension::model()->findByPk($id,array('select'=>array('requirement', 'requirement_online','is_dongle')) )->attributes;
            $training['requirement'] = unserialize($TrainingExtension['requirement']);
            $training['requirement_online'] = unserialize($TrainingExtension['requirement_online']);
            $is_dongle = $TrainingExtension['is_dongle'];
        }
        if( $training['way']==0 ){
            $participate_way = Yii::app()->request->getParam( 'way' );
            $participate_way = empty($participate_way) ? 'add2_1' : $participate_way;
        }else{
            $participate_way = $training['way']==2 ? 'add2_0' : 'add2_1';
        }
        $this->render($participate_way, array('id'=>$id,'training'=>$training, 'is_dongle'=>$is_dongle ) );
    }
    
    //邀请函
    public function actionInvitation(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
		$training = TrainingExtension::model()->findbypk($id);
        if(!isset($_POST['save'])){
            $way = Training::model()->findByPk($id,array('select'=>array('id','way')) )->attributes;
            $this->render('add3', array('id'=>$id,'training'=>$training, 'way'=>$way['way']));
            exit;
        }
        
        try{
            if(isset($_POST['isset_invitation'])){ //是否设置邀请函
				if(empty($training['invitation_image'])){
					if( empty($_FILES) ){
						throw new Exception('3');
					}
				}
				if( !empty($_FILES) ){
					$key = array_keys($_FILES);
					$filevalue = $_FILES[$key[0]];
					if(intval($filevalue['error'])===1){
						throw new Exception('1015');
					}
					
					$size = $filevalue['size']/1024/1024;
					if($size>1){
						throw new Exception('1015');
					}
					$upload = new Upload();
					$image_path = $upload->uploadFile($key[0]); //image为上传框name
					$getErrorMsg = $upload->getErrorMsg();
					if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
						throw new Exception('1014');
					}
					
					//判断图片类型
					$imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
					$image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
					
					if($imagetype=='.jpg' || $imagetype=='.jpeg') {
						$model_im = @imagecreatefromjpeg($image_all_path);
					}elseif($imagetype=='.png') {
						$model_im = @imagecreatefrompng($image_all_path);
					}else{
						unlink($image_all_path);
						throw new Exception('1019');
					}
					if(!$model_im){
						unlink($image_all_path);
						throw new Exception('1019');
					}
					//标准邀请函模板宽高
					$new_img_w = 610;
					$new_img_h = 430;

					$model_im_width = imagesx($model_im); //模板图片宽度
					$model_im_height = imagesy($model_im); //高度
					if( $model_im_width<$new_img_w || $model_im_height<$new_img_h ){
						unlink($image_all_path);
						throw new Exception('1020');
					}elseif( $model_im_width>$new_img_w || $model_im_height>$new_img_h ){
						$new_img = imagecreatetruecolor($new_img_w, $new_img_h);
						//copy部分图像并调整
						imagecopyresampled( $new_img ,$model_im ,0 , 0 , 0 ,0 ,$new_img_w ,$new_img_h ,$model_im_width ,$model_im_height );
						if($imagetype=='.png') {
							imagepng($new_img, $image_all_path);
						}else{
							imagejpeg($new_img, $image_all_path);
						}
						imagedestroy($new_img);
					}
					
					$data['invitation_image'] = $image_path;
				}
				
				$data['isset_invitation'] = 1;
				$data['invitation_note'] = trim(Yii::app()->request->getParam( 'invitation_note' ));
				
            }else{
				//当前培训存在尚未发送完成的邀请函任务，无法取消设置
				if( $training['sms_status']!=0 && $training['sms_status']!=5 ){
					throw new Exception('1038');
				}
				$data['isset_invitation'] = 0;
            }
			$data['id'] = $id;
			$id = TrainingExtension::model()->trainingExtensionSave($data);
            if(!$id){
				throw new Exception('1');
			}

        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
        
    }
    
    /////设置邀请函 5月优化 start////////////////////////////////////////////////////////////////////////////////////
    public function actionSetInvitation(){
        $msgNo = 'Y';
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $activity = Training::model()->findByPk($activity_id)->attributes;

        $activity['all_address'] = ServiceRegion::model()->getRedisCityList($activity['province_code']);
        $activity['all_address'] .= ' '.ServiceRegion::model()->getRedisCityList($activity['city_code']);
        $activity['all_address'] .= ' '.$activity['address'];
        $TrainingExtension = TrainingExtension ::model()->findByPk($activity_id)->attributes;
        $activity['isset_invitation'] = $TrainingExtension['isset_invitation'];
        $activity['invitation_image'] = $TrainingExtension['invitation_image'];
        $activity['isset_tosign_sms'] = $TrainingExtension['isset_tosign_sms'];
        $activity['start_before_hour'] = $TrainingExtension['start_before_hour'];
        $activity['invitation_note'] = $TrainingExtension['invitation_note'];

        $taskdata = SmsTask::model()->get_list(array(
            'column_name'=>'training',
            'column_id' => $activity_id
        ));
        $default_sms = array(
                'tosign_sms' => array(
                    'id' => 0,
                    'sms_template' => '亲，{您的培训邀请码: '.$activity['num'].'-0000}，时间'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'。请您现场出示并签到，{邀请函详情点击：http://e.fwxgx.com/activity/0000} 。地址：'.$activity['all_address'],
                    'sms_template_not_i' => '亲，您报名的培训“'.$activity['title'].'”，'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'，'.$activity['all_address'].'，请准时参加。'
                ),
                'hour_sms' => array(
                    'id' => 0,
                    'sms_template' => '亲，{您的培训邀请码: '.$activity['num'].'-0000}，时间'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'。请您现场出示并签到，{邀请函详情点击：http://e.fwxgx.com/activity/0000} 。地址：'.$activity['all_address'],
                    'sms_template_not_i' => '亲，您报名的培训“'.$activity['title'].'”，'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'，'.$activity['all_address'].'，请准时参加。'
                )
            );
        $taskdata = empty($taskdata) ? $default_sms : $taskdata;
        $this->render('add3_new', 
            array(
                'id'=>$activity_id,
                'activity'=>$activity, 
                'taskdata'=>$taskdata,
                'default_sms'=>$default_sms));
    }
    /*
     * 保存邀请函设置
     */
    public function actionSetInvitationSave(){
        $msgNo = 'Y';
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $activity = Training::model()->findByPk($activity_id)->attributes;
        try {
            //活动已开始不可再修改
            if( $activity['starttime']<=date('Y-m-d H:i:s') ){
                throw new Exception(1050);
            }
            //是否设置邀请函
            $isset_yaoqinghan = Yii::app()->request->getParam( 'isset_yaoqinghan' ) ? 1 : 0;  
            //是否勾选 短信配置
            $isset_sms = Yii::app()->request->getParam( 'isset_sms' ) ? 1 : 0;
            //是否勾选 报名短信
            $isset_tosign_sms = Yii::app()->request->getParam( 'isset_tosign_sms' ) ? 1 : 0;
            //是否勾选 活动开始前xx小时
            $isset_hour_sms = Yii::app()->request->getParam( 'isset_hour_sms' ) ? 1 : 0;
            //报名短信模板
            $tosign_sms_template = trim(Yii::app()->request->getParam( 'tosign_sms_template' ));
            
            if($isset_yaoqinghan){
                //替换中文符号
                $tosign_sms_template = str_replace(array('｛','｝'), array('{','}'), $tosign_sms_template);
                if( substr_count($tosign_sms_template, '{您的培训邀请码: '.$activity['num'].'-0000}')!==1 ){
                    throw new Exception(1049);//大括号中内容不可修改
                }
                if( substr_count($tosign_sms_template, '{邀请函详情点击：http://e.fwxgx.com/activity/0000}')!==1 ){
                    throw new Exception(1049);
                }
                if( substr_count($tosign_sms_template, '-0000}')!==1 ){
                    throw new Exception(1049);
                }
                if( substr_count($tosign_sms_template, '/0000}')!==1 ){
                    throw new Exception(1049);
                }
            }
            $tosign_sms_id = intval(Yii::app()->request->getParam( 'tosign_sms_id' ));
            //xx小时数
            $start_before_hour = intval(Yii::app()->request->getParam( 'start_before_hour' )) ?  : 24;
            //xx小时发送短信模板
            $timing_sms_template = trim(Yii::app()->request->getParam( 'timing_sms_template' ));
            
            if($isset_yaoqinghan){
                $timing_sms_template = str_replace(array('｛','｝'), array('{','}'), $timing_sms_template);
                if( substr_count($timing_sms_template, '{您的培训邀请码: '.$activity['num'].'-0000}')!==1 ){
                    throw new Exception(1049);//大括号中内容不可修改
                }
                if( substr_count($timing_sms_template, '{邀请函详情点击：http://e.fwxgx.com/activity/0000}')!==1 ){
                    throw new Exception(1049);//大括号中内容不可修改
                }
                if( substr_count($timing_sms_template, '-0000}')!==1 ){
                    throw new Exception(1049);
                }
                if( substr_count($timing_sms_template, '/0000}')!==1 ){
                    throw new Exception(1049);
                }
            }
            $hour_sms_id = intval(Yii::app()->request->getParam( 'hour_sms_id' ));
            
            //邀请函备注
            $yaoqinghan_note = trim(Yii::app()->request->getParam( 'yaoqinghan_note' ));
            //邀请函模板图片
            $activity_image_path = trim(Yii::app()->request->getParam( 'activity_image_path' ));
            if(!empty($_FILES)){
				$key = array_keys($_FILES);
				$filevalue = $_FILES[$key[0]];
				if(intval($filevalue['error'])===1){
					throw new Exception('1015');
				}
				$size = $filevalue['size']/1024/1024;
				if($size>1){
					throw new Exception('1015');
				}
				$upload = new Upload();
                $path = '/uploads/invitation_template/'.$this->filiale_id.'/';
                $upload->set('path',$path);
				$image_path = $upload->uploadFile($key[0]);
				$getErrorMsg = $upload->getErrorMsg();
				if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
					throw new Exception('1014');
				}
                $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
                $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
                $new_img_w = 610;
                $new_img_h = 430;
                $upstatus = FwUtility::createSmallImage($image_path, $new_img_w,$new_img_h);
                if($upstatus!='Y'){
                    unlink($image_all_path);
					$msgNo = 1014;
					$this->msg[$msgNo] = $upstatus;
                    throw new Exception($msgNo);
                }
				$activity_image_path = $image_path;
			}
            if( $isset_yaoqinghan && empty($activity_image_path) ){
                throw new Exception(1051);
            }
            $data['id'] = $activity_id;
            $data['isset_invitation'] = $isset_yaoqinghan;
            $data['invitation_image'] = $activity_image_path;
            $data['isset_tosign_sms'] = $isset_tosign_sms;
            $data['start_before_hour'] = $isset_hour_sms ? $start_before_hour : 0;
            $data['invitation_note'] = $yaoqinghan_note;
            
            //插入短信任务表
            $activity_starttime = strtotime($activity['starttime']);
            $send_time = $activity_starttime - $start_before_hour*3600;
            //此时间不可小于当前时间的20分钟后
            if( $data['start_before_hour'] && $send_time-time() < 1200 ){
                $msgNo = 1;
                $this->msg[$msgNo] = '距培训开始时间 '.$activity['starttime'].' 不足'.$start_before_hour.'小时20分钟，系统已来不及提前 '.$start_before_hour.'h 发送。';
                throw new Exception($msgNo);
            }
            $up = TrainingExtension::model()->trainingExtensionSave($data);
            if(!$up){
                throw new Exception(1);
            }
            
            if($tosign_sms_id){
                $tData['id'] = $tosign_sms_id;
            }
            $tData['column_name'] = 'training';
            $tData['filiale_id'] = $activity['filiale_id'];
            $tData['column_id'] = $activity_id;
            $tData['describe'] = '培训报名即发短信模板';
            $tData['sms_template'] = $tosign_sms_template;
            $tData['_delete'] = $data['isset_tosign_sms']==0 ? 1 : 0;
            $save = SmsTask::model()->taskSave($tData);
            if(!$save){
                throw new Exception(1);
            }
            
            if($hour_sms_id){
                $tData['id'] = $hour_sms_id;
            }else{
                unset($tData['id']);
            }
            $tData['describe'] = '培训开始前'.$start_before_hour.'h发送短信模板';
            $tData['send_time'] = date('Y-m-d H:i:s',$send_time);
            $tData['sms_template'] = $timing_sms_template;
            $tData['is_crontab'] = 1;
            $tData['_delete'] = $data['start_before_hour']==0 ? 1 : 0;
            $save = SmsTask::model()->taskSave($tData);
            if(!$save){
                throw new Exception(1);
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    ///设置邀请函 5月优化 end//////////////////////////////////////////////////////////////////////
    
    
    
    //弹出邀请函模板
    public function actionShowYqhModel(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $activity = Training::model()->findByPk($activity_id);
        $activity = $activity->attributes;
        $activity['province_code'] = ServiceRegion::model()->getRegionName($activity['province_code']);
        $activity['city_code'] = ServiceRegion::model()->getRegionName($activity['city_code']);
        $activity['imgpath'] = Yii::app()->request->getParam( 'imgpath' );
        
        $this->render('showyqhmodel',array('activity'=>$activity));
    }
    
	//培训详情
	public function actionContent(){
		$msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $save = Yii::app()->request->getParam( 'save' );
		$bakobj = new ARedisHash('training_content_draft_bak_' . $id);
        $TrainingExtension = TrainingExtension::model()->findByPk($id, array('select'=>array('id','content','video_form')));
        $TrainingExtension = $TrainingExtension->attributes;
        if(empty($save)){
            //rdis暂存的内容
            $bak = $bakobj->data;
            if( !empty($bak) && !empty(trim($bak['data'])) ){
                $TrainingExtension['content'] = $bak['data'];
            }
            $this->render('add4', array('id'=>$id,'training'=>$TrainingExtension));
            exit;
        }
        
        $data['id'] = $id;
        $extendata['id'] = $id;
        $data['status'] = intval(Yii::app()->request->getParam( 'status' ));
        $extendata['status'] = $data['status'];     
        $extendata['content'] = trim(Yii::app()->request->getParam( 'content' ));
        try {
            if( $data['status']==1 ){
                $Training = Training::model()->findByPk($id);
                //检测时间是否设置 符合发布需求
                if( $Training['starttime']==0 || $Training['endtime']==0 ){
                    throw new Exception(1031);
                }
                //大讲堂验证允许人数
                if($Training['way']==2 && $TrainingExtension['video_form']==3){
                    $GenseePlan_total = TrainingGenseePlan::model()->getResidual($Training['starttime'], $Training['endtime'], $id);
                    if( $Training['limit_number'] > $GenseePlan_total ){
                        throw new Exception(1063);
                    }
                }
            }
            
            $ins = Training::model()->trainingSave($data, $extendata);
            if(!$ins){
                throw new Exception(1);
            }
            $bakobj->clear();
            //创建大课堂
            if( $data['status']==1 && $Training['way']==2 ){
                if( $Training['is_create_gensee']==0 ){
                    $code = GenseeApi::model()->createGensee($id);
                    if($code!=0){
                       throw new Exception(1057);
                    }
                }else{
                    //更新计划表状态
                    TrainingGenseePlan::model()->updatePlan($Training);
                }
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
        
	}
    
    //培训报道
    public function actionReports(){
		$msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $bakobj = new ARedisHash('training_reports_draft_bak_' . $id);
        if(!isset($_POST['save'])){
            $training = Training::model()->findbypk($id,array('select'=>array('title')));
            $TrainingExtension = TrainingExtension::model()->findbypk($id,array('select'=>array('id','reports')));
            $data = array('id'=>$id, 'title'=>$training->title, 'reports'=>trim($TrainingExtension->reports));
            //rdis暂存的内容
            $bak = $bakobj->data;
            if( !empty($bak) && !empty(trim($bak['data'])) ){
                $data['reports'] = $bak['data'];
            }
            $this->render('reports', array('id'=>$id,'data'=>$data));
            exit;
        }
        $extendata['id'] = $id;
        $reports = Yii::app()->request->getParam( 'reports' );
        $report_img_num = substr_count($reports,'<img');
        $report_video_num = substr_count($reports,'<video');
        
        $reports = CHtml::encode($reports);
        $ins = TrainingExtension::model()->updateByPk($id, array('reports'=>$reports,'report_img_num'=>$report_img_num,'report_video_num'=>$report_video_num, '_update_time'=>date('Y-m-d H:i:s')));
        if($ins){
            $bakobj->clear();//保存后清空redis
            echo $this->encode($msgNo, $this->msg[$msgNo]);
        }else{
            echo $this->encode(1, $this->msg[1]);
        }
	}

    
    //复制
    public function actionCopy_training(){
        $id		= intval(Yii::app()->request->getParam( 'id' ));
        $data = Training::model()->findByPk($id);
        if(empty($data)){
            $msg = $this->msg['1022'];
            echo $this->encode('1022', $msg);exit;
        }
        
        $ins = Training::model()->copyTraining($id);
        if($ins){
            $msg = $this->msg['Y'];
            echo $this->encode('Y', $msg);
        }else{
            $msg = $this->msg['1'];
            echo $this->encode('1', $msg);
        }
    }
    
    
    // 取消培训
    public function actionCancel(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $offline_training = Yii::app()->request->getParam( 'offline_training' );
        $online_training = Yii::app()->request->getParam( 'online_training' );
        $Training = Training::model()->findByPk($id);
        try{
            if( $Training['status']!=1 || $Training['cancel']==3 ){
                throw new Exception('1043');
            }
            
            if( $Training['endtime'] <= date('Y-m-d H:i:s') ){
                throw new Exception('1044'); //次培训已结束 无法取消
            }
            if( $Training['starttime'] <= date('Y-m-d H:i:s') ){
                throw new Exception('1045'); //次培训已开始 无法取消
            }
            
            $cancel = 0;
            if( $offline_training==='true' && $online_training==='false' ){
                $cancel = 1;
            }elseif( $offline_training==='false' && $online_training==='true' ){
                $cancel = 2;
            }elseif( $offline_training==='true' && $online_training==='true' ){
                $cancel = 3;
            }
            if( intval($Training['cancel']) === $cancel ){
                throw new Exception('Y'); 
            }
            $up = Training::model()->updateByPk( $id, array('cancel'=>$cancel,'_update_time'=>date('Y-m-d H:i:s')) );
            if($up){
                //有人报名才产生任务
                $yibaoming = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$id));
                if( $yibaoming > 0){
                    $updata['sms_status'] = 1;
                }
                //$updata['status'] = $status;
                $updata['_update_time'] = date('Y-m-d H:i:s');
                TrainingExtension::model()->updateByPk( $id, $updata );
                
                //将已报名取消
                $participate_way_sql = '';
				if( $cancel==1 ){
					$participate_way_sql = ' and participate_way=1';
				}elseif( $cancel==2){
					$participate_way_sql = ' and participate_way=0';
				}
				$up = TrainingParticipate::model()->updateAll(
						array('status'=>2),'training_id=:training_id and status=1'.$participate_way_sql ,array(':training_id'=>$id)
					);
                if($up){
                    $oldData = $Training->attributes;
                    OperationLog::addLog(OperationLog::$operationTraining, 'edit', '取消培训', $id, $oldData, array('status'=>2));
                }
            }else{
                $msgNo = 1;
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);        
    }
 
    
    //删除 
    public function actionDelete(){
        $msgNo = 'Y';
        $training_id = intval(Yii::app()->request->getParam( 'id' ));
        $model = Training::model()->findByPk($training_id);
        $oldData = $model->attributes;
        $Connection = $model->dbConnection->beginTransaction();
        
        //报名表
        $del_1 = TrainingParticipate::model()->deleteAll('training_id=:training_id', array(':training_id'=>$training_id));
        //签到表
        $del_2 = TrainingParticipateSigninLog::model()->deleteAll('training_id=:training_id', array(':training_id'=>$training_id));
        //评论表
        $del_3 = ActivityComment::model()->deleteAll('activity_id=:training_id and status=2', array(':training_id'=>$training_id) );
        //点赞表
        $del_4 = ActivityCz::model()->deleteAll('czid=:training_id and type in(3,4,5)', array(':training_id'=>$training_id) );
        //收藏表
        $del_5 = ActivityCollect::model()->deleteAll('activity_id=:training_id and source=1', array(':training_id'=>$training_id) );
        //抽奖设置表
        $del_6 = ActivitySetDrawLots::model()->deleteAll('source=1 and activity_id=:training_id', array(':training_id'=>$training_id) );
        //培训扩展表
        $del_7 = TrainingExtension::model()->deleteByPk($training_id);
        //培训表
        $del_8 = Training::model()->deleteByPk($training_id);
        //短信模板
        $del_9 = SmsTask::model()->deleteAll('column_name="training" and column_id=:training_id', array(':training_id'=>$training_id) );
        //大讲堂计划表
        $del_10 = TrainingGenseePlan::model()->deleteAll('training_id=:training_id', array(':training_id'=>$training_id));
        $del_11 = $model['is_create_gensee']==1 ? GenseeApi::model()->deleteByPk($training_id) : 1;
        
        if( $del_1!==false && $del_2!==false && $del_3!==false && $del_4!==false && $del_5!==false && $del_6!==false && $del_7!==false && $del_8!==false && $del_9!==false && $del_10!==false && $del_11!==false){
            $Connection->commit();
            OperationLog::addLog(OperationLog::$operationTraining, 'del', '删除培训', $training_id, $oldData, array());
        }else{
            $msgNo = '1';
            $Connection->rollBack();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    
    
    //轮播图列表
    public function actionRolling_image(){

        if(!isset($_GET['iDisplayLength'])){
                $this->render('rolling_image');exit;
        }

        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;

        //$title = trim(Yii::app()->request->getParam( 'title' ));
        //$con['title'] = $title;
        $con['status'] = 1;
		$con['type'] = 1;
        if($this->filiale_id!=BRANCH_ID){
                $con['filiale_id'] = $this->filiale_id;
        }
        $list = ActivityLunbotu::model()->getlist($con, $ord, $field, $limit, $page);

        echo CJSON::encode($list);
    }
    
    //添加修改轮播图
    public function actionRolling_save(){
        $msgNo = 'Y';
        $id = trim(Yii::app()->request->getParam( 'id' ));
        if(!isset($_POST['save'])){
			if( empty($id) ){
				$data = array( 'id' => '', 'filiale_id' => '', 'image_title' => '', 'image_path' => '', 'image_link' => '', 'sort' => 0 );
			}else{
				$data = ActivityLunbotu::model()->findByPk($id);
			}
            $this->render('rolling_add',array('id'=>$id,'data'=>$data)); exit;
        }
		
        try {
			if ( empty($id) && empty($_FILES) ){
				throw new Exception('1018');
			}
			if(!empty($_FILES)){
				$key = array_keys($_FILES);
				$filevalue = $_FILES[$key[0]];
				if(intval($filevalue['error'])===1){
					throw new Exception('1015');
				}

				$size = $filevalue['size']/1024/1024;
				if($size>1){
					throw new Exception('1015');
				}
				$upload = new Upload();
				$image_path = $upload->uploadFile($key[0]);;
				$getErrorMsg = $upload->getErrorMsg();
				if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
					throw new Exception('1014');
				}
				
				$data['image_path'] = $image_path;
			}
			if( empty($id) ){
				$data['filiale_id'] = $this->filiale_id;
				$data['type'] = 1;  //培训讲座
			}else{
				$data['id'] = $id;
			}
            
			$data['image_title'] = trim(Yii::app()->request->getParam( 'image_title' ));
			$data['image_link'] = trim(Yii::app()->request->getParam( 'imgage_link' ));
			$data['sort'] = intval(Yii::app()->request->getParam( 'sort' ));
			$id = Training::model()->setRolling($data);
			if(!$id){
				throw new Exception('1');
			}
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //学员管理
    public function actionStudents_list(){

        $id   	= intval(Yii::app()->request->getParam( 'id' ));
        if(!isset($_GET['iDisplayLength'])){
            $getBranchList = ServiceRegion::model()->getCityList();
            $this->render('students_list',array('id'=>$id, 'getBranchList'=>$getBranchList));exit;
        }
        
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); //分支id前两位
        $search_type1 = trim(Yii::app()->request->getParam( 'search_type1' ));
        $search_content1 = trim(Yii::app()->request->getParam( 'search_content1' ));
        $search_type2 = trim(Yii::app()->request->getParam( 'search_type2' ));
        $search_content2 = trim(Yii::app()->request->getParam( 'search_content2' ));
        $training_way = trim(Yii::app()->request->getParam( 'training_way' ));
        $training_status = trim(Yii::app()->request->getParam( 'training_status' ));
        $is_signin = intval(Yii::app()->request->getParam( 'is_signin' )); //九月
        $con = $training_con = array();
        if($starttime){
            $con['t._create_time>'] = $starttime;
            $con['t._create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        
        if ( !empty($search_content1) ){
            $con[$search_type1] = $search_content1;
        }
        if ( !empty($search_content2) ){
            $con['t.'.$search_type2] = $search_content2;
        }
        if( $id ){
            $con['t.training_id'] = $id;
        }
        if( $training_way!='all' ){
            $con['t.participate_way'] = $training_way;
        }
        if( $training_status!='all' ){
            $con['t.status'] = $training_status;
        }
        if($this->filiale_id==BRANCH_ID){
            if($province_code!=BRANCH_ID){
                $con['training.filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['training.filiale_id'] = $this->filiale_id;
        }
        if($is_signin){ //九月
            $con['is_signin'] = $is_signin;
        }
        $list = TrainingParticipate::model()->get_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    
    //批量取消报名
    public function actionCancel_participate(){
        $msgNo = 'Y';
        $ids  = Yii::app()->request->getParam( 'ids' );
        $status = intval(Yii::app()->request->getParam( 'status' ));
        try{
            $Participate = TrainingParticipate::model()->idsByFind($ids);
            if(empty($Participate)){
                throw new Exception(3);
            }
            foreach ($Participate as $k=>$v){
                if($v->status==2){
                    $this->msg[1048] = '【ID='.$v->id.'】'.$this->msg[1048];
                    throw new Exception(1048);
                }
                $v->status = 2;
                $v->save();
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    //设置优秀学员
    public function actionSet_Good_Students(){
        $msgNo = 'Y';
        $ids  = Yii::app()->request->getParam( 'ids' );
        foreach($ids as $id){
            $data = TrainingParticipate::model()->findByPk($id, array('select'=>array('status')) );
            if( $data->status==1 ){
                TrainingParticipate::model()->updateByPk($id, array('is_good'=>1,'_update_time'=>date('Y-m-d H:i:s')));
            }
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //一键签到
    public function actionAkey_signin(){    
        $msgNo = 'Y';
        $ids  = Yii::app()->request->getParam( 'ids' );
        $signin_time  = Yii::app()->request->getParam( 'signin_time' );
        $today = substr($signin_time,0,10);
        $params['select'] = array('id','training_id', 'status','filiale_id', 'member_user_id' , '_create_time');
        $params['condition'] = 'status=:status';
        $params['params'] = array(':status'=>1);

        try{
            $TrainingParticipateSigninLogModel = TrainingParticipateSigninLog::model();
            
            if( $signin_time > date('Y-m-d 23:59:59') ){
                throw new Exception('1025'); //签到时间不可超出今天
            }
            
            $res = TrainingParticipate::model()->findAllByPk($ids, $params);
            if( empty($res) ){
                throw new Exception('1024'); //所选人员均不符合签到条件，请重新选择
            }
            $lognum = 0; //插入日志数量
            $signined = 0; //已经签到过的数量
            foreach($res as $v){
                $signinlogCount = $TrainingParticipateSigninLogModel->getCount(array('participate_id'=>$v['id'], 'date(signin_time)'=>$today));
                if(!$signinlogCount && ( $signin_time > $v['_create_time'] ) ){
                    $data['participate_id'] = $v['id'];
                    $data['training_id'] = $v['training_id'];
                    $data['type'] = 1;
                    $data['signin_time'] = $signin_time;
                    $ins = $TrainingParticipateSigninLogModel->SaveLog($data); //签到日志记录         
                    if($ins){
                       $lognum ++;
                        //积分
                        if( $v['member_user_id'] ){
                            $training = Training::model()->findByPk($v['training_id'], array('select'=>array('title')) );
                            $title = $training->title;
                            CreditLog::addCreditLog(CreditLog::$creditTraining, CreditLog::$typeKey[6], $v['training_id'], 'add', $title.'签到 ',$v['member_user_id'],$v['filiale_id']); 
                            //调研管理通知预览接口
                            ActivitySetDrawLots::model()->research($v['training_id'], 2, 2, $v['member_user_id']);
                        }    
                    }
                }else{
                    $signined ++;
                }  
            }

            if( $signined === count($ids) ){
                throw new Exception('1024');
            }else{
                $this->msg[$msgNo] = $lognum;
                throw new Exception('Y');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
       
    }
    
    
    
    //学员评价
    public function actionStudents_evaluation(){
        $ids  = Yii::app()->request->getParam( 'ids' );
        $evaluation  = CHtml::encode( Yii::app()->request->getParam( 'evaluation' ) );
        $up = array();
        foreach($ids as $id){
            $up[] = TrainingParticipate::model()->updateByPk($id, array('evaluation'=>$evaluation, '_update_time'=>date('Y-m-d H:i:s')));
        }
        if(count($ids)==count($up)){
            $msgNo = 'Y';
        }else{
            $msgNo = 1;
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //给学员发短信
    public function actionSendsms_To_Students(){        
        $msgNo = 'Y';
        $id = intval( Yii::app()->request->getParam( 'id' ) );
        $content = Yii::app()->request->getParam( 'content' );
        $ret = TrainingParticipate::model()->findByPk($id, array('select'=>array('mobile','training_id')) );
        $filiale_id = $this->filiale_id;
        if($filiale_id==BRANCH_ID){
            $Training = Training::model()->findByPk($ret['training_id'],array('select'=>['filiale_id']));
            $filiale_id = $Training['filiale_id'];
        }
        
        try {
            if( empty($ret) ){
                throw new Exception('3');
            }
            $mobile = $ret->mobile;
            if( !isMobilePhone($mobile) ){
                throw new Exception('1001');
            }
            if( PHP_OS=='WINNT' ){
                throw new Exception(6);
            }
            if( !sendSms($mobile, $content, 'fwsq') ){
				throw new Exception('1026');
			}
            writeIntoLog('smslog', 'training' . "\t" . '0' . "\t" . $filiale_id . "\t" . date('Y-m-d H:i:s') . "\t" . $ret['training_id'] . "\t" . $mobile . "\t" . $content . "\n" );
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //批量发送邀请函
    public function actionSend_Invitation(){
        $msgNo = 'Y';
        $ids =  Yii::app()->request->getParam( 'idArr' ) ;

        $params['select'] = array('id','training_id','realname' ,'mobile', 'yqh_path', 'invite_code');
        $params['condition'] = 'status=:status';
        $params['params'] = array(':status'=>0);
        $res = TrainingParticipate::model()->findAllByPk($ids, $params);
        $arr = array();
        try {
            if( empty($res) ){
                throw new Exception('1034');
            }
            $sendData = [];
            foreach($res as $k=>$v){
                $training = Training::model()->findByPk($v['training_id']);
                if( $training['endtime'] < date('Y-m-d H:i:s') ){ //培训已结束
                    $this->msg[1035] = '发送失败！【'.$training['title'].'】'.$this->msg[1035];
                    throw new Exception('1035');
                }
                
                if( empty($v['yqh_path']) ){
                    $this->msg[1035] = '发送失败！【'.$v['realname'].'】邀请函不存在';
                    throw new Exception('1035');
                }
                $sendData[] = array(
                    'id' => $v['id'],
                    'num' => $training['num'],
                    'invite_code' => $v['invite_code'],
                    'starttime' => $training['starttime'],
                    'endtime' => $training['endtime'],
                    'address' => $training['address'],
                    'mobile' => $v['mobile']
                );
            }
            
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        
        if( count($sendData)===count($ids) ){
            foreach($sendData as $sdata){
                $this->_sendSms($sdata);
                TrainingParticipate::model()->updateByPk($sdata['id'], array('status'=>1, '_update_time'=>date('Y-m-d H:i:s')) );
            }
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
        
    }
    
    //发短信
    private function _sendSms($data){
        $sendSmsTxt = '亲，您的培训邀请码: '.$data['num'].'-'.$data['invite_code'].'，';
        $sendSmsTxt .= '时间 '.date('m月d日 H:i',strtotime($data['starttime'])).' - '.date('m月d日 H:i',strtotime($data['endtime'])).'。';
        //$sendSmsTxt .= '活动邀请码'.$data['num'].'-'.$data['invite_code'];
        $sendSmsTxt .= '请您现场出示并签到，邀请函详情点击：';
        $sendSmsTxt .= EHOME.'/activity/0'.$data['id']; //id前加0 表明是讲座的报名id，否则是活动的报名id
        $sendSmsTxt .= ' 。地址：'.$data['address'];
        $mobile = $data['mobile'];
        if(YII_ENV=='dev'){
            @sendSms($mobile, $sendSmsTxt, 'fwsq');
            writeIntoLog('sendsms', $mobile.$sendSmsTxt."\n\r");
        }else{
            writeIntoLog('sendsms', $mobile.$sendSmsTxt."\n\r");
            @sendSms($mobile, $sendSmsTxt, 'fwsq');
        }
    }

    //签到
    public function actionSignin(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $training = Training::model()->getlist(array('id'=>$id), 'desc', 'id', 1, 0);
        $training = $training['data'][0];

        //生成活动的二维码供手机登录
        if( empty($training['qr_code_image']) ){
            $data = (YII_ENV=='dev'?'http://10.129.8.154':EHOME).'/index.php?r=sign/Training_sign&user_id='.$this->user_id.'&user_name='.$this->user_name.'&id='.$id;
            $qr_code_image = FwUtility::generateQrcodeCode($data);
            TrainingExtension::model()->updateByPk($id,array('qr_code_image'=>$qr_code_image));
        }else{
            $qr_code_image = $training['qr_code_image'];
        }
        $this->render('signin',array('data'=>$training, 'ewmname'=>$qr_code_image));
    }
    
    //获取报名信息
    public function actionGetParticipate(){
        $msgNo = 'Y';
        $training_id = intval(Yii::app()->request->getParam( 'training_id' ));
        $invite_code = trim(Yii::app()->request->getParam( 'invite_code' ));
        $signin_time = trim(Yii::app()->request->getParam( 'signin_time' ));
        //$today = substr($signin_time,0,10);
        $today = date('Y-m-d');
        
        $con['training_id'] = $training_id;
        $con['status'] = 1;
        
        try {
            if(strlen($invite_code)==4){
                $con['invite_code'] = $invite_code;
            }elseif(strlen($invite_code)==11){
                $con['mobile'] = $invite_code;
            }else{
                throw new Exception('1006');
            }
            
            $data = TrainingParticipate::model()->getlist($con, 'desc', 'id', '1',0);
            if(!$data['data']){
                throw new Exception('3');
            }
            $data = $data['data'][0];
            if(empty($data['company'])){
                $data['company'] = isset($data['extend']['company']) ? $data['extend']['company'] : '';
            }
            //print_r($data);die;
            //所选日期是否已签到
            $countCon = array('training_id' => $data['training_id'], 'participate_id' => $data['id'], 'date(signin_time)' => $today);
            $signinlogCount = TrainingParticipateSigninLog::model()->getCount($countCon);

            $res = array(
                'id'=> $data['id'],
                'type' => $signinlogCount==0 ? '未签到' : '已签到',
                'realname' => $data['realname'],
                'mobile' => $data['mobile'],
                'company' => $data['company'],
            );
            $this->msg[$msgNo] = $res;
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //报名签到
    public function actionSetParticipateSignin(){
        $msgNo = 'Y';
        $training_id = intval(Yii::app()->request->getParam( 'training_id' ));  
        $participate_id = intval(Yii::app()->request->getParam( 'participate_id' )); //为0属于现场新建
        $realname = trim(Yii::app()->request->getParam( 'realname' ));
        $mobile = trim(Yii::app()->request->getParam( 'mobile' ));
        $company = trim(Yii::app()->request->getParam( 'company' ));
        //$signin_time = trim(Yii::app()->request->getParam( 'signin_time' )); //签到、补签到时间
        $signin_time = date('Y-m-d H:i:s');
        try{
            if(!$training_id){
                throw new Exception('1006');
            }
            //if( $signin_time > date('Y-m-d 23:59:59') ){
            //    throw new Exception('1027');
            //}
            if(strlen($realname)>20){
                $msgNo = 'realname';
                $this->msg[$msgNo] = $this->shoujixnxi[$msgNo].$this->msg['5'];
                throw new Exception($msgNo);
            }
            if(!$mobile){
                $msgNo = 'mobile';
                $this->msg[$msgNo] = $this->shoujixnxi[$msgNo].$this->msg['4'];
                throw new Exception($msgNo);
            }
            if(!isMobilePhone($mobile) ){
                $msgNo = 'mobile';
                $this->msg[$msgNo] = $this->msg['1001'];
                throw new Exception($msgNo);
            }
            
            $count = 0;
            if($participate_id===0){
                $count = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$training_id, 'mobile'=>$mobile,'participate_way'=>1,'status'=>1));
                if( $count!=0 ){
                    $msgNo = 'mobile';
                    $this->msg[$msgNo] = $this->msg['1017'];
                    throw new Exception($msgNo);
                }
                $data['type'] = 1; //现场报名
				$data['source'] = 3; //后端网站新增用户
                $data['participate_way'] = 1; //现场报名属于线下培训
                $data['training_id'] = $training_id;
                $data['mobile'] = $mobile;
                if( $realname ){
                    $data['realname'] = $realname;
                }
                if( $company ){
                    $data['company'] = $company;
                }
                $participate_id = TrainingParticipate::model()->SaveData($data);
                
                if( !$participate_id ){
                    throw new Exception('1');
                }
                $data = array();
                $data['participate_id'] = $participate_id;
                $data['training_id'] = $training_id;
                $data['type'] = 0; //自己签到
                $data['signin_time'] = date('Y-m-d H:i:s');
                TrainingParticipateSigninLog::model()->SaveLog($data);
            }else{
                $count = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$training_id,'mobile'=>$mobile,'status'=>1,'id!'=>$participate_id));
                if( $count!=0 ){
                    $msgNo = 'mobile';
                    $this->msg[$msgNo] = $this->msg['1017'];
                    throw new Exception('1017');
                }
                //签到时间不可早于报名时间
                $TrainingParticipate = TrainingParticipate::model()->findByPk($participate_id,
                    array('select'=>array('id','realname','company','mobile','member_user_id','_create_time')));
                $_create_time = $TrainingParticipate['_create_time'];
                if( $signin_time < $_create_time ){
                    throw new Exception('1028');
                }
                $datasave['id'] = $participate_id;
                if($realname && $realname!=$TrainingParticipate['realname']){
                    $datasave['realname'] = $realname;
                }
                if($company && $company!=$TrainingParticipate['company']){
                    $datasave['company'] = $company;
                }
                if($mobile && $mobile!=$TrainingParticipate['mobile']){
                    $datasave['mobile'] = $mobile;
                }
                if( count($datasave) > 1 ){
                    $save = TrainingParticipate::model()->SaveData($datasave);
                }
                $logdata['participate_id'] = $participate_id;
                $logdata['training_id'] = $training_id;
                $logdata['signin_time'] = $signin_time;
                $ins_log = TrainingParticipateSigninLog::model()->SaveLog($logdata);
                if( !$ins_log ){
                    throw new Exception('1');
                }
                 //积分
                $title = trim(Yii::app()->request->getParam( 'title' ));
                $filiale_id = intval(Yii::app()->request->getParam( 'filiale_id' ));
                CreditLog::addCreditLog(CreditLog::$creditTraining, CreditLog::$typeKey[6], $training_id, 'add', $title.'签到 ',$TrainingParticipate['member_user_id'],$filiale_id);  
                //调研管理通知预览接口
				ActivitySetDrawLots::model()->research($training_id, 2, 2, $TrainingParticipate['member_user_id']);
            }
            
            
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //通过主键获取学员信息
    public function actionByPkParticipate(){
        $training_id = intval(Yii::app()->request->getParam( 'training_id' ));  
        $participate_id = intval(Yii::app()->request->getParam( 'participate_id' ));
        $data = ['realname'=>'', 'mobile'=>'', 'company'=>''];
        $Participate = TrainingParticipate::model()->findByPk($participate_id);
        if($Participate){
            if($Participate['training_id']==$training_id){
                $data['realname'] = $Participate['realname'];
                $data['mobile'] = $Participate['mobile'];
                $data['company'] = $Participate['company'];
                if(empty($data['company'])){
                    $extend = empty($Participate['extend']) ? array() : @unserialize($Participate['extend']);
                    $data['company'] = isset($extend['company']) ? $extend['company'] : '';
                }
            }  
        }
        echo $this->encode('Y', $data);
    }
    
    //新增学员
    public function actionNewAddParticipate(){
        $msgNo = 'Y';
        $participate_id = intval(Yii::app()->request->getParam( 'participate_id' ));  
        $training_id = intval(Yii::app()->request->getParam( 'training_id' ));  
        $realname = trim(Yii::app()->request->getParam( 'realname' ));
        $mobile = trim(Yii::app()->request->getParam( 'mobile' ));
        $company = trim(Yii::app()->request->getParam( 'company' ));
        $nottime = date('Y-m-d H:is');
        try{
            if(!$training_id){
                throw new Exception('1006');
            }
            if(empty($realname)){
                $msgNo = 'realname';
                $this->msg[$msgNo] = $this->shoujixnxi[$msgNo].$this->msg['4'];
                throw new Exception($msgNo);
            }
            if(mb_strlen($realname,'utf8')>20){
                $msgNo = 'realname';
                $this->msg[$msgNo] = $this->shoujixnxi[$msgNo].$this->msg['5'];
                throw new Exception($msgNo);
            }
            if(!$mobile){
                $msgNo = 'mobile';
                $this->msg[$msgNo] = $this->shoujixnxi[$msgNo].$this->msg['4'];
                throw new Exception($msgNo);
            }
            if(!isMobilePhone($mobile) ){
                $msgNo = 'mobile';
                $this->msg[$msgNo] = $this->msg['1001'];
                throw new Exception($msgNo);
            }
            if(mb_strlen($company,'utf8')>50){
                throw new Exception(1056);
            }
            
            $Training = Training::model()->findByPk($training_id);
            if(empty($Training)){
                throw new Exception('3');
            }
            if($Training['way']==2){
                throw new Exception(1052);//仅限于线下培训
            }
            if($participate_id==0){
                if($Training['starttime']<=$nottime){
                    throw new Exception(1053);
                }
                if($Training['status']!=1){
                    throw new Exception(1055);
                }
                if($Training['cancel']==1 || $Training['cancel']==3){
                    throw new Exception(1054);
                }
            }

            $count = 0;
            $con = array('training_id'=>$training_id, 'mobile'=>$mobile,'participate_way'=>1,'status'=>1);
            if($participate_id){
                $con['id!'] = $participate_id;
            }
            $count = TrainingParticipate::model()->getRequirementCount($con);
            if( $count!=0 ){
                $msgNo = 'mobile';
                $this->msg[$msgNo] = $this->msg['1017'];
                throw new Exception($msgNo);
            }
            if($participate_id==0){
                $data['filiale_id'] = $Training['filiale_id'];
                $data['type'] = 1; //现场报名
                $data['source'] = 5; //后端学员管理新增用户
                $data['participate_way'] = 1; //后台代报名属于线下培训
                $data['training_id'] = $training_id;
            }
            $data['mobile'] = $mobile;
            if( $realname ){
                $data['realname'] = $realname;
            }
            if( $company ){
                $data['company'] = $company;
            }
            if($participate_id){
                $data['id'] = $participate_id;
            }
            $save_id = TrainingParticipate::model()->SaveData($data);
            //$save_id = 1;
            if( !$save_id ){
                throw new Exception('1');
            }
            if( $participate_id==0 && PHP_OS!='WINNT' ){
                $Extension = TrainingExtension::model()->findByPk($training_id);
                if($Extension['isset_tosign_sms']){
                    $all_address = ServiceRegion::model()->getRedisCityList($Training['province_code']);
                    $all_address .= ' '.ServiceRegion::model()->getRedisCityList($Training['city_code']);
                    $all_address .= ' '.$Training['address'];
                    $content = '亲，您报名的培训';
                    $content .= '“'.$Training['title'].'”，';
                    $content .= date('m月d日 H:i',strtotime($Training['starttime'])).'~'.date('m月d日 H:i',strtotime($Training['endtime'])).'，';
                    $content .= $all_address.'';
                    $content .= '请准时参加。';
                    if( !sendSms($mobile, $content) ){
                        throw new Exception('1026');
                    }
                    writeIntoLog('smslog', 'training' . "\t" . '0' . "\t" . $Training['filiale_id'] . "\t" . date('Y-m-d H:i:s') . "\t" . $training_id . "\t" . $mobile . "\t" . $content . "\n" );
                }
            }
            
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //抽奖列表
    public function actionDraw_lots(){
        $activityId = trim(Yii::app()->request->getParam('activity_id'));
        $activityId = is_numeric($activityId) ? intval($activityId) : 0;
        $activityDrawLotArr = ActivitySetDrawLots::model()->find('activity_id=:activity_id  and source=1', array('activity_id' => $activityId));

        $activityArr = Training::model()->findbypk($activityId);
        if (empty($activityArr)) {
            echo CJSON::encode(array('3', $this->msg[3]));
            exit();
        }
        if(empty($activityDrawLotArr)){
            $setDrawLotsFlag = $this->_setDrawLots($activityId, $activityArr);
            $setDrawLots = CJSON::decode($setDrawLotsFlag, true);
            if($setDrawLots['status'] == 'Y'){
                $activityDrawLotArr = ActivitySetDrawLots::model()->find('activity_id=:activity_id  and source=1', array('activity_id' => $activityId));
            }else{
                echo '<h2 align="center">'.$setDrawLots['info'].'</h2>';
                exit;
            }
        }
        $over = 0;
        $activityEndTime = $activityArr['endtime'];
        $offlineTime = date('Y-m-d H:i:s', strtotime("$activityEndTime +1 day"));
        if( strtotime($offlineTime) < time() ){
            $over = 1;
        }
        $this->render('draw_lots', array('activityDrawLotArr' => $activityDrawLotArr, 'activityArr' => $activityArr, 'over'=>$over));
    }
    
    //中奖人列表
    public function actionDraw_lots_list()
    {
        $sSearch   	= trim(Yii::app()->request->getParam( 'sSearch' ));
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $activity_id= trim(Yii::app()->request->getParam( 'activity_id' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $activity_id= is_numeric($activity_id) ? intval($activity_id) : 0;
        

        $con['is_prize_winning'] = 1;
        $con['training_id']      = $activity_id;

        $list = TrainingParticipate::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    
    /**
     * 初始化抽奖信息
     * @param $activityId
     * @param $activityArr
     * @return string
     */
    public function _setDrawLots($activityId, $activityArr){
        $fn = new Fn();
        try {
            $activityEndTime = !empty($activityArr->endtime) ? $activityArr->endtime : date('Y-m-d H:i:s');
            $offlineTime = date('Y-m-d H:i:s', strtotime("$activityEndTime +1 day"));
            $onlineTime = date('Y-m-d H:i:s');
            $passwd = $fn->uniqueRand();
            $type = 2;
            $activityId = is_numeric($activityId) ? intval($activityId) : 0;

            /*if(strtotime($offlineTime) < time() ){
                    throw new Exception('1021');
            }*/
            $data = array(
                'activity_id' 	=> $activityId,
                'passwd' 		=> $passwd,
                'source'      => 1,
                'type'		 	=> $type,
                'online_time' 	=> $onlineTime,
                'offline_time' 	=> $offlineTime
            );
            $flag = ActivitySetDrawLots::model()->setDrawLotsSave($data);
            if($flag){
                    $msgNo = 'Y';
            } else {
                    throw new Exception('1');
            }
        } catch(\Exception $e){
                $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        return $this->encode($msgNo, $msg);
    }

    
    
    //设置抽奖方式
    public function actionSet_draw_lots_type(){
        try {
            $id   	= trim(Yii::app()->request->getParam( 'id' ));
            $type 	= trim(Yii::app()->request->getParam( 'status' ));

            $data = array(
                'id' 	=> $id,
                'type' 	=> $type,
            );
            $flag = ActivitySetDrawLots::model()->setDrawLotsTypeUpdate($data);
            if($flag){
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    //初始化抽奖记录
    public function actionInit_DrawLots(){
        try {
            $training_id = trim(Yii::app()->request->getParam( 'id' ));
            $trainingArr = Training::model()->findByPk($training_id);
            $data = array(
                'training_id' 	=> $training_id,
            );
            $flag = TrainingParticipate::model()->initializeDrawLots($data);

            $trainingEndTime = !empty($trainingArr->endtime) ? $trainingArr->endtime : date('Y-m-d H:i:s');
            $offlineTime = date('Y-m-d H:i:s', strtotime("$trainingEndTime +1 day"));

            $drawLotsData = array(
                'activity_id' 	=> $training_id,
                'offline_time' 	=> $offlineTime,
                'source' => 1
            );

            $drawLotsFlag = ActivitySetDrawLots::model()->setDrawLotsUpdate($drawLotsData);
            if($flag && $drawLotsFlag){
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    
    
    //统计分析
    public function actionStatistical(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        //限额人数
        $training = Training::model()->findByPk($id,array('select'=>array('title','num','limit_number','way')));
        $trainingext = TrainingExtension::model()->findByPk($id, array('select'=>array('requirement','requirement_online','views')) );
        
        $training = $training->attributes;
        $training['requirement'] = $trainingext['requirement'];
        $training['requirement_online'] = $trainingext['requirement_online'];
        $training['views'] = $trainingext['views'];
        
        //报名总人数
        $Participate_total = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$id,'status!'=>2));
        $training['xianchang'] = 'not';
        $training['tingkerenshu'] = $Participate_total;
        if($training['way']!=2){
            $training['xianchang'] = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$id,'participate_way'=>1,'status'=>1));
            //听课人数
            $training['tingkerenshu'] = TrainingParticipateSigninLog::model()->getListenNum( array('training_id'=>$id) ); 
        }

        if(isset($_GET['participate_way'])){
            $participate_way = intval(Yii::app()->request->getParam( 'participate_way' ));
        }else{
            if( $training['way']==1 || $training['way']==0 ){
                $participate_way = 1;
            }elseif( $training['way']==2 ){
                $participate_way = 0;
            }
        }
        
        $requirement_key = $participate_way==0 ? 'requirement_online' : 'requirement';
        
        //扩展项
        $requirement = empty($training[$requirement_key]) ? array() : unserialize($training[$requirement_key]);
        $textn = '';
        if(!empty($requirement)){
            foreach($requirement as $k=>$v){
                if( strstr($k,'text') ){
                    $requirement[$k] = substr($v,2,20);
                    $textn .= '{"mData": "'.$k.'", "bSortable": false, "bSearchable": true},';
                }else{
                    unset($requirement[$k]);
                }
            }
        }
        
        //是否有调研
        $research = Research::model()->checkResearch(array('column_type'=>2,'column_id'=>$id,'status'=>1,'_delete'=>0));
        $research_id = empty($research) ? 0 : (isset($research[0]['id'])?$research[0]['id']:0);
        $data = array(
            'id' => $id,
            'title' => $training['title'],
            'num' => $training['num'],
            'Participate_total' => $Participate_total,
            'views' => $training['views'],
            'limit_number' => $training['limit_number'],
            'xianchang' => $training['xianchang'],
            'tingkerenshu' => $training['tingkerenshu'],
            'requirement' => $requirement,
            'textn' => $textn
        );
        
        
        
        $this->render('statistical',array('data'=>$data,'participate_way'=>$participate_way,'research_id'=>$research_id));
    }
    
    
    //报名列表
    public function actionParticipate_list(){
        $training_id = intval(Yii::app()->request->getParam( 'id' ));
        $participate_way = Yii::app()->request->getParam( 'participate_way' );
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $con['training_id'] = $training_id;
        if( $participate_way!='all' ){
            $con['participate_way'] = $participate_way;
        }
        //$con['status!'] = 2;   
        $list = TrainingParticipate::model()->getlist($con, $ord, $field, $limit, $page);
        if(empty($list['data'])){
            echo CJSON::encode($list);die;
        }
        
        $shoujixnxi = $this->shoujixnxi;
        unset($shoujixnxi['realname'],$shoujixnxi['mobile']);
        
        //培训总天数
        $training = Training::model()->findByPk($training_id,array('select'=>array('title','num','starttime', 'endtime')));
        $training = $training->attributes;
        $training_day = (strtotime($training['endtime']) - strtotime($training['starttime'])) / 86400;
        $training_day = ceil($training_day);
        $training_day = $training_day<1 ? 1 : $training_day;
        
        foreach($list['data'] as $k=>$v){
           $extends = $v['extend'];
           unset($v['extend']);
           foreach($shoujixnxi as $keyname=>$text){
               if(isset($extends[$keyname])){
                   $v[$keyname] = $extends[$keyname];
               }else{
                   $v[$keyname] = '';
               }
           }
           $v['company'] = empty($v['_company']) ? $v['company'] : $v['_company'];
           //$qiandaodaynum = TrainingParticipateSigninLog::model()->getCount( array('participate_id'=>$v['id']) );
           //$v['qddays'] = $qiandaodaynum.'/'.$training_day; //签到天数、 培训天数 
           $data[] = $v;
        }
        
        $data = array('data' => $data, 'iTotalRecords' => $list['iTotalRecords'], 'iTotalDisplayRecords' => $list['iTotalDisplayRecords']);
        echo CJSON::encode($data);
    }
    
    //导出报名列表
    public function actionParticipate_excel(){
        $training_id = intval(Yii::app()->request->getParam( 'id' ));
        $participate_way = trim(Yii::app()->request->getParam( 'participate_way' ));
        $con['training_id'] = $training_id;
        if( $participate_way!='all' ){
            $con['participate_way'] = $participate_way;
        }
        //$con['status!'] = 2;
        $list = TrainingParticipate::model()->getlist($con, 'desc', 'id', 50000, 0);
        $training = Training::model()->findByPk($training_id,array('select'=>array('title','num','starttime', 'endtime')));
        $trainingExtension = TrainingExtension::model()->findByPk($training_id, array('select'=>array('requirement','requirement_online')));
        $training = $training->attributes;
        $training_day = (strtotime($training['endtime']) - strtotime($training['starttime'])) / 86400;
        $training_day = ceil($training_day);
        $training_day = $training_day<1 ? 1 : $training_day;

        //扩展项
        $requirement_key = $participate_way==0 ? 'requirement_online' : 'requirement';
        $requirement = empty($trainingExtension[$requirement_key])? array() : unserialize($trainingExtension[$requirement_key]);
        if(!empty($requirement)){
            foreach($requirement as $k=>$v){
                if(strstr($k,'text')){
                    $requirement[$k] = substr($v,2,20);
                }else{
                    unset($requirement[$k]);
                }
            }
        }
        
        $data = array();
        foreach($list['data'] as $v){
            $tmp['id'] = $v['id'];
            $tmp['num'] = $training['num'];
            $tmp['title'] = $training['title'];
            $tmp['realname'] = $v['realname'];
            $tmp['member_user_name'] = $v['member_user_name'];
            $tmp['mobile'] = $v['mobile'];
            $tmp['advice'] = $v['advice'];
            $tmp['qq'] = isset($v['extend']['qq']) ? $v['extend']['qq'] : '';
            $tmp['email'] = isset($v['extend']['email']) ? $v['extend']['email'] : '';
            $tmp['dongle'] = isset($v['extend']['dongle']) ? $v['extend']['dongle'] : '';
            $tmp['company'] = $v['company'];
            if(empty($tmp['company'])){
                $tmp['company'] = isset($v['extend']['company']) ? $v['extend']['company'] : '';
            }
            $tmp['position'] = isset($v['extend']['position']) ? $v['extend']['position'] : '';
            $tmp['major'] = isset($v['extend']['major']) ? $v['extend']['major'] : '';
            $tmp['work_num'] = isset($v['extend']['work_num']) ? $v['extend']['work_num'] : '';
            foreach($requirement as $text_k=>$text_v){
                $tmp[$text_k] = isset($v['extend'][$text_k]) ? $v['extend'][$text_k] : '';
				$tmp[$text_k] = (is_numeric($tmp[$text_k]) && strlen($tmp[$text_k])>15 ) ? '\''.$tmp[$text_k] : $tmp[$text_k];
            }
            $tmp['_create_time'] = $v['_create_time'];
            $tmp['participate_way_txt'] = $v['participate_way_txt'];
            $tmp['status_txt'] = $v['status_txt'];
            $tmp['invite_code'] = $v['invite_code'];
            $tmp['is_listen'] = $v['is_listen'];
            //$qiandaodaynum = TrainingParticipateSigninLog::model()->getCount( array('participate_id'=>$v['id']) );
            $tmp['signin_day'] = $v['signin_day']; //签到天数、 培训天数 
            $tmp['today_is_singin'] = $v['today_is_singin'];
            $tmp['is_prize_winning_txt'] = $v['is_prize_winning_txt'];
            
            $data[] = $tmp;
        }
     
        $extends = empty($requirement) ? '' : implode(',',$requirement).',';
        $headerstr = '报名ID,培训编号,培训标题,姓名,账号,手机号码,培训内容建议,QQ,Email,加密锁号,公司全称,职位,专业,从业年限,'.$extends.'报名时间,报名方式,报名状态,邀请码,是否听课,签到天数/培训天数,当日签到状态,中奖结果';
        $header = explode(',',$headerstr);
        FwUtility::exportExcel($data, $header,'培训报名明细',$training['title'].'_'.date('Y-m-d'));
    }
    
    
    
    //培训讲师
    public function actionLecturer(){
        $type = Yii::app()->request->getParam( 'type' );
        if(!isset($_GET['iDisplayLength'])){
            if($type){
                $this->render('lecturer_select');
            }else{
                $this->render('lecturer');
            }
            exit;
        }
        
        $member_user_name = trim(Yii::app()->request->getParam( 'member_user_name' ));
        $lecturer_name = trim(Yii::app()->request->getParam( 'lecturer_name' ));
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $con['_delete'] = 0;
        if( $this->filiale_id!=BRANCH_ID ){
            $con['filiale_id'] = $this->filiale_id;
        }
        if($member_user_name){
            $con['member_user_name'] = $member_user_name;
        }
        if($lecturer_name){
            $con['name'] = $lecturer_name;
        }
        $list = TrainingLecturer::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    
    //创建讲师
    public function actionLecturer_add(){
               
        $msgNo = 'Y';
        $id = trim(Yii::app()->request->getParam( 'id' ));
        if(!isset($_POST['save'])){
			if( empty($id) ){
				$data = array( 'id' => '', 'recommend_course' => '', 'level' => 1, 'name' => '', 'photo' => '', 'course_link' => '', 'member_user_name'=>'', 'introduce'=>'' );
			}else{
				$data = TrainingLecturer::model()->findByPk($id)->attributes;
                //$member = CommonMember::model()->find('member_user_id=:member_user_id' , array(':member_user_id'=>$data['member_user_id']) );
                //$data['member_user_name'] = $member['member_user_name'];
            }
            $this->render('lecturer_add',array('id'=>$id,'data'=>$data)); exit;
        }
		
        try {
			if ( empty($id) && empty($_FILES) ){
				throw new Exception('1018');
			}
			if(!empty($_FILES)){
				$key = array_keys($_FILES);
				$filevalue = $_FILES[$key[0]];
				if(intval($filevalue['error'])===1){
					throw new Exception('1015');
				}

				$size = $filevalue['size']/1024/1024;
				if($size>1){
					throw new Exception('1015');
				}
				$upload = new Upload();
				$image_path = $upload->uploadFile($key[0]);
				$getErrorMsg = $upload->getErrorMsg();
				if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
					throw new Exception('1014');
				}
				
				$data['photo'] = $image_path;
			}
            $member_user_name = trim(Yii::app()->request->getParam( 'member_user_name' ));
            
            //查询用户名是否存在用户表中
            $member = CommonMember::model()->find('member_user_name=:member_user_name' , array(':member_user_name'=>$member_user_name) );
            if( empty($member) ){
                throw new Exception('1029'); //账号不存在
            }
            $member_user_id = $member->member_user_id;
            $Lecturer = TrainingLecturer::model()->find('filiale_id=:filiale_id and _delete=0 and member_user_id=:member_user_id', array('filiale_id'=>$this->filiale_id, 'member_user_id'=>$member_user_id ) );
            
			if( empty($id) ){
				$data['filiale_id'] = $this->filiale_id;
                if( !empty($Lecturer) ){
                    throw new Exception('1030'); //此账号已存在 不可重复添加
                }
			}else{
				$data['id'] = $id;
                if( !empty($Lecturer) && $Lecturer->id != $id ){
                    throw new Exception('1030'); //此账号已存在 不可重复添加
                }
			}
            
            $data['member_user_id'] = $member->member_user_id;
            $data['member_user_name'] = $member_user_name;
			$data['name'] = trim(Yii::app()->request->getParam( 'name' ));
			$data['level'] = intval(Yii::app()->request->getParam( 'level' ));
			$data['recommend_course'] = trim(Yii::app()->request->getParam( 'recommend_course' ));
            $data['course_link'] = trim(Yii::app()->request->getParam( 'course_link' ));
            $data['introduce'] = Yii::app()->request->getParam( 'introduce' );
            if(!$id){
                $data['sort'] = 1;
            }
            
			$id = TrainingLecturer::model()->saveLecturer($data);
			if( !$id ){
				throw new Exception('1');
			}
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //修改讲师排序
    public function actionUpsort(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $sort = intval(Yii::app()->request->getParam( 'sort' ));
        $up = TrainingLecturer::model()->updateByPk( $id, array('sort'=>$sort, '_update_time'=>date('Y-m-d H:i:s')) );
        if( $up ){
            echo $this->encode($msgNo, $this->msg[$msgNo]);
        }else{
            echo $this->encode(1, $this->msg[1]);
        }
    }
    //删除讲师
    public function actionRemove(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $up = TrainingLecturer::model()->updateByPk( $id, array('_delete'=>1, '_update_time'=>date('Y-m-d H:i:s')) );
        if( $up ){
            echo $this->encode($msgNo, $this->msg[$msgNo]);
        }else{
            echo $this->encode(1, $this->msg[1]);
        }
    }
    
    //需求反馈
    public function actionDemand_Feedback(){
        if(!isset($_GET['iDisplayLength'])){
            $getBranchList = ServiceRegion::model()->getCityList();
            $this->render('demand_feedback' ,array( 'getBranchList'=>$getBranchList) );exit;
        }
   
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $starttime   	= trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime   	= trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code   	= trim(Yii::app()->request->getParam( 'province_code' ));
        
        $con['_delete'] = 0;
        if($this->filiale_id==BRANCH_ID){
            if($province_code!=BRANCH_ID){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
		
        if( $starttime && $endtime ){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime;
        }
        
        $list = TrainingDemandFeedback::model()->getlist($con, $ord, $field, $limit, $page);
      
        echo CJSON::encode($list);
    }
    
    //需求反馈导出
    public function actionDemand_Feedback_Excel(){
        $starttime   	= trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime   	= trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' ));
        $con['_delete'] = 0;
		if($this->filiale_id==BRANCH_ID){
            if($province_code!=BRANCH_ID){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
        if( $starttime && $endtime ){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime;
        }
        
        $list = TrainingDemandFeedback::model()->getlist($con, 'desc', 'id', 50000, 0);
        $training_wayArr = array(0=>'线下培训+网略直播', 1=>'线下培训', 2=>'网略直播');
        $data = array();
        foreach($list['data'] as $v){
            $tmp['member_user_name'] = $v['member_user_name'];    
            $tmp['region_name'] = $v['region_name'];      
            $tmp['realname'] = $v['realname'];
            $tmp['member_user_name'] = $v['member_user_name'];
            $tmp['mobile'] = $v['mobile'];
            $tmp['training_way'] = $training_wayArr[$v['training_way']];
            $tmp['type'] = $v['type'];
            $tmp['soft_name'] = $v['soft_name'];
            $tmp['expect_create_time'] = $v['expect_create_time'];
            $tmp['_create_time'] = $v['_create_time'];
            $tmp['feedback'] = $v['feedback'];
            
            $data[] = $tmp;
        }
        
        $headerstr = '账号,分支,姓名,手机号,培训方式,培训类别,软件名称,期望培训时间,反馈时间,反馈信息';
        $header = explode(',',$headerstr);
       
        FwUtility::exportExcel($data, $header,'培训需求反馈','培训需求反馈_'.date('Y-m-d'));
    }
    
    /*
     * 导出抽奖记录
     */
    public function actionExcel_draw(){
        $activity_id= trim(Yii::app()->request->getParam( 'activity_id' ));
        $activity_id= is_numeric($activity_id) ? intval($activity_id) : 0;
        $con['is_prize_winning'] = 1;
        $con['training_id']      = $activity_id;
        $list = TrainingParticipate::model()->getlist($con, 'desc', 'id', 50000, 0);
        $data = array();
        foreach($list['data'] as $k=>$v){
            $tmp['id'] = $v['id'];
            $tmp['realname'] = $v['realname'];
            $tmp['mobile'] = $v['mobile'];
            $tmp['prize_winning_time'] = $v['prize_winning_time'];
            $data[] = $tmp;
        }
        $header = array('ID','中奖人姓名','手机号码','中奖时间');
        FwUtility::exportExcel($data, $header,'培训中奖名单','培训中奖名单'.date('Y-m-d'));
    }
    
    /*
     * 设置排序
     */
    public function actionSetTrainingSort(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $training_sort = intval(Yii::app()->request->getParam( 'training_sort' ));
        $filiale_id = $this->filiale_id;

        $sortArr = [5,4,3,2,1,0];
        try {
            if(!$id){
                throw new Exception(1006);
            }
            if(!in_array( $training_sort ,$sortArr )){
                throw new Exception(1006);
            }
            $Training = Training::model()->findByPk($id);
            if(empty($Training)){
                throw new Exception(3);
            }
            if($this->filiale_id!=$Training->filiale_id){
                $filiale_id = $Training->filiale_id;
            }
            if($training_sort!=0){
                $count = Training::model()->getCount(['id!'=>$id,'sort'=>$training_sort,'filiale_id'=>$filiale_id]);
                if($count){
                    throw new Exception(1047);
                }
            }
            if($Training->sort!=$training_sort){
                $Training->sort = $training_sort;
                $Training->save();
            }
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    /*
     * 统计报表
     */
    public function actionReportTable(){
        $filiale_id = intval(Yii::app()->request->getParam( 'filiale_id' ));   
		$filiale_id = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;   
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
		$endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $type = trim(Yii::app()->request->getParam( 'type' ));
        if($type=='reportCourse'){
            Training::model()->reportCourse($filiale_id, $starttime);//按课程
        }elseif( $type=='reportLecturer' ){
            Training::model()->reportLecturer($filiale_id,$starttime, $endtime);//按讲师
        }elseif( $type=='reportCover' ){
            Training::model()->reportCover($filiale_id,$starttime, $endtime);//按讲师
        }else{
            exit('NOT '.$type);
        }
    }
    
    /*
     * 生成二维码
     */
    public function actionGetQRcode(){
        $id = intval(Yii::app()->request->getParam( 'id' ));   
        $again = Yii::app()->request->getParam( 'again' );
        $down = Yii::app()->request->getParam( 'down' );
        $ext = TrainingExtension::model()->findByPk($id, array('select'=>['id','link_qr_code']));
        
        if( !isset($ext['link_qr_code']) || empty($ext['link_qr_code']) || $again ){
            $link = (YII_ENV=='dev'?'http://10.129.8.154':EHOME).'/mobile/training/details.html?id='.$id;
            $link_qr_code = FwUtility::generateQrcodeCode($link);
            TrainingExtension::model()->trainingExtensionSave(array('id'=>$id,'link_qr_code'=>$link_qr_code));
            $ext->link_qr_code = $link_qr_code;
        }
        
        if ( $down ) {
            $filename =  $id . '.png';
            $file = Yii::getPathOfAlias('webroot').'/../..'.$ext['link_qr_code'];
            header("Content-type: octet/stream");
            header("Content-disposition:attachment;filename=" . $filename);
            header("Content-Length:" . filesize($file));
            readfile($file);
            exit;
        }
        
        $str = '<div style="width:200px;height:200px;text-align:center;">';
        $str .= '<div style="padding-top:20px;"><img src="'.UPLOADURL.'/'.$ext->link_qr_code.'"></div>';
        if( !is_file(Yii::getPathOfAlias('webroot').'/../..'.$ext->link_qr_code) ){
            $str .= '<a href="index.php?r=training/getqrcode&id='.$id.'&again=1" >刷新</a>';
        }
        echo $str;
    }

	/*
     * 获取大课堂回访地址
     */
	public function actionGet_courseware_url(){
        $msgNo = 'Y';
        $roomid = trim(Yii::app()->request->getParam( 'roomid' ));
        try {
            $data = GenseeApi::model()->getCoursewareUrl($roomid); 
            if(!is_array($data)){
				$msgNo = '1006';
                $this->msg[$msgNo] = $data;
                throw new Exception($msgNo);
			}
			$this->msg[$msgNo] = $data;
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    
    /*
     * 更新大课堂回访地址
     */
    public function actionUpCoursewareUrl(){
        $msgNo = 'Y';
        $training_id = intval(Yii::app()->request->getParam( 'training_id' ));
        $courseware_type = Yii::app()->request->getParam( 'courseware_type' );
        $coursewareId = trim(Yii::app()->request->getParam( 'coursewareId' ));
		$coursewareUrl = trim(Yii::app()->request->getParam( 'coursewareUrl' ));
		$updateCoursewareId = trim(Yii::app()->request->getParam( 'updateCoursewareId' ));
		$updateCoursewareUrl = trim(Yii::app()->request->getParam( 'updateCoursewareUrl' ));
        try {
            if(!$training_id || !in_array($courseware_type, ['recording','upload'])){
                throw new Exception(1006);
            }
            $up = GenseeApi::model()->upCoursewareUrl($training_id, $courseware_type, $coursewareId, $coursewareUrl, $updateCoursewareId, $updateCoursewareUrl); 
            
            if($up===NULL){
                throw new Exception(1059);
            }
            if($up !== 'Y'){
                $msgNo = '1006';
                $this->msg[$msgNo] = $up;
                throw new Exception($msgNo);
            }
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
}




     
        