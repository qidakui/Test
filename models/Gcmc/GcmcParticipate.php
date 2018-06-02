<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/20
 * Time: 10:13
 */

namespace application\models\Gcmc;
use application\models\ServiceRegion;
class GcmcParticipate extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_participate}}';
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
     * @return ActivityParticipate the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function SaveData($data){
        if(isset($data['id']) && !empty($data['id'])){
            $model = self::model()->findByPk($data['id']);
            $model->_update_time = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
            $model->status = 1;
        }
        foreach($data as $k=>$v){
            $model->$k = $v;
        }
        if( $model->save() ){
            return intval($model->primaryKey);
        }else{
            return false;
        }
    }
 
    

    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if( $key=='_create_time' ){
                    $criteria->addBetweenCondition('_create_time',$val['starttime'], $val['endtime']);
                }elseif($key=='search_content'){
                    if(isMobilePhone($val)){
                        $criteria->compare('phone', $val);
                    }else{
                        $criteria->addCondition('realname like \'%'.$val.'%\' OR phone like \'%'.$val.'%\'');
                    }
                }elseif($key!='_create_time' ){
                    $criteria->compare($key, $val);
                }
            }
        }
        //print_r($criteria);die;
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }

        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        
        $status_arr = [0=>'删除',1=>'正常',2=>'取消'];
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        $data = array();
        $city_name = $title = $datatime = $experts_name = '';
        foreach($ret as $k=>$v){
            $v = $v->attributes;
            if($k==0){
                $GcmcCourse = GcmcCourse::model()->findByPk($v['course_id']);
                $city_name = $GcmcCourse['filiale_id'];
                $title = $GcmcCourse['title'];
                $datatime = substr($GcmcCourse['starttime'],5,11).'~'.substr($GcmcCourse['endtime'],5,11);
                $experts_name = $GcmcCourse['first_experts_name'];
                $experts_name .= empty($GcmcCourse['second_experts_name']) ? '' : '、'.$GcmcCourse['second_experts_name'];
            }
            $v['city_name'] = $city_name;
            $v['title'] = $title;
            $v['datatime'] = $datatime;
            $v['experts_name'] = $experts_name;
            //$v['province'] = ServiceRegion::model()->getRegionName($v['province_code']); 
            if( $v['extend'] ){
                $v['extend'] = unserialize($v['extend']);
            }
            //for($i=1;$i<6;$i++){
            //   $v['text'.$i] = isset($v['extend'][$i])?$v['extend'][$i]:'';
            //}
            $v['text1'] = isset($v['extend']['text1'])?$v['extend']['text1']:'';
            $v['text2'] = isset($v['extend']['text2'])?$v['extend']['text2']:'';
            $v['text3'] = isset($v['extend']['text3'])?$v['extend']['text3']:'';
            $v['text4'] = isset($v['extend']['text4'])?$v['extend']['text4']:'';
            $v['text5'] = isset($v['extend']['text5'])?$v['extend']['text5']:'';
            //$v['company'] = isset($v['extend']['company']) ? $v['extend']['company'] : '';
            $v['position'] = isset($v['extend']['position']) ? $v['extend']['position'] : '';
            $v['work_num'] = isset($v['extend']['work_num']) ? $v['extend']['work_num'] : '';
            $v['status_txt'] = $status_arr[$v['status']];
            $v['cancel_time'] = $v['cancel_time']==0?'':$v['cancel_time'];
            $v['again_time'] = $v['again_time']==0?'':$v['again_time'];
            $v['signin_time'] = $v['signin_time']==0?'':$v['signin_time'];
            $data[] = $v;
        }
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
    
    /*
     * 根据条件获取记录
     */
    public function getOne($con = array()) {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
        }
        $data = self::model()->find($criteria);
        return $data;
    }
 
 
    //统计报名表
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