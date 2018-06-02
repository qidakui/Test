<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/12
 * Time: 18:24
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
 * @property string $describe
 */
namespace application\models\Privilege;
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

    public function roleSave($data){
        $model = new self();
        $model->role_name       = $data['role_name'];
        $model->role_text       = $data['role_text'];
        $model->describe        = $data['describe'];
        $model->_create_time    = date('Y-m-d H:i:s');
        $model->_update_time    = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    /**
     * 修改角色
     * @param $data
     * @return bool
     */
    public function roleUpdate($data){
        $id = $data['id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->role_name       = $data['role_name'];
        $model->role_text       = $data['role_text'];
        $model->describe        = $data['describe'];
        $model->_create_time        = date('Y-m-d H:i:s');
        $model->_update_time        = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

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
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
        }
        $data = !empty($data) ? $data : array();

        return array('data' => $data, 'count' => $count);
    }
}