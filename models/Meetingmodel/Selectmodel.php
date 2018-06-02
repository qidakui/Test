<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2017/8/31
 * Time: 10:13
 */
namespace application\models\Meetingmodel;
class Selectmodel extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{select_model}}';
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
        if($limit==500){ 
            //培训编辑页软件名称下拉框使用
            $criteria->select = 'id,category_id,soft_name_id,image,psd,logo,status';
        }
        if(!empty($con)){
            $word = isset($con['word']) ? $con['word'] : '';
            unset($con['word']);
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
            if($word){
                $softIdArr = Softname::model()->nameToId($word);
                $criteria->addInCondition('soft_name_id', $softIdArr);
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
            $v['category'] = \Yii::app()->params['training_category'][$v['category_id']];
            $Softname = Softname::model()->findByPk($v['soft_name_id']);
            $v['soft_name'] = empty($Softname) ? '' : $Softname->soft_name;
            $v['content'] = \CHtml::decode($v['content']);
            $data[] = $v;
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        //return array('data' => $data, 'count' => $count);
    }
   
    
    /*
     * 根据id获取一条
     */
    public function getData($id=0){
        $criteria = new \CDbCriteria;
        $criteria->compare('status', 1);
        $criteria->compare('source', 1);
        $softlist = Softname::model()->findAll($criteria);
        $soft_list = [];
        foreach ($softlist as $v){
            $soft_list[$v['id']] = $v['soft_name'];
        }
        $_data = array(
            'id' => 0,
            'soft_name_id' => 0,
            'category_id' => 0,
            'image' => '',
            'psd' => '',
            'logo' => '',
            'content' => '',
            'soft_list' => $soft_list,
            'category' => \Yii::app()->params['training_category']
        );
        $data = [];
        if($id){
            $data = self::model()->findByPk($id);
            $data = empty($data) ? $_data : $data->attributes;
            $data = array_merge($_data, $data );
        }else{
            $data = $_data;
        }
        return $data;
    }

	//根据软件名称及培训类别获取一条
	public function getSoftIdToModel($category_id, $soft_name_id){
		$criteria = new \CDbCriteria;
        $criteria->compare('status', 1);
        $criteria->compare('source', 1);
		$criteria->compare('category_id', $category_id);
		$criteria->compare('soft_name_id', $soft_name_id);
        $model = self::model()->find($criteria);
		return empty($model) ? array() : $model->attributes;
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