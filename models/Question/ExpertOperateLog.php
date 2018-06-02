<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/13
 * Time: 17:24
 */

/**
 * This is the model class for table "{{expert}}".
 *
 * The followings are the available columns in table '{{expert}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $expert_level
 * @property string $expert_field
 * @property integer $expert_type
 * @property integer $expert_point
 * @property integer $expert_state
 * @property string $apply_date
 * @property string $job
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Question;
use application\models\Question\UserQuestion;
use application\models\User\UserBrief;
use application\models\ServiceRegion;
use application\models\Member\CommonMember;
use application\models\Common\Message;
class ExpertOperateLog extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{expert_operate_log}}';
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
     * @return Expert the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        if(parent::beforeSave()){
            if($this->isNewRecord){
                $this->_update_time = date('y-m-d H:m:s');
                $this->_create_time = date('y-m-d H:m:s');
            }else{
                $this->_update_time = date('y-m-d H:m:s');
            }
            return true;
        }else{
            return false;
        }
    }

    public function leave($expert_id, $user_id){
        $id = $this->createNewLog($expert_id, 2);
        $data = array(
            'user_id' => $user_id,
            'subject' => '系统消息',
            'message' => "尊敬的答疑解惑专家：您已被管理员卸任了专家职责。如您今后想再次成为专家，可以一个月后重新进行申请。如有疑问，请与管理员联系。",
            'config_id' =>1,
        );

        Message::model()->messageSave($data);
        return $id;
    }

    public function changeLevel($expert_id){
        return $this->createNewLog($expert_id, 3);
    }

    public function applyPass($expert_id){
        return $this->createNewLog($expert_id, 1);
    }

    public function createNewLog($expert_id, $operate_id){
        $log = new self();
        $log->expert_id = $expert_id;
        $log->operate_id = $operate_id;
        $log->operate_time = date('y-m-d H:m:s');
        $log->save();
        return $log->primaryKey;
    }
}