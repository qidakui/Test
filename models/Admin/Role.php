<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/10
 * Time: 14:53
 */
/**
 * This is the model class for table "{{role}}".
 *
 * The followings are the available columns in table '{{role}}':
 * @property integer $id
 * @property string $role_name
 * @property string $role_text
 * @property integer $_delete
 * @property string $_update_time
 * @property string $_create_time
 */
namespace application\models\Admin;
class Role extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{role}}';
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

    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Role the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
}