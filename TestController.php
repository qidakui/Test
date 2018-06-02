<?php
class TestController extends Controller
{
    protected $curl;
    protected $mockUrl = "http://e.fwxgx.com/index.php?r=usc/Add_message_total&msgToId=24406829&messageId=192";

    public function actionIndex()
    {

        //$output = Yii::app()->curl->get($this->mockUrl);
        //print_r($output);
    }


    //生成报名sql
    public function actionDaoru(){
        header("Content-type: text/html; charset=utf-8");
        $objPHPExcel = FwUtility::readerExcel(__DIR__.'/../../renyuan.xlsx');

        $sql = "insert into `e_member_user`(admin_user_id, company, english_name, chinese_name, nationality, nationality_rate, profession, dob, age, id_card, passport, passport_time, work_permit, work_permit_time, application_time, security_certificate_time, adress, yrs, mth, employment_status, yes_no) values ";
        for($i=501; $i<=651; $i++){

            $B = trim( $objPHPExcel->getActiveSheet()->getCell('B'.$i)->getValue() ); //Company
            $C = trim( $objPHPExcel->getActiveSheet()->getCell('C'.$i)->getValue() ); //英文名
            $D = trim( $objPHPExcel->getActiveSheet()->getCell('D'.$i)->getValue() ); //中文名
            $E = trim( $objPHPExcel->getActiveSheet()->getCell('E'.$i)->getValue() ); //国籍
            $F = trim( $objPHPExcel->getActiveSheet()->getCell('F'.$i)->getValue() ); //nationality_rate
            $G = trim( $objPHPExcel->getActiveSheet()->getCell('G'.$i)->getValue() ); //工种
            $H = trim( $objPHPExcel->getActiveSheet()->getCell('H'.$i)->getValue() ); //生日
            //$I = trim( $objPHPExcel->getActiveSheet()->getCell('I'.$i)->getValue() ); //年龄
            $J = trim( $objPHPExcel->getActiveSheet()->getCell('J'.$i)->getValue() ); //身份证
            $K = trim( $objPHPExcel->getActiveSheet()->getCell('K'.$i)->getValue() ); //护照号码
            $L = trim( $objPHPExcel->getActiveSheet()->getCell('L'.$i)->getValue() ); //护照有效期
            $M = trim( $objPHPExcel->getActiveSheet()->getCell('M'.$i)->getValue() ); //准证号码
            $N = trim( $objPHPExcel->getActiveSheet()->getCell('N'.$i)->getValue() ); //准证有效期
            $O = trim( $objPHPExcel->getActiveSheet()->getCell('O'.$i)->getValue() ); //申请日期
            $P = trim( $objPHPExcel->getActiveSheet()->getCell('P'.$i)->getValue() ); //安全有效期
            $Q = trim( $objPHPExcel->getActiveSheet()->getCell('Q'.$i)->getValue() ); //Address
            $U = trim( $objPHPExcel->getActiveSheet()->getCell('U'.$i)->getValue() ); //yrs
            $V = trim( $objPHPExcel->getActiveSheet()->getCell('V'.$i)->getValue() ); //mth
            $W = trim( $objPHPExcel->getActiveSheet()->getCell('W'.$i)->getValue() ); //employment_status
            $X = trim( $objPHPExcel->getActiveSheet()->getCell('X'.$i)->getValue() ); //yes_no


            //$count = ActivityParticipate::model()->find('activity_id=78 and status=1 and mobile=:mobile',array('mobile'=>$D));
            //if($count){
            //    $phone[] = $D;
            //}
            $dob =  date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($objPHPExcel->getActiveSheet()->getCell('H'.$i)->getValue()));
            //$dob = date('Y-m-d H:i:s', strtotime($H));

            $age = $this->birthday($dob);
            $passport_time = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($L));
            $work_permit_time = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($N));
            $application_time = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($O));
            $security_certificate_time = date('Y-m-d', PHPExcel_Shared_Date::ExcelToPHP($P));



            $sql .= "(32, '{$B}', '{$C}', '$D', '$E', '$F', '$G', '".$dob."', '$age', '$J', '$K', '$passport_time', '$M', '$work_permit_time', '$application_time', '$security_certificate_time', '$Q', '$U', '$V', '$W', '$X'), ";

        }
        //echo "<pre>";

        echo $sql;

    }

    public function birthday($birthday){
        $age = strtotime($birthday);
        if($age === false){
            return false;
        }
        list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age));
        $now = strtotime("now");
        list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now));
        $age = $y2 - $y1;
        if((int)($m2.$d2) < (int)($m1.$d1))
            $age -= 1;
        return $age;
    }

    //生成报名sql
    public function actiontest(){

        header("Content-type: text/html; charset=utf-8"); 
        $objPHPExcel = FwUtility::readerExcel('F:/cc.xlsx');
      
        $sql = "insert into `e_activity_participate`(activity_id, user_id, user_name, realname, mobile, company, dai, extend, status, type ,signin_time , _create_time) values ";
        for($i=2; $i<=517; $i++){
            $A = trim( $objPHPExcel->getActiveSheet()->getCell('A'.$i)->getValue() ); //姓名
            $B = trim( $objPHPExcel->getActiveSheet()->getCell('B'.$i)->getValue() ); //电话
            $C = trim( $objPHPExcel->getActiveSheet()->getCell('C'.$i)->getValue() ); //单位
            $D = trim( $objPHPExcel->getActiveSheet()->getCell('D'.$i)->getValue() ); //职位
            $F = trim( $objPHPExcel->getActiveSheet()->getCell('F'.$i)->getValue() ); //报名时间
            //$H = trim( $objPHPExcel->getActiveSheet()->getCell('H'.$i)->getValue() ); //签到时间
            
            $extend = array();
            $extend['company'] = $C;
            $extend['position'] = $D;
            //$extend['email'] = $F;
            //$extend['text1'] = $E;
            $extendstr = serialize($extend);
            
            $sql .= "(145, 24397892, '18310331883', '".$A."', '".$B."', '".$C."', 1, '".$extendstr."', 1, 2, '2016-10-20 8:45:00', '".$F."'), ";

        }

        echo $sql;
       
    }
    
     //查看短信日志
    public function actionSms(){
		$log = '/opt/webroot/e/tongcheng_admin/branch/protected/runtime/training_sendsms/'.date('Ymd').'.log';
		
		if( !is_file($log) ){
			echo '<title>后端短信</title>NOT SMS';die;
		}
		if( isset($_GET['clear']) ){
			unlink($log);
			$this->redirect(array('sms'));die;
		}
        $file = file_get_contents($log);
		$e = explode("\n", trim($file));
		krsort($e);
		echo '<title>后端短信</title><body style="padding-left:300px;"><a href="index.php?r=training/sms&clear=1">清除</a>';
		foreach($e as $k=>$txt){
			echo '<p><div style="background-color:#d4d4d4;width:300px; border:1px solid red;">'.$txt.'</div></p>';
		}
		echo '</body>';
    }
    
}