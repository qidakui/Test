<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/16
 *
 */
namespace application\models\Activity;
use application\models\ServiceRegion;
class ActivityLunbotu extends \CActiveRecord
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
        return '{{activity_lunbotu}}';
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
            'id' => 'ID',
            'filiale_id' => '分支id',
            'province_code' => '省份',
            'city_code' => '城市',
            'county_code' => '区县',
            'image_title' => '图片标题',
            'image_link' => '图片链接',
            'image_path' => '图片路径',
            'user_id' => '用户id',
            'user_name' => '用户名',
            'sort' => '排序',
            'status' => '状态',
            '_create_time' => '创建时间',
            '_update_time' => '更改时间',
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



    //保存轮播图到数据库
    public function lunbotuSave($data){
        $model = new self();
        if( isset($data['id']) && !empty($data['id']) ){ //是否为修改
            $model = self::model()->findbypk($data['id']);
            $model->_update_time  = date('Y-m-d H:i:s');
        }else{
            $model->_create_time  = date('Y-m-d H:i:s');
        }
        $model->filiale_id = $data['filiale_id'];
        $model->image_title = $data['image_title'];
        $model->image_link = $data['image_link'];
        $model->user_id = \Yii::app()->user->user_id;
        $model->user_name = \Yii::app()->user->user_name;

        //修改时是否从新上传图片
        if(isset($data['image_path'])){ 
            $model->image_path = $data['image_path'];
        }
        $model->sort  = $data['sort'];
        $model->status = 1;
        $save = $model->save();
        $id = $model->primaryKey;
        if($save){
            if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationActivity, 'edit', '修改轮播图', $data['id'], $model->attributes, $data);
            }else{
                \OperationLog::addLog(\OperationLog::$operationActivity, 'add', '上传轮播图', $id, array(), $data);
            }
        }
        return $id;     
    }
    
    //保存轮播图到数据库
    public function lunbotu_Save($data){
        $model = new self();
        if( isset($data['id']) && !empty($data['id']) ){ //是否为修改
            $model = self::model()->findbypk($data['id']);
            $model->_update_time  = date('Y-m-d H:i:s');
        }else{
            $model->_create_time  = date('Y-m-d H:i:s');
        }
        if(isset($data['filiale_id'])){
            $model->filiale_id = $data['filiale_id'];
            $model->user_id = \Yii::app()->user->user_id;
            $model->user_name = \Yii::app()->user->user_name;
        }
        $model->image_title = $data['image_title'];
        $model->type = $data['type'];
        $model->image_link = $data['image_link'];
        //修改时是否从新上传图片
        if(isset($data['image_path'])){ 
            $model->image_path = $data['image_path'];
        }
        $model->sort  = $data['sort'];
        $model->status = 1;
        $save = $model->save();
        $id = $model->primaryKey;
        if($save){
            if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationActivity, 'edit', '修改轮播图', $data['id'], $model->attributes, $data);
            }else{
                \OperationLog::addLog(\OperationLog::$operationActivity, 'add', '上传轮播图', $id, array(), $data);
            }
        }
        return $id;     
    }


    //查询轮播图列表
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
             //获取分支名称 
            if($v['filiale_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
            }
            $data[$k]['city_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
       // return array('data' => $data, 'count' => $count);
    }
}