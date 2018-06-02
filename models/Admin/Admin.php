<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/10
 * Time: 14:32
 */
/**
 * This is the model class for table "{{admin}}".
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
namespace application\models\Admin;
use application\models\Privilege\Role;
use application\models\ServiceRegion;
class Admin extends \CActiveRecord
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
        return '{{admin}}';
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

    public function findByAdminInfo($name, $phone){
        $infoArr = Admin::model()->find('user_name=:user_name', array(':user_name' => $name));
        if(empty($infoArr)){
            $infoArr = Admin::model()->find('phone=:phone', array(':phone' => $phone));
            if(!empty($infoArr)){
                return true;
            }
        } else {
            return true;
        }
        return false;
    }

    public function adminSave($data){
        $model = new self();
        $model->user_name   = $data['user_name'];
        $model->password    = $data['password'];
        $model->phone       = $data['phone'];
        $model->email       = $data['email'];
        $model->random      = $data['random'];
        $model->branch_id   = $data['branch_id'];
        $model->_create_time      = date('Y-m-d H:i:s');
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function adminUpdate($data){
        $id = $data['id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->user_name   = $data['user_name'];
        $model->phone       = $data['phone'];
        $model->email       = $data['email'];
        $model->branch_id   = $data['branch_id'];
        $model->_create_time      = date('Y-m-d H:i:s');
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function adminResetPasswd($data){
        $id = $data['id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->password    = $data['password'];
        $model->random      = $data['random'];
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function adminUpdateStatus($id, $status){
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->status = $status;
        $flag = $model->save();
        return $flag;
    }
    public function getData($con = array(),$nums = null) {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
         }
        $ret = !empty($nums)?self::model()->findAll($criteria):self::model()->find($criteria);
        return $ret;
    }
    public function getAdminId($param,$cake = 'all'){
        $adminarray = array();
        $adminid = Admin::model()->getData($param,$cake);
        if(!empty($adminid)){
            foreach ($adminid as $key=>$item){
                $adminarray['ids'][] = $item->id;
                $areaName = ServiceRegion::model()->getBranchToCity($item['branch_id']);
                $adminarray['branchName'] = !empty($areaName[0]['region_name'])?$areaName[0]['region_name']:'全国';
            } 
        }
        return $adminarray;
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

        $userIds = columnToArr($ret, 'id');

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('user_id', $userIds);
        $adminRoleObj = AdminRole::model()->findAll($criteria);

        $adminRoleArr = objectToKeywordArr($adminRoleObj, 'user_id', 'role_id');
        $adminRoleIds = columnToArr($adminRoleObj, 'role_id');
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('id', $adminRoleIds);
        $roleObjs = Role::model()->findAll($criteria);

        $roleArr = objectToKeywordArr($roleObjs, 'id', 'role_name');

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $role_id  = !empty($adminRoleArr[$data[$k]['id']]) ? $adminRoleArr[$data[$k]['id']] : '';
            $data[$k]['role_name']   = !empty($roleArr[$role_id]) ? $roleArr[$role_id] : '';
            $data[$k]['role_id']     = !empty($role_id) ? $role_id : 0;
            $data[$k]['status_name'] = isset($this->statusKey[$data[$k]['status']]) ? $this->statusKey[$data[$k]['status']] : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
}