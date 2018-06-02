<?php
/**
 * 授权管理 
 * @author hd
 */
use application\models\ServiceRegion;
use application\models\Accredit\Accredit;
class AccreditController extends Controller{
    
     private $msg = array(
        'Y' => '操作成功',
         1 => '数据非法，请正常输入',
         2 => '分支信息错误',
    );    
    
    public function actionindex(){   
        $this->render('index');
    }
    
    public function actionAccredit_list(){
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord            = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field          = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord            = !empty($ord) ? $ord : 'asc';
        $field          = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        if(Yii::app()->user->branch_id != BRANCH_ID){
             $branchId = Yii::app()->user->branch_id;
             $con['filiale_id'] = $branchId;
        }else{
            $con = array();
        }
        $list = Accredit::model()->ger_Accredit_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);         
    }
    /**
     * 添加授权
     */
    public function actionOpen_accredit(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            try {
                $status = intval(Yii::app()->request->getParam('status'));
                $is_all = intval(Yii::app()->request->getParam('is_all'));
                 if(empty(Yii::app()->user->branch_id))
                    throw new Exception('2');
                $openconfig['filiale_id'] = Yii::app()->user->branch_id;
                $openconfig['status'] = isset($status)?$status:'0';
                $openconfig['is_all'] = isset($is_all)?$is_all:'0';
                $openAccredit = Accredit::model()->open_accredit($openconfig);
                if($openAccredit){
                    if($status == 0){
                        OperationLog::addLog(OperationLog::$operationOpenAccredit , 'del', '授权关闭', '', array(), $openAccredit);
                        $this->refreshRedis();
                    }else{
                        OperationLog::addLog(OperationLog::$operationOpenAccredit , 'add', '开区授权',$openAccredit, array(), $openconfig);  
                        $this->refreshRedis();
                    }
                    $msgNo = 'Y';
                }
            } catch (Exception $ex) {
                 $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        }else{
            $this->render('open_accredit',array('branch_id'=>Yii::app()->user->branch_id));
        }
    }
    public function refreshRedis(){
        $newarray = array();
        $openArea = ServiceRegion::model()->getProvinceList();
        foreach ($openArea as $key=>$item){
            $newarray[] = $item->filiale_id!=QG_BRANCH_ID?substr($item->filiale_id, 0, 2):$item->filiale_id;
        }
        foreach ($newarray as $key=>$row){         
            Yii::app()->redis->getClient()->delete('fwxgx_'.'global_check_accredit_'.$row.  md5('global_check_accredit_'.$row));
        }
        return true;    
    }
}

