<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/13
 * Time: 17:24
 */

/**
 * This is the model class for table "{{expert}}".
 *
 * The followings are the available columns in table '{{expert}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $expert_level
 * @property string $expert_field
 * @property integer $expert_type
 * @property integer $expert_point
 * @property integer $expert_state
 * @property string $apply_date
 * @property string $job
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Question;
use application\models\Question\UserQuestion;
use application\models\User\UserBrief;
use application\models\ServiceRegion;
use application\models\Member\CommonMember;
use application\models\Question\ExpertOperateLog;
use application\models\Common\Message;
class Expert extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{expert}}';
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
            'id' => '专家主键',
            'user_id' => '用户标识id',
            'expert_level' => '专家等级',
            'expert_field' => '专家擅长领域',
            'expert_type' => '专家类型，0-地方专家；1-特邀专家',
            'expert_point' => '专家分',
            'expert_state' => '专家状态，0-申请中；1-在职；2-卸任；3-删除；4-不批准',
            'apply_date' => '申请时间',
            'job' => '专家职称',
            '_create_time' => '创建时间',
            '_update_time' => '更新时间',
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
     * @return Expert the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    public function getExpertList($con, $orderBy, $order, $limit, $offset){
        $cond = array();
        $criteria = new \CDbCriteria;
        $user_array = array();
        if(isset($con['Nick']) || isset($con['UserName']) || isset($con['province_code'])){
            foreach ($con as $key => $val) {
                if($key == 'Nick')
                {
                    $userCon['Nick'] = $con['Nick'];
                    unset($con['Nick']);
                } else if($key == 'UserName'){
                    $userCon['UserName'] = $con['UserName'];
                    unset($con['UserName']);
                } else if($key == 'province_code') {
                    $userCon['left(regionID,2)'] = $con['province_code'];
                    unset($con['province_code']);
                }
            }
            $expertUserIds = $this->_getExpertUserId();
            $userCon['UIN'] = $expertUserIds;
            $userObj = UserBrief::model()->getUserInfo($userCon);

            if(!empty($userObj)){
                foreach($userObj as $key=>$val){
                    $user_array[] += $val->UIN;
                }
                $con['user_id'] = $user_array;
            }else{
                return array('data' => array(), 'iTotalRecords' => 0, 'iTotalDisplayRecords' => 0);
            }
        }

        if (!empty($con)) {
            foreach ($con as $key => $val) {
                if($key == 'time'){
                    $criteria->addBetweenCondition('_create_time', $val['start_time'], $val['end_time']);
                } else {
                    $criteria->compare($key, $val);
                }
            }
        }
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
        $criteria->limit = $limit;
        $criteria->offset = $offset;

        $expertObj = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        $userIds = columnToArr($expertObj, 'user_id');


        $userBriefObj = UserBrief::model()->getUserBriefObjByUIds($userIds);
        $userBriefArr = objectKeyToArr($userBriefObj, 'UIN');

        $userQuestionObj = UserQuestion::model()->getUserQuestionObjByUIds($userIds);
        $userQuestionArr = objectKeyToArr($userQuestionObj, 'user_id');
        if(isset($con['time'])){
            $cond = $con['time'];
        }
        foreach($expertObj as $k => $v){
            $data[$k] = $v->attributes;
            $user_id = !empty($v['user_id']) ? $v['user_id'] : '';
            $data[$k]['Nick']   = !empty($userBriefArr[$user_id]['Nick']) ? $userBriefArr[$user_id]['Nick'] : '';
            $data[$k]['UserName']   = !empty($userBriefArr[$user_id]['UserName']) ? $userBriefArr[$user_id]['UserName'] : '';
            $data[$k]['RegionName']   = !empty($userBriefArr[$user_id]['regionID']) ? ServiceRegion::model()->getRegionName(substr($userBriefArr[$user_id]['regionID'], 0, 2))  : '';
            $data[$k]['RealityName']   = !empty($userBriefArr[$user_id]['RealityName']) ? $userBriefArr[$user_id]['RealityName'] : '';
            $data[$k]['sMobile']   = !empty($userBriefArr[$user_id]['sMobile']) ? $userBriefArr[$user_id]['sMobile'] : '';
            $data[$k]['mobile'] = !empty($v->mobile) ? $v->mobile : (!empty($userBriefArr[$user_id]['sMobile']) ? $userBriefArr[$user_id]['sMobile'] : '');
            $data[$k]['email']   = !empty($userBriefArr[$user_id]['email']) ? $userBriefArr[$user_id]['email'] : '';
            $data[$k]['resume_full_src']    = UPLOADURL . $v['resume_src'];
            $data[$k]['resume_name']    = empty($v['resume_name']) ? '' : $v['resume_name'];
            if(empty($data[$k]['expert_level'])){
                $data[$k]['expert_level'] = '1';
            }
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);
    }

    public function UpdateExpertLevel($data, $type='change_level'){
        $member_user_id = !empty($data['user_id']) ? $data['user_id'] : 0;
        $level = !empty($data['expert_level']) ? $data['expert_level'] : 1;
        $model = self::model()->find('user_id=:user_id', array('user_id' => $member_user_id));
        if(empty($model)){
            $model = new self();
            $model->expert_level = $level;
            $model->user_id           = $member_user_id;
            $model->expert_state      = 1;
            $model->apply_date        = date('Y-m-d H:i:s');
            $model->_create_time      = date('Y-m-d H:i:s');
            $model->_update_time      = date('Y-m-d H:i:s');
        }else{
            if($type == 'apply_pass'){
                //卸任专家恢复
                $model->expert_state      = 1;
                $model->apply_date        = date('Y-m-d H:i:s');
                $model->_update_time      = date('Y-m-d H:i:s');
            }else{
                $model->expert_level  = $level;
                $model->_update_time      = date('Y-m-d H:i:s');
            }
        }

        $model->save();
        $id  = $model->primaryKey;
        if($type == 'change_level'){
            ExpertOperateLog::model()->changeLevel($id, $member_user_id);
            $message_data = array(
                'user_id' => $member_user_id,
                'subject' => '系统消息',
                'message' => "尊敬的答疑专家：恭喜您的专家等级升为" . $level . "级，感谢您一直以来的辛苦付出！",
                'config_id' =>1,
            );
            Message::model()->messageSave($message_data);
        }else{
            ExpertOperateLog::model()->applyPass($id, $member_user_id);
        }

        $memberFlag = CommonMember::model()->setIsExpert($member_user_id);
        $userQuestionFlag = UserQuestion::model()->setIsExpert($member_user_id);
        return !empty($id) && !empty($memberFlag) && !empty($userQuestionFlag);
    }

    public function addExpert($user_id,$expert_level=1){
        if(empty($user_id) || empty($expert_level))
            return false;
        $questionData = array(
            'expert_level'  => $expert_level,
            'user_id'       => $user_id,
        );
        return Expert::model()->UpdateExpertLevel($questionData, 'apply_pass');
    }

    public function delExpert($id){
        if(empty($id))
            return false;
        $model = self::model()->findByPk($id);
        $model->expert_state = 3;
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $memberFlag = CommonMember::model()->cancleIsExpert($model->user_id);
        $userQuestionFlag = UserQuestion::model()->cancleIsExpert($model->user_id);
        $id  = $model->primaryKey;
        return !empty($userQuestionFlag) && !empty($memberFlag) && !empty($id);
    }

    public function leaveExpert($id){
        if(empty($id))
            return false;
        $model = self::model()->findByPk($id);
        $model->expert_state = 2;
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $memberFlag = CommonMember::model()->cancleIsExpert($model->user_id);
        $userQuestionFlag = UserQuestion::model()->cancleIsExpert($model->user_id);
        $id  = $model->primaryKey;
        ExpertOperateLog::model()->leave($id, $model->user_id);
        return !empty($userQuestionFlag) && !empty($memberFlag) && !empty($id);
    }

    private function _getExpertUserId(){
        $expertObj = self::model()->findAll();
        $userIds = columnToArr($expertObj, 'user_id');
        return $userIds;
    }
    /**
     * 修改专家推荐
     */
    public function edit_expert_mes($data = null,$user_id){
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('user_id',explode(",",$user_id));
        return $this->updateAll($data,$criteria);
    }
    /**
     * 取消推荐
     */
    public function edit_recomm($id){
        return $this->updateByPk($id, array('filiale_id'=>0,'is_recommend'=>0,'is_all'=>0,'start_time'=>null,'end_time'=>null));
    }

    /**
     * 统计数量
     * @param $con
     */
    private function CountNums($con, $type = 'answer'){
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                if(!empty($val)){
                    if($key == 'time'){
                        $criteria->addBetweenCondition('_create_time', $val['start_time'], $val['end_time']);
                    } else {
                        $criteria->compare($key, $val);
                    }
                }
            }
        }
        if($type == 'answer'){
            $count = Answer::model()->count($criteria);
        } elseif($type == 'good'){
            $count = GoodAnswer::model()->count($criteria);
        }

        return $count;
    }
}