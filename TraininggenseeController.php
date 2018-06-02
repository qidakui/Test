<?php
use application\models\Training\Training;
use application\models\Training\TrainingGenseePlan;
use application\models\Training\TrainingGenseeMaxValue;
class TraininggenseeController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        0 => '参数错误',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '单次设置跨度不能超过一年',
        4 => '最大人数限制不可小于此时间段内已有培训人数限制总和',
        5 => '无权修改'
    );
    public $filiale_id;
    public function init(){
        parent::init();
        $this->filiale_id = Yii::app()->user->branch_id;
    }

    /*
     * 大讲堂计划列表
     */
    public function actionIndex(){
        //TrainingGenseePlan::model()->setPlan();die;
        //TrainingGenseePlan::model()->initializationData();die;
        
        $down = Yii::app()->request->getParam( 'down' );
        if($down && !Yii::app()->user->user_id){
            echo CJSON::encode(array('status'=>'0','info'=>'登录失效'));exit;
        }
        if(!isset($_GET['iDisplayLength']) && empty($down)){
            $mini = Yii::app()->request->getParam( 'mini' );
            $_starttime = Yii::app()->request->getParam( 'starttime' );
            $_endtime   = Yii::app()->request->getParam( 'endtime' );
            $BranchList = TrainingGenseePlan::model()->_getBranchList();
            $this->render('gensee_plan_list',array('BranchList'=>$BranchList,'mini'=>$mini,'_endtime'=>$_endtime,'_starttime'=>$_starttime));exit;
        }
        
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'asc';
        $field      = !empty($field) ? $field : 'date_time';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 32;
        $limit      = $down ? 10000 : $limit;

        $starttime = Yii::app()->request->getParam( 'starttime' );
        $endtime   	= Yii::app()->request->getParam( 'endtime' );
        if(strlen($starttime)<16){
            $time_slot = trim(Yii::app()->request->getParam( 'time_slot' ));
            list($starthour,$endhour) = explode('-',$time_slot);
            $starttime = $starttime .' '. $starthour;
            if($endhour=='24:00'){
                $endtime = date('Y-m-d H:i:s',strtotime($endtime)+86400);
            }else{
                $endtime = $endtime.' '. $endhour;
            }
        }

        if($starttime && $endtime && strlen($starttime)>=16 && strlen($endtime)>=16){
            $con['time'] = array($starttime,$endtime);
        }
        $con['status'] = 1;  
        $list = TrainingGenseePlan::model()->getlist($con, $ord, $field, $limit, $page);
        if($limit>32){
            $this->actionDownExcel($list, $con['time']);die;
        }
        echo CJSON::encode($list);
    }
    
    /*
     * 设置最大限制人数
     */
    public function actionSet_number(){
        $msgNo = 'Y';
        $startdate = trim(Yii::app()->request->getParam( 'startdate' ));
        $enddate = trim(Yii::app()->request->getParam( 'enddate' ));
        $maxvalue = intval(Yii::app()->request->getParam( 'maxvalue' ));
        
        try{
            if($this->filiale_id!=BRANCH_ID){
                throw new Exception('5');
            }
            if( strtotime($enddate)-strtotime($startdate)>31536000 ){
                throw new Exception('3');
            }
            //检查已经设置过的培训人数
            $training_limit_number = Training::model()->getLimitNumberSum($startdate, $enddate);
            if( $training_limit_number>$maxvalue ){
                $this->msg[4] .= '：'.$training_limit_number;
                throw new Exception('4');
            }
            $setnum = TrainingGenseeMaxValue::model()->saveData($startdate, $enddate, $maxvalue);
            $this->msg[$msgNo] = $setnum;
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 获取剩余数
     */
    public function actionGet_residual(){
        $msgNo = 'Y';
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $training_id = intval(Yii::app()->request->getParam( 'training_id' ));
        try{
            if( strtotime($endtime)-strtotime($starttime)>31536000 ){
                throw new Exception('3');
            }
            $total = TrainingGenseePlan::model()->getResidual($starttime, $endtime, $training_id);
            $this->msg[$msgNo] = $total; 
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
        
    }
    
    /*
     * 导出
     */
    public function actionDownExcel($data=array()){
        $file_dir = strstr(strtolower(php_uname('s')), 'windows') ? 'D:/' : '/tmp/';
        
        if(empty($data)){
            //检查文件是否存在   
            $file_name = Yii::app()->request->getParam( 'file_name' ).'.xls'; 
            if (! file_exists ( $file_dir . $file_name )) {    
                echo "NOT FILE";    
                exit();    
            }
            //打开文件    
            $file = fopen ( $file_dir . $file_name, "r" );    
            //输入文件标签     
            Header ( "Content-type: application/octet-stream" );    
            Header ( "Accept-Ranges: bytes" );    
            Header ( "Accept-Length: " . filesize ( $file_dir . $file_name ) );    
            Header ( "Content-Disposition: attachment; filename=大讲堂计划表_".date('Y-m-d').".xls" );    

            //读取文件内容并直接输出到浏览器    
            echo fread ( $file, filesize ( $file_dir . $file_name ) );    
            fclose ( $file ); 
            unlink($file_dir . $file_name);
        }else{
            $header = TrainingGenseePlan::model()->_getBranchList();
            $file_name =  '_tmp_gensee_plan_'.Yii::app()->user->user_id;
            FwUtility::exportExcel($data['data'], $header,'大讲堂计划表', $file_dir.$file_name, null, true);
            echo CJSON::encode(array('status'=>'Y','info'=>$file_name));
        }
        
    }
     
}




     
        