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
namespace application\models\Training;
class TrainingExtension extends \CActiveRecord
{
    public $status_name;
    private $statusKey = array(
        0 => '启用',
        1 => '停用',
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{training_extension}}';
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
     * @return Admin the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //保存扩展信息
    public function trainingExtensionSave($data){
        $model = self::model()->findByPk($data['id']);
        $oldData = isset($model->attributes) ? $model->attributes : array();
        if($model){
            $model->_update_time = date('Y-m-d H:i:s');
            if(empty($model->requirement)){
                $model->requirement = 'a:2:{s:8:"realname";s:1:"1";s:6:"mobile";s:1:"1";}';
            }
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
            $model->requirement = 'a:2:{s:8:"realname";s:1:"1";s:6:"mobile";s:1:"1";}';
        }
        foreach($data as $k=>$v){
            if($k==='requirement' || $k==='requirement_online' || $k==='old_time_and_address'){
                $model->$k = $v;       
            }else{
                $model->$k = \CHtml::encode($v);
            }
        }
        $model->save();
        $id = intval($model->primaryKey);
        if(isset($data['id'])){
            \OperationLog::addLog(\OperationLog::$operationTraining, 'edit', '修改培训扩展表', $data['id'], $oldData, $data);
        }else{
            \OperationLog::addLog(\OperationLog::$operationTraining, 'add', '插入培训扩展表', $id, array(), $data);
        }
        return $id;
    }

    //根据讲师账号更新主表讲师姓名
    public function update_lecturer_name($member_user_name, $lecturer_name){
        $member_user_name = trim($member_user_name);
        $lecturer_name = trim($lecturer_name);
        if($member_user_name){
            $criteria = new \CDbCriteria;
            $criteria->select = 'id';
            $criteria->compare('lecturer_account', $member_user_name);
            $list = self::model()->findAll($criteria);
            foreach($list as $v){
                Training::model()->updateByPk($v['id'], array('lecturer'=>$lecturer_name));
            }
        }
    }
    
    //获取活动浏览量
    public function getActivityViews($activity_id){
        $model = self::model()->findByPk($activity_id);
        return isset($model->views) && !empty($model->views)?$model->views:0;
    }
	
}