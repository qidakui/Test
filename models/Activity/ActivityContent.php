<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/13
 * Time: 14:55
 */

/**
 * This is the model class for table "{{activity_content}}".
 *
 * The followings are the available columns in table '{{admin_role}}':
 * @property string $id
 * @property string $user_id
 * @property string $role_id
 * @property integer $_delete
 * @property string $_update_time
 * @property string $_create_time
 */
namespace application\models\Activity;
class ActivityContent extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{activity_content}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
           
        );
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
        // @todo Please modify the following code to remove attributes that should not be searched.

       /* $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id,true);
        $criteria->compare('user_id',$this->user_id,true);
        $criteria->compare('role_id',$this->role_id,true);
        $criteria->compare('_delete',$this->_delete);
        $criteria->compare('_update_time',$this->_update_time,true);
        $criteria->compare('_create_time',$this->_create_time,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));*/
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return AdminRole the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //保存活动内容
    public function activityContentSave($data){
        $model = new self();
        $model->activity_id   = $data['activity_id'];
        $model->content   = $data['content'];
        $model->save();
        $id  = $model->primaryKey;
        \OperationLog::addLog(\OperationLog::$operationActivity, 'add', '创建活动内容', $data['activity_id'], array(), $data);
        return $id;
    }

    //修改活动内容
    public function activityContentUpdate($data){
        
        $activity_id = $data['activity_id'];
        if(empty($activity_id)){
            return false;
        }
        $model = self::model()->findByAttributes(array('activity_id'=>$activity_id));
        if(!$model){
            return $this->activityContentSave($data);
        }
        $model->activity_id   = $data['activity_id'];
        $model->content   = $data['content'];
        $model->save();
        $id  = $model->primaryKey;
        \OperationLog::addLog(\OperationLog::$operationActivity, 'edit', '修改活动内容', $activity_id, array($model['content']), $data);
        return $id;
    }
    
    //根据字段修改内容
    public function activityContentEdit($data){
        if(isset($data['activity_id']) && !empty($data['activity_id'])){
            $model = self::model()->findByAttributes(array('activity_id'=>$data['activity_id']));
            if($model){
                $model->_update_time = date('Y-m-d H:i:s');
            }else{
                $model = new self();
                $model->_create_time = date('Y-m-d H:i:s');
            }
            foreach($data as $k=>$v){
                $model->$k = \CHtml::encode($v);
            }
            $model->save();
            $id  = $model->primaryKey;
            return $id;
        }
    }
}