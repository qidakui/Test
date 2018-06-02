<?php
/**
 * 开通地区
 * @author hd
 */
use application\models\ServiceRegion;
use application\models\Home\IndexBanner;
class OpenareaController extends Controller{
    
    private $msg = array(
        'Y' => '操作成功',
        1 => '数据库操作错误',
        3 => '开通地区不能为空',
        4=>  '地区不存在'
    ); 
    private $save_path = '/uploads/openarea/';
    
    public function actionindex(){   
        $this->render('index');
    }
    public function actionOpenArea_list(){
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord            = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field          = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord            = !empty($ord) ? $ord : 'desc';
        $field          = !empty($field) ? $field : 'sort';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $con['_delete'] = 0;
        $con['is_parent'] = 0;
        $list = ServiceRegion::model()->openArealist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    /**
     * 开通地区页面
     */
    public function actionopenarea(){
        $region_id = trim(Yii::app()->request->getParam('region_id'));
        $open_area = array();
        if(!empty($region_id)){
            $open_area []= $region_id;
        }
        $province_list = ServiceRegion::model()->getCityList();
        unset($province_list[32]);
        $this->render('openarea',array( 'province_list' => $province_list,'open_area'=>$open_area));
    }
    /**
     * 开通地区
     */
    public function actionSaveOpenArea(){
        try {
            $open_area = Yii::app()->request->getParam('open_area');
            if(empty($open_area))
              throw new Exception('3');
            if(isset($_FILES['openarea_pic']) && !empty($_FILES['openarea_pic']) && !empty($_FILES['openarea_pic']['name'])) {
                $_FILES['openarea_pic']['name'] = $open_area.'.jpg'; 
                $upload     = new Upload();
                $upload->set('israndname',false);
                $upload->set('path',$this->save_path);
                $flag       = $upload->uploadFile('openarea_pic');
                $errorMsg   = $upload->getErrorMsg();
                if(empty($errorMsg)){
                    $data['openarea_pic'] = $flag;
                }else{
                    throw new Exception('1004');
                }
                $editservice = ServiceRegion::model()->edit_Open_Area($open_area,array('_delete'=>0));
                if($editservice){
                    $this->refreshRedis();
                    OperationLog::addLog(OperationLog::$operationOpenArea , 'edit', '开通地区', $open_area, array(), array('_delete'=>0));
                    $msgNo = 'Y';
                }
            }else{
                $msgNo = 'Y';
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    /**
     * 关闭地区
     */
    public function actionclose_area(){
        try {
            $region_id = Yii::app()->request->getParam('region_id');
            if(empty($region_id))
                throw new Exception('4');
            $editservice = ServiceRegion::model()->edit_Open_Area($region_id,array('_delete'=>1));
            if($editservice){
                $this->refreshRedis();
                OperationLog::addLog(OperationLog::$operationOpenArea, 'edit', '关闭地区', $region_id, array('_delete'=>0), array('_delete'=>1));
                $msgNo = 'Y';
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    /**
     * 刷新redis
     */
    public function refreshRedis(){
        $openArea = objectToArr(ServiceRegion::model()->getProvinceList());
        if(!empty($openArea)){
            Yii::app()->redis->getClient()->delete('new_global_open_area');
            Yii::app()->redis->getClient()->delete('global_open_area');
        }
        
    }
}

