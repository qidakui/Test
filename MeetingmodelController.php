<?php
/*
 * qik
 * 场地、模板管理
 */
use application\models\Meetingmodel\Meetingplace;
use application\models\Meetingmodel\Softname;
use application\models\Meetingmodel\Selectmodel;
use application\models\ServiceRegion;
class MeetingmodelController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        0 => '参数错误',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '请上传培训主题图片',
        5 => '上传图片过大',
        6 => '上传失败，请刷新重试',
        7 => '上传psd文件过大',
		8 => '已存在相同的软件名称与培训类别，请重新选择'
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
                        
    
    /*
     * 场地管理列表
     */
    public function actionIndex(){
        
        if(!isset($_GET['iDisplayLength'])){
            $iframe = Yii::app()->request->getParam( 'iframe' );
            $province_code = intval(Yii::app()->request->getParam( 'province_code' ));
            $this->render('meeting_place_list', array('iframe'=>$iframe, 'province_code'=>$province_code));exit;
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
        $province_code = intval(Yii::app()->request->getParam( 'province_code' ));
        $city_code = intval(Yii::app()->request->getParam( 'city_code' ));
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con['status'] = 1;    
        if( !empty($this->filiale_id) && $this->filiale_id!=BRANCH_ID ){ 
            $provinceId = ServiceRegion::model()->getBranchToCity($this->filiale_id);
            $provinceId = isset($provinceId[0]['region_id']) ? $provinceId[0]['region_id'] : 0;
            $con['province_code'] = $provinceId;
        }else{
            if($province_code){
                $con['province_code'] = $province_code;
            }
        }
        if($search_content){
            $con['word'] = $search_content;
        }
        $list = Meetingplace::model()->getList($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 创建修改场地
     */
    public function actionAdd(){
		$msgNo = 'Y';
		$id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = [];
        if(empty($_POST)){
			if( $id ){
				$data = Meetingplace::model()->findByPk($id); //echo "<pre>"; print_r($data);die;
            }else{
                $data = array(
                    'id' => 0,
                    'title' => '',
                    'province_code' => 0,
                    'province_name' => '',
                    'city_code' => 0,
                    'city_name' => '',
                    'address' => '',
                    'type' => '',
                    'place_head' => '',
                    'place_head_tel' => '',
                    'place_cost' => '',
                    'place_pnum' => '',
                    '_create_time' => '',
                );
            }
            
            $getCityList = ServiceRegion::model()->getCityList();
			$renderdata = array(
				'id'=>$id,
				'getCityList'=>$getCityList,
				'data' => $data,
			);
            $this->render('meeting_place_add',$renderdata);
			exit;
        }

		$title = trim(Yii::app()->request->getParam( 'title' ));
        $type = trim(Yii::app()->request->getParam( 'type' ));
		$province_code = trim(Yii::app()->request->getParam( 'province_code' ));
        $city_code = trim(Yii::app()->request->getParam( 'city_code' ));
        $address = trim(Yii::app()->request->getParam( 'address' ));
        $place_head = trim(Yii::app()->request->getParam( 'place_head' ));                
        $place_head_tel = Yii::app()->request->getParam( 'place_head_tel' ); 
        $place_cost = Yii::app()->request->getParam( 'place_cost' ); 
        $place_pnum = Yii::app()->request->getParam( 'place_pnum' ); 
		try {
			        
            $data['title'] = $title;
            $data['type'] = $type;
            $data['province_code'] = $province_code;
            $data['city_code'] = $city_code;
            $data['address'] = $address;
            $data['place_head'] = $place_head;
            $data['place_head_tel'] = $place_head_tel;
            $data['place_cost'] = $place_cost;
            $data['place_pnum'] = $place_pnum;
            $data['create_user_id'] = $this->user_id;
            
            if($id){
				$data['id'] = $id;
            }else{
                $data['user_id'] = $this->user_id;
            }
            //print_r($data);die;
            $save = Meetingplace::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //修改场地状态
    public function actionUpStatus(){
		$msgNo = 'Y';
        $id	= intval(Yii::app()->request->getParam( 'id' ));
		$edit_field	= trim(Yii::app()->request->getParam( 'edit_field' ));
		$edit_val	= intval(Yii::app()->request->getParam( 'edit_val' ));
		try{
			if(!$id || !in_array($edit_field, ['status']) ){
				throw new Exception(0);
			}
			$data['id'] = $id;
			$data[$edit_field] = $edit_val;
            $data['create_user_id'] = $this->user_id;
			$save = Meetingplace::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
		echo $this->encode($msgNo, $this->msg[$msgNo]);
	}
    
    
    
    
    
    /*
     * 课程模板列表
     */
    public function actionModelList(){
        if(!isset($_GET['iDisplayLength'])){
            $this->render('model_list');exit;
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
        
        $category_id = Yii::app()->request->getParam( 'category_id' );
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con['status'] = 1;    
        $con['source'] = 1;
        if($category_id!='all'){
            $con['category_id'] = $category_id;
        }
        if($search_content){
            $con['word'] = $search_content;
        }
        $list = Selectmodel::model()->getList($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    /*
     * 下载模板图片
     */
    public function actionDown(){
        $path = trim(Yii::app()->request->getParam( 'p' ));
        if($path){ 
            $e = explode('/',$path);
            $finename = end($e);
            $path = Yii::getPathOfAlias('webroot') . '/../../'.$path;
            header("Content-type: octet/stream");
            header("Content-disposition:attachment;filename=".$finename );
            header("Content-Length:" . filesize($path));
            readfile($path);
        }
    }
    
    /*
     * 创新、修改模板
     */
    public function actionAddModel(){
		$msgNo = 'Y';
        $id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = [];
        if(empty($_POST)){
			$data = Selectmodel::model()->getData($id);//print_r($data);die;
            $this->render('model_add', array('id'=>$id, 'data'=>$data));exit;
        }
 
		$soft_name_id	= intval(Yii::app()->request->getParam( 'soft_name_id' )); // 软件id
		$category_id	= intval(Yii::app()->request->getParam( 'category_id' )); //培训类别
		$content = trim(Yii::app()->request->getParam( 'content' ));
        
        //封面
		$image = isset($_FILES['image']) ? $_FILES['image'] : 0;
        $image_hide = Yii::app()->request->getParam( 'image_hide' );
        //psd
        $psd = isset($_FILES['psd']) ? $_FILES['psd'] : 0;
        
        //logo
        $logo = isset($_FILES['logo']) ? $_FILES['logo'] : 0;
                  
		try{
			//相同软件名称与培训类别只能有一对
			$Cdata['soft_name_id'] = $soft_name_id;
			$Cdata['category_id'] = $category_id;
			$Cdata['status'] = 1;
			if($id){
				$Cdata['id!'] = $id;
			}
			$count = Selectmodel::model()->getCount($Cdata);
			if($count){
				throw new Exception(8);			
			}

			//if( empty($image) && empty($image_hide)){
			//	throw new Exception(4);
			//}
            
			if( !empty($_FILES) ){
                //上传主题图
                if(isset($_FILES['image']) && !empty($_FILES['image'])){
                    $upload = new Upload();
                    $upload->set('path','/uploads/modelfile/training/image/');
                    $filevalue = $_FILES['image'];
                    if(intval($filevalue['error'])===1){
                        throw new Exception('5');
                    }
                    $size = $filevalue['size']/1024/1024;
                    if($size>1){
                        throw new Exception('5');
                    }
                    $image_path = $upload->uploadFile('image');
                    $getErrorMsg = $upload->getErrorMsg();
                    if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                        throw new Exception('6');
                    }
                    $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
                    $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
                    $upstatus = FwUtility::createSmallImage($image_path, 650,400);
                    if($upstatus!='Y'){
                        unlink($image_all_path);
                        $msgNo = 6;
                        $this->msg[$msgNo] = '主题图片不符合要求，'.$upstatus;
                        throw new Exception($msgNo);
                    }
                    $image = $image_path;
                }
                
                //上传psd
                if(isset($_FILES['psd']) && !empty($_FILES['psd'])){
                    $upload = new Upload();
                    $upload->set('path','/uploads/modelfile/training/psd/');
                    $upload->set('maxsize',1024*1024*5);
                    $upload->set('allowtype',array('psd'));
                    $filevalue = $_FILES['psd'];
                    if(intval($filevalue['error'])===1){
                        if(isset($image_all_path)){
                            unlink($image_all_path);
                        }
                        throw new Exception(7);
                    }
                    $size = $filevalue['size']/1024/1024;
                    if($size>10){
                        if(isset($image_all_path)){
                            unlink($image_all_path);
                        }
                        throw new Exception(7);
                    }
                    $psd_path = $upload->uploadFile('psd');
                    $getErrorMsg = $upload->getErrorMsg();
                    $psd_all_path = Yii::getPathOfAlias('webroot').'/../..'.$psd_path;
                    if(!empty($getErrorMsg) || !strstr($psd_path,'/uploads/') ){
                        if(isset($image_all_path)){
                            unlink($image_all_path);
                        }
                        unset($psd_all_path);
                        throw new Exception('6');
                    }
                    $psd = $psd_path;
                }
                
                //上传logo
                if(isset($_FILES['logo']) && !empty($_FILES['logo'])){
                    $upload = new Upload();
                    $upload->set('path','/uploads/modelfile/training/logo/');
                    $filevalue = $_FILES['logo'];
                    if(intval($filevalue['error'])===1){
                        if(isset($image_all_path)){
                            unlink($image_all_path);
                        }
                        if(isset($psd_all_path)){
                            unlink($psd_all_path);
                        }
                        throw new Exception('5');
                    }
                    $size = $filevalue['size']/1024/1024;
                    if($size>1){
                        if(isset($image_all_path)){
                            unlink($image_all_path);
                        }
                        if(isset($psd_all_path)){
                            unlink($psd_all_path);
                        }
                        throw new Exception('5');
                    }
                    $logo_path = $upload->uploadFile('logo');
                    $getErrorMsg = $upload->getErrorMsg();
                    if(!empty($getErrorMsg) || !strstr($logo_path,'/uploads/') ){
                        if(isset($image_all_path)){
                            unlink($image_all_path);
                        }
                        if(isset($psd_all_path)){
                            unlink($psd_all_path);
                        }
                        throw new Exception('6');
                    }
                    $imagetype = strtolower(substr($logo_path,strrpos($logo_path, '.'))); 
                    $logo_all_path = Yii::getPathOfAlias('webroot').'/../..'.$logo_path;
                    $upstatus = FwUtility::createSmallImage($logo_path, 90,90);
                    if($upstatus!='Y'){
                        if(isset($image_all_path)){
                            unlink($image_all_path);
                        }
                        if(isset($psd_all_path)){
                            unlink($psd_all_path);
                        }
                        unlink($logo_all_path);
                        $msgNo = 6;
                        $this->msg[$msgNo] = 'logo不符合要求，'.$upstatus;
                        throw new Exception($msgNo);
                    }
                    $logo = $logo_path;
                }
			}
            
            $data['category_id'] = $category_id;
            $data['soft_name_id'] = $soft_name_id;
            $data['content'] = $content;
            $data['last_user_id'] = $this->user_id;
            if($image){
                $data['image'] = $image;
            }
            if($psd){
                $data['psd'] = $psd;
            }
            if($logo){
                $data['logo'] = $logo;
            }
                
            if($id){
                $data['id'] = $id;
            }else{
                $data['filiale_id'] = $this->filiale_id;
                $data['user_id'] = $this->user_id;
                $data['source'] = 1; //来自培训
            }
			$save = Selectmodel::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 删除模板（物理删除）
     */
	public function actionDelmodel(){
		$msgNo = 'Y';
		$id   	= intval(Yii::app()->request->getParam( 'id' ));
		try {   
            $data['status'] = 0;
			$data['id'] = $id;
            $save = Selectmodel::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
	}
    
    /*
     * 软件名称列表
     */
    public function actionSoftlist(){
        if(!isset($_GET['iDisplayLength'])){
            $this->render('soft_name_list');exit;
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
         
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con['status'] = 1;    
        $con['source'] = 1;
        if($search_content){
            $con['word'] = $search_content;
        }
        $list = Softname::model()->getList($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
    
    
    /*
     * 创建修改软件
     */
    public function actionAddSoft(){
		$msgNo = 'Y';
		$id	= intval(Yii::app()->request->getParam( 'id' ));
		$data = [];
        if(empty($_POST)){
			if( $id ){
				$data = Softname::model()->findByPk($id); //echo "<pre>"; print_r($data);die;
            }else{
                $data = array(
                    'id' => 0,
                    'soft_name' => '',
                    'user_name' => '',
                    'last_user_name' => '',
                    '_create_time' => '',
                    'sort' => '',
                    '_update_time' => ''
                );
            }
            
			$renderdata = array(
				'id'=>$id,
				'data' => $data,
			);
            $this->render('soft_add',$renderdata);
			exit;
        }

		$soft_name = trim(Yii::app()->request->getParam( 'soft_name' ));
        $sort = intval(Yii::app()->request->getParam( 'sort' ));
		try {
			        
            $data['soft_name'] = $soft_name;
            $data['sort'] = $sort;
            $data['last_user_id'] = $this->user_id;
            $data['last_user_name'] = $this->user_name;
            if($id){
				$data['id'] = $id;
            }else{
                $data['source'] = 1; //1培训
                $data['user_id'] = $this->user_id;
                $data['user_name'] = $this->user_name;
            }
            //print_r($data);die;
            $save = Softname::model()->saveData($data);
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
	//根据软件名称id、培训类别 获取模板信息
	public function actionSoftToModel(){
		$msgNo = 'Y';
		$category_id = intval(Yii::app()->request->getParam( 'category_id' ));
		$soft_name_id = intval(Yii::app()->request->getParam( 'soft_name_id' ));
		$Selectmodel = Selectmodel::model()->getSoftIdToModel($category_id, $soft_name_id);
		unset($Selectmodel['content']);
		echo $this->encode($msgNo, $Selectmodel);
	}
    
}




     
        