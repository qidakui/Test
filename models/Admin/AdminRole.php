<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/10
 * Time: 14:55
 */

/**
 * This is the model class for table "{{admin_role}}".
 *
 * The followings are the available columns in table '{{admin_role}}':
 * @property string $id
 * @property string $user_id
 * @property string $role_id
 * @property integer $_delete
 * @property string $_update_time
 * @property string $_create_time
 */
namespace application\models\Admin;
class AdminRole extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{admin_role}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('_delete', 'numerical', 'integerOnly'=>true),
            array('user_id, role_id', 'length', 'max'=>10),
            array('_update_time, _create_time', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array('id, user_id, role_id, _delete, _update_time, _create_time', 'safe', 'on'=>'search'),
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
            'id' => 'ID',
            'user_id' => 'User',
            'role_id' => 'Role',
            '_delete' => 'Delete',
            '_update_time' => 'Update Time',
            '_create_time' => 'Create Time',
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

        $criteria=new \CDbCriteria;

        $criteria->compare('id',$this->id,true);
        $criteria->compare('user_id',$this->user_id,true);
        $criteria->compare('role_id',$this->role_id,true);
        $criteria->compare('_delete',$this->_delete);
        $criteria->compare('_update_time',$this->_update_time,true);
        $criteria->compare('_create_time',$this->_create_time,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
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

    public function adminRoleSave($data){
        $model = new self();
        $model->user_id   = $data['user_id'];
        $model->role_id   = $data['role_id'];
        $model->_create_time      = date('Y-m-d H:i:s');
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function adminRoleUpdate($data){
        $id = $data['id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->user_id   = $data['user_id'];
        $model->role_id   = $data['role_id'];
        $model->_create_time      = date('Y-m-d H:i:s');
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }
}