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
use application\models\Admin\Admin;
use application\models\ServiceRegion;
class GcmcCourse extends \CActiveRecord
{
    public $status_name;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_course}}';
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
			$model->$k = $v;
        }
		$model->save();
        $id  = intval($model->primaryKey);
        return $id;
    }
 
    
    //查询列表
    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='starttime'){
                    $criteria->addBetweenCondition('starttime', $con['starttime'], $con['endtime'].' 23:59:59');
                }elseif($key=='title'){
                    $criteria->addCondition('title like \'%'.$con['title'].'%\' OR first_experts_name like \'%'.$con['title'].'%\' OR second_experts_name like \'%'.$con['title'].'%\'');
                    //$criteria->params[':title'] = $con['title'];
                }elseif(!in_array($key,['title','starttime','endtime'])){
                    $criteria->compare($key, $val);
                }
            }
        }
       
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
       
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        //print_r($criteria);die;
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        foreach($ret as $k => $v){
            $v = $v->attributes;
			$city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
			$v['city_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
            $v['experts'] = $v['first_experts_name'].( empty($v['second_experts_name']) ?'':'、'.$v['second_experts_name']);
            $province_code = ServiceRegion::model()->getRedisCityList($v['province_code']);
            $city_code = ServiceRegion::model()->getRedisCityList($v['city_code']);
            $v['address'] = $province_code.' '.$city_code.' '.$v['address'];
			$data[] = $v;
        }
       // print_r($data);
        $data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);
    }
	
	/*
	* 根据id查询一条
	*/
	public function findByOne($id){
		$v = [];
		$data = self::model()->findByPk($id);
		if($data){
			$v = $data->attributes;
		}
		if(!empty($v['course'])){
			$v['course'] = unserialize($v['course']);
		}
		if(!empty($v['lecturer'])){
			$v['lecturer'] = unserialize($v['lecturer']);
            $v['lecturer'][0]['avatar'] = strpos($v['lecturer'][0]['avatar'],'http')===0 ? $v['lecturer'][0]['avatar'] : UPLOADURL.$v['lecturer'][0]['avatar'];
            $v['lecturer'][1]['avatar'] = strpos($v['lecturer'][1]['avatar'],'http')===0 ? $v['lecturer'][1]['avatar'] : UPLOADURL.$v['lecturer'][1]['avatar'];
		}
		if(!empty($v['object'])){
			$v['object'] = unserialize($v['object']);
			//$v['object']['content'] = implode("\n", $v['object']['content']);
		}
		if(!empty($v['course_table'])){
			$v['course_table'] = unserialize($v['course_table']);
		}
		return $v;
	}
    
    //生成编号 AA00开始
    function setDontRepeatNum(){
        $code = 'AA00';
        $activity = $this->getlist(array(), 'desc', 'id', 1, 0);
        if(!empty($activity['data']) && !empty($activity['data'][0]['num'])){
            $code = $activity['data'][0]['num'];
        }
        if($code==='ZZ99'){
            return 'ZZ100';
        }
        $az = array("A", "B", "C", "D", "E", "F", "G","H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $code1 = $code[0];
        $code2 = $code[1];
        $code3 = $code[2];
        $code4 = $code[3];

        if($code4<9){
            $code4 ++;
        }else{
            $code4 = 0;
            if($code3<9){
                $code3 ++;
            }else{
                $code3 = $code4 = 0;
                $k2 = array_search($code2,$az);
                if($k2<25){
                    $code2 = $az[$k2+1];
                }else{
                    $code2 = 'A';
                    $k1 = array_search($code1,$az);
                    if($k1<25){
                        $code1 = $az[$k1+1];
                    }
                }
            }
        }
        $lasttwo = $code1.$code2.$code3.$code4;
        return $lasttwo;
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