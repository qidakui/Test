<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2018/1/17
 * Time: 10:13
 */
namespace application\models\Training;
class TrainingGenseeMaxValue extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gensee_max_value}}';
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
    
    public function saveData($startdate, $enddate, $maxvalue){
        $res = 0;
        $dateArr = $this->_divideDate($startdate, $enddate);
        $criteria = new \CDbCriteria;
        $criteria->addBetweenCondition('date_time', $dateArr[0],end($dateArr));
        $data = self::model()->findAll($criteria);
        if( !empty($data) ){
            foreach($data as $model){
                if( $model['max_value']!=$maxvalue && in_array($model['date_time'],$dateArr) ){
                    $model->max_value = $maxvalue;
                    $model->_update_time = date('Y-m-d H:i:s');
                    if($model->save()){
                        $res++;
                    }
                }
                unset($dateArr[array_search($model['date_time'],$dateArr)]);
            }
        }

        if( !empty($dateArr) ){
            foreach($dateArr as $date_time){
                $inster_data[] = array(
                    'user_id' => \Yii::app()->user->user_id,
                    'date_time' => $date_time,
                    'max_value' => $maxvalue,
                    '_create_time' => date('Y-m-d H:i:s')
                );
            }
            //print_r($inster_data);die;
            $builder = \Yii::app()->db->schema->commandBuilder;
            $command = $builder->createMultipleInsertCommand('{{gensee_max_value}}', $inster_data);
            $res = $command->execute();
        }
        return $res;
    }
    
    /*
     * 将起始时间划分为整天
     */
    public function _divideDate($startdate,$enddate){
        $strtotime_startdate = strtotime($startdate);
        $strtotime_enddate = strtotime($enddate);
        $arr = array($startdate);
        while($strtotime_startdate<$strtotime_enddate){
            $strtotime_startdate+=86400;
            $arr[] = date('Y-m-d',$strtotime_startdate);
        }
        return $arr;
    }
    
    /*
     * 根据日期获取max_value
     */
    public function getMaxValue($date_time){
        $criteria = new \CDbCriteria;
        $criteria->select = 'max_value';
        $criteria->compare('date_time', $date_time);
        $data = self::model()->find($criteria);
        return empty($data) ? 700 : intval($data->max_value);
    }
    /*
     * 根据日期获取一段时间内的最小max_value
     */
    public function getMinMaxValue($starttime, $endtime){
        $criteria = new \CDbCriteria;
        $criteria->select = 'MIN(max_value) max_value'; //考虑给max_value加上索引
        $criteria->addBetweenCondition('date_time', substr($starttime,0,10), substr($endtime,0,10)); 
        $data = self::model()->find($criteria);
        return empty($data->max_value) ? 700 : intval($data->max_value);
    }
    
    
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return $count;
    }

    
     
}