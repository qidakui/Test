<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/25
 * Time: 16:58
 */
namespace application\models;
use application\models\Activity\Activity;
use application\models\Training\Training;
use application\models\Activity\ActivityExtension;
use application\models\Training\TrainingExtension;
use application\models\Gcmc\GcmcCourse;
use application\models\Gcmc\GcmcCourseExtension;
use application\models\ServiceRegion;
class SmsTask extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{sms_task}}';
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
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array();
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
     * @return ServiceRegion the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /*
     * 插入/修改
     */
    public function taskSave($data){
        if(isset($data['id']) && $data['id']){
            $model = self::model()->findByPk($data['id']);
            $model->_update_time = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
        }
        foreach($data as $k=>$v){
            if($k=='sms_template'){
				$v = str_replace("\n","",$v);
			}
            $model->$k = \CHtml::encode($v);
        }
        $model->save();
        return intval($model->primaryKey);
    }
    
    /*
     * 插入默认一条
     */
    public function taskSaveDefault($column_name, $column_id){
        if( $column_name=='activity' ){
            $column = Activity::model()->findByPk($column_id)->attributes;
        }elseif($column_name=='training'){
            $column = Training::model()->findByPk($column_id)->attributes;
        }elseif($column_name=='gcmc'){
            $column = GcmcCourse::model()->findByPk($column_id)->attributes;
        }
        $column['all_address'] = ServiceRegion::model()->getRedisCityList($column['province_code']);
        $column['all_address'] .= ' '.ServiceRegion::model()->getRedisCityList($column['city_code']);
        $column['all_address'] .= ' '.$column['address'];
        $column_arr = array('activity'=>'活动', 'training'=>'培训', 'gcmc'=>'GCMC');
        
        $tData['column_name'] = $column_name;
        $tData['filiale_id'] = $column['filiale_id'];
        $tData['column_id'] = $column_id;
        $tData['describe'] = $column_arr[$column_name].'报名即发短信模板';
        $tData['sms_template'] = '亲，您报名的'.$column_arr[$column_name].'“'.$column['title'].'”，'.date('m月d日H:i',strtotime($column['starttime'])).'~'.date('m月d日H:i',strtotime($column['endtime'])).'，'.$column['all_address'].'，请准时参加。';
        $this->taskSave($tData);

        $column_starttime = strtotime($column['starttime']);
        $send_time = $column_starttime - 24*3600;
        $tData['describe'] = $column_arr[$column_name].'开始前24h发送短信模板';
        $tData['send_time'] = date('Y-m-d H:i:s',$send_time);
        $tData['sms_template'] = $tData['sms_template'];
        $tData['is_crontab'] = 1;
        $this->taskSave($tData);
    }
    
    /*
     * 根据条件查询一条
     */
    public function get_list($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $data = self::model()->findAll($criteria);
        if( $data && in_array($con['column_name'],array('activity','training','gcmc')) ){
            foreach($data as $v){
                if($v['is_crontab']==0){
                    $_data['tosign_sms'] = $v->attributes;
                }else{
                    $_data['hour_sms'] = $v->attributes;
                }
            }
            $data = $_data;
        }
        return $data;
    }
    
    /*
     * 修改活动/培训开始时间后同步任务的执行时间
     */
    public function upSmsTask_send_time($column_name, $column_id, $starttime){
        $Extension = array();
        if($column_name=='activity'){
            $Extension = ActivityExtension::model()->findByPk(
                 $column_id,
                 array('select'=>['start_before_hour']));
        }elseif($column_name=='training'){
            $Extension = TrainingExtension::model()->findByPk(
                 $column_id,
                 array('select'=>['start_before_hour']));
        }elseif($column_name=='gcmc'){
            $Extension = GcmcCourseExtension::model()->findByPk(
                 $column_id,
                 array('select'=>['start_before_hour']));
        }
        if($Extension && isset($Extension['start_before_hour']) && $Extension['start_before_hour'] ){
            $criteria = new \CDbCriteria;
            $criteria->compare('column_name', $column_name);
            $criteria->compare('column_id', $column_id);
            $criteria->compare('is_crontab', 1);
            $smsTask = self::model()->find($criteria);
            if($smsTask){
                $send_time = strtotime($starttime) - intval($Extension['start_before_hour'])*3600;
                $send_time = date('Y-m-d H:i:s', $send_time);
                $smsTask->send_time = $send_time;
                $smsTask->save();
            }
        }
    }
    
    /*
     * 根据条件查统计数量
     */
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
}