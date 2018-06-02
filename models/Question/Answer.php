<?php
/**
 * Created by PhpStorm.
 * User: xinggx
 * Date: 2016/6/23
 * Time: 9:17
 */
namespace application\models\Question;
use application\models\User\UserBrief;
use application\models\Question\Adopt;
use application\models\Question\GoodAnswer;
class Answer extends \CActiveRecord
{
    public $answer_count;
    public $adopt_count;
    public $new_answer_count;
    public $new_adopt_count;
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{answer}}';
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
        return array();
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
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
    public function getAnswerList($con, $orderBy, $order, $limit, $offset){
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

        $userIds = columnToArr($ret, 'user_id');
        $userArr = UserBrief::model()->getQuestionUserArr($userIds);

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['con'] =trim(htmlspecialchars_decode($data[$k]['content']));
            $data[$k]['UserName'] = isset($userArr[$data[$k]['user_id']])? (!empty($userArr[$data[$k]['user_id']]['UserName'])?$userArr[$data[$k]['user_id']]['UserName']:$userArr[$data[$k]['user_id']]['Nick']) : $data[$k]['user_id'];
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

    public function delAnswer($id){
        //补充删除答案扣减积分
        if(empty($id)){
            return array();
        }
        $answerObj = self::model()->findByPK($id);
        $answerObj->_deleted = 1;
        $answerObj->_update_time = date('Y-m-d H:i:s');
        $questionId = $answerObj->question_id;
        $answerUserId = $answerObj->user_id;
        $status = $answerObj->status;
        $transaction = $answerObj->dbConnection->beginTransaction();
        if($answerObj->save()){
            $questionObj = Question::model()->getQuestion($answerObj->question_id);
            $questionObj->answer_count --;
            if($status == 1){
                $questionObj->status =1;
            }
            if($questionObj->answer_count == 0){
                $questionObj->last_answer_time = null;
            }
            $questionUserId = $questionObj->user_id;
            if($questionObj->save()){
                $userQuestionObj = UserQuestion::model()->getUserQuestion(array('user_id'=>$answerUserId));
                if($userQuestionObj){
                    $userQuestionObj->answer_count --;
                    if($userQuestionObj->answer_count <0){
                        $userQuestionObj->answer_count = 0;
                    }
                    if($status ==1){
                        $userQuestionObj->adopt_count --;
                    }
                    $GoodAnswerCount = GoodAnswer::model()->getGoodAnswerRecord($id);
                    if($GoodAnswerCount>0){
                        $userQuestionObj->answer_good_count --;
                    }
                    $userQuestionObj->_update_time = date('Y-m-d H:i:s');
                    if($userQuestionObj->save()){
                        if($status == 1){
                            $updateAdoptStatus = Adopt::model()->updateStatus(array('answer_id'=>$id,'user_id'=>$answerUserId,'adopt_user_id'=>$questionUserId));
                            if($updateAdoptStatus){
                                $changeGoodAnswer = GoodAnswer::model()->changeGoodAnswerStatus($id);
                                if($changeGoodAnswer['is_update']){
                                    $isGoodAnswer = $changeGoodAnswer['is_good_answer'];
                                    //惩罚积分操作
                                    if(!empty($answerObj)){
                                        foreach ($answerObj as $key=>$item){
                                            if($item->status == 1){ //被采纳扣除
                                                $flag = \FwUtility::informCredit('question','question_accept_delete',$answerObj->question_id,'subtract','采纳被删除:'.$questionObj->title,$item->user_id);
                                            }else{
                                                $flag = \FwUtility::informCredit('question','question_noaccept_delete',$answerObj->question_id,'subtract','回答被删除:'.$questionObj->title,$item->user_id);
                                            }
                                        }
                                    }                                  
                                    \OperationLog::addLog(\OperationLog::$operationAnswer, 'del', '删除答案', $id,'','');
                                    $transaction->commit();
                                    $msgNo = 'true';
                                }else{
                                    $isGoodAnswer = false;
                                    $transaction->rollBack();
                                    $msgNo = 'false';
                                }
                            }else{
                                $transaction->rollBack();
                                $msgNo = 'false';
                            }
                        }else{
                            $changeGoodAnswer = GoodAnswer::model()->changeGoodAnswerStatus($id);
                            if($changeGoodAnswer['is_update']){
                                $isGoodAnswer = $changeGoodAnswer['is_good_answer'];
                                \OperationLog::addLog(\OperationLog::$operationAnswer, 'del', '删除答案', $id,'','');
                                $transaction->commit();
                                $msgNo = 'true';
                            }else{
                                $isGoodAnswer = false;
                                $transaction->rollBack();
                                $msgNo = 'false';
                            }
                        }
                    }else{
                        $transaction->rollBack();
                        $msgNo = 'false';
                    }
                }else{
                    $transaction->commit();
                    $msgNo = 'true';
                }
            }else{
                $transaction->rollBack();
                $msgNo = 'false';
            }
        } else {
            $msgNo = 'false';
        }
        $data = array('data'=>$msgNo,'answerUserId'=>$answerUserId,'answerStatus'=>$status,'question_id'=>$questionId,'isGoodAnswer'=>$isGoodAnswer);
        return $data;
    }
    /*
     *
     * 删除问题，同样删除问题的答案
     * */
    public function delQuestionAnswer($question_id,$user_id){
        if(empty($question_id)){
            return array();
        }
        $criteria = new \CDbCriteria;
        $criteria->compare('question_id',$question_id );
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        if($ret){
            $answerIds = $this->_getAllAnswerIds($ret);
            $changeGoodAnswer = GoodAnswer::model()->changeGoodAnswerStatus($answerIds);
            if($changeGoodAnswer['is_update']){
                $isUpdate= Answer::model()->updateAll(array('_deleted'=>1),'question_id=:qid',array(':qid'=>$question_id));
                if($isUpdate){
                    $userQuestionObj = UserQuestion::model()->getUserQuestion(array('user_id'=>$user_id));
                    if($userQuestionObj){
                        if($userQuestionObj->answer_count >= $count){
                            $userQuestionObj->answer_count -= $count;
                        }else{
                            $userQuestionObj->answer_count = 0;
                        }
                        $userQuestionObj->_update_time = date('Y-m-d H:i:s');
                        if($userQuestionObj->save()){
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        return true;
                    }
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return true;
        }
    }

    private function _getAllAnswerIds($answer){
        if(!empty($answer)){
            foreach($answer as $key=>$value){
                $data[] = $value->id;
            }
        }
        return !empty($data) ? $data:array();
    }
}