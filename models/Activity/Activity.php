<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/13
 * Time: 14:32
 */
/**
 * This is the model class for table "{{activity}}".
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
namespace application\models\Activity;
use application\models\ServiceRegion;
use application\models\SmsTask;
use application\models\Research\Research;
use application\models\Template\Template;
class Activity extends \CActiveRecord
{
    public $status_name;
    private $statusKey = array(
        0 => '启用',
        1 => '停用',
    );
    private $_msg = array(
        0 => '屏蔽',
        2 => '尚未发布',
        3 => '已结束',
        4 => '活动进行中',
        5 => '名额已满',
        6 => '报名进行中',
        7 => '报名截止',
        8 => '未知'
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{activity}}';
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
            'id' => 'ID',
            'num' => '编号',
            'filiale_id' => '分支id',
            'category_id' => '所属分类',
            'province_code' => '地区',
            'city_code' => '城市',
            'address' => '活动地址',
            'user_id' => '作者uid',
            'user_name' => '创建人用户名',
            'title' => '活动标题',
            'activity_head' => '活动负责人',
            'activity_head_tel' => '活动负责人电话',
            'venue_head' => '场地负责人',
            'venue_head_tel' => '场地负责人电话',
            'venue_cost' => '场地费用',
            'venue_pnum' => '场地容纳人数',
            'outline' => '内容概要',
            'image' => '封面图片',
            'starttime' => '开始时间',
            'endtime' => '结束时间',
            'bm_endtime' => '报名截止时间',
            'lecturer' => '嘉宾讲师(多个)',
            'limit_number' => '限定人数',
            'product_name' => '产品名称',
            'isset_yaoqinghan' => '是否设置邀请函',
            'yaoqinghan_image' => '邀请函模板图片',
            'yaoqinghan_note' => '邀请函备注',
            'bm_huaxiao' => '报名花销',
            'requirement' => '报名需填项',
            'qr_code_image' => '活动二维码供手访问签到页',
            'status' => '0屏蔽 1正常 2推荐',
            '_create_time' => '创建时间',
            '_update_time' => '更新时间',
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

    //保存活动到数据库
    public function activitySave($data){
        if(isset($data['id'])){
            $model = self::model()->findbypk($data['id']);
            $oldData = $model->attributes;
            $model->_update_time = date('Y-m-d H:i:s');
            if( $model->endtime < date('Y-m-d H:i:s')){ //已结束的活动不允许更改时间
                unset($data['starttime'],$data['endtime']);
            }
            if(isset($data['template_type']) && empty($data['template_type'])){
                $requirement = empty($model->requirement) ? array() : unserialize($model->requirement);
                if(isset($requirement['share_code'])){
                    unset($requirement['share_code']);
                    $model->requirement = serialize($requirement);
                    unset($data['template_type']);
                }
            }
        }else{
            $model = new self();
            $model->user_id  = \Yii::app()->user->user_id;
            $model->user_name  = \Yii::app()->user->user_name;
            $model->_create_time = date('Y-m-d H:i:s');
        }
        foreach($data as $k=>$v){
            if($k==='requirement'){
                $model->$k = $v;       
            }else{
                $model->$k = \CHtml::encode($v);
            }
        }
       //print_r($model);die;
        $model->save();
        $id  = intval($model->primaryKey);
        if($id){
            if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationActivity, 'edit', '修改活动', $data['id'], $oldData, $data);
            }else{
                \OperationLog::addLog(\OperationLog::$operationActivity, 'add', '新建活动', $id, array(), $data);
            }
        }
        return $id;
    }


	//修改
    public function updateStatus($id, $status=1){
		$model = self::model()->findbypk($id);
		$model->status = $status;
		$model->_update_time  = date('Y-m-d H:i:s');
        return $model->save();
    }

    //推至首页
    public function updateRec_index($activity_id, $filiale_id){
        $activity = self::model()->findByPk($activity_id);
        if( $activity->status!=1 ){
            return 1024; //此活动尚未发布，无法推至首页
        }

        if( $filiale_id==BRANCH_ID ) {
            $activity = self::model()->find('national_rec_index!=0');
            if($activity){
                self::model()->updateByPk($activity->id, array('national_rec_index'=>0, '_update_time'=>date('Y-m-d H:i:s')) );
            }
            self::model()->updateByPk($activity_id, array('national_rec_index'=>1, '_update_time'=>date('Y-m-d H:i:s')) );
        }else{
            $activity = self::model()->find('rec_index!=0 and filiale_id=:filiale_id', array(':filiale_id'=>$filiale_id));
            if($activity){
                $up = self::model()->updateByPk($activity->id, array('rec_index'=>0, '_update_time'=>date('Y-m-d H:i:s')) );
            }
            self::model()->updateByPk($activity_id, array('rec_index'=>1, '_update_time'=>date('Y-m-d H:i:s')) );
        }
        return 'Y';
    }
    
    //删除活动
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
            $data[$k]['city_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
            //状态
            $data[$k]['status_txt'] = $this->getActivityStatus($v['id'], $v['status'], $v['starttime'], $v['endtime'], $v['bm_endtime'],$v['limit_number']);
            //此活动是否设置过调研
            $Research = Research::model()->checkResearch(array('column_id'=>$v['id'],'column_type'=>1,'status'=>1,'_delete'=>0));
            $data[$k]['research_id'] = empty($Research) ? 0 : $Research[0]['id'];
            
            $data[$k]['link'] = $v['status']==1 ? EHOME.'/activity/activity/id/'.$v['id'] : '';
        }
        $data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        //return array('data' => $data, 'count' => $count);
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

    
    //获取活动状态
    public  function getActivityStatus($activity_id, $status, $starttime, $endtime, $bm_endtime, $limit_number){
        $nowtime = date('Y-m-d H:i:s');
        //报名数
        $RequirementCount = ActivityParticipate::model()->getRequirementCount(array('activity_id'=>$activity_id,'status!'=>2));
        $status_txt = '';
        if($status==0){
            $status_txt = $this->_msg[0];
        }elseif($status==2){
            $status_txt = $this->_msg[2];
        }elseif($status==1){
            if($endtime < $nowtime ){
                $status_txt = $this->_msg[3];
            }else{
                if( $nowtime >= $starttime && $nowtime <=$endtime  ){
                    $status_txt = $this->_msg[4];
                }else{
                    if( $limit_number <= $RequirementCount ) {
                        $status_txt = $this->_msg[5];
                    }else{
                        if( $bm_endtime <= $nowtime ){
                            $status_txt = $this->_msg[7];
                        }else{
                            $status_txt = $this->_msg[6];
                        }
                    }
                    
                }
            }
        }else{
            $status_txt = $this->_msg[8];
        }
        
        return $status_txt;
    }
    
    //复制活动
    public function copyActivity($id){
        $activity = self::model()->findByPk($id);
        $model = new self();
        foreach($activity as $k=>$v){
            if( $k=='id' ){
                continue;
            }
            $model->$k = $v;
            $model->filiale_id = \Yii::app()->user->branch_id;
            $model->num = $this->setActivityNum();
            $model->user_id = \Yii::app()->user->user_id;
            $model->user_name = \Yii::app()->user->user_name;
            $model->rec_index = 0;
            $model->national_rec_index = 0;
			$model->qr_code_image = '';
            $model->endtime = date("Y-m-d H:i",time()+86400);
            $model->_create_time = date('Y-m-d H:i:s');
            $model->_update_time = null;
            $model->status = 2;
            $model->template_type = 0;
        }
        if($model->insert()){
            $activity_id = $model->primaryKey;
            
            $Edata['id'] = $activity_id;
            $Edata['isset_tosign_sms'] = 1;
            $Edata['start_before_hour'] = 24;
            ActivityExtension::model()->activityExtensionSave($Edata);
            //初始化短信配置模板
            SmsTask::model()->taskSaveDefault('activity', $activity_id);
            
            $ActivityContentModel = ActivityContent::model();
            $ActivityContent = $ActivityContentModel->findByAttributes(array('activity_id'=>$id));
            foreach($ActivityContent as $kc=>$vc){
                if( $kc=='id' ){
                    continue;
                }
                $ActivityContentModel->$kc = $vc;
                $ActivityContentModel->activity_id = $activity_id;
                $ActivityContentModel->_create_time = date('Y-m-d H:i:s');
                $ActivityContentModel->_update_time = null;
            }
            $ActivityContentModel->_new = 1;
            $ins = $ActivityContentModel->insert();
            return $ins;
       }else{
           return false;
       }
    }
    

    
    //生成活动编号 AA00开始
    function setActivityNum(){
        $code = 'AA00';
        $activity = $this->getlist(array(), 'desc', 'id', 1, 0);
        if(!empty($activity['data']) && !empty($activity['data'][0]['num'])){
            $code = $activity['data'][0]['num'];
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
	
}