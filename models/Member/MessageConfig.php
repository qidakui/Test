<?php

/**
 * This is the model class for table "{{message_config}}".
 *
 * The followings are the available columns in table '{{message_config}}':
 * @property string $id
 * @property string $subject
 * @property string $message
 * @property string $config
 * @property string $execute_time
 * @property integer $is_execute
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Member;
class MessageConfig extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{message_config}}';
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
            'id' => 'id',
            'subject' => '主题',
            'message' => '消息内容',
            'config' => '收件人执行配置序列号',
            'execute_time' => '执行时间',
            'is_execute' => '是否执行0未执行1执行',
            '_delete' => '是否删除0正常1删除',
            '_create_time' => 'Create Time',
            '_update_time' => 'Update Time',
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
     * @return MessageConfig the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function messageConfigSave($data){
        $model = new self();
        foreach($data as $key => $val){
            $model->$key = $val;
        }
        $model->_create_time      = date('Y-m-d H:i:s');
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function messageConfigUpdate($data){
        $id = $data['id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        foreach($data as $key => $val){
            $model->$key = $val;
        }
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }
}