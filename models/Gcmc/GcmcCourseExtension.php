<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/13
 * Time: 14:32
 */
/**
 * This is the model class for table "{{training}}".
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
namespace application\models\Gcmc;
class GcmcCourseExtension extends \CActiveRecord
{
    public $status_name;
    
    //定义收集信息项
    public $shoujixnxi = array(
        'realname' => '真实姓名',
        'mobile' => '手机号码',
        'qq' => 'QQ号码',
        'email' => '邮箱',
        'dongle' => '加密锁号',
        'company' => '单位全称',
        'company_type' => '单位性质',
        'position' => '职位',
        'major' => '专业',
        'work_num' => '从业年限',	
        'soft' => '已有软件',
        'text1' => '',
        'text2' => '',
        'text3' => '',
        'text4' => '',
        'text5' => ''			
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_course_extension}}';
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
     * @return Admin the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //保存/修改
    public function saveData( $data=array() ){

        if(isset($data['id'])){
            $model = self::model()->findbypk($data['id']);
            $model->_update_time = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
        }
        foreach($data as $k=>$v){
            //$model->$k = \CHtml::encode($v);
            if($k=='invitation_note'){
				$v = str_replace("\n","",$v);
			}
			$model->$k = $v;
        }
		$model->save();
        $id  = intval($model->primaryKey);
        return $id;
    }
    
    /*
     * 获取一条扩展信息
     */
    public function getExtension($id){
        $data = self::model()->findByPk($id);
        if(empty($data)){
            $model = new self();
            $model->id = $id;
            $model->requirement = array(
                'realname' => 1,
                'mobile' => 1,
                'company' => 1,
                'position' => 1,
                'work_num' => 1
            );
            $model->requirement = serialize($model->requirement);
            $model->_create_time = date('Y-m-d H:i:s');
            $model->status = 1;
            $model->save($model);
            $data = self::model()->findByPk($id);
        }
        $data = $data->attributes;
        $data['requirement'] = unserialize($data['requirement']);
        return $data;
    }
 
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return intval($count);
    }
	
}