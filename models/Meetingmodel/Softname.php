<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2017/8/31
 * Time: 10:13
 */
namespace application\models\Meetingmodel;
class Softname extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{soft_name}}';
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
     * @return ActivityParticipate the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //查询列表
    public function getList($con, $orderBy='desc', $order='id', $limit=1, $offset=0){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            if(isset($con['word'])){
                $word = $con['word'];
                $criteria->compare('soft_name', $word, true);
                unset($con['word']);
            }
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
        $data = array();
        foreach($ret as $v){
            $v = $v->attributes;
            $v['_update_time'] = $v['_update_time']==0 ? '' : $v['_update_time'];
            $data[] = $v;
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        //return array('data' => $data, 'count' => $count);
    }
    
    /*
     * 获取所有软件名称key=>val
     */
    public function getSoftKeyVal(){
        $data = [];
        $criteria = new \CDbCriteria;
        $ret = self::model()->findAll($criteria);
        foreach($ret as $v){
            $data[$v['id']] = $v['soft_name'];
        }
        return $data;
    }
    
    /*
     * 模糊查询软件名称获取id
     */
    public function nameToId($name){
        $data = [];
        if(!empty($name)){
            $criteria = new \CDbCriteria;
            $criteria->compare('source', 1);
            $criteria->compare('status', 1);
            $criteria->compare('soft_name', $name, true);
            $ret = self::model()->findAll($criteria);
            foreach($ret as $v){
                $data[] = $v->id;
            }
        }
        return $data;
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
            $model->$k = \CHtml::encode($v);
        }
		$model->save();
        $id  = intval($model->primaryKey);
        return $id;
    }

    
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return $count;
    }
    
   

}