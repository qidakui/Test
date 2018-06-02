<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/13
 * Time: 14:32
 */
/**
 * This is the model class for table "{{activity_comment}}".
 *
 * The followings are the available columns in table '{{activity_comment}}':
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
namespace application\models\Activity;
use application\models\Common\CommonMember;
class ActivityComment extends \CActiveRecord
{
    public $column_type_Arr = array(
        'activity'=>['val'=>1,'name'=>'同城活动'],
        'training'=>['val'=>2,'name'=>'培训报名'], 
        'product'=>['val'=>3,'name'=>'广联达产品'], //产品
        'information'=>['val'=>4,'name'=>'资讯'], //资讯
        'document' => ['val'=>5,'name'=>'图文资料'], //图文资料
        'video' => ['val'=>6,'name'=>'视频资料'],  //视频资料
		'topic' => ['val'=>7,'name'=>'活动话题'] //活动话题
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{activity_comment}}';
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


    //查询列表
    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            if(isset($con['time'])){
                $criteria->addBetweenCondition('_create_time',$con['time'][0],$con['time'][1]);
                unset($con['time']);
            }
            foreach($con as $key => $val){
                if($key=='comment'){
                    $criteria->compare($key, $val, true);
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

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        $data = array();
        $member = array();
        foreach($ret as $k => $v){
            $v = $v->attributes;
            if( !isset($member[$v['user_id']]) ){
                $CommonMember = CommonMember::model()->findMemberUserId($v['user_id']);
                $member[$v['user_id']] = empty($CommonMember) ? array('global_id'=>0,'member_user_name'=>'','member_nick_name'=>'') : $CommonMember->attributes;
            }
            $v['global_id'] = $member[$v['user_id']]['global_id'];
            $v['member_user_name'] = $member[$v['user_id']]['member_user_name'];
            $v['member_nick_name'] = $member[$v['user_id']]['member_nick_name'];
            $v['comment_short'] = cutstr($v['comment'], 160);
            //计算回复数
            if(empty($v['pid'])){
                $v['hf_num'] = $this->getCount(array('ancestor_comment_id'=>$v['id'],'_delete'=>0));
            }else{
                $v['hf_num'] = $this->getCount(array('pid'=>$v['id'],'_delete'=>0));
            }
            $v['client'] = empty($v['client']) ? 'PC端' : 'H5端';
            $data[] = $v;
        }
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
  
    //获得一条
    public function getFind($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $criteria->order = '_create_time desc';
        $data = self::model()->find($criteria);
        return $data;
    }

    //统计评论数
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
    
    //获取打赏分数和
    public function getScore($training_id){
        
        $res = self::model()->findAll(
            array(
                'select'=>'sum(score)  as score',
                'condition'=>'activity_id=:activity_id and status=2',
                'params' => array('activity_id'=>$training_id)
                )
            );
        return $res[0]->score;
    }
}