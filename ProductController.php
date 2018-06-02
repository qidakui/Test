<?php
use application\models\Product\Category;
use application\models\Product\Product;
use application\models\Product\ProductExtension;
use application\models\Product\ProductFeedback;
use application\models\Product\ProductActionLog;
use application\models\Admin\AdminRole;
use application\models\ServiceRegion;
use application\models\Activity\ActivityComment;
use application\models\Product\ProductAdvanceDownloadInfo;
use application\models\Product\ProductAdvanceTemplate;
use application\models\Product\ProductAdvanceSonTemplate;
use application\models\Product\ProductDownloadDetail;
use application\models\Product\ProductRenewalDetail;
class ProductController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '分类名称不能为空',
        3 => '分类ID不能为空',
        4 => '分类不存在',
        5 => '分类已存在',
        6 => '请先删除此分类下的子分类',
        7 => '此分类下有产品存在，不可删除',
        8 => '请正确选择分类',
        9 => '请上传宣传图片',
        1006 => '参数错误',
        1014 => '图片上传错误，请重试',
        1015 => '上传文件过大',
        1047 => '当前适用地已存在相同排序位',
        2001 => 'P5模板中产品介绍不能为空',
        2002 => '产品ID不能为空',
        2003 => 'P5模板中产品下载不能为空',
        2004 => 'P5模板中banner图不能为空',
        2005 => 'P5模板中自定义视频不能为空',
        2006 => 'P5模板中自定义视频不能少于3个',
        2007 => 'P5模板中自定义标题不能为空',
        2008 => 'P5模板中自定义子类图片视频不能并存',
        2009 => 'P5模板中自定义视频不能为空',
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
    
    //列表
    public function actionIndex(){  
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            //判断是否为超级管理员
            $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id'=>$this->user_id));
            $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
            $role_id = $adminrole['role_id'];
            $Categroy = $this->actionSelectCategroy();
            $this->render('list',array('getCityList'=>$getCityList, 'role_id'=>$role_id,'categroy'=>$Categroy ));exit;
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
        $category_parent_id = intval(Yii::app()->request->getParam( 'category_parent_id' ));
        $category_id = intval(Yii::app()->request->getParam( 'category_id' ));
        $title = trim(Yii::app()->request->getParam( 'title' ));
        $con = array('status!'=>0);
        if($starttime){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        
        if($this->filiale_id==BRANCH_ID){
            if($province_code!=BRANCH_ID){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
        if($title){
            $con['title'] = $title;
        }
        if($category_parent_id){
            $con['category_parent_id'] = $category_parent_id;
        }
        if($category_id){
            $con['category_id'] = $category_id;
        }

        $list = Product::model()->get_list($con, $ord, $field, $limit, $page);
        //print_r($list);
        echo CJSON::encode($list);
    }
    
    //导出产品
    public function actionProduct_Excel(){  

        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); //分支id前两位
        $category_parent_id = intval(Yii::app()->request->getParam( 'category_parent_id' ));
        $category_id = intval(Yii::app()->request->getParam( 'category_id' ));
        $title = trim(Yii::app()->request->getParam( 'title' ));
        $con = array('status!'=>0);
        if($starttime){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        if($this->filiale_id==BRANCH_ID){
            if($province_code!=BRANCH_ID){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
        if($title){
            $con['title'] = $title;
        }
        if($category_parent_id){
            $con['category_parent_id'] = $category_parent_id;
        }
        if($category_id){
            $con['category_id'] = $category_id;
        }

        $list = Product::model()->get_list($con, 'desc', 'id', 50000, 0);
        $select = array( 'select'=>array('views_num','share_num','praise_num','collection_num','consulting_num','down_num','comment_num','advice_num') );
        $data = array();
        foreach($list['data'] as $k=>$v){
           $tmp['id'] = $v['id'];
           $tmp['apply_province_name'] = $v['apply_province_name'];
           $tmp['region_name'] = $v['region_name'];
           $tmp['title'] = $v['title'];
           $tmp['category_parent'] = $v['category_parent'];
           $tmp['category'] = $v['category'];
           $tmp['_create_time'] = $v['_create_time'];
           $tmp['_update_time'] = $v['_update_time'];
           $tmp['_update_time'] = $v['_update_time'];
           $tmp['version'] = $v['version'];
           $tmp['create_name'] = $v['create_name'];
           $tmp['audit_name'] = $v['audit_name'];
           $ProductExtension = ProductExtension::model()->findByPk($v['id'], $select );
           if($ProductExtension){
               foreach($select['select'] as $ek){
                   $tmp[$ek] = $ProductExtension[$ek];
               }
           }
           $data[] = $tmp;
        }
        $headerstr = 'ID,适用地区,发布分支,产品标题,一级分类,二级分类,创建时间,最近更新时间,最新版本号,创建者,审核总监,浏览量,分享量,点赞量,收藏量,购买咨询量,下载量,评论量,建议量';
        $header = explode(',',$headerstr);
        FwUtility::exportExcel($data, $header,'产品列表','产品列表_'.date('Y-m-d'));
    }
    
    
    public function actionAdd(){
       // print_r($_POST);die;
        $getCityList = ServiceRegion::model()->getCityList();
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $Categroy = $this->actionSelectCategroy();
        $template_type = Product::model()->templateTypeKey;
        if( $this->filiale_id==BRANCH_ID ){
            $renderData['region_id'] = BRANCH_ID;
            $renderData['region_name'] = '全国';
        }else{
            $getBranchToCity = ServiceRegion::model()->getBranchToCity($this->filiale_id);
            $renderData['region_id'] = $getBranchToCity[0]['region_id'];
            $renderData['region_name'] = $getBranchToCity[0]['region_name'];
        }
        if($id){
            $data = Product::model()->getProduct($id);
        }else{
            $data = array('id' => 0, 'filiale_id' => '', 'apply_province_code' => '', 'category_parent_id' => '', 'category_id' => '',
                'title' => '', 'image' => '', 'sort' => '', 'version' => '', 'user_id' => '', 'create_name' => '', 'audit_name' => '',
                'note' => '', 'advice_url' => '', 'banner_pic' => '', 'custom_title'=>'','custom_video' => serialize(''), 'template_type' => 0);
        }
        $renderData['getCityList'] = $getCityList;
        $renderData['categroy_list'] = $Categroy;
        $renderData['template_type'] = $template_type;
        $renderData['id'] = $id;
        $renderData['data'] = $data;
        $this->render('add1',$renderData);
    }
    
    public function actionAdd_Op(){
        $msgNo = 'Y';
        $data['title'] = trim(Yii::app()->request->getParam('title'));
        try{
            if(empty($_FILES)){
                throw new Exception(9);
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
            
            $data['category_parent_id'] = intval(Yii::app()->request->getParam('category_parent_id'));
            $data['category_id'] = intval(Yii::app()->request->getParam('category_id'));
            
            if( empty($data['category_parent_id']) ){
                throw new Exception(8);
            }
            $data['apply_province_code'] = intval(Yii::app()->request->getParam('apply_province_code'));
            $data['version'] = trim(Yii::app()->request->getParam('version'));
            $data['advice_url'] = trim(Yii::app()->request->getParam('advice_url'));
            $data['user_id'] = $this->user_id;
            $data['create_name'] = trim(Yii::app()->request->getParam('create_name'));
            $data['audit_name'] = trim(Yii::app()->request->getParam('audit_name'));
            $data['note'] = trim(Yii::app()->request->getParam('note'));
            $data['template_type'] = trim(Yii::app()->request->getParam('template_type'));
            if($data['template_type'] == 1){
                $data['banner_pic'] = trim(Yii::app()->request->getParam('banner_pic'));
                $data['renewal_link']= trim(Yii::app()->request->getParam('renewal_link'));
                $data['custom_title']= trim(Yii::app()->request->getParam('custom_title'));
                $custom_video_id = Yii::app()->request->getParam('custom_video_id');
                $custom_video_name = Yii::app()->request->getParam('custom_video_name');
                $data['custom_video'] = array();
                if(empty($data['banner_pic']))
                    throw new Exception('2004');
                if(empty($data['custom_title']))
                    throw new Exception('2007');
                if(empty($custom_video_id))
                    throw new Exception('2005');
                foreach($custom_video_id as $k => $v){
                    if(!empty($v))
                        $data['custom_video'][$v] = $custom_video_name[$k];
                }
                if(count($data['custom_video']) < 3)
                    throw new Exception('2006');
                $data['custom_video'] = serialize($data['custom_video']);
            }else{
                $data['banner_pic'] = '';
                $data['custom_video'] = '';
            }
            $data['filiale_id'] = $this->filiale_id;
            $data['status'] = 2;
            $keywords = Yii::app()->request->getParam('keywords');
            if($keywords){
                $extdata['keywords'] = serialize($keywords);
            }
            $extdata['advice_url'] = $data['advice_url'];
			
            $upload = new Upload();
            $path = '/uploads/product/filiale_id_'.$this->filiale_id.'/';
            $upload->set('path',$path);
            $image_path = $upload->uploadFile($key[0]);
            $getErrorMsg = $upload->getErrorMsg();
            if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                throw new Exception('1014');
            }
            $data['image'] = $image_path;
            $id = Product::model()->ProductSave($data, $extdata);
            if(!$id){
		throw new Exception(1);
            }
            echo $this->encode($msgNo, $id);die;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    public function actionEdit_Op(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $product = Product::model()->findByPk($id);
        $data['id'] = $id;
        $data['title'] = trim(Yii::app()->request->getParam('title'));
        $data['template_type'] = trim(Yii::app()->request->getParam('template_type'));
        try{
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
            }

            if($data['template_type'] == 1){
                $data['banner_pic'] = trim(Yii::app()->request->getParam('banner_pic'));
                $data['renewal_link']= trim(Yii::app()->request->getParam('renewal_link'));
                $data['custom_title']= trim(Yii::app()->request->getParam('custom_title'));
                $custom_video_id = Yii::app()->request->getParam('custom_video_id');
                $custom_video_name = Yii::app()->request->getParam('custom_video_name');
                $data['custom_video'] = array();
                if(empty($data['banner_pic']))
                    throw new Exception('2004');
                 if(empty($data['custom_title']))
                     throw new Exception('2007');
                if(empty($custom_video_id))
                    throw new Exception('2005');
                foreach($custom_video_id as $k => $v){
                    if(!empty($v))
                        $data['custom_video'][$v] = $custom_video_name[$k];
                }
                if(count($data['custom_video']) < 3)
                    throw new Exception('2006');
                $data['custom_video'] = serialize($data['custom_video']);
            }else{
                $data['banner_pic'] = '';
                $data['custom_video'] = serialize('');
            }
            $data['category_parent_id'] = intval(Yii::app()->request->getParam('category_parent_id'));
            $data['category_id'] = intval(Yii::app()->request->getParam('category_id'));
            if( empty($data['category_parent_id']) ){
                throw new Exception(8);
            }
            $data['apply_province_code'] = intval(Yii::app()->request->getParam('apply_province_code'));
            $data['version'] = trim(Yii::app()->request->getParam('version'));
            $data['advice_url'] = trim(Yii::app()->request->getParam('advice_url'));
            $data['create_name'] = trim(Yii::app()->request->getParam('create_name'));
            $data['audit_name'] = trim(Yii::app()->request->getParam('audit_name'));
            $data['note'] = trim(Yii::app()->request->getParam('note'));
            $keywords = Yii::app()->request->getParam('keywords');
            if($keywords){
                $extdata['keywords'] = serialize($keywords);
            }else{
                $extdata['keywords'] = '';
            }
            $extdata['advice_url'] = $data['advice_url'];
			
            if(!empty($_FILES)){
                $upload = new Upload();
                $path = '/uploads/product/filiale_id_'.$product->filiale_id.'/';
                $upload->set('path',$path);
                $image_path = $upload->uploadFile($key[0]);
                $getErrorMsg = $upload->getErrorMsg();
                if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                    throw new Exception('1014');
                }
                $data['image'] = $image_path;
            }
            $up = Product::model()->ProductSave($data, $extdata);
            if(!$up){
				throw new Exception(1);
			}
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    public function actionAdvance_template(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $product_advance_template = ProductAdvanceTemplate::model()->findAll('product_id=:product_id and _delete=:_delete',array('product_id' => $id,'_delete'=>0));
        $product_advance_son_template = ProductAdvanceSonTemplate::model()->findAll('product_id=:product_id and _delete=:_delete',array('product_id' => $id,'_delete'=>0));
        if(empty($product_advance_template))
            $product_advance_template = array(new ProductAdvanceTemplate());
        $this->render('product_advance_template',array('product_id' => $id,'template_infos' => $product_advance_template));
    }

    public function actionSave_advance_template(){
        try{
            $msgNo = 'Y';
            $ids = Yii::app()->request->getParam( 'id' );
            $sonids = Yii::app()->request->getParam( 'son_id' );
            $product_id = Yii::app()->request->getParam( 'product_id' );
            $types = Yii::app()->request->getParam( 'type' );
            $titles = Yii::app()->request->getParam( 'title' );
            $animation_types = Yii::app()->request->getParam( 'animation_type' );
            $font_colors = Yii::app()->request->getParam( 'font_color' );
            $sub_titles = Yii::app()->request->getParam( 'sub_title' );
            $descs = Yii::app()->request->getParam( 'desc' );
            $background_pics = Yii::app()->request->getParam( 'background_pic' );
            if(empty($ids)){
                throw new Exception(2001);
            }
            if(empty($product_id))
                throw new Exception(2002);

            //删除失效模板片段
            $sections = ProductAdvanceTemplate::model()->find_by_product_id($product_id);
            $delete_ids = array_diff(array_keys($sections),$ids);
            foreach($delete_ids as $delete_id){
                if(!empty($delete_id))
                    ProductAdvanceTemplate::model()->deleteRecordByPK($delete_id);
            }
            //新增及修改模板片段
            foreach($ids as $index=>$id){
                $info = array('id' => $ids[$index], 'type' => $types[$index], 'animation_type' => $animation_types[$index],
                    'title' => $titles[$index], 'sub_title' => $sub_titles[$index], 'desc' => $descs[$index],'font_color' => $font_colors[$index],
                    'background_pic' => $background_pics[$index],'sort' => $index, 'product_id' => $product_id);
                $result = ProductAdvanceTemplate::model()->create_or_update_record($info);
                if(!$result){
                    throw new Exception(1);
                }
            }
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);

    }
    /**
     * 子项片段设置
     */
    public function actionSon_advance_template(){
        if(!isset($_GET['iDisplayLength'])){
            $id = intval(Yii::app()->request->getParam( 'id' ));
            $this->render('product_advance_son_template',array('product_id' => $id));exit;
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
        $product_id = trim(Yii::app()->request->getParam('product_id'));
        $con['_delete'] = 0;
        $con['product_id'] = $product_id;
        $list = ProductAdvanceTemplate::model()->get_recruitment_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);        
    }
    /**
     * 子项视频设置
     */
    public function actionsave_advance_son_video(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                 $content = trim(Yii::app()->request->getParam( 'content'));
                 $template_id = trim(Yii::app()->request->getParam('template_id'));
                 if(empty($content))
                    throw new Exception('2009'); 
                 if(empty($template_id))
                     throw new Exception('1006'); 
                 $findInfo = ProductAdvanceTemplate::model()->findByPk($template_id);
                 if($findInfo->is_son_pic == 2){
                     throw new Exception('2008'); 
                 }
                 $flag = ProductAdvanceTemplate::model()->updateByPk($template_id,array('video_url'=>$content));

                 if($flag){
                     $msgNo = 'Y';
                 }else{
                     throw new Exception('1');
                 }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);           
        }else{
             $template_id = trim(Yii::app()->request->getParam('template_id'));
             $findInfo = ProductAdvanceTemplate::model()->findByPk($template_id);
             $this->render('product_advance_son_video',array('template_id'=>$template_id,'info'=>$findInfo));
        }
    }
    /**
     * 初始化子项记录
     */
    public function actioninitialize(){
        try {
            $template_id = trim(Yii::app()->request->getParam('template_id'));
            $product_id = trim(Yii::app()->request->getParam('product_id'));
            if(empty($template_id))
                throw new Exception('1006');
            if(empty($product_id))
                throw new Exception('1006');
            $flag = ProductAdvanceTemplate::model()->updateByPk($template_id,array('is_son_pic'=>1,'video_url'=>''));
            $editson = ProductAdvanceSonTemplate::model()->updateAll(array('_delete'=>1),'template_id=:template_id and product_id=:product_id',array(':template_id'=>$template_id,':product_id'=>$product_id));
            $msgNo = 'Y';
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    /**
     * 保存子项片段
     */
    public function actionSave_advance_son_template(){
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try{
            $msgNo = 'Y';
            $template_id= Yii::app()->request->getParam( 'template_id');
            $product_id = Yii::app()->request->getParam( 'product_id' );
            $ids = Yii::app()->request->getParam('son_id');
            $background_son_pic = Yii::app()->request->getParam( 'background_son_pic' );
            if(empty($ids)){
                throw new Exception(2001);
            }
            if(empty($product_id))
                throw new Exception(2002);
            if(empty($template_id))
                throw new Exception(1006);
            $findInfo = ProductAdvanceTemplate::model()->findByPk($template_id);
            if(!empty($findInfo)){
                if(!empty($findInfo->video_url)){
                    throw new Exception('2008'); 
                }
            }            
            //删除失效模板片段
            $sections = ProductAdvanceSonTemplate::model()->find_by_product_id($template_id,$product_id);
            if(!empty($sections)){
                 $delete_ids = array_diff(array_keys($sections),$ids);
                 foreach($delete_ids as $delete_id){
                      if(!empty($delete_id))
                           ProductAdvanceSonTemplate::model()->deleteRecordByPK($delete_id);
                 }
            }
            //新增及修改模板片段
            foreach($ids as $index=>$id){
                $info = array('id' => $ids[$index],'background_son_pic' => $background_son_pic[$index],'template_id' => $template_id, 'sort' => $index,'product_id' => $product_id);
                $result = ProductAdvanceSonTemplate::model()->create_or_update_record($info);
                if(!$result){
                    throw new Exception(1);
                }
            }
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);          
      }else{
        $product_id = intval(Yii::app()->request->getParam( 'product_id' ));
        $template_id = intval(Yii::app()->request->getParam( 'template_id' ));
        $product_advance_son_template = ProductAdvanceSonTemplate::model()->findAll('product_id=:product_id and template_id=:template_id and _delete=:_delete',array(':product_id' => $product_id,'template_id'=>$template_id,':_delete'=>0));
        if(empty($product_advance_son_template))
            $product_advance_son_template = array(new ProductAdvanceSonTemplate());
        $this->render('product_son_template',array('product_id' => $product_id,'template_id'=>$template_id,'template_infos' => $product_advance_son_template));           
      }  
    }
    public function actionAdvance_download(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $download_infos = ProductAdvanceDownloadInfo::model()->findAll('product_id=:product_id',array('product_id' => $id));
        if(empty($download_infos))
            $download_infos = array(new ProductAdvanceDownloadInfo());
        $this->render('product_advance_download',array('product_id' => $id,'download_infos' => $download_infos));
    }

    public function actionSave_advance_download(){
        try{
            $msgNo = 'Y';
            $ids = Yii::app()->request->getParam( 'id' );
            $product_id = Yii::app()->request->getParam( 'product_id' );
            $types = Yii::app()->request->getParam( 'type' );
            $titles = Yii::app()->request->getParam( 'title' );
            $descs = Yii::app()->request->getParam( 'desc' );
            $product_pic = Yii::app()->request->getParam( 'product_pic' );
            $download_url = Yii::app()->request->getParam( 'download_url' );
            $download_pic = Yii::app()->request->getParam( 'download_pic' );
            $status = Yii::app()->request->getParam( 'status' );
            if(empty($ids)){
                throw new Exception(2003);
            }
            if(empty($product_id))
                throw new Exception(2002);

            //删除下载项
            $download_infos = ProductAdvanceDownloadInfo::model()->find_by_product_id($product_id);
            $delete_ids = array_diff(array_keys($download_infos),$ids);
            foreach($delete_ids as $delete_id){
                if(!empty($delete_id))
                    ProductAdvanceDownloadInfo::model()->deleteRecordByPK($delete_id);
            }

            //新增及修改下载项
            foreach($ids as $index=>$id){
                $info = array('id' => $ids[$index], 'download_type' => $types[$index], 'product_pic' => $product_pic[$index],
                    'title' => $titles[$index], 'desc' => $descs[$index],'download_url' => $download_url[$index],
                    'sort' => $index, 'download_pic' => $download_pic[$index], 'product_id' => $product_id);
                if($info['download_type'] == 1 && !preg_match('/(^http:\/\/)|(^https:\/\/)/i', $info['download_url'])){
                    $info['download_url'] = 'http://' . $info['download_url'];
                }
                $result = ProductAdvanceDownloadInfo::model()->create_or_update_record($info);
                if(!$result){
                    throw new Exception(1);
                }
            }
            Product::model()->updateByPk($product_id, array('status'=>$status));
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);

    }

    //异步上传图片
    public function actionSave_pic(){
        if (isset($_FILES['pic'])) {
            $upload = new Upload();
            $flag = $upload->uploadFile('pic');
            if (empty($upload->getErrorMsg())) {
                echo CJSON::encode(array('base_url' => UPLOADURL,'pic_url' => $flag, 'status' => 'Y'));exit;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        }
    }
    
    //产品介绍
    public function actionContent(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
		$bakobj = new ARedisHash('product_content_draft_bak_' . $id);
        if(!isset($_POST['save'])){
            //如果模板类型不为默认模板则重定向到advance_template;
            $product = Product::model()->findByPk($id);
            if(!empty($product) && $product->template_type != 0){
                $this->redirect(array('product/advance_template','id' =>$id));exit;
            }
            $productExtension = ProductExtension::model()->findByPk($id, array('select'=>array('id','content')));
            $productExtension = $productExtension->attributes;
            //rdis暂存的内容
            $bak = $bakobj->data;
            if( !empty($bak) && !empty(trim($bak['data'])) ){
                $productExtension['content'] = $bak['data'];
            }
            $this->render('add2', array('id'=>$id,'data'=>$productExtension));
            exit;
        }
        $extendata['id'] = $id;
        //$data['status'] = intval(Yii::app()->request->getParam( 'status' ));
        $extendata['content'] = trim(Yii::app()->request->getParam( 'content' ));
        $ins = ProductExtension::model()->productExtensionSave($extendata);
        if($ins){
            $bakobj->clear();
            echo $this->encode($msgNo, $this->msg[$msgNo]);
        }else{
            echo $this->encode(1, $this->msg[1]);
        }
    }
    //下载体验
    public function actionDown_Experience(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
		$bakobj = new ARedisHash('product_down_experience_draft_bak_' . $id);
        if(!isset($_POST['save'])){
            //如果模板类型不为默认模板则重定向到advance_template;
            $product = Product::model()->findByPk($id);
            if(!empty($product) && $product->template_type != 0){
                $this->redirect(array('product/advance_download','id' =>$id));exit;
            }
            $productExtension = ProductExtension::model()->findByPk($id, array('select'=>array('id','down_experience')));
            $productExtension = $productExtension->attributes;
            //rdis暂存的内容
            $bak = $bakobj->data;
            if( !empty($bak) && !empty(trim($bak['data'])) ){
                $productExtension['down_experience'] = $bak['data'];
            }
            $this->render('add3', array('id'=>$id,'data'=>$productExtension));
            exit;
        }
        $extendata['id'] = $id;
        $status = intval(Yii::app()->request->getParam( 'status' ));
        $extendata['down_experience'] = trim(Yii::app()->request->getParam( 'content' ));
        $ins = ProductExtension::model()->productExtensionSave($extendata);
        if($ins){
            $bakobj->clear();
            Product::model()->updateByPk($id, array('status'=>$status));
            echo $this->encode($msgNo, $this->msg[$msgNo]);
        }else{
            echo $this->encode(1, $this->msg[1]);
        }
    }
    
    //设置热门排序
    public function actionSet_Hot_Product(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam('id'));
        $sortArr = array(0=>0, 1=>4, 2=>3, 3=>2, 4=>1);
        $sort = intval(Yii::app()->request->getParam('hot_product'));
        try {
            if(!$id){
                throw new Exception(1006);
            }
            if(!isset($sortArr[$sort])){
                throw new Exception(1006);
            }
            $sort = $sortArr[$sort];
            $Product = Product::model()->findByPk($id);
            if(empty($Product)){
                throw new Exception(3);
            }
            if($sort!=0){
                $count = Product::model()->getCount(['id!'=>$id,'sort'=>$sort,'apply_province_code'=>$Product->apply_province_code]);
                if($count){
                    throw new Exception(1047);
                }
            }
            if($Product->sort!=$sort){
                $Product->sort = $sort;
                $Product->save();
            }
        } catch(\Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    
    //索引管理设置
    public function actionRecommended_Set(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam('id'));
        if(!isset($_POST['save'])){
            $data = Product::model()->findByPk($id);
            $this->render('recommended',array('id'=>$id,'data' => $data));exit;
        }
        $data['id'] = $id;
        $data['down_link'] = trim(Yii::app()->request->getParam('down_link'));
        $data['video_link'] = trim(Yii::app()->request->getParam('video_link'));
        $data['data_link'] = trim(Yii::app()->request->getParam('data_link'));
        $data['training_link'] = trim(Yii::app()->request->getParam('training_link'));
        $data['activity_link'] = trim(Yii::app()->request->getParam('activity_link'));
        $up = Product::model()->ProductSave($data, $data);
        if(!$up){
            $msgNo = 1;
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //统计分析
    public function actionStatistical(){
        $id = intval(Yii::app()->request->getParam('id'));
        $data = ProductExtension::model()->findByPk($id);
        $this->render('statistical',array('data' => $data));
    }
    
    //活动评论列表
    public function actionComment(){
        
        $id = intval(Yii::app()->request->getParam( 'id' ));
        if(!isset($_GET['iDisplayLength'])){
            $this->render('comment_list',array('id'=>$id));exit;
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
        $con = array('_delete'=>0,'activity_id'=>$id, 'status'=>3);
        $list = ActivityComment::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    //删除评论
    public function actionComment_Del(){
        $msgNo = 'Y';
        $ids = Yii::app()->request->getParam( 'ids' );
        $product_id = intval(Yii::app()->request->getParam( 'id' ));
        $Product = Product::model()->findByPk($product_id,array('select'=>array('filiale_id')));
        $filiale_id = $Product['filiale_id'];
        try{
            if(!is_array($ids)){
               throw new Exception('1006'); 
            }
            $del = ActivityComment::model()->updateByPk(array_keys($ids),array('_delete'=>1),'status=3');
            if(!$del){
                throw new Exception('1'); 
            }
            //评论是否被删完 删完要还要删除redis中的用户user_id 就当他没评论过
            //$count = ActivityComment::model()->getCount(array('activity_id'=>$activity_id,'user_id'=>$this->user_id,'_delete'=>0));
            //$test=Yii::app()->cache->set("abc" ,"1234567");
            
            //记录日志
            foreach($ids as $id=>$user_id){
                CreditLog::addCreditLog(CreditLog::$creditTraining, CreditLog::$typeKey[11], $product_id, 'subtract', '删除评论',$user_id,$filiale_id);
            }
            
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //分类展示列表
    public function actionCategory(){
        $category_info = Category::model()->get_list();     
        $this->render('category',array('category_info' => $category_info));
    }
    
    //分类下拉框 
    public function actionSelectCategroy(){
        $category_id = intval(Yii::app()->request->getParam('category_id'));
        $list = Category::model()->getChildCategory($category_id);
        if(!$category_id){
            return $list;
        }else{
            $option = '';
            foreach($list as $v){
                $option .= '<option value='.$v['id'].'>'.$v['name'].'</option>';
            }
            echo $option;
        }
    }
    
    //购买咨询列表
    public function actionConsult(){
        if(!isset($_GET['iDisplayLength'])){
            $cityList = array();
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('consult',array('getCityList' => $getCityList,'cityList'=>$cityList));exit;
        }
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord            = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field          = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord            = !empty($ord) ? $ord : 'desc';
        $field          = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code'));
        $city_code = intval(Yii::app()->request->getParam( 'city_code'));
        $search_type = Yii::app()->request->getParam( 'search_type' );
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con = array('_delete'=>0,'type'=>0);
        if($starttime){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        
        if($this->filiale_id==BRANCH_ID){
            if( $province_code!=BRANCH_ID ){
                $con['apply_province_code'] = $province_code;
                if(!empty($city_code)){
                    $con['apply_city'] = $city_code;
                }
            }
        }else{
            $region_id = ServiceRegion::model()->getRegionIdByBranch($this->filiale_id);
            $con['apply_province_code'] = $region_id;
            if(!empty($city_code)){
                $con['apply_city'] = $city_code;
            }
        }
        if(!empty($search_content) && in_array($search_type,array('product_title'))){
            $proRes = Product::model()->findInfo(array('title'=>$search_content));
            $con['product_id']  = !empty($proRes)?$proRes->id:'0';
        }
        if(!empty($search_content) && in_array($search_type,array('member_user_name','realname', 'mobile'))){
            $con[$search_type] = $search_content;
        }
        $list = ProductFeedback::model()->get_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    
    public function actionConsult_Excel(){
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code')); 
        $city_code     = intval(Yii::app()->request->getParam( 'city_code')); 
        $search_type = Yii::app()->request->getParam( 'search_type' );
        $search_content = trim(Yii::app()->request->getParam( 'search_content' ));
        $con = array('_delete'=>0,'type'=>0);
        if($starttime){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        
        if($this->filiale_id!=BRANCH_ID){
            $region_id = ServiceRegion::model()->getRegionIdByBranch($this->filiale_id);
            $con['apply_province_code'] = $region_id;
        }
        if( !empty($search_content) && in_array($search_type,array('member_user_name','realname', 'mobile')) ){
            $con[$search_type] = $search_content;
        }
        $list = ProductFeedback::model()->get_list($con, 'desc', 'id', 50000, 0);
        $data = array();
        foreach($list['data'] as $k=>$v){
           $tmp['id'] = $v['id'];
           $tmp['order_sn'] = $v['order_sn'];
           $tmp['_create_time'] = $v['_create_time'];
           $tmp['order_time'] = $v['order_time'];
           $tmp['apply_province_name'] = $v['apply_province_name'];
           $tmp['apply_city_name'] = $v['apply_city_name'];
           $tmp['member_user_name'] = $v['member_user_name'];
           $tmp['realname'] = $v['realname'];
           $tmp['mobile'] = $v['mobile'];
           $tmp['unit'] = $v['unit'];
           $tmp['salesman'] = $v['salesman'];
           $tmp['order_amount'] = $v['order_amount'];
           $tmp['title'] = $v['title'];
           $tmp['buy_product'] = $v['buy_product'];
           $tmp['content'] = $v['content'];
           $tmp['reply'] = $v['reply'];
           $tmp['statusname'] = $v['statusname'];
           $data[] = $tmp;
        }
        $headerstr = '编号,订单号,反馈时间,订单日期,省,市,账号,姓名,联系电话,单位,销售员,订单金额,产品名称,购买产品,咨询详情,处理记录,状态';
        $header = explode(',',$headerstr);
        FwUtility::exportExcel($data, $header,'产品购买咨询','产品购买咨询_'.date('Y-m-d'));
    }
    
    //产品建议列表
    public function actionAdvice(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('advice',array('getCityList' => $getCityList));exit;
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
        $con = array('_delete'=>0,'type'=>1);
        if($starttime){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        
        if($this->filiale_id==BRANCH_ID){
            if( $province_code!=BRANCH_ID ){
                $con['apply_province_code'] = $province_code;
            }
        }else{
            $region_id = ServiceRegion::model()->getRegionIdByBranch($this->filiale_id);
            $con['apply_province_code'] = $region_id;
        }
        $list = ProductFeedback::model()->get_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    //导出产品建议
    public function actionAdvice_Excel(){
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); //分支id前两位
        $con = array('_delete'=>0,'type'=>1);
        if($starttime){
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }
        
        if($this->filiale_id==BRANCH_ID){
            if( $province_code!=BRANCH_ID ){
                $con['apply_province_code'] = $province_code;
            }
        }else{
            $region_id = ServiceRegion::model()->getRegionIdByBranch($this->filiale_id);
            $con['apply_province_code'] = $region_id;
        }
        $list = ProductFeedback::model()->get_list($con, 'desc', 'id', 50000, 0);
        $data = array();
        foreach($list['data'] as $k=>$v){
           $tmp['id'] = $v['id'];
           $tmp['_create_time'] = $v['_create_time'];
           $tmp['apply_province_name'] = $v['apply_province_name'];
           $tmp['title'] = $v['title'];
           $tmp['member_user_name'] = $v['member_user_name'];
           $tmp['mobile'] = $v['mobile'];
           $tmp['email'] = $v['email'];
           $tmp['content'] = $v['content'];
           $data[] = $tmp;
        }
        $headerstr = '编号,反馈时间,地区,产品,账号,手机号,邮箱,产品建议内容';
        $header = explode(',',$headerstr);
        FwUtility::exportExcel($data, $header,'产品建议','产品建议_'.date('Y-m-d'));
    }
    
    //保存处理记录
    public function actionSave_Reply(){
        $id           = intval(Yii::app()->request->getParam('id'));
        $reply        = trim(Yii::app()->request->getParam('reply'));
        $order_sn     = trim(Yii::app()->request->getParam('order_sn'));
        $buy_product  = trim(Yii::app()->request->getParam('buy_product'));
        $order_time  = trim(Yii::app()->request->getParam('order_time'));
        $salesman    = trim(Yii::app()->request->getParam('salesman'));
        $unit         = trim(Yii::app()->request->getParam('unit'));
        $order_amount = trim(Yii::app()->request->getParam('order_amount'));
        $statusname   = trim(Yii::app()->request->getParam('statusname'));
        $data['reply'] = \CHtml::encode($reply);
        $data['user_id'] = $this->user_id;
        $data['reply_time'] = date('Y-m-d H:i:s');
        $data['_update_time'] = date('Y-m-d H:i:s');
        $data['order_sn'] = $order_sn;
        $data['buy_product'] = $buy_product;
        $data['order_time'] = $order_time;
        $data['salesman'] = $salesman;
        $data['unit'] = $unit;
        $data['order_amount'] = $order_amount;
        $data['statusname'] = $statusname;
        $up = ProductFeedback::model()->updateByPk($id, $data);
        $msgNo = !$up ? 1 : 'Y';
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    /*购买咨询页面*/
    public function actionreplyview(){
        $id = intval(Yii::app()->request->getParam('id'));
        $product_id = intval(Yii::app()->request->getParam('product_id'));
        $Product = Product::model()->findByPk($product_id);
        $data = ProductFeedback::model()->findByPk($id);
        $this->render('replyview',array('Product'=>$Product,'id'=>$id,'data'=>$data));
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
                throw new Exception(2);
            }
            $data = array('filiale_id'=>$this->filiale_id, 'parent_id' => $parent_id, 'name' => $category_name, '_delete'=>0);
            if(empty($id)){
                //查询分类名称是否已存在
                $count = Category::model()->getCount($data);
                if( $count ){
                    throw new Exception(5);
                }
                if($sort===''){
                    $sort = Category::model()->get_big_sort($parent_id);
                }
                $data['sort'] = $sort;
                $insert = Category::model()->createCategory($data);
                if(!$insert){
                    throw new Exception(1);
                }
            }else{
                $data['id!'] = $id;
                $count = Category::model()->getCount($data);
                if( $count ){
                    throw new Exception(5);
                }
                $category = Category::model()->findByPk($id);
                if(empty($category)){
                    throw new Exception(4);
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
    
    //删除
    public function actionDel_category(){
        $msgNo = 'Y';
        try{
            $id = intval(Yii::app()->request->getParam('id'));
            if(empty($id)){
                throw new Exception('3');
            }
            $category = Category::model()->findByPk($id);
            if( $category['parent_id']==0 ){ //.一级分类
                $CategoryCount = Category::model()->getCount( 
                    array(
                        'parent_id'=>$id,
                        'filiale_id'=>$this->filiale_id,
                        '_delete'=>0) );
                if( $CategoryCount ){
                    throw new Exception(6);
                }else{
                    $productCount = Product::model()->getCount( array('category_parent_id'=>$id) );
                    if( $productCount ){
                        throw new Exception(7);
                    }
                    $up = $category->updateCategory(array( '_delete' => 1));
                    if(!$up){
                        throw new Exception(1);
                    }
                }
            }else{ //二级分类
                $productCount = Product::model()->getCount( array('category_id'=>$id) );
                if( $productCount ){
                    throw new Exception(7);
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
    
    //删除产品及相关数据
    public function actionDelete(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $model = Product::model()->findByPk($id);
        $Connection = $model->dbConnection->beginTransaction();
        //评论表
        $del_1 = ActivityComment::model()->deleteAll('activity_id=:training_id and status=3', array(':training_id'=>$id) );
        //购买咨询
        $del_2 = ProductFeedback::model()->deleteAll('product_id=:product_id', array(':product_id'=>$id) );
        //扩展表
        $del_3 = ProductExtension::model()->deleteByPk($id);
        //日志记录表
        $del_4 = ProductActionLog::model()->deleteAll('obj_id=:product_id', array(':product_id'=>$id) );
        //产品表
        $del_5 = Product::model()->deleteByPk($id);
        if( $del_1!==false && $del_2!==false && $del_3!==false && $del_4!==false && $del_5!==false ){
            $Connection->commit();
        }else{
            $msgNo = '1';
            $Connection->rollBack();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    /**
     * 下载体验报表统计
     */
    public function actionreport(){
        if(!isset($_GET['iDisplayLength'])){
            $this->render('report');exit;
        }
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord            = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field          = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord            = !empty($ord) ? $ord : 'asc';
        $field          = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));        
        $con = array();
        if($starttime){
                $con['_create_time>'] = $starttime;
                $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }        
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            $findAll = Product::model()->findInfo(array('template_type'=>1),'all');
            foreach ($findAll as $key=>$item){
                $findIds [] = $item->id;
            }
            $con['obj_id'] = $findIds;
        } else {
            $find_branch_All = Product::model()->findInfo(array('template_type'=>1,'filiale_id'=>Yii::app()->user->branch_id),'all');
            foreach ($findAll as $key=>$item){
                $findIds [] = $item->id;
            }            
            $con['obj_id'] = $findIds;
        }
        $list = ProductDownloadDetail::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list); 
    }
     /*
      * 自动续费 
      */
    function actionrenewal(){
	if(!isset($_GET['iDisplayLength'])){
            $this->render('renewal');exit;
        }
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord            = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field          = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord            = !empty($ord) ? $ord : 'asc';
        $field          = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));        
        $con = array();
        if($starttime){
                $con['_create_time>'] = $starttime;
                $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }        
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            $findAll = Product::model()->findInfo(array('template_type'=>1),'all');
            foreach ($findAll as $key=>$item){
                $findIds [] = $item->id;
            }
            $con['obj_id'] = $findIds;
        } else {
            $find_branch_All = Product::model()->findInfo(array('template_type'=>1,'filiale_id'=>Yii::app()->user->branch_id),'all');
            foreach ($findAll as $key=>$item){
                $findIds [] = $item->id;
            }            
            $con['obj_id'] = $findIds;
        }
        $list = ProductRenewalDetail::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);        
    }
     /*
      * 导出excel表格. 
      */
    function actionexporttable(){
	 $filiale_id = $this->filiale_id;   
         $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
	 $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
         $type = trim(Yii::app()->request->getParam( 'type' ));
         if($type=='downloadstatis'){
            ProductDownloadDetail::model()->exportdownload($filiale_id,$starttime,$endtime);//下载统计
        }else if($type=='renewalstatis'){
             ProductRenewalDetail::model()->exportrenewal($filiale_id,$starttime,$endtime);//续费统计
        }
    }
}




     
        