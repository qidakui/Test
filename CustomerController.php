<?php
/**
 * 客服管理
 * @author hd
 */
use application\models\ServiceRegion;
use application\models\Customer\CustomerService;
class CustomerController extends Controller{
    
    private $msg = array(
        'Y' => '操作成功',
         1 => '数据库操作错误',
         2 => '客服QQ不能为空',
         3 => '此客服QQ已存在',
         4 => '数据错误，请联系管理员',
         5 => '当前分支下，只能存在一个客服',
    ); 
    private $setting_config = array(
            '0'=>'不限制',
            '1'=>'登录用户才可咨询',
            '2'=>'服务授权用户才可咨询'
    );
    public function actionindex(){   
        $this->render('index');
    }
    public function actionService_list(){
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
        }
        $con['_delete'] = 0;
        $list = CustomerService::model()->ger_Service_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);          
    }
    public function actionadd(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            $newarray = array();
            try {
                $service_qq = addslashes(trim(Yii::app()->request->getParam( 'service_qq' )));
                $setting_config = intval(Yii::app()->request->getParam( 'setting_config' ));
                $service_id = intval(Yii::app()->request->getParam( 'service_id' ));
                //非空检查
                if(empty($service_qq))
                   throw new Exception('2');
                 $newarray['service_qq'] = $service_qq;
                 $newarray['setting_config'] = $setting_config;
                 $newarray['filiale_id'] = Yii::app()->user->branch_id;
                 $newarray['user_id'] =Yii::app()->user->user_id;
                 if(!empty($service_id)){
                      $loldata = CustomerService::model()->findByPk($service_id);
                      if(!empty($loldata)){
                          $editservice = $loldata->editsave(array('id'=>$service_id,'service_qq'=>$service_qq,'setting_config'=>$setting_config,'_update_time'=>date('Y-m-d H:i:s'))); 
                          if($editservice){
                              OperationLog::addLog(OperationLog::$operationCustomerService , 'edit', '客服管理', $editservice, $loldata->attributes, array('service_qq'=>$service_qq,'_update_time'=>date('Y-m-d H:i:s')));
                              $this->refreshRedis();
                              $msgNo = 'Y';
                          }else{
                              throw new Exception('1');
                          }
                      }else{
                          throw new Exception('4');
                      }

                 }else{
                     $checkres = CustomerService::model()->checkInfo(array('service_qq'=>$service_qq,'_delete'=>0),'service');
                     if(!empty($checkres))
                         throw new Exception('3');
                     $checkBranchId = CustomerService::model()->checkInfo(array('filiale_id'=>Yii::app()->user->branch_id,'_delete'=>0),'branch_id');
                     if(!empty($checkBranchId))
                          throw new Exception('5');
                      $saveService = CustomerService::model()->CustomerServiceSave($newarray);
                      if($saveService){
                          OperationLog::addLog(OperationLog::$operationCustomerService , 'add', '客服管理', $saveService, array(), $newarray);
                          $this->refreshRedis();
                          $msgNo = 'Y';
                      }else{
                          throw new Exception('1');
                      }
                 }
            } catch (Exception $ex) {
                 $msgNo = $ex->getMessage();
            }
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        }else{
            $service_id= intval(Yii::app()->request->getParam('service_id'));
            $editinfo = CustomerService::model()->findByPk($service_id);
            $this->render('add',array('data'=>$editinfo,'service_id'=>$service_id,'setting_config'=>$this->setting_config ));
              
        }
    }
    public function actiondel_service(){
        try {
            $service_id = trim(Yii::app()->request->getParam( 'service_id' ));
            if(empty($service_id))
                throw new Exception('1');
            $model = CustomerService::model()->findbypk($service_id);
            if(empty($model))
                throw new Exception('1');
            $model->_delete = 1;
            $flag = $model->save();
            if ($flag) {
                $msgNo = 'Y';
                $delteRes = $this->refreshRedis();
                if($delteRes){
                     OperationLog::addLog(OperationLog::$operationCustomerService, 'del', '客服管理', $service_id, array(), array());
                }else{
                    throw new Exception('1'); 
                }
            }else{
                 throw new Exception('1');
            }
        } catch (Exception $ex) {
             $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    public function refreshRedis(){
        $newarray = array();
        $openArea = ServiceRegion::model()->getProvinceList();
        foreach ($openArea as $key=>$item){
            $newarray[] = $item->filiale_id!=QG_BRANCH_ID?substr($item->filiale_id, 0, 2):$item->filiale_id;
        }
        foreach ($newarray as $key=>$row){         
            Yii::app()->redis->getClient()->delete('fwxgx_'.'global_CustomerService_test_'.$row.  md5('global_CustomerService_test_'.$row));
        }
        return true;
    }
}

