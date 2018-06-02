<?php
use application\models\Activity\Activity;
use application\models\Activity\ActivityExtension;
use application\models\Activity\ActivityContent;
use application\models\Activity\ActivityComment;
use application\models\Activity\ActivityShare;
use application\models\Activity\ActivityCollect;
use application\models\Activity\ActivityLunbotu;
use application\models\Activity\ActivityParticipate;
use application\models\Activity\ActivitySetDrawLots;
use application\models\Template\Template;
use application\models\ServiceRegion;
use application\models\SmsTask;
use application\models\Research\Research;
class ActivityController extends Controller
{

    private $msg = array(
            'Y' => '成功',
            1 => '操作数据库错误',
            2 => '数据已经存在',
            3 => '数据不存在',
            4 => '不可为空',
            5 => '不可过长',
            6 => '权限不足',
            1001 => '手机号错误',
            1002 => '邮箱错误',
            1003 => '管理员不能为空',
            1004 => '密码不能为空',
            1005 => '请选择角色',
            1006 => '参数错误',
            1007 => '参加人数不能为0',
            1008 => '标题不能为空',
            1009 => '开始时间不能为空',
            1010 => '结束时间不能为空',
            1011 => '嘉宾讲师不能为空',
            1012 => '活动地点不能为空',
            1013 => '详细内容不能为空',
            1014 => '图片上传错误，请重试',
            1015 => '上传文件过大',
            1016 => '地区不能不选',
            1017 => '此手机号已存在',
            1018 => '请按要求上传邀请函模板图片',
            1019 => '图片格式不支持',
            1020 => '图片宽高过小，请上传合适宽高的图片',
            1021 => '活动已结束',
            1022 => '此活动已被管理员删除，请刷新页面重试',
            1023 => '此活动尚未完善，请先补充活动内容再进行复制',
            1024 => '此活动尚未发布，无法推至首页',
            1025 => '报名截止时间不可晚于活动开始时间',
            1026 => '人数限制不可低于已报名人数',
            1027 => '报名截止时间不可晚于活动结束时间',
            1028 => '大括号“{}”及其中内容不可修改',
            1029 => '开始时间已过，不可再修改配置',
            1030 => '当前分支已存在相同排序位，请先将其设置为默认',
            1031 => '结束时间必须晚于开始时间',
            1032 => '请上传主图'
    );

    //定义活动类型
    public $activity_type = array(
        0 => '全部',
        1 => '知识',
        2 => '产品',
        3 => '粉丝',
        4 => '其他'
    );
    //定义收集信息项
    public $shoujixnxi = array(
        'realname' => '真实姓名',
        'mobile' => '手机号码',
        'share_code' => '分享码',
        'qq' => 'QQ号码',
        'email' => '邮箱',
        'dongle' => '加密锁号',
        'company' => '单位全称',
        'company_type' => '单位性质',
        'position' => '职位',
        'major' => '专业',
        'work_num' => '从业年限',	
        'soft' => '已有软件',
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


    //活动列表
    public function actionActivity_list(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('activity_list',array('getCityList'=>$getCityList));exit;
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
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); 
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
        
        if($search_type==='title'){
                $con['title'] = $search_content;
        }elseif($search_type==='id'){
                $con['id'] = intval($search_content);
        }elseif($search_type==='num'){
                $con['num'] = $search_content;
        }
		if($search_content=='#最近操作#'){
			$con = [];
			$user_id = 'activity_'.Yii::app()->user->user_id;
			$happenlately = new ARedisHash("happenlately");
			$con['id'] = isset($happenlately->data[$user_id]) ? intval($happenlately->data[$user_id]) : 0;
		}
        $list = Activity::model()->getlist($con, $ord, $field, $limit, $page);
        //print_r($list);
        echo CJSON::encode($list);
    }


    //上传图片 （活动封面）
    public function actionActivityUploadPhoto(){
        $msgNo = 'Y';
        $type = trim(Yii::app()->request->getParam( 'type' )); //是否为邀请函
        
        try{
            $upload = new Upload();
            if(empty($_FILES)){
                throw new Exception('3');
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
            
            $image_path = $upload->uploadFile($key[0]); //image为上传框name
            $getErrorMsg = $upload->getErrorMsg();
            if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                throw new Exception('1014');
            }
            $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
            $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
            
            if($imagetype=='.jpg' || $imagetype=='.jpeg') {
                $model_im = @imagecreatefromjpeg($image_all_path);
            }elseif($imagetype=='.png') {
                $model_im = @imagecreatefrompng($image_all_path);
            }elseif($imagetype=='.gif') {
                $model_im = @imagecreatefromgif($image_all_path);
            }else{
                unlink($image_all_path);
                throw new Exception('1019');
            }
            if(!$model_im){
                unlink($image_all_path);
                throw new Exception('1019');
            }
            if($type==='yqh'){
                $new_img_w = 610;
                $new_img_h = 430;
            }else{
                $new_img_w = 650;
                $new_img_h = 400;
            }
            $model_im_width = imagesx($model_im); //模板图片宽度
            $model_im_height = imagesy($model_im); //高度
            if($model_im_width<$new_img_w || $model_im_height<$new_img_h){
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

				if($type!='yqh'){ //生成小封面图供H5使用
					$new_img_w = 350;
					$new_img_h = 200;
					$new_img = imagecreatetruecolor($new_img_w, $new_img_h);
					//copy部分图像并调整
					imagecopyresampled( $new_img ,$model_im ,0 , 0 , 0 ,0 ,$new_img_w ,$new_img_h ,$model_im_width ,$model_im_height );
					$image_all_path = $newImagePath = str_replace($imagetype, '_h5'.$imagetype, $image_all_path);
					if($imagetype=='.png') {
						imagepng($new_img, $image_all_path);
					}else{
						imagejpeg($new_img, $image_all_path);
					}
					imagedestroy($new_img);
				}
            }
            $this->msg[$msgNo] = $image_path;
            
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }

        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }



    //活动信息本信息
    public function actionActivityAddBasic(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        //$this->filiale_id = 77;
        $province_id = ServiceRegion::model()->getRegionIdByBranch($this->filiale_id);
        $region_name = ServiceRegion::model()->getRegionName($province_id);
        
        $data = array();
        if($activity_id){
            $data = Activity::model()->findbypk($activity_id);
			if( $data ){
				$data = $data->attributes;
				$ActivityExtension = ActivityExtension::model()->findByPk($activity_id);
				if( empty($ActivityExtension) ){
					ActivityExtension::model()->activityExtensionSave(array('id'=>$activity_id));
				}
				$ActivityExtension = ActivityExtension::model()->findByPk($activity_id);
				$data['address_coordinates'] = empty($ActivityExtension)?'':$ActivityExtension->address_coordinates;
			}
        }else{
            $data = array('id' => 0,'filiale_id' => '','category_id' => '','province_code' => $province_id,'city_code' => '',
                'county_code' => '','address' => '','title' => '','activity_head' => '','activity_head_tel' => '',
                'venue_head' => '','venue_head_tel' => '','venue_cost' => '','venue_pnum' => '','outline' => '',
                'image' => '','starttime' => '','endtime' => '','online_activity' => 0,'bm_endtime' => '',
                'lecturer' => '','limit_number' => '','product_name' => '','product_type' => '','isset_yaoqinghan' => '',
                'yaoqinghan_image' => '','bm_huaxiao' => '', 'requirement' => '','address_coordinates'=>'',
                'template_type ' => 0,'is_show_list' => 1);
        }
        
        //如果用户的分支ID为QG_BRANCH_ID,则可以将该资料创建到指定省或全国
        if ($this->filiale_id == QG_BRANCH_ID) {
            $province_list = ServiceRegion::model()->getProvinceArr();
            unset($province_list[QG_BRANCH_ID]);
            $province_list = array(QG_BRANCH_ID=>'全国')+$province_list;
        } else {
            //分支用户只能将资料创建到所属省
            $province_list = array(QG_BRANCH_ID=>'全国',$province_id=>$region_name);
        }
        $this->render('activity_add1', array('activity'=>$data,'activity_id'=>$activity_id,'province_list'=>$province_list));
    }
    
    //报名
    public function actionActivityAddBm(){

        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        if(isset($_POST['save'])){
                $data['id'] = $activity_id;
				$is_dongle = Yii::app()->request->getParam( 'is_dongle' );
				$is_dongle = $is_dongle ? 1 : 0;
                $keybox = Yii::app()->request->getParam( 'keybox' );
                $is_check = Yii::app()->request->getParam( 'is_check' );
                $keybox['realname'] = 'on';
                $keybox['mobile'] = 'on';
                $requirement = array('realname'=>1,'mobile'=>1);
                //print_r($keybox);die;
                foreach($keybox as $k=>$v){
                        if(in_array($k, array('text1','text2','text3','text4','text5'))){
                                $requirement[$k] = $is_check[$k].'|'.$_POST[$k];
                        }else{
                                $requirement[$k] = $is_check[$k];
                        }
                }
                $data['requirement'] = serialize($requirement);
                $id = Activity::model()->activitySave($data);
                if($id){
					ActivityExtension::model()->updateByPk($activity_id, array('is_dongle'=>$is_dongle));
                    OperationLog::addLog(OperationLog::$operationActivity, 'edit', '活动报名设置', $activity_id, array(), array());
                    echo $this->encode('Y', $this->msg['Y']);
                }
                die;
        }else{
                $activity = Activity::model()->findbypk($activity_id);
				$ActivityExtension = ActivityExtension::model()->findbypk($activity_id);
				$is_dongle = $ActivityExtension['is_dongle'];
                $data = $activity['requirement'];
                if(empty($activity->template_type)){
                    unset($this->shoujixnxi['share_code']);
                }
                $data = unserialize($data);
        }

        $this->render('activity_add2', array('activity_id'=>$activity_id,'activity'=>$data, 'is_dongle'=>$is_dongle));
    }
    //设置邀请函（废弃）
    public function actionActivityAddYqh(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        if(isset($_POST['save'])){
            $oldactivity = Activity::model()->findByPk($activity_id,array('select'=>array('id','isset_yaoqinghan','yaoqinghan_image','yaoqinghan_note')));
            
            $isset_yaoqinghan = Yii::app()->request->getParam( 'isset_yaoqinghan' );
            $isset_yaoqinghan = $isset_yaoqinghan?1:0;
            $data['id'] = $activity_id;
            $data['isset_yaoqinghan'] = $isset_yaoqinghan;
            if($data['isset_yaoqinghan']){
                $data['yaoqinghan_note'] = CHtml::encode(Yii::app()->request->getParam( 'yaoqinghan_note' ));
                $data['yaoqinghan_image'] = Yii::app()->request->getParam( 'activity_image_path' );
            }

            $cid = Activity::model()->activitySave($data);
            if($cid){
                echo $this->encode('Y', $this->msg['Y']);
                OperationLog::addLog(OperationLog::$operationActivity, 'edit', '活动邀请函设置', $activity_id, $oldactivity['attributes'], $data);
            }else{
                echo $this->encode(1, $this->msg['1']);
            }
        }else{
            $activity = Activity::model()->findbypk($activity_id);
            $this->render('activity_add3', array('activity_id'=>$activity_id,'activity'=>$activity, 'endtime'=>$activity['endtime']));
        }
    }
    
     //设置邀请函
    public function actionSetInvitation(){
        $msgNo = 'Y';
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $activity = Activity::model()->findByPk($activity_id)->attributes;

        $activity['all_address'] = ServiceRegion::model()->getRedisCityList($activity['province_code']);
        $activity['all_address'] .= ' '.ServiceRegion::model()->getRedisCityList($activity['city_code']);
        $activity['all_address'] .= ' '.$activity['address'];
        $ActivityExtension = ActivityExtension ::model()->findByPk($activity_id)->attributes;
        $activity['isset_yaoqinghan'] = $ActivityExtension['isset_yaoqinghan'];
        $activity['yaoqinghan_image'] = $ActivityExtension['yaoqinghan_image'];
        $activity['isset_tosign_sms'] = $ActivityExtension['isset_tosign_sms'];
        $activity['start_before_hour'] = $ActivityExtension['start_before_hour'];
        $activity['yaoqinghan_note'] = $ActivityExtension['yaoqinghan_note'];

        $taskdata = SmsTask::model()->get_list(array(
            'column_name'=>'activity',
            'column_id' => $activity_id
        ));
        $default_sms = array(
                'tosign_sms' => array(
                    'id' => 0,
                    'sms_template' => '亲，{您的活动邀请码: '.$activity['num'].'-0000}，时间'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'。请您现场出示并签到，{邀请函详情点击：http://e.fwxgx.com/activity/0000} 。地址：'.$activity['all_address'],
                    'sms_template_not_i' => '亲，您报名的活动“'.$activity['title'].'”，'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'，'.$activity['all_address'].'，请准时参加。'
                ),
                'hour_sms' => array(
                    'id' => 0,
                    'sms_template' => '亲，{您的活动邀请码: '.$activity['num'].'-0000}，时间'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'。请您现场出示并签到，{邀请函详情点击：http://e.fwxgx.com/activity/0000} 。地址：'.$activity['all_address'],
                    'sms_template_not_i' => '亲，您报名的活动“'.$activity['title'].'”，'.date('m月d日H:i',strtotime($activity['starttime'])).'~'.date('m月d日H:i',strtotime($activity['endtime'])).'，'.$activity['all_address'].'，请准时参加。'
                )
            );
        //print_r($default_sms['tosign_sms']['sms_template_not_i']);
        $taskdata = empty($taskdata) ? $default_sms : $taskdata;
        $this->render('activity_add3_new', 
            array(
                'activity_id'=>$activity_id,
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
        $activity = Activity::model()->findByPk($activity_id)->attributes;
        try {
            //活动已开始不可再修改
            if( $activity['starttime']<=date('Y-m-d H:i:s') ){
                throw new Exception(1029);
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
                if( substr_count($tosign_sms_template, '{您的活动邀请码: '.$activity['num'].'-0000}')!==1 ){
                    throw new Exception(1028);//大括号中内容不可修改
                }
                if( substr_count($tosign_sms_template, '{邀请函详情点击：http://e.fwxgx.com/activity/0000}')!==1 ){
                    throw new Exception(1028);
                }
                if( substr_count($tosign_sms_template, '-0000}')!==1 ){
                    throw new Exception(1028);
                }
                if( substr_count($tosign_sms_template, '/0000}')!==1 ){
                    throw new Exception(1028);
                }
            }
            $tosign_sms_id = intval(Yii::app()->request->getParam( 'tosign_sms_id' ));
            //xx小时数
            $start_before_hour = intval(Yii::app()->request->getParam( 'start_before_hour' )) ?  : 24;
            //xx小时发送短信模板
            $timing_sms_template = trim(Yii::app()->request->getParam( 'timing_sms_template' ));
            if($isset_yaoqinghan){
                $timing_sms_template = str_replace(array('｛','｝'), array('{','}'), $timing_sms_template);
                if( substr_count($timing_sms_template, '{您的活动邀请码: '.$activity['num'].'-0000}')!==1 ){
                    throw new Exception(1028);//大括号中内容不可修改
                }
                if( substr_count($timing_sms_template, '{邀请函详情点击：http://e.fwxgx.com/activity/0000}')!==1 ){
                    throw new Exception(1028);//大括号中内容不可修改
                }
                if( substr_count($timing_sms_template, '-0000}')!==1 ){
                    throw new Exception(1028);
                }
                if( substr_count($timing_sms_template, '/0000}')!==1 ){
                    throw new Exception(1028);
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
                throw new Exception(1018);
            }
            $data['id'] = $activity_id;
            $data['isset_yaoqinghan'] = $isset_yaoqinghan;
            $data['yaoqinghan_image'] = $activity_image_path;
            $data['isset_tosign_sms'] = $isset_tosign_sms;
            $data['start_before_hour'] = $isset_hour_sms ? $start_before_hour : 0;
            $data['yaoqinghan_note'] = $yaoqinghan_note;
            
            //插入短信任务表
            $activity_starttime = strtotime($activity['starttime']);
            $send_time = $activity_starttime - $start_before_hour*3600;
            //此时间不可小于当前时间的20分钟后
            if( $data['start_before_hour'] && $send_time-time() < 1200 ){
                $msgNo = 1;
                $this->msg[$msgNo] = '距活动开始时间 '.$activity['starttime'].' 不足'.$start_before_hour.'小时20分钟，系统已来不及提前 '.$start_before_hour.'h 发送。';
                throw new Exception($msgNo);
            }
            $up = ActivityExtension::model()->activityExtensionSave($data);
            if(!$up){
                throw new Exception(1);
            }
            
            if($tosign_sms_id){
                $tData['id'] = $tosign_sms_id;
            }
            $tData['column_name'] = 'activity';
            $tData['filiale_id'] = $activity['filiale_id'];
            $tData['column_id'] = $activity_id;
            $tData['describe'] = '活动报名即发短信模板';
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
            $tData['describe'] = '活动开始前'.$start_before_hour.'h发送短信模板';
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
    
    //活动详情
    public function actionActivityAddYq(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $activity_content = trim(Yii::app()->request->getParam( 'activity_content' ));
        $outline = strip_tags($activity_content);
        $outline = str_replace(array(' ','&nbsp;'), array('',''),$outline);
        $outline = cutstr($outline,300,'');
        $activity_data = Activity::model()->findByPk($activity_id,array('select'=>array('endtime','template_type')));
        
        if(isset($_POST['save'])){
            $status = intval(Yii::app()->request->getParam( 'status' ));
            $data['id'] = $activity_id;
            $data['outline'] = $outline;
            if($status!==3){                    
                $data['status'] = $status===1?1:2;
				if($data['status']==2){
					$data['rec_index'] = 0;
					$data['national_rec_index'] = 0;
				}
            }
            $id = Activity::model()->activitySave($data);

            if($id){
                $content['activity_id'] = $id;
                $content['content'] = \CHtml::encode($activity_content);
                $cid = ActivityContent::model()->activityContentUpdate($content);
                if($cid){
                    $bakobj = new ARedisHash('activity_content_draft_bak_' . $activity_id);
                    $bakobj->clear();
                    echo $this->encode('Y', $this->msg['Y']);
                    
                }else{
                    echo $this->encode(1, $this->msg['1']);
                }
            }else{
                echo $this->encode(1, $this->msg['1']);
            }
            die;
        }
        $activity = array();
        if($activity_id && !isset($_POST['save'])){
            $activity = ActivityContent::model()->findByAttributes(array('activity_id'=>$activity_id));
            $bakobj = new ARedisHash('activity_content_draft_bak_' . $activity_id);
            //rdis暂存的内容
            $bak = $bakobj->data;
            if( !empty($bak) && !empty(trim($bak['data'])) ){
                $activity['content'] = $bak['data'];
            }
        }
        $this->render('activity_add4', array(
            'activity_id'=>$activity_id,
            'activity'=>$activity,
            'endtime'=>$activity_data['endtime'], 
            'template_type'=>$activity_data['template_type'])
        );
    }
    
    //自由模板第五步后跳转的页面
    public function actionActivity_send(){
        $msgNo = 'Y';
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $status = Yii::app()->request->getParam( 'status' );
        if($status){
            try{
                if(empty($activity_id) || !in_array($status, [1,2])){
                    throw new Exception(1006);
                }
                if($this->filiale_id!=BRANCH_ID){
                    $activity = Activity::model()->findByPk($activity_id);
                    if(empty($activity)){
                        throw new Exception(1022);
                    }
                    if($activity['filiale_id']!=$this->filiale_id){
                        throw new Exception(6);
                    }
                }
                $data['id'] = $activity_id;
                $data['status'] = $status;
                $up = Activity::model()->activitySave($data);
                if(!$up){
                    throw new Exception(1);
                }
                
            } catch(Exception $e){
                $msgNo = $e->getMessage();
            }
            echo $this->encode($msgNo, $this->msg[$msgNo]);
        }else{
            $this->render('activity_send', array('activity_id'=>$activity_id));
        }
    }

    //活动报道
    public function actionActivityReports(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));

        $activity = array();
        if(isset($_POST['save'])){
            $id = intval(Yii::app()->request->getParam( 'id' ));
            $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
            
            $old = ActivityContent::model()->findByPk($id);
            
            $activity_content = trim(Yii::app()->request->getParam( 'activity_content' ));
            
            $reports['activity_id'] = $activity_id;
            $reports['reports'] = $activity_content;
            $up = ActivityContent::model()->activityContentEdit($reports);
           
            if($up){
                $photo_num = substr_count($activity_content,'<img src=');
                $extension['id'] = $activity_id;
                $extension['photo_num'] = $photo_num;
                ActivityExtension::model()->activityExtensionSave($extension);
                $bakobj = new ARedisHash('activity_reports_draft_bak_' . $activity_id);
                $bakobj->clear();
                echo $this->encode('Y', $this->msg['Y']);
                
                OperationLog::addLog(OperationLog::$operationActivity, 'edit', '修改活动报道', $activity_id, $old['reports'], array('reports'=>$activity_content));
            }else{
                echo $this->encode(1, $this->msg['1']);
            }
            exit;
        }else{
            $title = Activity::model()->findByPk($activity_id,array('select'=>array('title')));
            $title = $title->title;
            $activity = ActivityContent::model()->findByAttributes(array('activity_id'=>$activity_id));
            if(empty($activity)){
                $activity['id'] = 0;
                $activity['activity_id'] = $activity_id;
                $activity['reports'] = '';
            }else{
                $activity = $activity['attributes'];
            }
            $activity['title'] = $title;
            
            $bakobj = new ARedisHash('activity_reports_draft_bak_' . $activity_id);
            //rdis暂存的内容
            $bak = $bakobj->data;
            if( !empty($bak) && !empty(trim($bak['data'])) ){
                $activity['reports'] = $bak['data'];
            }
        }

        $this->render('activity_reports', array('activity_id'=>$activity_id,'activity'=>$activity));
    }

    //弹出邀请函模板
    public function actionShowYqhModel(){
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
        $activity = Activity::model()->findByPk($activity_id);
        $activity = $activity->attributes;
       //$ss =  ServiceRegion::model()->getCityByProvince(11);
        $activity['province_code'] = ServiceRegion::model()->getRegionName($activity['province_code']);
        $activity['city_code'] = ServiceRegion::model()->getRegionName($activity['city_code']);
        $this->render('showyqhmodel',array('activity'=>$activity));
    }
    public function actionShowYulan(){
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
        $reports = Yii::app()->request->getParam( 'reports' );
        $type = Yii::app()->request->getParam( 'type' );
        if($type!='reports' && $type!='content'){
            $this->render('show_yulan',array('reports'=>$reports));die;
        }
        if(isset($_POST['save'])){
            ActivityContent::model()->activityContentEdit(array('activity_id'=>$activity_id,$type=>$reports));
        }else{
            $res = ActivityContent::model()->findByAttributes(array('activity_id'=>$activity_id));
            //print_r($res);
            $this->render('show_yulan',array('reports'=>$res[$type]));
        }
        
    }


    //活动签到
    public function actionActivityQiandao(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $activity = Activity::model()->findByPk(
            $activity_id,
            array('select'=>array('id','num','title','filiale_id','starttime','endtime','address','qr_code_image'))
        );

        //生成活动的二维码供手机登录
        if(empty($activity['qr_code_image'])){
            $data = (YII_ENV=='dev'?'http://10.129.8.154':EHOME).'/index.php?r=sign/login&user_id='.$this->user_id.'&user_name='.$this->user_name.'&activity_id='.$activity_id;
            $qr_code_image = FwUtility::generateQrcodeCode($data);
            Activity::model()->updateByPk($activity_id,array('qr_code_image'=>$qr_code_image));
        }else{
            $qr_code_image = $activity['qr_code_image'];
        }
        $this->render('activity_qiandao',array('activity'=>$activity,'activity_id'=>$activity_id,'ewmname'=>$qr_code_image));
    }
    
    //获取报名信息
    public function actionGetParticipate(){
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
        $invite_code = trim(Yii::app()->request->getParam( 'invite_code' ));
        $con['activity_id'] = $activity_id;
        $con['status!'] = 2;
        if(strlen($invite_code)==4){
            $con['invite_code'] = $invite_code;
        }elseif(strlen($invite_code)==11){
            $con['mobile'] = $invite_code;
        }else{
            $this->_showMsg($this->msg['1006'],1006);
       }
      
        $data = ActivityParticipate::model()->getlist($con, 'desc', 'id', '1',0);
        if(!$data['data']){
            $this->_showMsg($this->msg['3'],3);
        }
        $extend = unserialize($data['data'][0]['extend']);

        $res = array(
            'id'=> $data['data'][0]['id'],
            'type' => ($data['data'][0]['type']==2 || $data['data'][0]['type']==1)?'已签到':'未签到',
            'realname' => $data['data'][0]['realname'],
            'mobile' => $data['data'][0]['mobile'],
            'company' => $data['data'][0]['company'],
        );
        $this->_showMsg($res,'Y');
    }


    //报名签到
    public function actionSetParticipateSignin(){
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));  
        $participate_id = intval(Yii::app()->request->getParam( 'participate_id' )); //为0属于现场新建
        $realname = trim(Yii::app()->request->getParam( 'realname' ));
        $mobile = trim(Yii::app()->request->getParam( 'mobile' ));
        $company = trim(Yii::app()->request->getParam( 'company' ));
        if(!$activity_id){
            $this->_showMsg($this->msg['1006'],'1006');
        }
        
        if(strlen($realname)>20){
            $this->_showMsg($this->shoujixnxi['realname'].$this->msg['5'],'realname');
        }
        if(!$mobile){
            $this->_showMsg($this->shoujixnxi['mobile'].$this->msg['4'],'mobile');
        }

        $fn = new Fn();
        if(!isMobilePhone($mobile) ){
            $this->_showMsg($this->msg['1001'],'mobile');
        }
        $count = 0;
        if($participate_id===0){
            $count = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'mobile'=>$mobile,'status!'=>2));
            $data['type'] = 1; //现场报名
            $data['user_id'] = $this->user_id;
            $data['user_name'] = $this->user_name;
            $data['activity_id'] = $activity_id;
            $data['signin_time'] = date('Y-m-d H:i:s');
        }else{
            $count = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'mobile'=>$mobile,'status!'=>2,'id!'=>$participate_id));
            $data['id'] = $participate_id;
            $data['type'] = 2;//签到
            $res = ActivityParticipate::model()->findByPk($participate_id);
            if(empty($res['signin_time']) || $res['signin_time']==0){
                $data['signin_time'] = date('Y-m-d H:i:s');
            }  
            if($res['status']==2){
                $this->_showMsg($this->msg['3'],'notdata');
            }
        }
        if($count!=0){
            $this->_showMsg($this->msg['1017'],'mobile');
        }

        if($company && strlen($company)>150){
            $this->_showMsg($this->shoujixnxi['company'].$this->msg['5'],'company');
        }
        $data['company'] = $company;

        $data['realname'] = $realname;
        $data['mobile'] = $mobile;
        $data['controller_user_id'] = $this->user_id;

        $id = ActivityParticipate::model()->ActivityRequirementSave($data);
        if($id){
            $logtext = $participate_id==0 ? '活动现场报名' : '活动报名签到';
            OperationLog::addLog(OperationLog::$operationActivity, 'add', $logtext, $activity_id, array(), $data);
            
            //积分
            if($participate_id && (empty($res['signin_time']) || $res['signin_time']==0) ){
                $title = trim(Yii::app()->request->getParam( 'title' ));
                $filiale_id = intval(Yii::app()->request->getParam( 'filiale_id' ));
                //$activity = Activity::model()->findByPk($activity_id,array('select'=>array('title','filiale_id')));
                CreditLog::addCreditLog(CreditLog::$creditActivity, CreditLog::$typeKey[6], $activity_id, 'add', $title.'签到，电话：'.$mobile.$realname,$res['user_id'],$filiale_id); 

				//调研管理通知预览接口
				$ss = ActivitySetDrawLots::model()->research($activity_id, 1, 2, $res['user_id']);

            }
            
            $this->_showMsg('OK','Y');
        }else{
            $this->_showMsg($this->msg['1'],'1');
        }
    }

    //地图
    public function actionShow_map(){
        $this->render('map');
    }

    //保存活动基本信息
    public function actionActivityAddOp(){
        $msgNo = 0;
        try{
            //标题
            $title		= trim(Yii::app()->request->getParam( 'title' )); 
            //活动负责人
            $activity_head = trim(Yii::app()->request->getParam( 'activity_head' ));
            //活动负责人电话
            $activity_head_tel = trim(Yii::app()->request->getParam( 'activity_head_tel' ));
            //活动类型
            $category_id 	= intval(Yii::app()->request->getParam( 'category' )); 
            //开始时间
            $starttime	= trim(Yii::app()->request->getParam( 'starttime' ));
            //结束时间
            $endtime	= trim(Yii::app()->request->getParam( 'endtime' ));
            //省份id
            $province_code	= Yii::app()->request->getParam( 'province_code' ); 
            //城市id
            $city_code = Yii::app()->request->getParam( 'city_code' ); 
            //详细地址
            $address 	= trim(Yii::app()->request->getParam( 'address' )); 
            //场地负责人
            $venue_head = trim(Yii::app()->request->getParam( 'venue_head' ));
            //场地负责人电话
            $venue_head_tel = trim(Yii::app()->request->getParam( 'venue_head_tel' ));
            //场地费用
            $venue_cost = trim(Yii::app()->request->getParam( 'venue_cost' ));
            //容纳人数
            $venue_pnum = intval(trim(Yii::app()->request->getParam( 'venue_pnum' )));
            //产品名称
            $product_type = trim(Yii::app()->request->getParam( 'product_type' ));
            //产品名称
            $product_name = trim(Yii::app()->request->getParam( 'product_name' ));
            //活动封面图片
            $image		= trim(Yii::app()->request->getParam( 'activity_image_path' )); 
            //人数限制
            $limit_number 	= trim(Yii::app()->request->getParam( 'limit_number' )); 
            //嘉宾讲师 
            $lecturer 	= Yii::app()->request->getParam( 'lecturer' ); 
            //报名截止时间
            $bm_endtime = trim(Yii::app()->request->getParam( 'bm_endtime' )); 
            //是否为线上活动 (线上活动报名截止时间不可晚于活动结束时间)
            $online_activity = empty(Yii::app()->request->getParam( 'online_activity' )) ? 0 : 1;

            //报名花销
            $bm_huaxiao = trim(Yii::app()->request->getParam( 'baominghuaxiao_value' )); 
            //模板类型
            $template_type = intval(Yii::app()->request->getParam( 'template_type' )); 
            
            //是否在前台显示
            $is_show_list = Yii::app()->request->getParam( 'is_show_list' ) ? 0 : 1; 

            //获取所有变量到数组$data
            $data = get_defined_vars();

            if(empty($title)){
                throw new Exception('1008');
            }
            if(empty($starttime)){
                throw new Exception('1009');
            }
            if(empty($endtime)){
                throw new Exception('1010');
            }
            if( $starttime>=$endtime ){
                throw new Exception('1031');
            }
 
            if( empty($online_activity) && $province_code==BRANCH_ID ){
                throw new Exception('1016');
            }
            if(empty($online_activity) && empty($address)){
                throw new Exception('1012');
            }
            if( empty($image) ){
                throw new Exception('1032');
            }
            if(empty($lecturer)){
                throw new Exception('1011');
            }
            if(empty($limit_number)){
                throw new Exception('1007');
            }
            
            $fn = new Fn();
            if(!isMobilePhone($activity_head_tel) ){
                throw new Exception('1001');
            } 
            if($venue_head_tel && !isMobilePhone($venue_head_tel) ){
                 throw new Exception('1001');
            }
            if( $online_activity ){
                if( $bm_endtime>$endtime ){
                    throw new Exception('1027');
                }
            }else{
                if( $bm_endtime>$starttime ){
                    throw new Exception('1025');
                }
            }
            $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
            if($activity_id){
                $Activity = Activity::model()->findByPk($activity_id);
                $ParticipateCount = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'status!'=>2));
                if( intval($ParticipateCount) > intval($limit_number) ){
                    throw new Exception('1026');
                }
                $data['id'] = $activity_id;
            }else{
                $data['num'] = Activity::model()->setActivityNum(); //获取编码
                $data['filiale_id'] = $this->filiale_id;
                $data['status'] = 2;
            }

            unset($data['msgNo']);

            $id = Activity::model()->activitySave($data);

            if($id){
                $extension['id'] = $id;
                $extension['address_coordinates'] = Yii::app()->request->getParam( 'address_coordinates' );
                $ExtensionData = ActivityExtension::model()->findByPk($id);
                if( empty($ExtensionData) ){
                    $extension['isset_tosign_sms'] = 1;
                    $extension['start_before_hour'] = 24;
                }
                ActivityExtension::model()->activityExtensionSave($extension);
                
                //修改开始时间时需判断是否存在未执行的定时短信任务 存在则要相应同步执行时间
                if($activity_id){
                    if( strtotime($Activity['starttime'])!=strtotime($starttime) ){
                        SmsTask::model()->upSmsTask_send_time('activity', $activity_id, $starttime);
                    }
                }else{
                    //初始化短信配置模板
                    SmsTask::model()->taskSaveDefault('activity', $id);
                }
                
                $msgNo = 'Y';
                echo $this->encode($msgNo, $id);die;
            } else {
                throw new Exception('1');
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }

        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    //复制活动
    public function actionCopy_activity(){
        $id		= intval(Yii::app()->request->getParam( 'id' ));
        $activity = Activity::model()->findByPk($id);
        if(empty($activity)){
            $msg = $this->msg['1022'];
            echo $this->encode('1022', $msg);exit;
        }
        if($activity['template_type']==1){
            ActivityContent::model()->activityContentSave(['activity_id'=>$id,'content'=>'']);
        }
        $ActivityContent = ActivityContent::model()->findByAttributes(array('activity_id'=>$id));
        if(empty($ActivityContent)){
            $msg = $this->msg['1023'];
            echo $this->encode('1023', $msg);exit;
        }
        $ins = Activity::model()->copyActivity($id);
        if($ins){
            $msg = $this->msg['Y'];
            echo $this->encode('Y', $msg);
        }else{
            $msg = $this->msg['1'];
            echo $this->encode('1', $msg);
        }
    }

    private function _showMsg($msg,$code='error'){
        $res['msg'] = $msg;
        $res['code'] = $code;
        echo CJSON::encode($res);
        exit;
    }
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


    //活动轮播图列表
    public function actionActivity_lunbotu(){
        if(!isset($_GET['iDisplayLength'])){
                $this->render('activity_lunbotu_list');exit;
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
        $con['type'] = 0;
        if($this->filiale_id!=BRANCH_ID){
                $con['filiale_id'] = $this->filiale_id;
        }
        $list = ActivityLunbotu::model()->getlist($con, $ord, $field, $limit, $page);

        echo CJSON::encode($list);
    }

    //轮播图上传页面
    function actionActivity_lunbotu_add(){
        $id   	= trim(Yii::app()->request->getParam( 'id' ));
        if(empty($id))
        {
            $getlunbotu = array(
                'id' => '',
                'filiale_id' => '',
                'image_title' => '',
                'image_path' => '',
                'image_link' => '',
                'sort' => 0
            );
        }
        else
        {
            $getlunbotu = ActivityLunbotu::model()->findbypk($id)->attributes;
            if(empty($getlunbotu))
            {
                throw new Exception('3');
            }
        }
        $this->render('activity_lunbotu_add',array('lunbotu'=>$getlunbotu));
    }

    //上传图片 （轮播图）
    public function actionActivity_lunbotu_add_op(){
        $msgNo = 'Y';
        try{
            $upload = new Upload();
            if(empty($_FILES)){
                throw new Exception('3');
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
            $image_path = $upload->uploadFile($key[0]); //image为上传框name
            $getErrorMsg = $upload->getErrorMsg();
            if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                throw new Exception('1014');
            }
            $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
            if( !in_array($imagetype, array('.jpg','.jpeg','.gif','.png')) ) {
                //unlink($image_all_path);
                throw new Exception('1019');
            }
            $image_title = htmlspecialchars(trim(Yii::app()->request->getParam( 'image_title' )));
            $image_link = htmlspecialchars(trim(Yii::app()->request->getParam( 'imgage_link' )));
            $sort = intval(Yii::app()->request->getParam( 'sort' ));

            $data['filiale_id'] = $this->filiale_id;
            $data['image_title'] = $image_title;
            $data['image_link'] = $image_link;
            $data['image_path'] = $image_path;
            $data['sort'] = $sort;

            $id = ActivityLunbotu::model()->lunbotuSave($data);
            if(!$id){
                $msgNo = 1;
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    //修改上传信息
    public function actionActivity_lunbotu_update_op(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));    
        try{
            $model = ActivityLunbotu::model()->findbypk($id);
            //判断数据是否存在
            if( empty($model) ){
                throw new Exception(3);
            }
            //是否从新上传图片
            if( isset($_FILES['image']['name']) && !empty($_FILES['image']['name'])){
                $upload = new Upload();
                $key = array_keys($_FILES);
                $filevalue = $_FILES[$key[0]];
                if(intval($filevalue['error'])===1){
                    throw new Exception('1015');
                }

                $size = $filevalue['size']/1024/1024;
                if($size>1){
                    throw new Exception('1015');
                }
                $image_path = $upload->uploadFile($key[0]); //image为上传框name
                $getErrorMsg = $upload->getErrorMsg();
                if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                    throw new Exception('1014');
                }
                $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
                if( !in_array($imagetype, array('.jpg','.jpeg','.gif','.png')) ) {
                    //unlink($image_all_path);
                    throw new Exception('1019');
                }
                $data['image_path'] = $image_path;
            }
            $image_title = htmlspecialchars(trim(Yii::app()->request->getParam( 'image_title' )));
            $image_link = htmlspecialchars(trim(Yii::app()->request->getParam( 'imgage_link' )));
            $sort = intval(Yii::app()->request->getParam( 'sort' ));
            
            $data['id'] = $id;
            $data['filiale_id'] = $this->filiale_id;
            $data['image_title'] = $image_title;
            $data['image_link'] = $image_link;
            $data['sort'] = $sort;

            $id = ActivityLunbotu::model()->lunbotuSave($data);
            if( !$id ){
                $msgNo = 1;
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    //删除轮播图
    public function actionActivity_lunbotu_del(){
        $id = Yii::app()->request->getParam( 'id' );

        $model = ActivityLunbotu::model()->findbypk($id);
        $model->status = 0;
        $model->_update_time = date('Y-m-d H:i:s');
        $flag = $model->save();
        if($flag){
            OperationLog::addLog(OperationLog::$operationActivity, 'del', '删除轮播图', $id, $model->attributes, array('status'=>0));
                $msgNo = 'Y';
                $msg = $this->msg[$msgNo];
                echo $this->encode($msgNo, $msg);
        } else {
                $msgNo = 1;
                $msg = $this->msg[$msgNo];
                echo $this->encode($msgNo, $msg);
        }
    }
    
    //删除活动
    public function actionRemove_activity(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $oldData = Activity::model()->findByPk($activity_id);
        $oldData = isset($oldData->attributes) ? $oldData->attributes : array();
        Activity::model()->removeActivity($activity_id);
         //短信模板
        SmsTask::model()->deleteAll('column_name="activity" and column_id=:column_id', array(':column_id'=>$activity_id) );
        OperationLog::addLog(OperationLog::$operationActivity, 'del', '删除活动', $activity_id, $oldData, array());
        echo 1;
    }
    //推至首页
    public function actionRec_index(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        $msgNo = Activity::model()->updateRec_index($activity_id, $this->filiale_id);
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //导出活动列表
    public function actionActivity_excel(){
        $starttime = Yii::app()->request->getParam( 'starttime' );
        $endtime = Yii::app()->request->getParam( 'endtime' );
        $filiale_id = intval(Yii::app()->request->getParam( 'province_code' ));
        $search_type = Yii::app()->request->getParam( 'search_type' );
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));

        $con = array('status!'=>0);
        if($starttime && $endtime){
            $con['starttime>'] = $starttime;
            $con['starttime<'] = $endtime.' 23:59:59';
        }
        if($this->filiale_id==BRANCH_ID){
            if($filiale_id){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($filiale_id);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
       
        if($search_content){
            if($search_type==='title'){
                $con['title'] = $search_content;
            }elseif($search_type==='num'){
                $con['num'] = $search_content;
            }elseif($search_type==='id'){
                $con['id'] = intval($search_content);
            }
        }
        $list = Activity::model()->getlist($con, 'desc', '_create_time', 1000, 0);

        $get_software_category = get_software_category();
        $get_software_category[0] = '所有软件';
        $data = array();
        foreach($list['data'] as $k=>$v){
            
            $tmp['id'] = $v['id'];
            $tmp['num'] = $v['num'];
            $tmp['province_code'] = ServiceRegion::model()->getRegionName($v['province_code']);
            $tmp['city_code'] = ServiceRegion::model()->getRegionName($v['city_code']);
            $tmp['title'] = $v['title'];
            $tmp['category_id'] = $this->activity_type[$v['category_id']];
            $tmp['lecturer'] = $v['lecturer'];
            $tmp['product_type'] = empty($v['product_type']) ? '' : $get_software_category[$v['product_type']]; 
            $tmp['product_name'] = $v['product_name'];
            $tmp['activity_head'] = $v['activity_head'];
            $tmp['activity_head_tel'] = $v['activity_head_tel'];
            $tmp['user_name'] = $v['user_name'];
            $tmp['time'] = $v['starttime'].'至'.$v['endtime'];
            $tmp['limit_number'] = $v['limit_number'];
            $tmp['bm_endtime'] = $v['bm_endtime'];
            $tmp['bm_huaxiao'] = $v['bm_huaxiao'];
            $tmp['address'] = $v['address'];
            $tmp['venue_pnum'] = $v['venue_pnum'];
            $tmp['venue_cost'] = $v['venue_cost'];
            $tmp['venue_head'] = $v['venue_head'];
            $tmp['venue_head_tel'] = $v['venue_head_tel'];
            $tmp['_create_time'] = $v['_create_time'];
            $tmp['status'] = Activity::model()->getActivityStatus($v['id'], $v['status'], $v['starttime'], $v['endtime'], $v['bm_endtime'],$v['limit_number']);

            $extension = ActivityExtension::model()->findByPk($v['id']);
            $tmp['views_count'] = isset($extension['views'])?$extension['views']:0; //浏览量
            $tmp['comment_count'] = ActivityComment::model()->getCount(array('activity_id'=>$v['id']));  //评论量
            $tmp['share_count'] = ActivityShare::model()->getCount(array('activity_id'=>$v['id']));  //转发量
            $tmp['collect_count'] = ActivityCollect::model()->getCount(array('activity_id'=>$v['id']));  //收藏量
            $tmp['reports_image_count'] = isset($extension['photo_num'])?$extension['photo_num']:0;  //报道图片量
            $tmp['video_count'] = 0;  //视频量
            $tmp['participate_count'] = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$v['id'],'status!'=>2));  //报名人数
            $tmp['signin_count'] = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$v['id'],'type!'=>0));  //签到人数
            $tmp['scene_count'] = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$v['id'],'type'=>1));  //现场建档
            $tmp['prize_count'] = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$v['id'],'is_prize_winning'=>1));  //抽奖次数
            $data[] = $tmp;
        }
        
        
        $header = array('ID','活动编号','省','市','活动名称','活动类型','讲师','产品类型','产品名称','负责人','负责人电话','创建人','活动时间','限定人数','报名截止时间','报名费用','活动地址','场地容纳人数','场地费用','场地联系人','联系人电话','创建时间','状态','浏览量','评论量','转发量','收藏量','报道图片量','视频量','报名人数','签到人数','现场建档','抽奖次数');
 
        FwUtility::exportExcel($data, $header,'活动明细','活动列表_'.date('Y-m-d'));
    }
    
    //统计分析
    public function actionStatistical(){
        $activity_id = intval(Yii::app()->request->getParam( 'id' ));
        
        //报名总人数
        $Participate_total = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'status!'=>2));
        //限额人数
        $activity = Activity::model()->findByPk($activity_id,array('select'=>array('title','num','limit_number','requirement','template_type')));
        $limit_number = $activity->limit_number;
        $title = $activity->title;
        
        //扩展项
        $requirement = empty($activity['requirement'])?array():  unserialize($activity['requirement']);
        $textn = '';
        if(!empty($requirement)){
            foreach($requirement as $k=>$v){
                if(strstr($k,'text') || $k=='share_code'){
                    $requirement[$k] = $k=='share_code' ? $this->shoujixnxi[$k] : substr($v,2,30);
                    $textn .= '{"mData": "'.$k.'", "bSortable": false, "bSearchable": true},';
                }else{
                    unset($requirement[$k]);
                }
            }
        }
        //报名签到人数
        $Participate_signin = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'type'=>2,'status!'=>2));
        //现场签到
        $Participate_the_scene_signin = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'type'=>1));
        //参会人数
        $Participate_attend = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'type!'=>0));
        $extension = ActivityExtension::model()->findByPk($activity_id);
        //是否有调研
        $research = Research::model()->checkResearch(array('column_type'=>1,'column_id'=>$activity_id,'status'=>1,'_delete'=>0));
        $research_id = empty($research) ? 0 : (isset($research[0]['id'])?$research[0]['id']:0);
        $data = array(
            'activity_id' => $activity_id,
            'title' => $title,
            'num' => $activity['num'],
            'Participate_total' => $Participate_total,
            'limit_number' => $limit_number,
            'Participate_signin' => $Participate_signin,
            'Participate_the_scene_signin' => $Participate_the_scene_signin,
            'Participate_attend' => $Participate_attend,
            'views' => isset($extension['views'])?$extension['views']:0,
            'requirement' => $requirement,
            'template_type' => $activity['template_type'],
            'textn' => $textn,
            'research_id' => $research_id
        );
       
        $this->render('statistical',array('data'=>$data));
    }

    
    //报名列表
    public function actionParticipate_list(){
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;

        $activity = Activity::model()->findByPk($activity_id);

        $con['activity_id'] = $activity_id;
        $con['status!'] = 2;
        $list = ActivityParticipate::model()->getlist($con, $ord, $field, $limit, $page);
        if(empty($list['data'])){
            echo CJSON::encode($list);die;
        }
        $shoujixnxi = $this->shoujixnxi;
        unset($shoujixnxi['realname'],$shoujixnxi['mobile']);
 
        foreach($list['data'] as $k=>$v){
           $extends = $v['extends'];
           unset($v['extends'],$v['extend']);
           foreach($shoujixnxi as $keyname=>$text){
               if(isset($extends[$keyname])){
                   $v[$keyname] = $extends[$keyname];
               }else{
                   $v[$keyname] = '';
               }
           }
           $data[] = $v;
        }
        //print_r($data);
        $data = array('data' => $data, 'iTotalRecords' => $list['iTotalRecords'], 'iTotalDisplayRecords' => $list['iTotalDisplayRecords']);
        echo CJSON::encode($data);
    }
    
    //导出报名列表
    public function actionParticipate_excel(){
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
        
        $con['activity_id'] = $activity_id;
        $con['status!'] = 2;
        $list = ActivityParticipate::model()->getlist($con, 'desc', 'id', 50000, 0);
        
        $activity = Activity::model()->findByPk($activity_id);
        //扩展项
        $requirement = empty($activity['requirement'])?array():  unserialize($activity['requirement']);
        if(!empty($requirement)){
            foreach($requirement as $k=>$v){
                if(strstr($k,'text') || $k=='share_code'){
                    $requirement[$k] = $k=='share_code' ? $this->shoujixnxi[$k] : substr($v,2,20);
                }else{
                    unset($requirement[$k]);
                }
            }
        }
        
        $province = ServiceRegion::model()->getRegionName($activity['province_code']);
        $city = ServiceRegion::model()->getRegionName($activity['city_code']);
        $title = $activity['title'];
        
        $data = array();
        foreach($list['data'] as $v){

            $tmp['id'] = $v['id'];
            $tmp['member_user_name'] = $v['member_user_name']=='=@='?'"'.$v['member_user_name'].'"':$v['member_user_name'];
            $tmp['province'] = $province;
            $tmp['city'] = $city;
            $tmp['title'] = $title;
            $tmp['realname'] = $v['realname'];
            $tmp['mobile'] = $v['mobile'];
            $tmp['qq'] = isset($v['extends']['qq']) ? $v['extends']['qq'] : '';
            $tmp['email'] = isset($v['extends']['email']) ? $v['extends']['email'] : '';
            $tmp['dongle'] = isset($v['extends']['dongle']) ? $v['extends']['dongle'] : '';
            $tmp['company'] = isset($v['extends']['company']) ? $v['extends']['company'] : '';
            $tmp['position'] = isset($v['extends']['position']) ? $v['extends']['position'] : '';
            $tmp['major'] = isset($v['extends']['major']) ? $v['extends']['major'] : '';
            $tmp['work_num'] = isset($v['extends']['work_num']) ? $v['extends']['work_num'] : '';
            foreach($requirement as $text_k=>$text_v){
                $tmp[$text_k] = isset($v['extends'][$text_k]) ? $v['extends'][$text_k] : '';
				$tmp[$text_k] = (is_numeric($tmp[$text_k]) && strlen($tmp[$text_k])>15 ) ? '\''.$tmp[$text_k] : $tmp[$text_k];
            }
            if($v['type']==1){
                $tmp['bm_type'] = '现场报名';
            }elseif($v['type']==0 || $v['type']==2){
                $tmp['bm_type'] = '网站报名';
            }else{
                $tmp['bm_type'] = '其他方式';
            }
            $tmp['_create_time'] = $v['_create_time'];
            if($v['invite_code']){
                $tmp['invite_code'] = $activity['num'].'-'.$v['invite_code'];
            }else{
                $tmp['invite_code'] = '';
            }
            $tmp['type'] = $v['type']==0 ? '未签到' : '已签到';
            $tmp['signin_time'] = $v['signin_time'];
            $tmp['is_prize_winning'] = $v['is_prize_winning']==1 ? '中奖' : '未中奖';      
            $data[] = $tmp;
        }
        
        $requirementstr = empty($requirement) ? '' : implode(',', $requirement).',';
        $headerstr = 'ID,新干线账号,省,市,活动名称,姓名,手机号码,QQ号码,邮箱,加密锁号,单位全称,职位,专业,从业年限,'.$requirementstr.'报名方式,报名时间,邀请码,签到状态,签到时间,中奖结果';
        $header = explode(',',$headerstr);
       
        FwUtility::exportExcel($data, $header,'活动报名明细',$activity['title'].'_'.date('Y-m-d'));
    }
    
    //活动评论列表
    public function actionComment_List(){
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
        if(!isset($_GET['iDisplayLength'])){
            $getBranchList = ServiceRegion::model()->getBranchList();
            $this->render('activity_comment_list',array('getBranchList'=>$getBranchList,'activity_id'=>$activity_id));exit;
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
        $search_type = trim(Yii::app()->request->getParam( 'search_type' ));
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con = array('_delete'=>0,'activity_id'=>$activity_id, 'status'=>1);
        $list = ActivityComment::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    //删除评论
    public function actionComment_Del(){
        $msgNo = 'Y';
        $ids = Yii::app()->request->getParam( 'ids' );
        $activity_id = intval(Yii::app()->request->getParam( 'activity_id' ));
        $activity = Activity::model()->findByPk($activity_id,array('select'=>array('filiale_id')));
        $filiale_id = $activity['filiale_id'];
        try{
            if(!is_array($ids)){
               throw new Exception('1006'); 
            }
            $idArr = array_keys($ids);
            $del = ActivityComment::model()->updateByPk($idArr,array('_delete'=>1),'status=1');
            if(!$del){
                throw new Exception('1'); 
            }
            //删除子评论
            $up = ActivityComment::model()->updateAll(
                array('_delete'=>1,'_update_time'=>date('Y-m-d H:i:s')),
                'pid IN('.implode(',',$idArr).') AND _delete=0');

            //记录日志
            foreach($ids as $id=>$user_id){
                OperationLog::addLog(OperationLog::$operationActivity, 'del', '删除评论', $id, array(), array());
                CreditLog::addCreditLog(CreditLog::$creditActivity, CreditLog::$typeKey[11], $activity_id, 'subtract', '删除评论',$user_id,$filiale_id);
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    //获取省市 (已废弃)
    public function actionGetCityList(){
        $criteria = new \CDbCriteria;
        if(!isset($_POST['province_code']) && !isset($_POST['city_code'])){
                $criteria->compare('is_parent', 0);	
        }elseif(isset($_POST['province_code'])){
                $criteria->compare('region_id>', intval($_POST['province_code'])*100);
                $criteria->compare('region_id<', intval($_POST['province_code']+1)*100);
        }
        $criteria->compare('_delete', 0);
        $res = ServiceRegion::model()->findAll($criteria);
        $option = '<option value="0">全国</option>';
        foreach($res as $val){
                $option .= '<option value="'.$val['region_id'].'">'.$val['region_name'].'</option>';
        }
        echo $option;
    }
    
     //根据省region_id获取城市
    public function actionCity_List(){
       
        $province_code = trim(Yii::app()->request->getParam('province_code'));
        $citylist = ServiceRegion::model()->getCityByProvince($province_code);
        $option = '';
        $unsetcity = array('北京', '天津', '上海', '重庆'); //需要去掉的二级地区
        foreach($citylist as $id=>$name){
            if(in_array($name, $unsetcity) || in_array($id,[3503,3508,3509])){
                continue;
            }
            $option .= '<option value="'.$id.'">'.$name.'</option>';
        }
        echo $option;
    }
    
    //抽奖列表
    public function actionDraw_lots(){
        $activityId = trim(Yii::app()->request->getParam('activity_id'));
        $activityId = is_numeric($activityId) ? intval($activityId) : 0;
        $activityDrawLotArr = ActivitySetDrawLots::model()->find('activity_id=:activity_id', array('activity_id' => $activityId));
        $activityArr = Activity::model()->findbypk($activityId);
        if (empty($activityArr)) {
                echo CJSON::encode(array('3', $this->msg[3]));
                exit();
        }
        if(empty($activityDrawLotArr)){
                $setDrawLotsFlag = $this->_setDrawLots($activityId, $activityArr);
                $setDrawLots = CJSON::decode($setDrawLotsFlag, true);
                if($setDrawLots['status'] == 'Y'){
                        $activityDrawLotArr = ActivitySetDrawLots::model()->find('activity_id=:activity_id', array('activity_id' => $activityId));
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
        if(!empty($statr_time) && !empty($end_time)){
                $con['time'] = array('statr_time' => $statr_time, 'end_time' => $end_time);
        }
        if(!empty($sSearch)){
                $con['phone'] = $sSearch;
        }

        $con['is_prize_winning'] = 1;
        $con['activity_id']      = $activity_id;

        $list = ActivityParticipate::model()->getlist($con, $ord, $field, $limit, $page);
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
                $type = 0;
                $activityId = is_numeric($activityId) ? intval($activityId) : 0;

                /*if(strtotime($offlineTime) < time() ){
                        throw new Exception('1021');
                    }*/
                $data = array(
                                'activity_id' 	=> $activityId,
                                'passwd' 		=> $passwd,
                                'source'      => 0,
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

    public function actionInit_DrawLots(){
        try {
            $activity_id = trim(Yii::app()->request->getParam( 'id' ));
            $activityArr = Activity::model()->findByPk($activity_id);
            $data = array(
                'activity_id' 	=> $activity_id,
            );
            $flag = ActivityParticipate::model()->initializeDrawLots($data);

            $activityEndTime = !empty($activityArr->endtime) ? $activityArr->endtime : date('Y-m-d H:i:s');
            $offlineTime = date('Y-m-d H:i:s', strtotime("$activityEndTime +1 day"));

            $drawLotsData = array(
                'activity_id' 	=> $activity_id,
                'offline_time' 	=> $offlineTime
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

    public function actionImport_execl(){
        $this->render('import_execl');
    }

    public function actionImport_execl_op(){
        try {
            $upload   = new Upload();
            $fileName = $upload->uploadFile('filename');
            $objPHPExcel = FwUtility::readerExcel(UPLOAD_PATH.$fileName);
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
            $activity_id = trim(Yii::app()->request->getParam( 'id' ));
            if(empty($activity_id)){
                throw new Exception('1006');
            }
            $arr = array();
            //循环读取excel文件,读取一条,插入一条
            for($j=2;$j<=$highestRow;$j++)
            {
                $arr['user_id']      = '24397892';
                $arr['user_name']    = '18310331883';
                $arr['dai']          = '1';
                $arr['activity_id']  = $activity_id;//获取公司信息
                $arr['company']  = $objPHPExcel->getActiveSheet()->getCell("A".$j)->getValue();//获取公司信息
                $arr['realname'] = $objPHPExcel->getActiveSheet()->getCell("B".$j)->getValue();//获取姓名
                $arr['mobile']   = $objPHPExcel->getActiveSheet()->getCell("C".$j)->getValue();//获取电话
                $extend['text1']    = $objPHPExcel->getActiveSheet()->getCell("D".$j)->getValue();//获取性别
                $extend['position'] = $objPHPExcel->getActiveSheet()->getCell("E".$j)->getValue();//获取性别
                $arr['extend']      =  serialize($extend);
                ActivityParticipate::model()->ActivityRequirementSave($arr);
                $msgNo = 'Y';
            }
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $msg;
    }
    
    /*
     * 导出抽奖记录
     */
    public function actionExcel_draw(){
        $activity_id= trim(Yii::app()->request->getParam( 'activity_id' ));
        $activity_id= is_numeric($activity_id) ? intval($activity_id) : 0;
        $con['is_prize_winning'] = 1;
        $con['activity_id']      = $activity_id;
        $list = ActivityParticipate::model()->getlist($con, 'desc', 'id', 50000, 0);
        $data = array();
        foreach($list['data'] as $k=>$v){
            $tmp['id'] = $v['id'];
            $tmp['realname'] = $v['realname'];
            $tmp['mobile'] = $v['mobile'];
            $tmp['prize_winning_time'] = $v['prize_winning_time'];
            $data[] = $tmp;
        }
        $header = array('ID','中奖人姓名','手机号码','中奖时间');
        FwUtility::exportExcel($data, $header,'活动中奖名单','活动中奖名单'.date('Y-m-d'));
    }
    
    
    /*
     * 设置排序
     */
    public function actionSetActivitySort(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $training_sort = intval(Yii::app()->request->getParam( 'sort' ));
        $filiale_id = $this->filiale_id;

        $sortArr = [5,4,3,2,1,0];
        try {
            if(!$id){
                throw new Exception(1006);
            }
            if(!in_array( $training_sort ,$sortArr )){
                throw new Exception(1006);
            }
            $Training = Activity::model()->findByPk($id);
            if(empty($Training)){
                throw new Exception(3);
            }
            if($this->filiale_id!=$Training->filiale_id){
                $filiale_id = $Training->filiale_id;
            }
            if($training_sort!=0){
                $count = Activity::model()->getCount(['id!'=>$id,'sort'=>$training_sort,'filiale_id'=>$filiale_id]);
                if($count){
                    throw new Exception(1030);
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
     * 生成二维码
     */
    public function actionGet_qr_code(){
        $id = intval(Yii::app()->request->getParam( 'id' ));   
        $again = Yii::app()->request->getParam( 'again' );
        $down = Yii::app()->request->getParam( 'down' );
        $ext = ActivityExtension::model()->findByPk($id, array('select'=>['id','link_qr_code']));
        
        if( !isset($ext['link_qr_code']) || empty($ext['link_qr_code']) || $again ){
            $link = (YII_ENV=='dev'?'http://10.129.8.154':EHOME).'/mobile/activity/details.html?id='.$id;
            $link_qr_code = FwUtility::generateQrcodeCode($link);
            ActivityExtension::model()->activityExtensionSave(array('id'=>$id,'link_qr_code'=>$link_qr_code));
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
            $str .= '<a href="index.php?r=activity/get_qr_code&id='.$id.'&again=1" >刷新</a>';
        }
        echo $str;
    }
    
    
    //生成报名sql
    public function actionDaoru(){
        header("Content-type: text/html; charset=utf-8"); 
        $activity_id = 1758;
        $objPHPExcel = FwUtility::readerExcel('D:/'.$activity_id.'.xlsx');
        $row = $objPHPExcel->getSheet(0)->getHighestRow();
 
        $phone = array();
        $havePhone = array();
        $sql = "insert into `e_activity_participate`(activity_id, user_id, user_name, realname, mobile, company, dai, extend, status, _create_time) values ";
        for($i=2; $i<=$row; $i++){
            $A = trim( $objPHPExcel->getActiveSheet()->getCell('A'.$i)->getValue() ); //姓名
            $B = trim( $objPHPExcel->getActiveSheet()->getCell('B'.$i)->getValue() ); //手机号
            $C = trim( $objPHPExcel->getActiveSheet()->getCell('C'.$i)->getValue() ); //单位
            //$D = trim( $objPHPExcel->getActiveSheet()->getCell('D'.$i)->getValue() ); //销售经理
            //$E = trim( $objPHPExcel->getActiveSheet()->getCell('E'.$i)->getValue() ); //QQ号
            //$F = trim( $objPHPExcel->getActiveSheet()->getCell('F'.$i)->getValue() ); //用户账号
            
            //$count = ActivityParticipate::model()->find('activity_id=78 and status=1 and mobile=:mobile',array('mobile'=>$D));
            if($B) {
                if(in_array($B, $phone)){
                    $havePhone[] = $B;
                }else{
                    $phone[] = $B;
                }
            }
            
            
            $extend = array();
            $extend['company'] = $C;
            //$extend['text1'] = $D;
            //$extend['qq'] = $E;
            //$extend['dongle'] = $C;
          
            $extendstr = serialize($extend);
            $sql .= "(".$activity_id.", 24397892, '18310331883', '".$A."', '".$B."', '".$C."', 1, '".$extendstr."', 1, '2018-05-07 00:00:00'), ";
            
        }
        //print_r($havePhone);die;
        //print_r(count(array_unique($phone)));die;
        
 
        echo $sql;
       
    }
}