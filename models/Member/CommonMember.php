<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/13
 * Time: 13:44
 */
/**
 * This is the model class for table "{{common_member}}".
 *
 * The followings are the available columns in table '{{common_member}}':
 * @property string $id
 * @property string $member_user_id
 * @property string $member_user_name
 * @property string $member_nick_name
 * @property string $global_id
 * @property integer $status
 * @property integer $experience
 * @property integer $credits
 * @property integer $money
 * @property integer $is_question_expert
 * @property integer $is_lecturer_expert
 * @property string $onlinetime
 * @property integer $grade
 * @property integer $_delete
 * @property integer $_login_time
 * @property string $_create_time
 * @property string $_update_time
 * @property integer $is_question_contribution_expert
 * @property integer $is_lecturer_contribution_expert
 */
namespace application\models\Member;
use application\models\ServiceRegion;
use application\models\User\UserBrief;
use application\models\Member\CommonLock;
use application\models\Admin\Admin;
class CommonMember extends \CActiveRecord
{
    private $msg = array(
            'Y' => '成功',
             1 => '操作数据库错误',
             2 => '数据已经存在',
             3 => '数据不存在',
             4 => '参数错误',
    );    
    private $statysKey = array(
        0 => '正常',
        1 => '暂停',
    );

    public $regionID;
    private $isQuestionContributionExpert = '特约专家';
    private $isLecturerContributionExpert = '特约讲师';
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{common_member}}';
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
            'id' => 'id',
            'member_user_id' => 'sso 用户id',
            'member_user_name' => '用户名',
            'member_nick_name' => '用户昵称',
            'global_id' => '关联云id',
            'status' => '用户状态 0=正常1=锁定',
            'experience' => '经验值',
            'credits' => '积分',
            'money' => '广币',
            'is_question_expert' => '是否答疑专家',
            'is_lecturer_expert' => '是否网络讲师',
            'onlinetime' => '在线时长',
            'grade' => '成长等级',
            '_delete' => '是否删除0正常1删除',
            '_login_time' => '登录时间',
            '_create_time' => '创建时间',
            '_update_time' => '修改时间',
            'is_question_contribution_expert' => '是否特约专家',
            'is_lecturer_contribution_expert' => '是否特约讲师',
            'content' => '冻结原因',
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
     * @return CommonMember the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 返回数据模型基于用户主键的变量.
     * If the data model is not found, an HTTP exception will be raised.
     */
    public function loadModel($member_user_id) {
        if (isset($member_user_id)) {
            $model = $this->find("member_user_id=:member_user_id and _delete=:_delete", array(':member_user_id' => $member_user_id,'_delete'=>0));
        }
        return $model;
    }

    public function MemberUpdateExpert($data){
        try {
            $ids    = !empty($data['ids']) ? $data['ids'] : 0;
            if(empty($ids))
                throw new \CException('4');
            $model = self::model()->loadModel($ids);
            $result = $this->setLock($data);//设置用户冻结，记录
            if(!is_array($result))
                throw new \CException('4');
            foreach($result as $key => $val){
                 $model->$key = $val;
            }
            $model->_update_time      = date('Y-m-d H:i:s');
            $model->save();
            $setRedis = $this->setRedisInfo('global_member_info_'.$model->member_user_id,$model->attributes);
            $id  = $model->primaryKey;
            if($id){
                $msgNo = 'Y';
            }else{
                throw new \CException('1');
            }
        } catch (\Exception $ex) {
            $msgNo = $ex->getMessage();
        }
         return $msgNo;
    }

    public function expertUpdateCommonMember($data){
        $member_user_id    = !empty($data['member_user_id']) ? $data['member_user_id'] : 0;
        $model = self::model()->find('member_user_id=:member_user_id', array('member_user_id' => $member_user_id));
        if(empty($model)){
           return false;
        }
        foreach($data as $key => $val){
            $model->$key = $val;
        }
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $setRedis = $this->setRedisInfo('global_member_info_'.$model->member_user_id,$model->attributes);
        $id  = $model->primaryKey;
        return $id;
    }

    public function setIsExpert($user_id){
        $member_data = array(
            'is_question_contribution_expert' => 1,
            'is_question_expert'              => 1,
            'member_user_id'                  => $user_id,
        );
        return CommonMember::model()->expertUpdateCommonMember($member_data);
    }

    public function cancleIsExpert($user_id){
        $member_data = array(
            'is_question_contribution_expert' => 0,
            'is_question_expert'              => 0,
            'member_user_id'                         => $user_id,
        );
        return CommonMember::model()->expertUpdateCommonMember($member_data);
    }

    /**
     * 增加消息数量
     * @param $data
     * @return int
     */
    public function updateMessageNumber($data){
        $id    = !empty($data['member_user_id']) ? $data['member_user_id'] : 0;
        $model = self::model()->find('member_user_id=:member_user_id', array('member_user_id' => $id));
        $model->message_number = $model->message_number + 1;
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function updateMessageNumberNew(){
        $connection = \Yii::app()->db; //连接
        $sql = "update e_common_member set message_number=message_number+1";
        $command=$connection->createCommand($sql);
        $command->execute();
    }

    public function getlist($con, $orderBy, $order, $limit = null, $offset = null){
        $regionIds = array();
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
        if($limit != null || $offset != null){
            $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
            $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        }

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);

        $userIds = columnToArr($ret, 'member_user_id');

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('UIN', $userIds);
        $userObj = UserBrief::model()->findAll($criteria);
        foreach($userObj as $user){
            $userArr[$user->UIN] = $user->attributes;
        }

        foreach($userObj as $user){
            $regionIds[] = !empty($user->regionID) ? substr($user->regionID, 0 ,2) : '';
        }

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('left(region_id,2)', $regionIds);
        $criteria->compare('is_parent', 0);
        $ServiceRegionObj = ServiceRegion::model()->findAll($criteria);
        foreach($ServiceRegionObj as $serviceRegion){
            if(!empty($serviceRegion)){
                $serviceRegionArr[$serviceRegion->region_id] = $serviceRegion->region_name;
            }
        }
        //账号冻结
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('member_user_id', $userIds);
        $criteria->compare('_delete', 0);
        $memberLockObj = CommonLock::model()->findAll($criteria);
        if(!empty($memberLockObj)){
            foreach ($memberLockObj as $row){
                $managerName = Admin::model()->findByPk($row->manager_id);
                $memberLockArr[$row->member_user_id] = $managerName->user_name;
            }
        }
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $region_id = !empty($userArr[$data[$k]['member_user_id']]['regionID']) ? substr($userArr[$data[$k]['member_user_id']]['regionID'],0 , 2) : '';
            $data[$k]['city_name']       = !empty($serviceRegionArr[$region_id]) ? $serviceRegionArr[$region_id] : '';
            $data[$k]['user_name']       = !empty($userArr[$data[$k]['member_user_id']]['UserName']) ? $userArr[$data[$k]['member_user_id']]['UserName'] : '';
            $data[$k]['nike_name']       = !empty($userArr[$data[$k]['member_user_id']]['Nick']) ? $userArr[$data[$k]['member_user_id']]['Nick'] : '';
            $data[$k]['mail']            = !empty($userArr[$data[$k]['member_user_id']]['email']) ? $userArr[$data[$k]['member_user_id']]['email'] : '';
            $data[$k]['mobile']          = !empty($userArr[$data[$k]['member_user_id']]['sMobile']) ? $userArr[$data[$k]['member_user_id']]['sMobile'] : '';
            $data[$k]['Address']         = !empty($userArr[$data[$k]['member_user_id']]['Address']) ? $userArr[$data[$k]['member_user_id']]['Address'] : '';
            $data[$k]['manager_name']    = !empty($memberLockArr[$data[$k]['member_user_id']]) ? $memberLockArr[$data[$k]['member_user_id']] : ''; //冻结人
            $data[$k]['multiple']        = !empty($userArr[$data[$k]['member_user_id']]['multiple']) ? $this->flipindustry($userArr[$data[$k]['member_user_id']]['multiple']) : '';
            $data[$k]['status_name'] = !empty($this->statysKey[$data[$k]['status']]) ? $this->statysKey[$data[$k]['status']] : '';
            $data[$k]['is_question_contribution_expert_name']      = $data[$k]['is_question_contribution_expert'] == 1 ? $this->isQuestionContributionExpert : '';
            $data[$k]['is_lecturer_contribution_expert_name']      = $data[$k]['is_lecturer_contribution_expert'] == 1 ? $this->isLecturerContributionExpert : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

    public function getSmsMessagelist($con, $orderBy, $order, $limit = null, $offset = null){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key == 'not_user'){
                    $criteria->addCondition("member_user_id not in (select msg_to_id from e_message where config_id=$val)");
                } else {
                    $criteria->compare($key, $val);
                }
            }
        }

        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
        if($limit != null || $offset != null){
            $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
            $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        }

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);


        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
        }
        $data = !empty($data) ? $data : array();
        return $data;
    }
    /**
     * 设置用户redis
     * @param type $mark
     * @param type $result
     * @return type
     */
    public function setRedisInfo($mark,$result = null){
        $prefix='fwxgx_'.$mark;
        $key=$prefix.md5($mark);//令牌键值
        $RedisRes = \Yii::app()->redis->getClient()->get($key);
        if(!empty($RedisRes)){
            $delRedis = \Yii::app()->redis->getClient()->delete($key);
            if($delRedis){
               $setValue = \Yii::app()->redis->getClient()->set($key,serialize($result));
            }
        }else{
              $setValue = \Yii::app()->redis->getClient()->set($key,serialize($result));
        }
            return $setValue;
    }
    /**
     * 设置用户冻结信息
     */
    public function setLock($data){
        $newarray = array();
        try {
            if(empty($data))
               throw new \CException('4');
            $flag = CommonLock::model()->SaveLock($data);
            if($flag == 'Y'){
                 $msgNo = 0;
            }else{
                throw new \CException('1');
            }
        } catch (\Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        if($msgNo ==0){
            unset($data['content']);
            unset($data['ids']);
            $newarray = $data;
        }
        return $msgNo ==0 ? $newarray:$msgNo;
    }
    /**
     * 转换从事行业 $userArr[$data[$k]['member_user_id']]['multiple']
     */
    public function flipindustry($industry = null){
        $newarray = array();
        $specialty = \Yii::app()->params['majorCate'];
        if(empty($industry)){
            return '';
        }
        $array = unserialize($industry);
        if(!empty($array)){
            foreach ($specialty as $key=>$item){
                if(in_array($key, $array)){
                    $newarray[] = $item;
                }
            }            
        }
        return !empty($newarray)?implode(',', $newarray):'';
    }
}