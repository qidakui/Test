<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/12
 * Time: 10:15
 */
/**
 * This is the model class for table "{{privilege}}".
 *
 * The followings are the available columns in table '{{privilege}}':
 * @property string $id
 * @property string $privilege_name
 * @property string $privilege_module
 * @property string $privilege_link
 * @property integer $srot
 * @property integer $status
 * @property integer $parent_id
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Privilege;
class Privilege extends \CActiveRecord
{
    private $statusKey = array(
        0 => '启用',
        1 => '停用',
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{privilege}}';
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
     * @return Privilege the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function getParentList($con){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }

        $ret = self::model()->findAll($criteria);
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data);
    }

    public function privilegeSave($data){
        $model = new self();
        $model->privilege_name      = $data['privilege_name'];
        $model->privilege_module    = $data['privilege_module'];
        $model->privilege_link      = $data['privilege_link'];
        $model->sort                = $data['sort'];
        $model->parent_id           = $data['parent_id'];
        $model->_create_time        = date('Y-m-d H:i:s');
        $model->_update_time        = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    /**
     * 修改栏目
     * @param $data
     * @return bool
     */
    public function privilegeUpdate($data){
        $id = $data['id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->privilege_name      = $data['privilege_name'];
        $model->privilege_module    = $data['privilege_module'];
        $model->privilege_link      = $data['privilege_link'];
        $model->sort                = $data['sort'];
        $model->parent_id           = $data['parent_id'];
        $model->_create_time        = date('Y-m-d H:i:s');
        $model->_update_time        = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    /**
     * 更改状态 停用启用
     * @param $id
     * @param $status
     * @return bool
     */
    public function privilegeUpdateStatus($id, $status){
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        if($model->parent_id == 0){
            $flag =  self::model()->updateAll(array('status' => $status), 'parent_id=:parent_id', array('parent_id' => $id));
            $model->status = $status;
            $model->save();
        } else {
            $model->status = $status;
            $flag = $model->save();
        }

        return $flag;
    }

    public function getlists($con, $orderBy, $order, $limit, $offset){
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

            $data[$k]['status_name'] = isset($this->statusKey[$data[$k]['status']]) ? $this->statusKey[$data[$k]['status']] : '';
        }
        $data = !empty($data) ? $data : array();

        return array('data' => $data, 'count' => $count);
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
        if(!empty($limit) && isset($offset)){
            $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
            $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        }

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            if($data[$k]['parent_id'] == 0){
                $son = self::model()->findAll('parent_id=:parent_id and _delete=0', array('parent_id' => $data[$k]['id']));
                if(!empty($son)){
                    foreach($son as $key => $arr){
                        $data[$k]['son'][] = $arr->attributes;
                    }
                }
            }

            $data[$k]['status_name'] = isset($this->statusKey[$data[$k]['status']]) ? $this->statusKey[$data[$k]['status']] : '';
        }
        $data = !empty($data) ? $data : array();

        return array('data' => $data, 'count' => $count);
    }
}