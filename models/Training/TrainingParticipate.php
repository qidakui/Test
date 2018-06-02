<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/20
 * Time: 10:13
 */

namespace application\models\Training;
use application\models\Training\Training;
use application\models\Common\CommonMember;
class TrainingParticipate extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{training_participate}}';
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
        return array(
			'training' => array(self::BELONGS_TO, get_class(Training::model()), 'training_id','joinType' => 'join','select' => 'filiale_id,title,num,apply_province_code,starttime,endtime'),
		);
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
     * @return ActivityParticipate the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //查询列表 （此方法仅用于学员列表）
    public function get_list($con, $orderBy, $order, $limit, $offset){
        $return_data = array('data' => array(), 'iTotalRecords' => 0, 'iTotalDisplayRecords' => 0,);
        $criteria = new \CDbCriteria();
        $is_signin = 0; //九月
        if(!empty($con)){
            if(isset($con['is_signin'])){ //九月
                $is_signin = $con['is_signin'];
                $training_id_sql = isset($con['t.training_id']) ? ' where training_id='.$con['t.training_id'] : '';
                if( $is_signin==1){
                    $criteria->addCondition('t.id IN(select DISTINCT participate_id from e_training_participate_signin_log '.$training_id_sql.')');
                }else{
                    $criteria->addCondition('t.id NOT IN(select DISTINCT participate_id from e_training_participate_signin_log '.$training_id_sql.')');
                }
                unset($con['is_signin']);
            }
            
            foreach($con as $key => $val){
                if($key=='title'){
					$criteria->addSearchCondition($key, $val);
				}elseif($key=='filiale_id' && is_array($val) ){
                    $criteria->addInCondition($key,$val);
                }else{
					$criteria->compare($key, $val);
				}
            }
        }
        
        $criteria->with = array('training');//print_r($criteria);
        $count = self::model()->count($criteria);
        if($count==0){
            return $return_data;
        }
        
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', 't.'.$order, $orderBy);
        }
        $criteria->limit = $limit; 
        $criteria->offset = $offset; 
        $res = self::model()->findAll($criteria);
        if( empty($res) ){
            return $return_data;
        }
        $participate_way = array(0=>'网络直播', 1=>'线下培训' );
        $status = array(0=>'审核中', 1=>'报名成功', 2=>'取消报名');
        $today_time = date('Y-m-d');
        foreach($res as $vv){
            $v = $vv->attributes;
            $training = $vv->training->attributes;
            $v['training_num'] = $training['num'];
            $v['training_title'] = $training['title'];
            $v['apply_province_code'] = $training['apply_province_code'];
            
            $v['participate_way_txt'] = $participate_way[$v['participate_way']];
            $v['training_advice'] = empty($v['training_advice']) ? '' : cutstr($v['training_advice'],50);   
            $v['status_txt'] = $status[$v['status']];
            
            if($v['participate_way']==1){
                $v['invite_code'] = !empty($v['invite_code']) ? $training['num'].'-'.$v['invite_code'] : '';
                //今天是否签到
                $today_is_singin = TrainingParticipateSigninLog::model()->getCount( array('participate_id'=>$v['id'],'date(signin_time)'=>$today_time) );
                $v['today_is_singin'] = $today_is_singin==0 ? '未签到' : '已签到'; 
                //签到天数/培训天数
                if( $is_signin==2 ){ //九月
                    $signin_day = 0;
                }else{
                    $signin_day = TrainingParticipateSigninLog::model()->getCount( array('participate_id'=>$v['id']) );
                }
                $training_day = (strtotime($training['endtime'])-strtotime($training['starttime']))/86400;
                $training_day = ceil($training_day);
                $training_day = $training_day<1 ? 1 : $training_day;
                $v['signin_day'] = $signin_day. '/' . $training_day;
                $v['is_listen'] = $signin_day==0 ? '否' : '是'; //是否听课 待改
            }else{
                $v['invite_code'] = '-';
                $v['today_is_singin'] = '-';
                $v['signin_day'] = '-';
                $v['is_listen'] = empty($v['yqh_path']) ? '否' : '是';
            }
 
            $v['is_good'] = $v['is_good']==1?'是':'否';
            $v['reward'] = $v['reward']==0 ? 0 : $v['reward'].'积分';
            $v['is_prize_winning_txt'] = $v['is_prize_winning']==0 ? '未中奖' : '中奖';
            if( $v['extend'] ){
                $v['extend'] = unserialize($v['extend']);
            }
            $data[] = $v;
        }
        $return_data = array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        return $return_data;
    }
    
    //老查询列表方法
    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }

        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }

        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        $data = array();
        $training_ids = array();
        $ids = array();
        foreach($ret as $v){
            $v = $v->attributes;
            $data[] = $v;
            $ids[] = $v['id'];
            $training_ids[] = $v['training_id'];
        }
        
        if( !empty($data) ){
            $training = Training::model()->findTrainingByPk($training_ids, array('id','num','title', 'starttime', 'endtime'));

            //报名方式
            $participate_way = array(0=>'网络直播', 1=>'线下培训' );
            $status = array(0=>'审核中', 1=>'报名成功', 2=>'取消报名');
            $today_time = date('Y-m-d');
            foreach($data as $k=>$v){
                $data[$k]['training_num'] = $training[$v['training_id']]['num'];
                $data[$k]['training_title'] = $training[$v['training_id']]['title'];
                $data[$k]['participate_way_txt'] = $participate_way[$v['participate_way']];
                $data[$k]['training_advice'] = empty($data[$k]['training_advice'])? '' : cutstr($data[$k]['training_advice'],50); 
                $data[$k]['status_txt'] = $status[$v['status']];
                
                if($v['participate_way']==1){
                    $data[$k]['invite_code'] = !empty($v['invite_code']) ? $training[$v['training_id']]['num'].'-'.$v['invite_code'] : '';
                    //今天是否签到
                    $today_is_singin = TrainingParticipateSigninLog::model()->getCount( array('participate_id'=>$v['id'],'date(signin_time)'=>$today_time) );
                    $data[$k]['today_is_singin'] = $today_is_singin==0 ? '未签到' : '已签到'; 
                    //签到天数/培训天数
                    $signin_day = TrainingParticipateSigninLog::model()->getCount( array('participate_id'=>$v['id']) );
                    $training_day = (strtotime($training[$v['training_id']]['endtime'])-strtotime($training[$v['training_id']]['starttime']))/86400;
                    $training_day = ceil($training_day);
                    $training_day = $training_day<1 ? 1 : $training_day;
                    $data[$k]['signin_day'] = $signin_day. '/' . $training_day;
                    $data[$k]['is_listen'] = $signin_day==0 ? '否' : '是'; //是否听课 待改
                    
                }else{
                    $data[$k]['invite_code'] = '-';
                    $data[$k]['today_is_singin'] = '-';
                    $data[$k]['signin_day'] = '-';
                    $data[$k]['is_listen'] = empty($v['yqh_path']) ? '否' : '是';
                }
                $data[$k]['is_good'] = $v['is_good']==1?'是':'否';
                $data[$k]['reward'] = $v['reward']==0 ? 0 : $v['reward'].'积分';
                $data[$k]['is_prize_winning_txt'] = $v['is_prize_winning']==0 ? '未中奖' : '中奖';
                $data[$k]['status_txt'] = $v['status']==2 ? '取消报名' : ($v['status']==1 ? '正常' : '审核中');
                if( $v['extend'] ){
                    $data[$k]['extend'] = unserialize($v['extend']);
                    if(isset($data[$k]['extend']['advice']) && $data[$k]['extend']['advice']=='undefined'){
						$data[$k]['extend']['advice'] = '';
					}
                }
                $data[$k]['_company'] = $v['company'];
                $CommonMember = CommonMember::model()->findMemberUserId($v['member_user_id']);
                $data[$k]['member_user_name'] = isset($CommonMember['member_user_name']) ? $CommonMember['member_user_name'] : '';
            }
           // print_r($data);
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        //return array('data' => $data, 'count' => $count);
    }
    
    public function idsByFind($ids){
        if(empty($ids)){
			return array();
		}
		$Criteria = new \CDbCriteria();
		//$Criteria->select = 'id,title';
		$Criteria->addInCondition('id', $ids);
		$list = self::model()->findAll($Criteria);
		return $list;
    }

    public function SaveData($data){
        if(isset($data['id']) && !empty($data['id'])){
            $model = self::model()->findByPk($data['id']);
            $oldData = isset($model->attributes) ? $model->attributes : array();
            $model->_update_time = date('Y-m-d H:i:s');
            if(isset($data['company']) && !empty($model['extend'])){
                $extend = @unserialize($model['extend']);
                if(isset($extend['company'])){
                    $extend['company'] = $data['company'];
                    $model->extend = serialize($extend);
                }
            }
        }else{
            $model = new self();
            $model->status = 1;
            //$model->controller_user_id = \Yii::app()->user->user_id;
            $model->_create_time = date('Y-m-d H:i:s');
        }
        foreach($data as $k=>$v){
            $model->$k = $v;
        }
        if( $model->save() ){
            $id = intval($model->primaryKey);
            if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationTraining, 'edit', '编辑学员信息', $data['id'], $oldData, $data);
            }else{
                \OperationLog::addLog(\OperationLog::$operationTraining, 'add', '新增学员', $id, array(), $data);
            }
            return $id;
        }else{
            return false;
        }
    }
    

    //统计报名表
    public function getRequirementCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }

        $count = self::model()->count($criteria);
        return intval($count);
    }


    /**
     * 初始化中奖信息
     * @param $data
     * @return bool
     */
    public function initializeDrawLots($data){
        $flag = true;
        $id = $data['training_id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findAll('training_id=:training_id', array('training_id' => $id));
        if(!empty($model)){
            foreach($model as $val){
                $val->prize_winning_time    = '0000:00:00 00:00:00';
                $val->is_prize_winning      = 0;
                $DrawLotsFlag = $val->save();
                if(!$DrawLotsFlag){
                    $flag = false;
                }
            }
        }

        return $flag;
    }

	//报名人次
	public function getIdsRequirementCount( $_training_ids=array() ){
        $criteria = new \CDbCriteria;
		$criteria->compare('status', 1);	
		$criteria->addInCondition('training_id', $_training_ids);
        $count = self::model()->count($criteria);
        return intval($count);
    }
	
	//报名人数
	public function getDistinctMobileCount( $_training_ids=array() ){
		$sql = 'status=1 and training_id in('.implode(',',$_training_ids).') and participate_way=1';
		$count = \Yii::app()->db->createCommand()
            ->select("count(distinct mobile) as c ")
            ->from("e_training_participate")
            ->where($sql)
            ->queryAll();
        return isset($count[0]['c']) ? $count[0]['c'] : 0;
    }

}