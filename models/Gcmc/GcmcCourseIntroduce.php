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
use application\models\ServiceRegion;
class GcmcCourseIntroduce extends \CActiveRecord
{
    public $status_name;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_course_introduce}}';
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
        $is_score = false;
        if(isset($con['_score'])){
            $is_score = true;
            unset($con['_score']);
            $typeArr = GcmcType::model()->get_name_list(array('parent_id'=>0,'_delete'=>0));
            if( isset($con['title']) && in_array($con['title'],$typeArr) ){
                $con['type_parent_id'] = array_search($con['title'], $typeArr);
                unset($con['title']);
            }
        }
        
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='title'){
                    $criteria->compare('title', $con['title'], true);
                }else{
                    $criteria->compare($key, $val);
                }
            }
        }
       
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
      // print_r($criteria);
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        //print_r($criteria);die;
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        foreach($ret as $k => $v){
            $v = $v->attributes;
            if($is_score){
                $_v['id'] = $v['id'];
                if($v['filiale_id']==BRANCH_ID){
                    $_v['city_name'] = '全国';
                }else{
                   $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
                    $_v['city_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:''; 
                }
                $_v['title'] = $v['title'];
                $_v['classify'] = isset($typeArr[$v['type_parent_id']]) ? $typeArr[$v['type_parent_id']] : '';
                $_v['type'] = '课程';
                $statistical = GcmcScore::model()->statistical(1,$v['id']);
                $v = $_v + $statistical;
            }
			$data[] = $v;
        }
        $data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);
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