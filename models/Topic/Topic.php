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
namespace application\models\Topic;
use application\models\ServiceRegion;
use application\models\Activity\ActivityComment;
class Topic extends \CActiveRecord
{
    public $column_type_arr = array('activity'=>1, 'training'=>2);     
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{topic}}';
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
    public function saveData($data){
        if(isset($data['id'])){
            $model = self::model()->findbypk($data['id']);
            $oldData = isset($model->attributes) ? $model->attributes : array();
            $model->_update_time = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
        }
        foreach($data as $k=>$v){
            if($k=='column_type'){
                $model->$k = $this->column_type_arr[$v];
            }else{
                $model->$k = \CHtml::encode($v);
            }
        }
        $model->save(); 
        $id  = intval($model->primaryKey);
        if($id){
            if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationTopic, 'edit', '修改话题', $data['id'], $oldData, $data);
            }else{
                \OperationLog::addLog(\OperationLog::$operationTopic, 'add', '新建话题', $id, array(), $data);
            }
        }
        return $id;
    }
            
            
    //查询列表
    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='title'){
					$criteria->addSearchCondition('title', $val);
                }else{
					$criteria->compare($key, $val);
				}
            }
        }
       
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
       
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        //print_r($criteria);
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        $data = [];
        foreach($ret as $k => $v){
            $v = $v->attributes;
            //获取分支名称 （待改）
            /*if($v['filiale_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
            }
            $v['filiale_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';  */
            $ccon = array('status'=>7,'activity_id'=>$v['id']);
            $v['comment_num'] = ActivityComment::model()->getCount($ccon);
            $c = ActivityComment::model()->getFind($ccon);
            $v['last_comment_time'] = empty($c) ? '' : $c['_create_time'];
            $v['filiale_name'] = $v['filiale_id']==BRANCH_ID ? '全国账户' : '分支账户';
            $data[] = $v;
        }
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
     
    /*
     * 批量修改状态及其下评论状态 
     * 仅支持活动
     */
    public function updateStatus($ids){
        $filiale_id = \Yii::app()->user->branch_id;
        if($filiale_id==BRANCH_ID){
           $data = self::model()->findAllByPk($ids); 
        }else{
            $data = self::model()->findAllByPk($ids, 'filiale_id=:filiale_id', array(':filiale_id'=>$filiale_id));
        }
        $up = array();
        if($data){
            foreach($data as $v){
                $v->status = 0;
                $v->_update_time = date('Y-m-d H:i:s');
                $up[] = $v->save();
            }
        }
        return $up;
    }
}