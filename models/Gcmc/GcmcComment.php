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
use application\models\User\UserBrief;
class GcmcComment extends \CActiveRecord
{
 
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_comment}}';
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
                    $criteria->addBetweenCondition('_create_time', $con['starttime'], $con['endtime'].' 23:59:59');
                }elseif($key=='title'){
                    //$criteria->addCondition('title like \'%'.$con['title'].'%\' OR first_experts_name like \'%'.$con['title'].'%\' OR second_experts_name like \'%'.$con['title'].'%\'');
                    $criteria->compare('member_user_name', $val, true);
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
        $column_type_Arr = [1=>'课程详情',2=>'新闻详情',3=>'案例详情', 4=>'话题详情'];
        foreach($ret as $k => $v){
            //用户信息
            if( $v['member_user_id'] && empty($v['mobile']) && empty($v['email']) ){
                $user = UserBrief::model()->findByPk($v['member_user_id'], array('select'=>['sMobile','email']));
                if( $user && ($user['sMobile'] || $v['email']) ){
                    $v->mobile = $user['sMobile'];
                    $v->email = $user['email'];
                    $v->save();
                }
            }
            $v = $v->attributes;
            if($v['filiale_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
            }
            $v['city_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
            $v['type'] = $v['pid']==0 ? '评论' : '回复';
            $v['column_type'] = $column_type_Arr[$v['column_type']];
            $v['comment_txt'] = cutstr($v['comment'],200);
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