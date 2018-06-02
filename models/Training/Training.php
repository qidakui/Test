<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/13
 * Time: 14:32
 */
/**
 * This is the model class for table "{{training}}".
 *
 * The followings are the available columns in table '{{admin}}':
 * @property string $id
 * @property string $user_name
 * @property string $password
 * @property string $phone
 * @property string $email
 * @property string $random
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Training;
use application\models\ServiceRegion;
use application\models\Activity\ActivityLunbotu;
use application\models\Research\Research;
use application\models\Research\ResearchIssue;
use application\models\Research\UserResearch;
class Training extends \CActiveRecord
{
    public $status_name;

    private $_msg = array(
        0 => '屏蔽',
        2 => '尚未发布',
        3 => '培训取消',
        4 => '培训下线',
        5 => '名额已满',
        6 => '报名进行中',
        7 => '培训进行中',
        8 => '回看培训'
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{training}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array();
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array();
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {

    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Admin the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //保存/修改
    public function trainingSave($data, $extdata=array()){
        if(isset($data['id'])){
            $model = self::model()->findbypk($data['id']);
            $oldData = isset($model->attributes) ? $model->attributes : array();
            $model->_update_time = date('Y-m-d H:i:s');
            if(isset($data['release_num'])){
                $model->release_num = intval($model->release_num) + 1;
            }
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
            //获取编码
            $model->num = $this->setTrainingNum(); 
        }
		 
        foreach($data as $k=>$v){
            $model->$k = \CHtml::encode($v);
        }
        $Connection = $model->dbConnection->beginTransaction();
        
        if($model->save()){
            $id  = intval($model->primaryKey);
            $extdata['id'] = $id;
            $eid = TrainingExtension::model()->trainingExtensionSave($extdata);
            if($eid){
                $Connection->commit();
            }else{
                $id = 0;
                $Connection->rollBack();
            }
        }else{
            $id = 0;
            $Connection->rollBack();
        }
        
        if($id){
            if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationTraining, 'edit', '修改培训', $data['id'], $oldData, $data);
            }else{
                \OperationLog::addLog(\OperationLog::$operationTraining, 'add', '新建培训', $id, array(), $data);
            }
        }
        return $id;
    }

    //查询单条
    public function getTraining($id){
        $data = self::model()->findByPk($id);
        $extdata = TrainingExtension::model()->findByPk($id);
        if( empty($extdata) ) {
            TrainingExtension::model()->trainingExtensionSave(array('id'=>$id));
            $extdata = TrainingExtension::model()->findByPk($id);
        }
        $data = $data->attributes;
        $data['starttime'] = substr($data['starttime'], 0, 16);
        $data['endtime'] = substr($data['endtime'], 0, 16);
        $extdata = $extdata->attributes;
        unset($extdata['id'],$extdata['_create_time'], $extdata['_update_time']);
        $res = array_merge($data, $extdata);
        return $res;
    }
    
    //删除
    public function removeActivity($activity_id){
        $activity = self::model()->deleteByPk($activity_id); //主表
        if($activity){
            ActivityExtension::model()->deleteByPk($activity_id); //附表
            ActivityContent::model()->deleteAll('activity_id=:activity_id',array(':activity_id'=>$activity_id));
            ActivityComment::model()->deleteAll('activity_id=:activity_id',array(':activity_id'=>$activity_id));
            ActivityParticipate::model()->deleteAll('activity_id=:activity_id',array(':activity_id'=>$activity_id));
        }
        return $activity_id;
    }

    //查询列表
    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='title'){
					$criteria->addSearchCondition('title', $val);
                }elseif($key=='city_code'){
                    $criteria->addCondition('city_code ='.$val.' OR apply_city_code='.$val);
                }elseif($key=='filiale_id' && is_array($val) ){
                    $criteria->addInCondition('filiale_id',$val);
                }else{
					$criteria->compare($key, $val);
				}
            }
        }
       
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
       
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        //print_r($criteria);
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
       
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            //获取分支名称 （待改）
            if($v['filiale_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
            }
            $data[$k]['filiale_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
            //适用范围
            $apply_province_name = $apply_city_name = '';
            if($v['way']!=1){
                if($v['apply_province_code']){
                    if($v['apply_province_code']==BRANCH_ID){
                        $apply_province_name = '全国';
                    }else{
                        $apply_province_name= ServiceRegion::model()->getRedisCityList($v['apply_province_code']);
                    }
                }
                if($v['apply_city_code']){
                    if($v['apply_city_code']==BRANCH_ID){
                        $apply_city_name = '全省';
                    }else{
                        $apply_city_name= ServiceRegion::model()->getRedisCityList($v['apply_city_code']);
                    }
                }
            }
            $data[$k]['apply_address'] = $apply_province_name . ' ' . $apply_city_name;
             
            //培训地址
            $province_name = $city_name = '';
            if($v['way']!=2){
                $province_name = ServiceRegion::model()->getRedisCityList($v['province_code']);
                $city_name = ServiceRegion::model()->getRedisCityList($v['city_code']);
                
                $data[$k]['alladdress'] = $province_name.' '.$city_name. $v['address'];
            }else{
                $data[$k]['alladdress'] = '';
            }
            
            //状态
            $data[$k]['status_txt'] = $this->getTrainingStatus($v['id'], $v['status'], $v['is_show_list'], $v['cancel'], $v['starttime'], $v['endtime'], $v['tosign_endtime'],$v['limit_number'], $v['way']);
            
            $extdata = TrainingExtension::model()->findByPk($v['id']);
            
            if( empty($extdata) ){
                TrainingExtension::model()->trainingExtensionSave(array('id'=>$v['id'] ));
                $extdata = TrainingExtension::model()->findByPk($v['id']);
            }
            
            
            $data[$k]['user_name'] = $extdata['user_name'];
            $data[$k]['video_form'] = $v['way']==1 ? '' : \Yii::app()->params['training_video_platform'][$extdata['video_form']];
            $data[$k]['lecturer_account'] = $extdata['lecturer_account'];
            $data[$k]['training_head'] = $extdata['training_head'];
            $data[$k]['training_head_tel'] = $extdata['training_head_tel'];
            $data[$k]['venue_pnum'] = $extdata['venue_pnum'];
            $data[$k]['venue_cost'] = $extdata['venue_cost'];
            $data[$k]['venue_head'] = $extdata['venue_head'];
            $data[$k]['venue_head_tel'] = $extdata['venue_head_tel'];
            $data[$k]['report_img_num'] = $extdata['report_img_num'];
            $data[$k]['report_video_num'] = $extdata['report_video_num'];
            $data[$k]['qr_code_image'] = $extdata['qr_code_image'];
            $data[$k]['views'] = $extdata['views'];
            
            $pcon['training_id'] = $v['id'];
            $pcon['status'] = 1;
            if( $v['way']==0 ){
                $pcon['participate_way'] = 0;
                $data[$k]['online_participate_num'] = TrainingParticipate::model()->getRequirementCount($pcon);
                $pcon['participate_way'] = 1;
                $data[$k]['offline_participate_num'] = TrainingParticipate::model()->getRequirementCount($pcon);
                
                $data[$k]['cost'] = '线下：'.($v['offline_cost']==0?'免费':$v['offline_cost'].'元')."<br>".'  网络：'.($v['online_cost']==0?'免费':$v['online_cost'].'元');
            }elseif( $v['way']==1 ){
                $pcon['participate_way'] = 1;
                $data[$k]['offline_participate_num'] = TrainingParticipate::model()->getRequirementCount($pcon);
                
                $data[$k]['cost'] = $v['offline_cost']==0?'免费':$v['offline_cost'].'广币';
            }elseif( $v['way']==2 ){
                $pcon['participate_way'] = 0;
                $data[$k]['online_participate_num'] = TrainingParticipate::model()->getRequirementCount($pcon);
                
                $data[$k]['cost'] = $v['online_cost']==0?'免费':$v['online_cost'].'广币';
                $data[$k]['limit_number'] = '';
            }
            //此活动是否设置过调研
            $Research = Research::model()->checkResearch(array('column_id'=>$v['id'],'column_type'=>2,'status'=>1,'_delete'=>0));
            $data[$k]['research_id'] = empty($Research) ? 0 : $Research[0]['id'];
            $data[$k]['qrcodeurl'] = isset($Research[0]['qrcodeurl']) ? $Research[0]['qrcodeurl'] : '';
            $data[$k]['link'] = $v['status']==1 ? EHOME.'/training/detail/id/'.$v['id'] : '';
            //查询大讲堂进入地址
            if( $limit<100 && $v['way']!=1 && $extdata['video_form']==3){
                $Gensee = GenseeApi::model()->findByPk($v['id'], ['select'=>['id','teacherJoinUrl','teacherToken','assistantToken', 'sdkid', 'updateCoursewareUrl','coursewareId','coursewareUrl','courseware_type']]);
                if($Gensee){
                    $data[$k]['teacherJoinUrl'] = $Gensee['teacherJoinUrl'].'?nickname='.urlencode('讲师'.$v['lecturer']).'&token='.$Gensee['teacherToken'];
                    $data[$k]['assistantJoinUrl'] = $Gensee['teacherJoinUrl'].'?nickname='.urlencode('助教').'&token='.$Gensee['assistantToken'];
                    $data[$k]['sdkid'] = $Gensee['sdkid'];
					$data[$k]['roomid'] = $Gensee['id'];
					$data[$k]['coursewareId'] = $Gensee['coursewareId'];
					$data[$k]['coursewareUrl'] = $Gensee['coursewareUrl'];
                    $data[$k]['updateCoursewareUrl'] = $Gensee['updateCoursewareUrl'];
                    $data[$k]['courseware_type'] = $Gensee['courseware_type'];
                }
            }
        }
        //print_r($data);
        $data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        //return array('data' => $data, 'count' => $count);
    }
    
    //根据多个培训主键获取培训信息
    public function findTrainingByPk($ins, $field=array()){
        $select = empty($field) ? array() : array('select'=>$field);
        $res = self::model()->findAllByPk($ins, $select);
        $data = array();
        if(empty($res)){
            return $data;
        }
        
        foreach($res as $k=>$v){
            $data[$v->id] = $v;
        }
        return $data;
    }
    
    //获取状态
    public  function getTrainingStatus($id, $status, $is_show_list, $cancel, $starttime, $endtime, $tosign_endtime, $limit_number, $way){
        $nowtime = date('Y-m-d H:i:s');
        //报名数
        if( $way==2 ){
            $RequirementCount = 0;
        }else{
            $RequirementCount = TrainingParticipate::model()->getRequirementCount(array('training_id'=>$id,'status!=2'));
        }
        
        $status_txt = '';
        if( $cancel==3 ){
            $status_txt = '培训取消';
        }
        if( $way==1 && $cancel==1 ){
            $status_txt = '培训取消';
        }
        if( $way==2 && $cancel==2 ){
            $status_txt = '培训取消';
        }
        if( $status_txt ){
            return $status_txt;
        }
        switch ( $status ){
            case 2:
                $status_txt = $this->_msg[2];
            break;
            case 1:
                if( $endtime < $nowtime ){
                    $status_txt = $this->_msg[8];
                }else{
                    if( $RequirementCount >= $limit_number ) {
                        $status_txt = $this->_msg[5];
                    }else{
                        if( $nowtime<$starttime ){
                            $status_txt = $this->_msg[6];
                        }elseif( $nowtime >= $starttime && $nowtime <=$endtime  ){
                            $status_txt = $this->_msg[7];
                        }
                    }
                }
            break;
        }
        if( $way==0 ){
            if( $cancel==1 ){
                $status_txt = '网络直播'.$status_txt.'<br>线下培训取消';
            }elseif( $cancel==2 ){
                $status_txt = '线下培训'.$status_txt.'<br>网络直播取消';
            }
        }
        $status_txt = $is_show_list==0 ? $status_txt.'<div class="c-red">【前台不显示】</div>' : $status_txt;
        return $status_txt;
    }
    
    //复制
    public function copyTraining($id){
        $Training = self::model()->findByPk($id);
        $model = new self();
        foreach($Training as $k=>$v){
            if( $k=='id' ){
                continue;
            }
            $model->$k = $v;
            $model->filiale_id = \Yii::app()->user->branch_id;
            $model->num = $this->setTrainingNum();
            $model->starttime = null;
            $model->endtime = null;
            $model->is_show_week = 0;
            $model->_create_time = date('Y-m-d H:i:s');
            $model->_update_time = null;
            $model->task_time = null;
            $model->status = 2;
            $model->cancel = 0;
			$model->is_create_gensee = 0;
        }
        $Connection = $model->dbConnection->beginTransaction();
        if($model->insert()){
            $insert_id  = intval($model->primaryKey);
            $TrainingExtensionModel = TrainingExtension::model();
            $TrainingExtension = $TrainingExtensionModel->findByPk($id);
            foreach($TrainingExtension as $kc=>$vc){
                $TrainingExtensionModel->$kc = $vc;
                $TrainingExtensionModel->id = $insert_id;
                $TrainingExtensionModel->user_id = \Yii::app()->user->user_id;
                $TrainingExtensionModel->user_name = \Yii::app()->user->user_name;
                $TrainingExtensionModel->_create_time = date('Y-m-d H:i:s');
                $TrainingExtensionModel->_update_time = null;
                $TrainingExtensionModel->status = 2;
                $TrainingExtensionModel->sms_status = 0;
                $TrainingExtensionModel->sms_status_time = null;
                $TrainingExtensionModel->qr_code_image = null;
                $TrainingExtensionModel->link_qr_code = null; 
				$TrainingExtensionModel->old_time_and_address = null;
				if($TrainingExtension->video_form==3){
					$TrainingExtensionModel->video_form = 0;
				}
            }
            $TrainingExtensionModel->_new = 1;
            $ins = $TrainingExtensionModel->insert();
            if($ins){
                $Connection->commit();
                return true;
            }else{
                $Connection->rollBack();
                return false;
            }
       }else{
           $Connection->rollBack();
           return false;
       }
    }
  
    //设置轮播图
	public function setRolling($data){
		if( isset($data['id']) && !empty($data['id']) ){
            $model = ActivityLunbotu::model()->findbypk($data['id']);
			$model->_update_time = date('Y-m-d H:i:s');
			unset($data['id']);
		}else{
			$model = ActivityLunbotu::model();
			$model->user_id = \Yii::app()->user->user_id;
			$model->user_name = \Yii::app()->user->user_name;
			$model->_create_time = date('Y-m-d H:i:s');
			$model->_new = true;
		}
		
		foreach($data as $k=>$v){
			$model->$k = \CHtml::encode($v);	
		}

		if( $model->save() ){
			return intval($model->primaryKey);
		}else{
			return false;
		}
	}

    
    //生成编号 AA00开始
    function setTrainingNum(){
        $code = 'AA00';
        $training = $this->getlist(array(), 'desc', 'id', 1, 0);
        if(!empty($training['data']) && !empty($training['data'][0]['num'])){
            $code = $training['data'][0]['num'];
        }
        if($code==='ZZ99'){
            return 'ZZ100';
        }
        $az = array("A", "B", "C", "D", "E", "F", "G","H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $code1 = $code[0];
        $code2 = $code[1];
        $code3 = $code[2];
        $code4 = $code[3];

        if($code4<9){
            $code4 ++;
        }else{
            $code4 = 0;
            if($code3<9){
                $code3 ++;
            }else{
                $code3 = $code4 = 0;
                $k2 = array_search($code2,$az);
                if($k2<25){
                    $code2 = $az[$k2+1];
                }else{
                    $code2 = 'A';
                    $k1 = array_search($code1,$az);
                    if($k1<25){
                        $code1 = $az[$k1+1];
                    }
                }
            }
        }
        $lasttwo = $code1.$code2.$code3.$code4;
        return $lasttwo;
    }
    
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return intval($count);
    }
	
    /*
     * 计算时间段内大讲堂培训limit_number和
     */
	public function getLimitNumberSum($startdate, $enddate){
        $criteria = new \CDbCriteria;
        $criteria->select = 'SUM(limit_number) limit_number';
        $criteria->addCondition('is_create_gensee=1 AND status=1 AND way=2 AND cancel=0 ');
        $criteria->addCondition('starttime BETWEEN \''.$startdate.' 00:00:00'.'\' AND \''.$enddate.' 23:59:59'.'\' OR endtime BETWEEN \''.$startdate.' 00:00:00'.'\' AND \''.$enddate.' 23:59:59'.'\' ');
        $data = self::model()->find($criteria);
        return empty($data['limit_number']) ? 0 : intval($data['limit_number']);
    }
	

    /*
     * 培训统计报--按课程
     */
    public function reportCourse($filiale_id, $starttime){
		//$manyidu_fuhedu = ResearchIssue::model()->statistical(279, 2, array(2,4));
		//echo "<pre>";print_r($manyidu_fuhedu);die;
        header("Content-type: text/html; charset=utf-8");
		if($filiale_id==BRANCH_ID || empty($filiale_id)){
		   $city[0]['region_name'] = '全国';
		}else{
		   $city = ServiceRegion::model()->getBranchToCity($filiale_id);
		}
		$filiale_name = isset($city[0]['region_name'])?$city[0]['region_name']:'';
		//echo  ServiceRegion::model()->getRedisCityList(1101);die;
        $criteria = new \CDbCriteria;
        $scon = explode('-',$starttime);
        $year = $scon[0];
        $month = $scon[1];
        $criteria->compare('status', 1);
        $criteria->addInCondition('way',array(1,0)); //只导线下
        if ($month == '12'){
            $next = $year+1;
            $startime = $year."-".$month."-01 00:00:00";
            $endtime = $next."-01-01 00:00:00";
            $criteria->addCondition("starttime between '{$startime}' and '{$endtime}' or endtime between '{$startime}' and '{$endtime}' or (starttime<'{$startime}' and endtime>'{$endtime}')");
        }  else {
            $next = $month+1;
            $startime = $year."-".$month."-01 00:00:00";
            $endtime = $year."-".$next."-01 00:00:00";
            $criteria->addCondition("starttime between '{$startime}' and '{$endtime}' or endtime between '{$startime}' and '{$endtime}' or (starttime<'{$startime}' and endtime>'{$endtime}')");
        }
        if( !empty($filiale_id) && $filiale_id!=BRANCH_ID ){
            if( is_array($filiale_id) ){
                $criteria->addInCondition('filiale_id',$filiale_id);
            }else{
                $criteria->compare('filiale_id', $filiale_id);
            }
        }
		$criteria->order = 'filiale_id';
        $category = \Yii::app()->params['training_category'];
        $training_type = array(
            0 => '',
            1 => '公司日常',
            2 => '企业定制',
            3 => '高职院校',
            4 => '收费培训'
        );
        
        $list = self::model()->findAll($criteria);
        $data = array();
		//echo "<pre>";print_r($list);die;
        foreach($list as $k=>$v){
            $tmp['id'] = $v['id'];
            
            if($v['filiale_id']==BRANCH_ID){
               $city[0]['region_name'] = '全国';
            }else{
               $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
            }
            $tmp['filiale_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
            $tmp['month'] = intval($month).'月';
            $tmp['lecturer'] = $v['lecturer'];
            $tmp['daynum'] = round((strtotime($v['endtime'])-strtotime($v['starttime']))/3600/24); //天数
            $tmp['daynum'] = $tmp['daynum'] ? $tmp['daynum'] : 1;
            $tmp['num'] = $v['num'];
            $tmp['category_id'] = $category[$v['category_id']];
            $tmp['training_type'] = $training_type[$v['training_type']];
            $tmp['title'] = $v['title'];
            $tmp['time'] = $v['starttime'].' 至 '.$v['endtime'];
            $tmp['city'] = '';
            if($v['city_code']!=BRANCH_ID && !empty($v['city_code'])){
                $tmp['city'] = ServiceRegion::model()->getRedisCityList($v['city_code']);
            }
            $tmp['limit_number'] = $v['limit_number']; //计划人数
            //报名人次
            $tmp['baoming'] = TrainingParticipate::model()->getRequirementCount( array('training_id'=>$v['id'],'participate_way'=>1, 'status'=>1) );
            if($tmp['baoming']){
                //签到人次
                $tmp['qiandao'] = TrainingParticipateSigninLog::model()->getParticipateSigninCount( array($v['id']) ); 
                //调研人次
                $tmp['diaoyan'] = UserResearch::model()->getResearchMemberNum(2, $v['id'], array(2,4));
                //到场率（签到÷报名）
                $tmp['daochanglv'] = empty($tmp['baoming']) ? '0%' :round($tmp['qiandao'] / $tmp['baoming'] * 100,2).'%';
                //离场率（未调研÷签到)
                $tmp['lichanglv'] = empty($tmp['qiandao']) ? '0%' : round(($tmp['qiandao']-$tmp['diaoyan'])/$tmp['qiandao'] * 100,2).'%';
                //机房使用率（签到÷计划数）
                $tmp['jifanglv'] = empty($tmp['limit_number']) ? '0%' : round($tmp['qiandao']/$tmp['limit_number']*100,2).'%';
                
                //满意度\符合度
                $manyidu_fuhedu = ResearchIssue::model()->statistical($v['id'], 2, array(2,4));
				$manyidu_fuhedu = isset($manyidu_fuhedu['data'][$v['id']]) ? $manyidu_fuhedu['data'][$v['id']] : array('satisfied'=>0,'conform'=>0);
                $tmp['manyidu'] = isset($manyidu_fuhedu['satisfied']) && $manyidu_fuhedu['satisfied'] ? $manyidu_fuhedu['satisfied'].'%' :'0%';
                $tmp['fuhedu'] = isset($manyidu_fuhedu['conform']) && $manyidu_fuhedu['conform'] ? $manyidu_fuhedu['conform'].'%' :'0%';
            }else{
                $tmp['qiandao'] = 0;
                $tmp['diaoyan'] = 0;
                $tmp['daochanglv'] = '0%';
                $tmp['lichanglv'] = '0%';
                $tmp['jifanglv'] = '0%';
                $tmp['manyidu'] = '0%';
                $tmp['fuhedu'] = '0%';
            }
            
            $data[] = $tmp;
        }

		//echo "<pre>";print_r($data);die;
        $headerstr = '编号,分支,月,讲师姓名,天数,课程编号,课程类别,课程类型,课程名称,培训时间,市/区,计划人数,报名人次,签到人次,调研人次,到场率（签到÷报名）,离场率（未调研÷签到),机房使用率（签到÷计划数）,满意度,符合度';
        $header = explode(',',$headerstr);
        \FwUtility::exportExcel($data, $header,'培训统计表-按课程','培训统计表-按课程_'.$filiale_name.'-'.substr($starttime,0,7));
    }
	
	//培训统计表-按讲师
	public function reportLecturer($filiale_id, $starttime, $endtime){
        header("Content-type: text/html; charset=utf-8"); 
 
        $endA = explode('-',$endtime);
		$starttime = date('Y-m-01 H:i:s', strtotime($starttime)); 
		$endtime = date('Y-m-d H:i:s', strtotime($endA[0].'-'.$endA[1]." +1 month")-1); 
        //echo $starttime.'--'.$endtime;die;
	
		$where = 't.status=1 AND t.way IN(1,0)'; 
		$where .= ' AND t.filiale_id='.$filiale_id;
		
		$start_time = strtotime($starttime);
		$end_time = strtotime($endtime);
		$t_data = [];
		$data = [];
		$limit_number_sum = [];
		while($start_time < $end_time ){
			$year_month = date('Y-m-d', $start_time);//
					
			$start_time = strtotime('+1 month',$start_time);
			$next_month = date('Y-m-d', $start_time);
			$sql = $where.' AND (t.starttime BETWEEN \''.$year_month.'\' AND \''.$next_month.'\' OR t.endtime BETWEEN \''.$year_month.'\' AND \''.$next_month.'\')  ';
			$training_list = \Yii::app()->db->createCommand()
				->select("t.id, t.limit_number, t.lecturer, t.starttime, t.endtime")
				->from("e_training as t")
				//->leftJoin('e_training_extension as e', 't.id=e.id')
				->where($sql)
				->queryAll();

			$_data = [];
			$training_ids = [];
			$ResearchIssue = [];
			$limit_number = [];
			if($training_list){
				foreach($training_list as $k=>$v){
					$training_ids[$v['lecturer']][] = $v['id'];

					$limit_number[$v['lecturer']] =  isset($limit_number[$v['lecturer']]) ? intval($limit_number[$v['lecturer']]) + $v['limit_number'] : intval($v['limit_number']);
					
					//满意度\符合度
                    $manyidu_fuhedu = ResearchIssue::model()->statistical($v['id'], 2, array(2,4));
					$manyidu_fuhedu = isset($manyidu_fuhedu['data'][$v['id']]) ? $manyidu_fuhedu['data'][$v['id']] : array('satisfied'=>0,'conform'=>0);
                    
					//满意度
					$manyidu = isset($manyidu_fuhedu['satisfied']) && $manyidu_fuhedu['satisfied'] ? $manyidu_fuhedu['satisfied'] :0;
                    //符合度
					$fuhedu = isset($manyidu_fuhedu['conform']) && $manyidu_fuhedu['conform'] ? $manyidu_fuhedu['conform'] :0;
					
					$ResearchIssue[$v['lecturer']][$v['id']]['manyidu'] = $manyidu;
					$ResearchIssue[$v['lecturer']][$v['id']]['fuhedu'] = $fuhedu;
				}
			}
			$limit_number_sum[$year_month] = $limit_number;
			$t_data_ResearchIssue[$year_month] = $ResearchIssue;
			$t_data[$year_month] = $training_ids;
		}
		//echo "<pre>";print_r($t_data);die;
		if($filiale_id==BRANCH_ID){
		   $city[0]['region_name'] = '全国';
		}else{
		   $city = ServiceRegion::model()->getBranchToCity($filiale_id);
		}

		$filiale_name = isset($city[0]['region_name'])?$city[0]['region_name']:'';
		$x = 1;
		foreach($t_data as $month=>$val_1 ){
			foreach($val_1 as $lecturer=>$_training_ids ){
				$tmp['id'] = $x;
				
				$tmp['filiale_name'] = $filiale_name;
				$tmp['month'] = substr($month,5,2).'月';
				$tmp['lecturer'] = $lecturer;
				$tmp['limit_number'] = $limit_number_sum[$month][$lecturer];
				//报名人次
                $tmp['baoming_renshu'] = TrainingParticipate::model()->getIdsRequirementCount($_training_ids);
				//听课(签到)人次
				$tmp['tingke_renshu'] = TrainingParticipateSigninLog::model()->getParticipateSigninCount( $_training_ids );
				//调研人次
				$tmp['diaoyan_renshu'] =UserResearch::model()->getResearchMemberNum(2, $_training_ids, array(2,4));
				//到场率（签到÷报名）
                $tmp['daochanglv'] = empty($tmp['baoming_renshu']) ? '0%' :round($tmp['tingke_renshu'] / $tmp['baoming_renshu'] * 100,2).'%';
				//离场率（未调研÷签到)
                $tmp['lichanglv'] = empty($tmp['tingke_renshu']) ? '0%' : round(($tmp['tingke_renshu']-$tmp['diaoyan_renshu'])/$tmp['tingke_renshu'] * 100,2).'%';
				//机房使用率（签到÷计划数）
                $tmp['jifanglv'] = empty($tmp['limit_number']) ? '0%' : round($tmp['tingke_renshu']/$tmp['limit_number']*100,2).'%';
  
				//满意度\符合度
                $tmp['manyidu'] = 0;
				$tmp['fuhedu'] = 0;
				$training_total = count($t_data_ResearchIssue[$month][$lecturer]);
				if( $training_total ){
					foreach($_training_ids as $t_key=>$training_id){
						$tmp['manyidu'] += $t_data_ResearchIssue[$month][$lecturer][$training_id]['manyidu'];
						$tmp['fuhedu'] += $t_data_ResearchIssue[$month][$lecturer][$training_id]['fuhedu'];
					}
					$tmp['manyidu'] = round($tmp['manyidu']/$training_total,2);
					$tmp['fuhedu'] = round($tmp['fuhedu']/$training_total,2);
				}
				$data[] = $tmp;
				$x += 1;
			}
		}
		if($data){
			foreach($data as $dk=>$dv){
				$data[$dk]['manyidu'] = $dv['manyidu'].'%';
				$data[$dk]['fuhedu'] = $dv['fuhedu'].'%';
			}
		}
		//echo "<pre>";print_r($data);die;
		$headerstr = '编号,分支,月,讲师,计划人数,报名人次,签到人次,调研人次,到场率（签到÷报名）,离场率（未调研÷签到),机房使用率（签到÷计划数）,满意度,符合度';
        $header = explode(',',$headerstr);
      // print_r($data);die;
        \FwUtility::exportExcel($data, $header,'培训统计表-按讲师','培训统计表-按讲师_'.$filiale_name.'-'.substr($starttime,0,7).'至'.substr($endtime,0,7));
    }
    
    /*
     * 培训覆盖统计表
     */
    public function reportCover($filiale_id, $starttime, $endtime){
        header("Content-type: text/html; charset=utf-8"); 
        
        //echo UserResearch::model()->getResearchMemberNum(2,238 , 2);
        //die;
        //$filiale_id = \Yii::app()->user->branch_id==BRANCH_ID ? $filiale_id : \Yii::app()->user->branch_id;
        
        $endA = explode('-',$endtime);
		$starttime = date('Y-m-01 H:i:s', strtotime($starttime)); 
		$endtime = date('Y-m-d H:i:s', strtotime($endA[0].'-'.$endA[1]." +1 month")-1); 
        $start_time = strtotime($starttime);
		$end_time = strtotime($endtime);
		$t_data = [];
		$data = [];
        $where = 't.status=1 AND t.way IN(1,0)'; 
        if( $filiale_id!=BRANCH_ID && !empty($filiale_id) ){
            $where .= ' AND t.filiale_id='.$filiale_id;
        }
		
        //while start=====================
		while($start_time < $end_time ){
			$year_month = date('Y-m-d', $start_time);//
					
			$start_time = strtotime('+1 month',$start_time);
			$next_month = date('Y-m-d', $start_time);
			$sql = $where.' AND (t.starttime BETWEEN \''.$year_month.'\' AND \''.$next_month.'\' OR t.endtime BETWEEN \''.$year_month.'\' AND \''.$next_month.'\')  ';
			$training_list = \Yii::app()->db->createCommand()
				->select("t.id, t.filiale_id, t.province_code, t.city_code, t.starttime, t.endtime")
				->from("e_training as t")
				//->leftJoin('e_training_extension as e', 't.id=e.id')
				->where($sql)
				->queryAll();
			//echo "<pre>";print_r($training_list);die;
            $_data = [];
			$training_ids = [];
			$ResearchIssue = [];
			if($training_list){
				foreach($training_list as $k=>$v){
					$training_ids[$v['city_code']][] = $v['id'];

					//满意度\符合度
                    $manyidu_fuhedu = ResearchIssue::model()->statistical($v['id'], 2, array(2,4));
					$manyidu_fuhedu = isset($manyidu_fuhedu['data'][$v['id']]) ? $manyidu_fuhedu['data'][$v['id']] : array('satisfied'=>0,'conform'=>0);
                    
					//满意度
					$manyidu = isset($manyidu_fuhedu['satisfied']) && $manyidu_fuhedu['satisfied'] ? $manyidu_fuhedu['satisfied'] :0;
                    //符合度
					$fuhedu = isset($manyidu_fuhedu['conform']) && $manyidu_fuhedu['conform'] ? $manyidu_fuhedu['conform'] :0;
					
					$ResearchIssue[$v['city_code']][$v['id']]['manyidu'] = $manyidu;
					$ResearchIssue[$v['city_code']][$v['id']]['fuhedu'] = $fuhedu;
				}
			}
			$t_data_ResearchIssue[$year_month] = $ResearchIssue;
			$t_data[$year_month] = $training_ids;
		}//while end=====================

		//print_r($t_data_ResearchIssue);die;

        if($filiale_id==BRANCH_ID || empty($filiale_id)){
		   $city[0]['region_name'] = '全国';
		}else{
		   $city = ServiceRegion::model()->getBranchToCity($filiale_id);
		}
		$filiale_name = isset($city[0]['region_name'])?$city[0]['region_name']:'';
          // print_r($t_data);die; 
		foreach($t_data as $month=>$val_1 ){
			foreach($val_1 as $city_code=>$_training_ids ){
				//$tmp['filiale_name'] = $filiale_name;
				$tmp['month'] = substr($month,5,2).'月';
				$tmp['city_code'] = ServiceRegion::model()->getRedisCityList($city_code);
				
				//报名人次
				//$criteria->addBetweenCondition('_create_time', date('Y-m-d 00:00',$start_time), $next_month);
				$tmp['baoming_renci'] = TrainingParticipate::model()->getIdsRequirementCount( $_training_ids );

				//听课（签到）人次
				$tmp['tingke_renci'] = TrainingParticipateSigninLog::model()->getParticipateSigninCount( $_training_ids );
				//调研人次
				$tmp['diaoyan_renci'] = UserResearch::model()->getResearchMemberNum(2, $_training_ids, array(2,4));
				//报名人数
                $tmp['baoming_renshu'] = TrainingParticipate::model()->getDistinctMobileCount($_training_ids);
				//听课(签到)人数
				$tmp['tingke_renshu'] = TrainingParticipateSigninLog::model()->getDistinctMobileSigninCount( $_training_ids );
				//调研人数
				$tmp['diaoyan_renshu'] =UserResearch::model()->getResearchDistinctMemberNum(2, $_training_ids, array(2,4));

				//满意度\符合度
                $tmp['manyidu'] = 0;
				$tmp['fuhedu'] = 0;
				$training_total = count($t_data_ResearchIssue[$month][$city_code]);
				if( $training_total ){
					foreach($_training_ids as $t_key=>$training_id){
						$tmp['manyidu'] += $t_data_ResearchIssue[$month][$city_code][$training_id]['manyidu'];
						$tmp['fuhedu'] += $t_data_ResearchIssue[$month][$city_code][$training_id]['fuhedu'];
					}
					$tmp['manyidu'] = round($tmp['manyidu']/$training_total,2);
					$tmp['fuhedu'] = round($tmp['fuhedu']/$training_total,2);
				}
				$data[] = $tmp;
			}
		}
		//print_r($data);die;
		if($data){
			foreach($data as $dk=>$dv){
				$data[$dk]['manyidu'] = $dv['manyidu'].'%';
				$data[$dk]['fuhedu'] = $dv['fuhedu'].'%';
			}
		}
		//echo "<pre>";print_r($data);die;
		$headerstr = '月份,市,报名人次,听课人次,调研人次,报名人数,听课人数,调研人数,满意度,符合度';
        $header = explode(',',$headerstr);
      // print_r($data);die;
        \FwUtility::exportExcel($data, $header,'综合统计表','培训综合统计表_'.$filiale_name.'-'.substr($starttime,0,7).'至'.substr($endtime,0,7));
    }
    
}