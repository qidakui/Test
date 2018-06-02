<?php
use application\models\User\SmsLog;
class SmslogController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        0 => '参数错误',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '不可为空',
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
            $this->render('/admin/smslog');exit;
        }

        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'filiale_id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 40;
        
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        
        $yesterday = date("Y-m-d",strtotime("-1 day"));
        $starttime = empty($starttime) ? $yesterday : $starttime ;
        $endtime = empty($endtime) ? $yesterday : $endtime;
        $con = [];
        if($starttime==$endtime){
            $con['send_time'] = $starttime;
        }else{
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
        //print_r($con);
        $list = SmsLog::model()->getlist($con, $ord, $field, $limit, $page);

        echo CJSON::encode($list);
    }
    
    public function actionSmslogExcel(){
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        
        $yesterday = date("Y-m-d",strtotime("-1 day"));
        $starttime = empty($starttime) ? $yesterday : $starttime ;
        $endtime = empty($endtime) ? $yesterday : $endtime;
        $con = [];
        if($starttime==$endtime){
            $con['send_time'] = $starttime;
        }else{
            $con['starttime'] = $starttime;
            $con['endtime'] = $endtime;
        }
        //print_r($con);
        $list = SmsLog::model()->getlist($con, 'desc', 'filiale_id', 40, 0);
        $data = array();
        foreach($list['data'] as $v){
            $tmp['filiale'] = $v['filiale'];
            $tmp['activity'] = $v['activity'];
            $tmp['training'] = $v['training'];
            $tmp['artificial'] = $v['artificial'];
            $tmp['total'] = $v['total'];
            $data[] = $tmp;
        }
        $headerstr = '分支,同城活动,培训报名,手动发送,总数';
        $header = explode(',',$headerstr);
        FwUtility::exportExcel($data, $header,'短信发送记录','短信发送记录_'.$starttime.'~'.$endtime);
    }
  
}




     
        