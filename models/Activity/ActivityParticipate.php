<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/20
 * Time: 10:13
 */
namespace application\models\Activity;
use application\models\Member\CommonMember;
class ActivityParticipate extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{activity_participate}}';
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
            'id' => 'ID',
            'activity_id' => '活动id',
            'user_id' => '报名人uid',
            'user_name' => '用户名',
            'prize_winning_time' => '中奖时间',
            'is_prize_winning' => '是否中奖1中奖0未中奖',
            'type' => '1现场报名 2签到',
            'signin_time' => '签到时间',
            'controller_user_id' => '审核人uid',
            'realname' => '真实姓名',
            'mobile' => '手机',
            'company' => '单位名称',
            'dai' => '1代报名',
            'down_yqh_time' => '下载邀请函时间，0未下载',
            'yqh_path' => '邀请函路径',
            'invite_code' => '邀请码',
            'extend' => '扩展信息',
            'status' => '1通过 0未通过 2取消',
            '_create_time' => '申请时间',
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
     * @return ActivityParticipate the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //查询列表
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
            if( $v['type']!=1){
                $criteriaCommonMember = new \CDbCriteria;
                $criteriaCommonMember->select = 'member_user_name';
                $criteriaCommonMember->compare('member_user_id', $v['user_id']);
                $CommonMember = CommonMember::model()->find($criteriaCommonMember);
                $member_user_name = $CommonMember['member_user_name'];
            }else{
                $member_user_name = '';
            }
            $data[$k]['member_user_name'] = $member_user_name;
            if(strstr($data[$k]['extend'], '&quot;')){
				$data[$k]['extend'] = \CHtml::decode($data[$k]['extend']);
			}
            $data[$k]['extends'] = @unserialize($data[$k]['extend']);
            if($v['company']){
                $data[$k]['extends']['company'] = $v['company'];
            }else{
				if(!isset($data[$k]['extends']['company'])){
					$data[$k]['extends']['company'] = '';
				}
                $data[$k]['company'] = $data[$k]['extends']['company'];
            }
        }
       
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        //return array('data' => $data, 'count' => $count);
    }

    //保存活动到数据库
    public function ActivityRequirementSave($data){
        if(isset($data['id']) && !empty($data['id'])){
            $model = self::model()->findbypk($data['id']);
            $model->_update_time  = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
        }
        
        $model->status = 1;
        foreach($data as $name=>$value){
            $model->$name  = $value;
        }
        $model->save();
        return intval($model->primaryKey);
    }

    //统计报名表
    public function getRequirementCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }

        $count = self::model()->count($criteria);
        return intval($count);
    }
    
    /*
     * 统计活动分享签到人数
     */
    public function getShareSignCount($column_id, $share_code){
        $criteria = new \CDbCriteria;
        $criteria->compare('activity_id', $column_id);
        $criteria->compare('status', 1);
        $criteria->compare('type', 2);
        $criteria->compare('extend', $share_code, true);
        $count = self::model()->count($criteria);
        return $count;
    }

    /**
     * 初始化中奖信息
     * @param $data
     * @return bool
     */
    public function initializeDrawLots($data){
        $flag = true;
        $id = $data['activity_id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findAll('activity_id=:activity_id', array('activity_id' => $id));
        if(!empty($model)){
            foreach($model as $val){
                $val->prize_winning_time    = '0000:00:00 00:00:00';
                $val->is_prize_winning      = 0;
                $DrawLotsFlag = $val->save();
                if(!$DrawLotsFlag){
                    $flag = false;
                }
            }
        }

        return $flag;
    } 

}