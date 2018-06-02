<?php
use application\models\Gcmc\GcmcCourse;
use application\models\Gcmc\GcmcCourseExtension;
use application\models\Gcmc\GcmcCourseIntroduce;
use application\models\Gcmc\GcmcExperts;
use application\models\Gcmc\GcmcParticipate;
use application\models\Gcmc\GcmcBanner;
use application\models\Gcmc\GcmcNews;
use application\models\Gcmc\GcmcCase;
use application\models\Gcmc\GcmcAudition;
use application\models\Gcmc\GcmcTopic;
use application\models\Gcmc\GcmcComment;
use application\models\Gcmc\GcmcScore;
use application\models\ServiceRegion;
use application\models\SmsTask;
use application\models\Gcmc\GcmcType;
class GcmcController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        0 => '参数错误',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '不可为空',
        5 => '不可过长',
        6 => '本地环境无法发送短信',
        7 => '此手机号已存在',
        1001 => '手机号错误',
        1002 => '邮箱错误',
        1014 => '图片上传错误，请重试',
        1015 => '上传文件过大',
		1016 => '讲师账号不可为空',
		1017 => '此讲师账号已存在，请更换',
		1018 => '没有文件被上传',
        1019 => '图片格式不支持',
        1020 => '请上传不小于规定宽高的图片',
		1021 => '最多只能设置两个坐镇专家',
        1022 => '请选择两个不同的专家',
        1023 => '分类名称不能为空',
        1024 => '分类ID不能为空',
        1025 => '分类不存在',
        1026 => '分类已存在',
        1027 => '请先删除此分类下的子分类',
        1028 => '此分类下有课程存在，不可删除',
        1029 => '请正确选择分类',
        1030 => '请上传横版照片',
        1031 => '请上传竖版照片',
        1032 => '请上传方版照片',
        1049 => '大括号“{}”及其中内容不可修改',
        1050 => '开始时间已过，不可再修改配置',
        1051 => '请按要求上传邀请函模板图片',
        1052 => '讲师1不可为空',
        1053 => '讲师1和讲师2不可是同一个讲师',
        1054 => '此二级分类已经被绑定，请选择其他分类',
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

    //Banner列表
    public function actionBanner(){
        if(!isset($_GET['iDisplayLength'])){
            $this->render('banner_list');exit;
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
        $con = [];                
        $list = GcmcBanner::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
   
    //添加Banner
    public function actionBrannerAdd(){
        $msgNo = 'Y';
		$id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = $experts_list['data'] = [];
        if(empty($_POST)){
			if( $id ){
				$data = GcmcBanner::model()->findByPk($id); //echo "<pre>"; print_r($data);die;
            }else{
                $data = array(
                    'id' => 0,
                    'location' => 0,
                    'title' => '',
                    'type' => 0,
                    'link' => '',
                    'image' => '',
                    'status' => 1,
                );
            }

            $location = GcmcBanner::model()->location;
			$renderdata = array(
				'id'=>$id,
				'location'=>$location,
				'data' => $data,
			);
            $this->render('banner_add',$renderdata);
			exit;
        }
        $filiale_id = $this->filiale_id;
		$title = trim(Yii::app()->request->getParam( 'title' ));
		$link = trim(Yii::app()->request->getParam( 'link' ));
        $type = intval(Yii::app()->request->getParam( 'type' ));
        $location = intval(Yii::app()->request->getParam( 'location' ));
        $status = intval(Yii::app()->request->getParam( 'status' )); //讲师名称1
        $image_hide = Yii::app()->request->getParam( 'image_hide' ); //是否有图片
		try {
			if($id){
				$data['id'] = $id;
            }else{
                $data['user_id'] = $this->user_id;
                $data['filiale_id'] = $filiale_id;
            }
            $data['status'] = $status;
            $data['type'] = $type;
            $data['title'] = $title;
            $data['link'] = $link;
            $data['location'] = $location;
                  
            //上传封面图
            if(!$image_hide){
                if(empty($_FILES)){
                    throw new Exception(1018);
                }
                $key = array_keys($_FILES);
                $filevalue = $_FILES[$key[0]];
                if(intval($filevalue['error'])===1){
                    throw new Exception('1015');
                }
                $size = $filevalue['size']/1024/1024;
                if($size>5){
                    throw new Exception('1015');
                }
                $upload = new Upload();
                $path = '/uploads/gcmc/Banner/';
                $upload->set('path',$path);
				$upload->set('maxsize',1024*1024*5);
                $image_path = $upload->uploadFile($key[0]);
                $getErrorMsg = $upload->getErrorMsg();
                if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                    throw new Exception('1014');
                }
                $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
                if($location==4){
                    $upstatus = FwUtility::createSmallImage($image_path, 1920,450);
                }else{
                    $upstatus = FwUtility::createSmallImage($image_path, 1920,450);
                }
                
                if($upstatus!='Y'){
                    unlink($image_all_path);
					$msgNo = 1014;
					$this->msg[$msgNo] = $upstatus;
                    throw new Exception($msgNo);
                }
            }else{
                $image_path = $image_hide;
            }
            $data['image'] = $image_path;
    		$save = GcmcBanner::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 获取分类
     */
    public function actionGetGcmcType(){
        $get_all_type = Yii::app()->request->getParam( 'get_all_type' );
        $tcon['_delete'] = 0;   
        if($get_all_type){
            $gcmctype = GcmcType::model()->get_list();
            $option = '';
            foreach($gcmctype as $k=>$v){
                if($v['parent_id']==0){
                    $yiji[$v['id']] = $v['category_name'];
                }else{
                    $erji[$v['parent_id']][] = $v;
                }
            }
            ksort($erji);
            foreach($erji as $ek=>$ev){
                $option .= '<option value='.$ek.'>'.$yiji[$ek].'</option>';
                foreach($ev as $_k=>$_v){
                    $option .= '<option value='.$_v['id'].'>&nbsp;&nbsp;&nbsp;&nbsp;'.$_v['category_name'].'</option>';
                }
            }
            echo $option;die;
        }else{
            $tcon['parent_id'] = Yii::app()->request->getParam( 'id' );
            $tcon['bangding'] = 1;
            $tcon['CourseIntroduceId'] = intval(Yii::app()->request->getParam( 'CourseIntroduceId' ));
            $gcmctype = GcmcType::model()->get_name_list($tcon);//print_r($gcmctype);die;
            echo CJSON::encode($gcmctype);
        }        
    }
    
    /*
     * 课程介绍列表
     */
    public function actionCourseIntroduce(){
        if(!isset($_GET['iDisplayLength'])){
            $this->render('course_introduce_list');exit;
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
        $con['status'] = 1;                
        $list = GcmcCourseIntroduce::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 创建课程介绍
     */
    public function actionCourseIntroduceAdd(){
		$msgNo = 'Y';
		$id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = [];
        if(empty($_POST)){
			if( $id ){
				$data = GcmcCourseIntroduce::model()->findByPk($id); //echo "<pre>"; print_r($data);die;
            }else{
                $data = array(
                    'id' => 0,
                    'type_parent_id' => 0,
                    'type_id' => 0,
                    'title' => '',
                    'video_link' => '',
                    'introduce' => '',
                    'describe' => ''
                );
            }
            $tcon['_delete'] = 0;   
            $tcon['parent_id'] = 0;
            $gcmctype = GcmcType::model()->getlist($tcon, 'desc', 'sort', 200, 0);
			$renderdata = array(
				'id'=>$id,
				'gcmctype'=>$gcmctype,
				'data' => $data,
			);
            $this->render('course_introduce_add',$renderdata);
			exit;
        }

		$type_parent_id = intval(Yii::app()->request->getParam( 'type_parent_id' ));
        $type_id = intval(Yii::app()->request->getParam( 'type_id' ));
        $GcmcType = GcmcType::model()->findByPk($type_id);
		$title = $GcmcType['name'];
		$video_link = trim(Yii::app()->request->getParam( 'video_link' ));
        $introduce = trim(Yii::app()->request->getParam( 'introduce' ));                
        $describe = Yii::app()->request->getParam( 'describe' ); 
		try {
			        
            $data['type_parent_id'] = $type_parent_id;
            $data['type_id'] = $type_id;
            $data['title'] = $title;
            $data['video_link'] = $video_link;
            $data['introduce'] = $introduce;
            $data['describe'] = $describe;
            
            if($id){
				$data['id'] = $id;
                $CourseIntroduceCon['id!'] = $id;
            }else{
                $data['user_id'] = $this->user_id;
                $data['status'] = 1;
                $data['filiale_id'] = $this->filiale_id;
            }
            //分类是否已经被绑定
            $CourseIntroduceCon['type_parent_id'] = $type_parent_id;
            $CourseIntroduceCon['type_id'] = $type_id;
            $CourseIntroduceCon['status'] = 1;
            $CourseIntroduceCount = GcmcCourseIntroduce::model()->getCount($CourseIntroduceCon);
            if( $CourseIntroduceCount ){
                throw new Exception(1054);
            }
            $save = GcmcCourseIntroduce::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
     /*
	*	更改课程状态
	*/
	public function actionUpCourseIntroduceStatus(){
		$msgNo = 'Y';
        $id	= intval(Yii::app()->request->getParam( 'id' ));
		$edit_field	= 'status';
		$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(!$id || !in_array($edit_field, ['status']) ){
				throw new Exception(0);
			}
			$data['id'] = $id;
			$data[$edit_field] = $edit_val;
			$save = GcmcCourseIntroduce::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
	}
    
    /*
     * 课程安排列表
     */
    public function actionCourse(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('course_list', array('getCityList'=>$getCityList));exit;
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
        $endtime        = trim(Yii::app()->request->getParam( 'endtime' ));
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
		$con = ['status!'=>0];
        if( $filiale_id!=BRANCH_ID ){
             $con['filiale_id'] = $filiale_id;
        }

        if($search_content){
            $con['title'] = $search_content;
        }
        if( $starttime && $endtime ){
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
       
        $list = GcmcCourse::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    
    
	/*
     * 创建课程安排
     */
    public function actionCourseAdd(){
		$msgNo = 'Y';
        $type	= trim(Yii::app()->request->getParam( 'type' ));
		$id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = $experts_list['data'] = [];
        if(empty($_POST)){
			$getCityList = ServiceRegion::model()->getCityList();
			if( $id ){
				$data = GcmcCourse::model()->findByPk($id); //echo "<pre>"; print_r($data);die;
            }else{
                $data = array(
                    'id' => 0,
                    'type_parent_id' => 0,
                    'title' => '',
                    'content' => '',
                    'province_code' => 0,
                    'city_code' => 0,
                    'address' => '',
                    'starttime' => '',
                    'endtime' => '',
                    'image' => '',
                    'first_experts_id' => '',
                    'first_experts_name' => '',
                    'second_experts_id' => '',
                    'second_experts_name' => ''
                );
            }
            $tcon['_delete'] = 0;   
            $tcon['parent_id'] = 0;
            $gcmctype = GcmcType::model()->getlist($tcon, 'desc', 'sort', 200, 0);
			$renderdata = array(
				'id'=>$id,
				'getCityList'=>$getCityList, 
				'gcmctype'=>$gcmctype,
				'data' => $data,
			);
            $this->render('add1',$renderdata);
			exit;
        }
        $province_code  = intval(Yii::app()->request->getParam( 'province_code' ));
        $city_code  = intval(Yii::app()->request->getParam( 'city_code' ));
        $filiale_id = $this->filiale_id;
        if($this->filiale_id==BRANCH_ID){
            $filiale_id = ServiceRegion::model()->getProvinceToFiliale($province_code);
        }
        $address  = trim(Yii::app()->request->getParam( 'address' ));
		$type_parent_id = intval(Yii::app()->request->getParam( 'type_parent_id' ));
		$title = trim(Yii::app()->request->getParam( 'title' ));
		$content = trim(Yii::app()->request->getParam( 'content' ));
        $first_experts_id = intval(Yii::app()->request->getParam( 'first_experts_id' ));
        $first_experts_name = trim(Yii::app()->request->getParam( 'first_experts_name' )); //讲师名称1
        $second_experts_id = intval(Yii::app()->request->getParam( 'second_experts_id' ));
        $second_experts_name = trim(Yii::app()->request->getParam( 'second_experts_name' )); //讲师名称2
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $image_hide = Yii::app()->request->getParam( 'image_hide' ); //是否有图片
		try {
			if($id){
				$data['id'] = $id;
            }else{
                $data['user_id'] = $this->user_id;
                $data['status'] = 2;
            }
            $data['filiale_id'] = $filiale_id;
            $data['type_parent_id'] = $type_parent_id;
            $data['title'] = $title;
            $data['content'] = $content;
            $data['province_code'] = $province_code;
            $data['city_code'] = $city_code;
            $data['address'] = $address;
            $data['starttime'] = $starttime;
            $data['endtime'] = $endtime;
            $data['first_experts_id'] = $first_experts_id && $first_experts_name ? $first_experts_id : 0;
            $data['first_experts_name'] = $first_experts_id && $first_experts_name ? $first_experts_name : '';
            $data['second_experts_id'] = $second_experts_id && $second_experts_name ? $second_experts_id : 0;
            $data['second_experts_name'] = $second_experts_id && $second_experts_name ? $second_experts_name : '';
            if( empty($data['first_experts_id']) || empty($data['first_experts_name']) ){
                throw new Exception(1052);
            }
            if( $data['first_experts_id'] == $data['second_experts_id'] ){
                throw new Exception(1053);
            }

            //上传封面图
            if(!$image_hide){
                if(empty($_FILES)){
                    throw new Exception(1018);
                }
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
                $path = '/uploads/gcmc/KeCheng/'.$filiale_id.'/';
                $upload->set('path',$path);
                $image_path = $upload->uploadFile($key[0]);
                $getErrorMsg = $upload->getErrorMsg();
                if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                    throw new Exception('1014');
                }
                $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
                $upstatus = FwUtility::createSmallImage($image_path, 380,204);
                if($upstatus!='Y'){
                    unlink($image_all_path);
					$msgNo = 1014;
					$this->msg[$msgNo] = $upstatus;
                    throw new Exception($msgNo);
                }
            }else{
                $image_path = $image_hide;
            }
            $data['image'] = $image_path;
            if(!$id){
                $data['num'] = GcmcCourse::model()->setDontRepeatNum();
            }
            $GcmcCourse = GcmcCourse::model()->findByPk($id);
    		$save_id = GcmcCourse::model()->saveData($data);
            if($save_id){
                $this->msg[$msgNo] = $save_id;
            }else{
                throw new Exception(1);
            }
            if($id){
                if( strtotime($GcmcCourse['starttime']) != strtotime($starttime) ){
                    SmsTask::model()->upSmsTask_send_time('gcmc', $id, $starttime);
                }
            }
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

	
    /*
     * 设置报名须填项
     */
	public  function actionSetParticipate(){
		$msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $shoujixnxi = GcmcCourseExtension::model()->shoujixnxi;
        if(empty($_POST)){
            $data = GcmcCourseExtension::model()->getExtension($id);//print_r($data);die;
            $this->render('add2', array('id'=>$id,'data'=>$data, 'shoujixnxi'=>$shoujixnxi));    
            exit;
        }
                        
        $keybox = Yii::app()->request->getParam( 'keybox' );
        $is_check = Yii::app()->request->getParam( 'is_check' );
        $keybox['realname'] = 'on';
        $keybox['mobile'] = 'on';
        $keybox['company'] = 'on';
        $keybox['position'] = 'on';
        $keybox['work_num'] = 'on';
        $requirement = array('realname'=>1,'mobile'=>1,'company'=>1,'position'=>1,'work_num'=>1);
        //print_r($keybox);die;
        foreach($keybox as $k=>$v){
            if(in_array($k, array('text1','text2','text3','text4','text5'))){
                $requirement[$k] = $is_check[$k].'|'.$_POST[$k];
            }else{
                $requirement[$k] = $is_check[$k];
            }
        }
        $data['id'] = $id;
        $data['requirement'] = serialize($requirement);
        $id = GcmcCourseExtension::model()->saveData($data);
        if($id){
            echo $this->encode('Y', $this->msg['Y']);
        }
	}
    
    
    //邀请函
    public function actionSetInvitation(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $data = GcmcCourse::model()->findByPk($id)->attributes;

        $data['all_address'] = ServiceRegion::model()->getRedisCityList($data['province_code']);
        $data['all_address'] .= ' '.ServiceRegion::model()->getRedisCityList($data['city_code']);
        $data['all_address'] .= ' '.$data['address'];
        $Extension = GcmcCourseExtension ::model()->findByPk($id)->attributes;
        $data['isset_invitation'] = $Extension['isset_invitation'];
        $data['invitation_image'] = $Extension['invitation_image'];
        $data['isset_tosign_sms'] = $Extension['isset_tosign_sms'];
        $data['start_before_hour'] = $Extension['start_before_hour'];
        $data['invitation_note'] = $Extension['invitation_note'];
        

        $taskdata = SmsTask::model()->get_list(array(
            'column_name'=>'gcmc',
            'column_id' => $id
        ));//print_r($taskdata);die;
        $default_sms = array(
                'tosign_sms' => array(
                    'id' => 0,
                    'sms_template' => '亲，{您的培训邀请码: '.$data['num'].'-0000}，时间'.date('m月d日H:i',strtotime($data['starttime'])).'~'.date('m月d日H:i',strtotime($data['endtime'])).'。请您现场出示并签到，{邀请函详情点击：http://e.fwxgx.com/activity/0000} 。地址：'.$data['all_address'],
                    'sms_template_not_i' => '亲，您报名的培训“'.$data['title'].'”，'.date('m月d日H:i',strtotime($data['starttime'])).'~'.date('m月d日H:i',strtotime($data['endtime'])).'，'.$data['all_address'].'，请准时参加。'
                ),
                'hour_sms' => array(
                    'id' => 0,
                    'sms_template' => '亲，{您的培训邀请码: '.$data['num'].'-0000}，时间'.date('m月d日H:i',strtotime($data['starttime'])).'~'.date('m月d日H:i',strtotime($data['endtime'])).'。请您现场出示并签到，{邀请函详情点击：http://e.fwxgx.com/activity/0000} 。地址：'.$data['all_address'],
                    'sms_template_not_i' => '亲，您报名的培训“'.$data['title'].'”，'.date('m月d日H:i',strtotime($data['starttime'])).'~'.date('m月d日H:i',strtotime($data['endtime'])).'，'.$data['all_address'].'，请准时参加。'
                )
            );
        $taskdata = empty($taskdata) ? $default_sms : $taskdata;
        $this->render('add3', 
            array(
                'id'=>$id,
                'data'=>$data, 
                'taskdata'=>$taskdata,
                'default_sms'=>$default_sms));
    }
    
    /*
     * 保存邀请函设置
     */
    public function actionSetInvitationSave(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $data = GcmcCourse ::model()->findByPk($id);
        try {
            //活动已开始不可再修改
            if( $data['starttime']<=date('Y-m-d H:i:s') ){
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
                if( substr_count($tosign_sms_template, '{您的培训邀请码: '.$data['num'].'-0000}')!==1 ){
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
                if( substr_count($timing_sms_template, '{您的培训邀请码: '.$data['num'].'-0000}')!==1 ){
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
                $path = '/uploads/gcmc/invitation_template/'.$data['filiale_id'].'/';
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
			
            $dataExte['id'] = $id;
            $dataExte['isset_invitation'] = $isset_yaoqinghan;
            $dataExte['invitation_image'] = $activity_image_path;
            $dataExte['isset_tosign_sms'] = $isset_tosign_sms;
            $dataExte['start_before_hour'] = $isset_hour_sms ? $start_before_hour : 0;
            $dataExte['invitation_note'] = $yaoqinghan_note;
            
            //插入短信任务表
            $activity_starttime = strtotime($data['starttime']);
            $send_time = $activity_starttime - $start_before_hour*3600;
            //此时间不可小于当前时间的20分钟后
            if( $dataExte['start_before_hour'] && $send_time-time() < 600 ){
                $msgNo = 1;
                $this->msg[$msgNo] = '距培训开始时间 '.$data['starttime'].' 不足'.$start_before_hour.'小时10分钟，系统已来不及提前 '.$start_before_hour.'h 发送。';
                throw new Exception($msgNo);
            }
            $up = GcmcCourseExtension::model()->saveData($dataExte);
            if(!$up){
                throw new Exception(1);
            }
            if($tosign_sms_id){
                $tData['id'] = $tosign_sms_id;
            }
            $tData['column_name'] = 'gcmc';
            $tData['filiale_id'] = $data['filiale_id'];
            $tData['column_id'] = $id;
            $tData['describe'] = 'GCMC报名即发短信模板';
            $tData['sms_template'] = $tosign_sms_template;
            $tData['_delete'] = $dataExte['isset_tosign_sms']==0 ? 1 : 0;
            $save = SmsTask::model()->taskSave($tData);
            if(!$save){
                throw new Exception(1);
            }
            
            if($hour_sms_id){
                $tData['id'] = $hour_sms_id;
            }else{
                unset($tData['id']);
            }
            $tData['describe'] = 'GCMC课程开始前'.$start_before_hour.'h发送短信模板';
            $tData['send_time'] = date('Y-m-d H:i:s',$send_time);
            $tData['sms_template'] = $timing_sms_template;
            $tData['is_crontab'] = 1;
            $tData['_delete'] = $dataExte['start_before_hour']==0 ? 1 : 0;
            $save = SmsTask::model()->taskSave($tData);
            if(!$save){
                throw new Exception(1);
            }
            //print_r($_POST);die;
            $data->status = intval(Yii::app()->request->getParam( 'status' ));
            $data->save();
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 现场签到
     */
    public function actionSignin(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $data = GcmcCourse::model()->findByPk($id);
        //生成二维码供手机登录
        if( empty($data['qr_code_image']) ){
            $qr_code_image_url = (YII_ENV=='dev'?'http://10.129.8.154':EHOME).'/index.php?r=sign/gcmc_sign&id='.$id;
            //$qr_code_image_url = EHOME . '?id='.$id;
            $qr_code_image = FwUtility::generateQrcodeCode($qr_code_image_url);
            $data->qr_code_image = $qr_code_image;
            $data->save();
        }else{
            $qr_code_image = $data['qr_code_image'];
        }
        $this->render('signin',array('data'=>$data, 'ewmname'=>$qr_code_image));
    }
    
    /*
     * 获取报名信息
     */
    public function actionGetParticipate(){
        $msgNo = 'Y';
        $course_id = intval(Yii::app()->request->getParam( 'course_id' ));
        $invite_code = trim(Yii::app()->request->getParam( 'invite_code' ));
                        
        $con['course_id'] = $course_id;
        $con['status'] = 1;
        
        try {
            if(strlen($invite_code)==4){
                $con['invite_code'] = $invite_code;
            }elseif(strlen($invite_code)==11){
                $con['mobile'] = $invite_code;
            }else{
                throw new Exception(0);
            }
            
            $data = GcmcParticipate::model()->getOne($con);
            if( empty($data) ){
                throw new Exception(3);
            }

            $res = array(
                'id'=> $data['id'],
                'type' => $data['signin_time']==0 ? '未签到' : '已签到',
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
        $course_id = intval(Yii::app()->request->getParam( 'course_id' ));  
        $participate_id = intval(Yii::app()->request->getParam( 'participate_id' )); //为0属于现场新建
        $realname = trim(Yii::app()->request->getParam( 'realname' ));
        $mobile = trim(Yii::app()->request->getParam( 'mobile' ));
        $company = trim(Yii::app()->request->getParam( 'company' ));
                        
        try{
            $GcmcCourse = GcmcCourse::model()->findByPk($course_id);
            if(!$course_id){
                throw new Exception(0);
            }     
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
            
            if( $realname ){
                $data['realname'] = $realname;
            }
            if( $company ){
                $data['company'] = $company;
            }
            $data['mobile'] = $mobile;
            $data['signin_time'] = date('Y-m-d H:i:s'); //现场报名即签到
            $count = 0;
            if($participate_id===0){
                $count = GcmcParticipate::model()->getCount(array('course_id'=>$course_id, 'mobile'=>$mobile ,'status'=>1));
                if( $count!=0 ){
                    $msgNo = 'mobile';
                    $this->msg[$msgNo] = $this->msg[7];
                    throw new Exception($msgNo);
                }
                $data['filiale_id'] = $GcmcCourse['filiale_id'];
                $data['source'] = 1; //PC端现场报名
                $data['course_id'] = $course_id;
            }else{
                $count = GcmcParticipate::model()->getCount(array('course_id'=>$course_id,'mobile'=>$mobile,'status'=>1,'id!'=>$participate_id));
                if( $count!=0 ){
                    $msgNo = 'mobile';
                    $this->msg[$msgNo] = $this->msg[7];
                    throw new Exception(7);
                }
                $data['id'] = $participate_id;
                //积分
                //$title = trim(Yii::app()->request->getParam( 'title' ));
                //$filiale_id = intval(Yii::app()->request->getParam( 'filiale_id' ));
                //CreditLog::addCreditLog(CreditLog::$creditTraining, CreditLog::$typeKey[6], $training_id, 'add', $title.'签到 ',$TrainingParticipate['member_user_id'],$filiale_id);  
                //调研管理通知预览接口
				//ActivitySetDrawLots::model()->research($training_id, 2, 2, $TrainingParticipate['member_user_id']);
            }
            $participate_id = GcmcParticipate::model()->SaveData($data);
            if( !$participate_id ){
                throw new Exception(1);
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 专家列表
     */
    public function actionExperts(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $select_experts = Yii::app()->request->getParam( 'select_experts' );
            $view_file = $select_experts ? 'select_experts_list' : 'experts_list';
            $this->render($view_file,array('getCityList'=>$getCityList,'select_experts'=>$select_experts));exit;
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
                
        $filiale_id   	= $this->filiale_id==BRANCH_ID ? intval(Yii::app()->request->getParam( 'filiale_id' )) : $this->filiale_id;
        $name = trim(Yii::app()->request->getParam( 'search_content' ));
		$con['status'] = 1;
        if($name){
            $con['name'] = $name;
        }
        if($filiale_id!=BRANCH_ID && !empty($filiale_id)){
            $con['filiale_id'] = $filiale_id;
        }
        
        $list = GcmcExperts::model()->getlist($con, $ord, $field, $limit, $page);

        echo CJSON::encode($list);
    }
    
    /*
     * 新增专家
     */
    public function actionAddExperts(){
		$msgNo = 'Y';
        $id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = [];
        if(empty($_POST)){
            $getCityList = ServiceRegion::model()->getCityList();
			if($id){
				$data = GcmcExperts::model()->findByPk($id);
				$data = empty($data) ? [] : $data->attributes;
			}else{
				$data = array(
					'id' => 0,
					'filiale_id' => 0,
					'member_user_id' => 0,
					'member_user_name' => '',
                    'is_question_expert' => 0,
					'name' => '',
					'photo_wide' => '',
                    'photo_vertical' => '',
                    'photo_square' => '',
                    'home_photo' => 'xxx',
					'introduce' => '',
					'describe' => 0,
                    'row' => 0,
                    'column_val' => 0,
                    'getCityList' => $getCityList
				);
			}
            
            $this->render('experts_add', array('id'=>$id, 'getCityList'=>$getCityList, 'data'=>$data));exit;
        }

        $filiale_id = intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
		$name	= trim(Yii::app()->request->getParam( 'name' ));
		$member_user_id	= trim(Yii::app()->request->getParam( 'member_user_id' ));
		$member_user_name	= trim(Yii::app()->request->getParam( 'member_user_name' ));
        //是否为答疑专家
        $is_question_expert = intval(Yii::app()->request->getParam( 'is_question_expert' ));
        $row = intval(Yii::app()->request->getParam( 'row' ));
        $column_val = intval(Yii::app()->request->getParam( 'column_val' ));
        //简介
        $introduce = trim(Yii::app()->request->getParam( 'introduce' )); 
        //详情
		$describe = trim(Yii::app()->request->getParam( 'describe' ));
        
        //横照片
		$photo_wide = isset($_FILES['photo_wide']) ? $_FILES['photo_wide'] : 0;
        $photo_wide_hide = Yii::app()->request->getParam( 'photo_wide_hide' );
        //竖照片
        $photo_vertical = isset($_FILES['photo_vertical']) ? $_FILES['photo_vertical'] : 0;
        $photo_vertical_hide = Yii::app()->request->getParam( 'photo_vertical_hide' );
        //方照片
        $photo_square = isset($_FILES['photo_square']) ? $_FILES['photo_square'] : 0;
        $photo_square_hide = Yii::app()->request->getParam( 'photo_square_hide' );
        
        //优先显示图片
        $home_photo = Yii::app()->request->getParam( 'home_photo' );

		try{
			if( !$member_user_id || !$member_user_name ){
				throw new Exception(1016);
			}
			$data['member_user_id'] = $member_user_id;
			//$count = GcmcExperts::model()->getCount($countcon);
			//if( $count ){
			//	throw new Exception(1017);
			//}

			if($home_photo=='photo_wide' && empty($photo_wide) && empty($photo_wide_hide)){
				throw new Exception(1030);
			}
            if($home_photo=='photo_vertical' && empty($photo_vertical) && empty($photo_vertical_hide)){
				throw new Exception(1031);
			}
            if(empty($photo_square) && empty($photo_square_hide)){
				throw new Exception(1032);
			}
            $lecturer_photo = array(
                'photo_wide' => $photo_wide_hide,
                'photo_vertical' => $photo_vertical_hide,
                'photo_square' => $photo_square_hide
            );
            
			if( !empty($_FILES) ){
				$key = array_keys($_FILES);
        		$upload = new Upload();
				$path = '/uploads/gcmc/ExpertsAvatar/';
				$upload->set('path',$path);
                foreach($key as $n=>$input_name){
                    $filevalue = $_FILES[$key[$n]];
                    if(intval($filevalue['error'])===1){
                        throw new Exception('1015');
                    }
                    $size = $filevalue['size']/1024/1024;
                    if($size>1){
                        throw new Exception('1015');
                    }
                    $image_path = $upload->uploadFile($input_name);
                    $getErrorMsg = $upload->getErrorMsg();
                    if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                        throw new Exception('1014');
                    }
                    $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
                    $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
                    switch ($input_name){
                        case 'photo_wide':
                            $new_img_w = 600;
                            $new_img_h = 300;
                            $error = '横版图片不符要求';
                        break;
                        case 'photo_vertical':
                            $new_img_w = 200;
                            $new_img_h = 300;
                            $error = '竖图片不符要求';
                        break;
                        case 'photo_square':
                            $new_img_w = 300;
                            $new_img_h = 300;
                            $error = '方版图片不符要求';
                        break;
                    }
                    $upstatus = FwUtility::createSmallImage($image_path, $new_img_w,$new_img_h);
                    if($upstatus!='Y'){
                        unlink($image_all_path);
                        $msgNo = 1014;
                        $this->msg[$msgNo] = $error.'，'.$upstatus;
                        throw new Exception($msgNo);
                    }
                    $lecturer_photo[$input_name] = $image_path;
                }
			}
            
            if($id){
                $data['id'] = $id;
            }
			$data['filiale_id'] = $filiale_id;
			$data['user_id'] = $this->user_id;
			$data['member_user_id'] = $member_user_id;
			$data['member_user_name'] = $member_user_name;
			$data['name'] = $name;
            $data['column_val'] = $column_val;
            $data['is_question_expert'] = $is_question_expert;
            $data['row'] = $row;
			$data['photo_wide'] = $lecturer_photo['photo_wide'];
            $data['photo_vertical'] = $lecturer_photo['photo_vertical'];
            $data['photo_square'] = $lecturer_photo['photo_square'];
            if(isset($lecturer_photo[$home_photo])){
                $data['home_photo'] = $lecturer_photo[$home_photo];
            }
			$data['introduce'] = $introduce;
            $data['describe'] = $describe;
            
			$save = GcmcExperts::model()->saveExperts($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

	/*
	*	更改专家状态、排序
	*/
	public function actionUpExpertsStatus(){
		$msgNo = 'Y';
        $id	= intval(Yii::app()->request->getParam( 'id' ));
		$edit_field	= trim(Yii::app()->request->getParam( 'edit_field' ));
		$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(!$id || !in_array($edit_field, ['show_location','status']) ){
				throw new Exception(0);
			}
			/*if( $edit_field==='sort' && $edit_val===1 ){
				$count = GcmcExperts::model()->getCount(['sort'=>1,'status'=>1]);
				if( $count>=2 ){
					throw new Exception(1021);
				}
			}*/
			$data['id'] = $id;
			$data[$edit_field] = $edit_val;
			$save = GcmcExperts::model()->saveExperts($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
	}
    
    /*
	*	更改课程状态
	*/
	public function actionUpCourseStatus(){
		$msgNo = 'Y';
        $id	= intval(Yii::app()->request->getParam( 'id' ));
		$edit_field	= 'status';
		$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(!$id || !in_array($edit_field, ['sort','status']) ){
				throw new Exception(0);
			}
			$data['id'] = $id;
			$data[$edit_field] = $edit_val;
            $GcmcCourse = GcmcCourse::model()->findByPk($id);
            $Connection = $GcmcCourse->dbConnection->beginTransaction();
			$save = GcmcCourse::model()->saveData($data);
            //删除表明
            GcmcParticipate::model()->updateAll(
                    array('status'=>0),'course_id=:course_id' ,array('course_id'=>$id)
                );
            //删除短信任务
            SmsTask::model()->deleteAll('column_name=:column_name and column_id=:column_id', array('column_name'=>'gcmc', 'column_id'=>$id));
           // var_dump($upComment);die;
            if( $save ){
                $Connection->commit();
            }else{
                $msgNo = '1';
                $Connection->rollBack();
            }
            
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
	}
    
    
    /*
     * 报名管理
     */
    public function actionParticipate(){
        $course_id = intval(Yii::app()->request->getParam( 'course_id' ));
        if(!isset($_GET['iDisplayLength'])){
            //$getCityList = ServiceRegion::model()->getCityList();
            //$gcmctype = GcmcType::model()->getlist(['parent_id'=>0,'_delete'=>0], 'desc', 'sort', 200, 0);
            
            //$GcmcCourse = GcmcCourse::model()->findByPk($course_id, array('select'=>['id','title']));
            $Extension = GcmcCourseExtension::model()->findByPk($course_id);
            //扩展项
            $requirement = empty($Extension['requirement']) ? array() : unserialize($Extension['requirement']);
            $textn = '';
            if(!empty($requirement)){
                foreach($requirement as $k=>$v){
                    if( strstr($k,'text') ){
                        $requirement[$k] = substr($v,2,50);
                        $textn .= '{"mData": "'.$k.'", "bSortable": false, "bSearchable": true, "sWidth":"80"},';
                    }else{
                        unset($requirement[$k]);
                    }
                }
            }
            $data = array(
                'requirement' => $requirement,
                'textn' => $textn
            );
            $this->render('participate_list', array('course_id'=>$course_id,'data'=>$data));exit;
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
        //$province_code = trim(Yii::app()->request->getParam( 'province_code' ));
        //$search_type = trim(Yii::app()->request->getParam( 'search_type' ));
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        //$type = intval(Yii::app()->request->getParam( 'type' ));
        $con['status!'] = 0;
        $con['course_id'] = $course_id;
        if($starttime && $endtime){
            $con['_create_time'] = ['starttime'=>$starttime.' 00:00:00','endtime'=>$endtime.' 23:59:59'];
        }
        if( $search_content ){
            $con['search_content'] = $search_content;
        }                     
        $list = GcmcParticipate::model()->getlist($con, $ord, $field, $limit, $page);//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 删除报名
     */
    public function actionDelParticipate(){
        $msgNo = 'Y';
        $ids = Yii::app()->request->getParam( 'ids' );
        $up = GcmcParticipate::model()->updateByPk(
            $ids,
            array('status'=>0,'_update_time'=>date('Y-m-d H:i:s'))
        );
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 导出报名
     */
    public function actionExcelParticipate(){
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = trim(Yii::app()->request->getParam( 'province_code' ));
        $search_type = trim(Yii::app()->request->getParam( 'search_type' ));
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $type = intval(Yii::app()->request->getParam( 'type' ));
        $con['status'] = 1;
        if($starttime && $endtime){
            $con['_create_time'] = ['starttime'=>$starttime.' 00:00:00','endtime'=>$endtime.' 23:59:59'];
        }
        if($this->filiale_id!=BRANCH_ID){
            $ServiceRegion = ServiceRegion::model()->getBranchInfo($this->filiale_id);
            $province_code = $ServiceRegion[0]['region_id'];
        }
        if($province_code){
            $con['province_code'] = $province_code;
        }
        if($search_type=='realname'){
            $con['realname'] = $search_content;
        }elseif($search_type=='phone'){
            $con['phone'] = $search_content;
        }
        if($type){
            $con['type'] = $type;
        }
        $data = array();
        $list = GcmcParticipate::model()->getlist($con, 'desc', 'id', 5000, 0);
        foreach($list['data'] as $k=>$v){
            $tmp['id'] = $v['id'];
            $tmp['province_code'] = ServiceRegion::model()->getRegionName($v['province_code']);
            $tmp['type'] = $v['type'];
            $tmp['realname'] = $v['realname'];
            $tmp['phone'] = $v['phone'];
            $tmp['company'] = $v['company'];
            $tmp['position'] = $v['position'];
            $tmp['work_num'] = $v['work_num'];
            $tmp['_create_time'] = $v['_create_time'];
            $data[] = $tmp;
        }
        $header = ['ID','地区','类型','姓名','电话','单位','职务','工作年限','报名时间'];
        FwUtility::exportExcel($data, $header,'报名明细','GCMC报名名单_'.date('Y-m-d'));
    }
    
    public function actionGcmcType(){
        
        $category_info = GcmcType::model()->get_list();
        $this->render('gcmc_type',array('category_info' => $category_info));
    }
    
     //新建一级分类、新建子分类、修改分类，id为空时新增,否则为修改
    public function actionCreate_or_edit_category(){
        $msgNo = 'Y';
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            $parent_id = trim(Yii::app()->request->getParam('parent_id'));
            $category_name = trim(Yii::app()->request->getParam('category_name'));
            $sort = Yii::app()->request->getParam('sort');
            if(empty($category_name)){
                throw new Exception(1023);
            }
            $data = array('filiale_id'=>$this->filiale_id, 'parent_id' => $parent_id, 'name' => $category_name, '_delete'=>0);
            if(empty($id)){
                //查询分类名称是否已存在
                $count = GcmcType::model()->getCount($data);
                if( $count ){
                    throw new Exception(1026);
                }
                if($sort===''){
                    $sort = GcmcType::model()->get_big_sort($parent_id);
                }
                $data['sort'] = $sort;
                $insert = GcmcType::model()->createCategory($data);
                if(!$insert){
                    throw new Exception(1);
                }
            }else{
                $data['id!'] = $id;
                $count = GcmcType::model()->getCount($data);
                if( $count ){
                    throw new Exception(1026);
                }
                $category = GcmcType::model()->findByPk($id);
                if(empty($category)){
                    throw new Exception(1025);
                }else{
                    $up = $category->updateCategory(array( 'name' => $category_name, 'sort'=>$sort ));
                    if(!$up){
                        throw new Exception(1);
                    }
                }
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //删除分类
    public function actionDel_category(){
        $msgNo = 'Y';
        try{
            $id = intval(Yii::app()->request->getParam('id'));
            if(empty($id)){
                throw new Exception('3');
            }
            $category = GcmcType::model()->findByPk($id);
            if( $category['parent_id']==0 ){ //.一级分类
                $CategoryCount = GcmcType::model()->getCount( 
                    array(
                        'parent_id'=>$id,
                        '_delete'=>0) );
                if( $CategoryCount ){
                    throw new Exception(1027);
                }else{
                    $productCount = GcmcCourse::model()->getCount( array('type_parent_id'=>$id,'status!'=>0) );
                    if( $productCount ){
                        throw new Exception(1028);
                    }
                    $GcmcCourseIntroduceCount = GcmcCourseIntroduce::model()->getCount( array('type_parent_id'=>$id,'status!'=>0) );
                    if( $GcmcCourseIntroduceCount ){
                        throw new Exception(1028);
                    }
                    $up = $category->updateCategory(array( '_delete' => 1));
                    if(!$up){
                        throw new Exception(1);
                    }
                }
            }else{ //二级分类
                $productCount = GcmcCourseIntroduce::model()->getCount( array('type_id'=>$id,'status'=>1) );
                if( $productCount ){
                    throw new Exception(1028);
                }
                $up = $category->updateCategory(array( '_delete' => 1));
                if(!$up){
                   throw new Exception(1);
                }
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    /*
     * 新闻列表
     */
    public function actionNews(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('news_list', array('getCityList'=>$getCityList));exit;
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
        $endtime        = trim(Yii::app()->request->getParam( 'endtime' ));
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
		$con = ['status!'=>0];
        if( $filiale_id!=BRANCH_ID ){
             $con['filiale_id'] = $filiale_id;
        }

        if($search_content){
            $con['title'] = $search_content;
        }
        if( $starttime && $endtime ){
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
       
        $list = GcmcNews::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 创建新闻
     */
    public function actionNewsAdd(){
		$msgNo = 'Y';
        $type	= trim(Yii::app()->request->getParam( 'type' ));
		$id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = $experts_list['data'] = [];
        if(empty($_POST)){
			$getCityList = ServiceRegion::model()->getCityList();
			if( $id ){
				$data = GcmcNews::model()->findByPk($id); //echo "<pre>"; print_r($data);die;
            }else{
                $data = array(
                    'id' => 0,
                    'filiale_id' => 0,
                    'province_code' => 0,
                    'title' => '',
                    'province_code' => 0,
                    'image' => '',
                    'sort' => 0,
                    'content' => '',
                    'elite' => 0
                );
            }
			$renderdata = array(
				'id'=>$id,
				'getCityList'=>$getCityList, 
				'data' => $data,
			);
            $this->render('news_add',$renderdata);
			exit;
        }
        $province_code  = intval(Yii::app()->request->getParam( 'province_code' ));
        $filiale_id = $this->filiale_id;
        if($this->filiale_id==BRANCH_ID){
            $filiale_id = ServiceRegion::model()->getProvinceToFiliale($province_code);
        }
		$title = trim(Yii::app()->request->getParam( 'title' ));
		$content = trim(Yii::app()->request->getParam( 'content' ));
        $sort = intval(Yii::app()->request->getParam( 'sort' ));
        $image_hide = Yii::app()->request->getParam( 'image_hide' ); //是否有图片
		try {
			if($id){
				$data['id'] = $id;
            }else{
                $data['user_id'] = $this->user_id;
                $data['status'] = 1;
            }
            $data['filiale_id'] = $filiale_id;
            $data['province_code'] = $province_code;
            $data['title'] = $title;
            $data['content'] = $content;
            $data['sort'] = $sort;
                   
            //上传封面图
            if(!$image_hide){
                if(empty($_FILES)){
                    throw new Exception(1018);
                }
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
                $path = '/uploads/gcmc/News/'.$filiale_id.'/';
                $upload->set('path',$path);
                $image_path = $upload->uploadFile($key[0]);
                $getErrorMsg = $upload->getErrorMsg();
                if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                    throw new Exception('1014');
                }
                $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
                $upstatus = FwUtility::createSmallImage($image_path, 250,140);
                if($upstatus!='Y'){
                    unlink($image_all_path);
					$msgNo = 1014;
					$this->msg[$msgNo] = $upstatus;
                    throw new Exception($msgNo);
                }
            }else{
                $image_path = $image_hide;
            }
            $data['image'] = $image_path;
    		$save = GcmcNews::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
	*	更改新闻状态
	*/
	public function actionUpNewsStatus(){
		$msgNo = 'Y';
        $id	= intval(Yii::app()->request->getParam( 'id' ));
		$edit_field	= trim(Yii::app()->request->getParam( 'edit_field' ));
		$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(!$id || !in_array($edit_field, ['elite','status']) ){
				throw new Exception(0);
			}
			$data['id'] = $id;
			$data[$edit_field] = $edit_val;
			$save = GcmcNews::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
	}
    
    /*
     * 案例管理
     */
    public function actionCase(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('case_list', array( 'getCityList'=>$getCityList));exit;
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
        $endtime        = trim(Yii::app()->request->getParam( 'endtime' ));
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
		$con = ['status!'=>0];
        if( $filiale_id!=BRANCH_ID && !empty($filiale_id) ){
             $con['filiale_id'] = $filiale_id;
        }

        if($search_content){
            $con['title'] = $search_content;
        }
        if( $starttime && $endtime ){
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
       
        $list = GcmcCase::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 编辑案例
     */
    public function actionEditCase(){
		$msgNo = 'Y';
        $type	= trim(Yii::app()->request->getParam( 'type' ));
		$id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = $experts_list['data'] = [];
        if(empty($_POST)){
			$getCityList = ServiceRegion::model()->getCityList();
			if( $id ){
				$data = GcmcCase::model()->findByPk($id); //echo "<pre>"; print_r($data);die;
            }else{
                $data = array(
                    'id' => 0,
                    'author' => '',
                    'title' => '',
                    'image' => '',
                    'content' => ''
                );
            }
			$renderdata = array(
				'id'=>$id,
				'getCityList'=>$getCityList, 
				'data' => $data,
			);
            $this->render('case_edit',$renderdata);
			exit;
        }

		$title = trim(Yii::app()->request->getParam( 'title' ));
        $author = trim(Yii::app()->request->getParam( 'author' ));
		$content = trim(Yii::app()->request->getParam( 'content' ));
        $image_hide = Yii::app()->request->getParam( 'image_hide' ); //是否有图片
		try {
			$data['id'] = $id;
            $data['author'] = $author;
            $data['title'] = $title;
            $data['content'] = $content;
 
            //上传封面图
            if(!$image_hide){
                if(empty($_FILES)){
                    throw new Exception(1018);
                }
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
                $path = '/uploads/gcmc/Case/'.date('Ym').'/';
                $upload->set('path',$path);
                $image_path = $upload->uploadFile($key[0]);
                $getErrorMsg = $upload->getErrorMsg();
                if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                    throw new Exception('1014');
                }
                $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
                $upstatus = FwUtility::createSmallImage($image_path, 290,180);
                if($upstatus!='Y'){
                    unlink($image_all_path);
					$msgNo = 1014;
					$this->msg[$msgNo] = $upstatus;
                    throw new Exception($msgNo);
                }
            }else{
                $image_path = $image_hide;
            }
            $data['image'] = $image_path;
    		$save = GcmcCase::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 设置案例状态
     */
    public function actionSetCaseStatus(){
        $msgNo = 'Y';
        $ids	= Yii::app()->request->getParam( 'ids' );
		$edit_field	= trim(Yii::app()->request->getParam( 'edit_field' ));
		$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(empty($ids) || !in_array($edit_field, ['status']) || !in_array($edit_val, [0,1,3]) ){
				throw new Exception(0);
			}
			$up = GcmcCase::model()->updateByPk(
                $ids,
                array('status'=>$edit_val,'_update_time'=>date('Y-m-d H:i:s'))
            );
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    
    /*
     * 试听列表
     */
    public function actionAudition(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('audition_list', array( 'getCityList'=>$getCityList));exit;
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
        $endtime        = trim(Yii::app()->request->getParam( 'endtime' ));
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
		$con = [];
        if( $filiale_id!=BRANCH_ID ){
             $con['filiale_id'] = $filiale_id;
        }

        if($search_content){
            $con['title'] = $search_content;
        }
        if( $starttime && $endtime ){
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
       
        $list = GcmcAudition::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 话题管理
     */
    public function actionTopic(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('topic_list', array( 'getCityList'=>$getCityList));exit;
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
        $endtime        = trim(Yii::app()->request->getParam( 'endtime' ));
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
		$con['status'] = 1;
        if( $filiale_id!=BRANCH_ID ){
             $con['filiale_id'] = $filiale_id;
        }

        if($search_content){
            $con['title'] = $search_content;
        }
        if( $starttime && $endtime ){
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
       
        $list = GcmcTopic::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /**
     * 设置话题状态
     */
    public function actionUpTopicStatsu(){
        $msgNo = 'Y';
        $id	= Yii::app()->request->getParam( 'id' );
		$edit_field	= trim(Yii::app()->request->getParam( 'edit_field' ));
		//$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(empty($id) || !in_array($edit_field, ['status','elite','top']) ){
				throw new Exception(0);
			}
			GcmcTopic::model()->setTopElite($id,$edit_field);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     *  互动管理
     */
    public function actionComments(){
        //栏目id
        $column_id   	= intval(Yii::app()->request->getParam( 'column_id' ));
        //栏目类型
        $column_type   	= intval(Yii::app()->request->getParam( 'column_type' ));
        $column_type = in_array($column_type,[1,2,3,4]) ? $column_type : 0;
        
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('comments_list', array('column_id'=>$column_id,'column_type'=>$column_type, 'getCityList'=>$getCityList));exit;
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
        $endtime        = trim(Yii::app()->request->getParam( 'endtime' ));
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
        
		$con['status'] = 1;
        if($column_id){
            $con['column_id'] = $column_id;
            $con['column_type'] = $column_type;
        }else{
            $con['column_type!'] = 4;
        }
        if( $filiale_id!=BRANCH_ID ){
             $con['filiale_id'] = $filiale_id;
        }

        if($search_content){
            $con['title'] = $search_content;
        }
        if( $starttime && $endtime ){
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
     
        $list = GcmcComment::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    
     /*
     * 更改评论状态（删除）
     */
    public function actionSetCommentsStatus(){
        $msgNo = 'Y';
        $ids	= Yii::app()->request->getParam( 'ids' );
		$edit_field	= trim(Yii::app()->request->getParam( 'edit_field' ));
		$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(empty($ids) || !in_array($edit_field, ['status']) || !in_array($edit_val, [0]) ){
				throw new Exception(0);
			}
			$up = GcmcComment::model()->updateByPk(
                $ids,
                array('status'=>$edit_val,'_update_time'=>date('Y-m-d H:i:s'))
            );
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 评分列表
     */
    public function actionScorelist(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('score_list', array( 'getCityList'=>$getCityList));exit;
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
		
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $table = Yii::app()->request->getParam( 'table' );
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
        
		$con = ['status!'=>0];
        if( $filiale_id!=BRANCH_ID ){
             $con['filiale_id'] = $filiale_id;
        }

        if($search_content){
            $con['title'] = $search_content;
        }
        $con['_score'] = 1;
        switch ($table) {
            case 'course_introduce':
                $list = GcmcCourseIntroduce::model()->getlist($con, $ord, $field, $limit, $page);
            break;
            case 'case':
                $list = GcmcCase::model()->getlist($con, $ord, $field, $limit, $page);
            break;
            case 'news':
                $list = GcmcNews::model()->getlist($con, $ord, $field, $limit, $page);
            break;
            default:
                $list = GcmcCourseIntroduce::model()->getlist($con, $ord, $field, $limit, $page);
            break;
        }                
        
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 导出到Excel
     */
    public function actionDownExcel(){
        $column = trim(Yii::app()->request->getParam( 'column' ));
        $starttime   	= trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime        = trim(Yii::app()->request->getParam( 'endtime' ));
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $filiale_id     = $this->filiale_id==BRANCH_ID ? $filiale_id : $this->filiale_id;
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        
		$con = ['status!'=>0];
        if( $filiale_id!=BRANCH_ID ){
             $con['filiale_id'] = $filiale_id;
        }
        if($search_content){
            $con['title'] = $search_content;
        }
        if( $starttime && $endtime ){
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
        
        $filenameArr = array('course'=>'课程列表', 'experts'=>'讲师列表', 'news'=>'新闻列表', 'comments'=>'话题列表', 'score'=>'评分列表');
        switch ($column) {
            case 'course':
                $list = GcmcCourse::model()->getlist($con, 'desc', 'id', 10000, 0);
                $header = array('id'=>'ID', 'city_name'=>'地区', 'title'=>'课程名称', 'experts'=>'讲师', 'address'=>'地点', '_create_time'=>'时间');
            break;
            case 'experts':
                $list = GcmcExperts::model()->getlist($con, 'desc', 'id', 10000, 0);
                $header = array('id'=>'ID', 'city_name'=>'地区', 'name'=>'姓名', 'member_user_name'=>'广联云账号', 'introduce'=>'简介', 'show_location_txt'=>'显示位置', 'column_val'=>'排序值');
            break;
            case 'news':
                $list = GcmcNews::model()->getlist($con, 'desc', 'id', 10000, 0);
                $header = array('id'=>'ID', 'city_name'=>'地区', 'title'=>'标题', '_create_time'=>'创建时间', 'location'=>'发表位置', 'sort'=>'排序值');
            break;
            case 'comments':
                $con['column_type!'] = 4;
                $list = GcmcComment::model()->getlist($con, 'desc', 'id', 10000, 0);
                $header = array('id'=>'ID', 'member_user_name'=>'学员账户', 'mobile'=>'学员手机号', 'email'=>'学员邮箱', 'column_title'=>'标题', 'city_name'=>'地区', 'comment'=>'评论内容', 'column_type'=>'分类', 'type'=>'类别', '_create_time'=>'互动时间');
            break;
            case 'score':
                $table = trim(Yii::app()->request->getParam( 'table' ));
                $con['_score'] = 1;
                switch ($table) {
                    case 'course_introduce':
                        $list = GcmcCourseIntroduce::model()->getlist($con, 'desc', 'id', 10000, 0);
                        $filenameArr[$column] .= '_课程';
                    break;
                    case 'case':
                        $list = GcmcCase::model()->getlist($con, 'desc', 'id', 10000, 0);
                        $filenameArr[$column] .= '_案例';
                    break;
                    case 'news':
                        $list = GcmcNews::model()->getlist($con, 'desc', 'id', 10000, 0);
                        $filenameArr[$column] .= '_新闻';
                    break;
                    default:
                        $list = GcmcCourseIntroduce::model()->getlist($con, 'desc', 'id', 10000, 0);
                        $filenameArr[$column] .= '_课程';
                    break;
                }  
                $header = array('id'=>'ID', 'city_name'=>'地区', 'classify'=>'课程分类', 'title'=>'标题', 'type'=>'类型', 'total'=>'评论人数', 'score'=>'分数', 'stars5'=>'五星', 'stars4'=>'四星', 'stars3'=>'三星', 'stars2'=>'二星', 'stars1'=>'一星');
            break;
        } 
        $data = [];
        foreach($header as $key=>$tabletitle){
            foreach($list['data'] as $k=>$v){
                $data[$k][$key] = $v[$key];
            }
        }
        //echo "<pre>";print_r($data);
        FwUtility::exportExcel($data, $header,$filenameArr[$column],$filenameArr[$column].'_'.date('Y-m-d'));
        
    }
}




     
        