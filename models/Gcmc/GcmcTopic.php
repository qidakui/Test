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
class GcmcTopic extends \CActiveRecord
{
 
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_topic}}';
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
    
    /*
     * 设置置顶 加精
     */
    public function setTopElite($id, $edit_field){
        $model = self::model()->findbypk( $id );
        if($model){
            if( $edit_field=='status' ){
                $model->status = 0;
            }elseif( $edit_field=='top' ){
                $top_v = empty($model['top']) ? time() : 0;
                $model->top = $top_v;
            }elseif( $edit_field=='elite' ){
                //当前时间减去10年
                $top_v = empty($model['elite']) ? time()-315360000 : 0;
                $model->elite = $top_v;
            }
            $model->_update_time = date('Y-m-d H:i:s');
            $model->save();
        }
    }
 
    
    //查询列表
    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='starttime'){
                    $criteria->addBetweenCondition('_create_time', $con['starttime'], $con['endtime'].' 23:59:59');
                }elseif($key=='title'){
                    $criteria->compare('title', $con['title'], true);
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
        $typeArr = [1=>'吐槽' ,2=>'案例' ,3=>'课程' ,4=>'业务'];
        foreach($ret as $k => $v){
            $v = $v->attributes;
            $v['type_txt'] = $typeArr[$v['type']];
			$data[] = $v;
        }
       // print_r($data);
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