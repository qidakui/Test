<?php

use application\models\Question\Question;
use application\models\Question\Answer;
use application\models\Question\GoodAnswer;
use application\models\Question\UserQuestion;
use application\models\User\UserBrief;
use application\models\ServiceRegion;
use application\models\Question\Follow;
use application\models\Question\Adopt;
use application\models\Expert\Expert;
use application\models\Question\MemberReported;

class AnswerController extends Controller {

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        1001 => '答案对应的问题id不能为空',
        1002 => '答案内容不能为空',
        1005 => '您输入的内容中包含违法词汇，请重新输入！',
        1006 => '创建答案成功!',
        1007 => '请不要频繁提交！'
    );

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex() {
        $this->render('index');
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    public function actionSave_answer() {
        $user_id = $this->checkLogin();
        try {
            $question_id = intval(Yii::app()->request->getParam('question_id'));
            $answer_content = trim(Yii::app()->request->getParam('answer_content'));
            if (YII_ENV == 'dev') {
                $user_ip = '182.48.117.2';
            } else {
                $user_ip = getIpInfo();
            }
            $city_name = getIpLookup($user_ip)['city'];
            if ($city_name) {
                $area_code = ServiceRegion::model()->getRegionId($city_name);
            } else {
                $area_code = '';
            }
            $filter_content = filterDict($answer_content);
            if ($filter_content) {
                $is_valid = 0;
                throw new Exception('1005');
            } else {
                $is_valid = 1;
            }
            if (empty($question_id)) {
                throw new Exception('1001');
            }
            if (empty($answer_content)) {
                throw new Exception('1002');
            }
            $expert = Expert::model()->isExpert($user_id);
            $is_expert = $expert ? 1 : 0;
            $data = array(
                'is_expert' => $is_expert,
                'question_id' => $question_id,
                'content' => $answer_content,
                'is_valid' => $is_valid,
                'user_id' => $user_id,
                'user_ip' => $user_ip,
                'area_code' => $area_code,
            );
            $checkTime = CheckComment::checkMessageReply(CheckComment::$checkAnswer, 10);
            if (!$checkTime) {
                throw new Exception('1007');
            }
            $answer_add_token = trim(Yii::app()->request->getParam('answer_add_token'));
            if (Yii::app()->session['answerAddToken'] != $answer_add_token) {
                throw new Exception('1007');
            }
            Yii::app()->session['answerAddToken'] = '';
            $flag = Answer::model()->answerSave($data);
            if ($flag['answer_id'] && $is_valid == 1) {
                $prizeUrl = PrizeLog::addPrizeLog(PrizeLog::$prizeQuestion, PrizeLog::$typeKey['question_answer'], $question_id, BRANCH_ID);
                $messageFlag = MessageLog::addMessage(MessageLog::$creditQuestion, $flag['questionUserId'], '您的问题有人回答了', $flag['questionTitle'], $flag['question_id'], $flag['from_user_id'], $flag['from_user_nick']);
                if (!$messageFlag) {
                    writeIntoLog('answer_send_message', "----------\r\n" . date("H:i:s") . "\r\n" . '消息发送失败' . json_encode(array('错误信息' => $messageFlag, '答案id' => $flag['answer_id'], '问题id' => $flag['question_id'], '发送人' => $flag['from_user_id'], '接收人' => $flag['questionUserId'])) . "\r\n--------------\r\n");
                }
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo CJSON::encode(array('status' => $msgNo, 'msg' => $this->msg[$msgNo]));
    }

    public function actionReport() {
        $user_id = getUserId();
        $report_atr = Yii::app()->request->getParam('report_attr');
        $report_content = Yii::app()->request->getParam('report_content');
//        $report_data_hash = get_report_post_data($report_atr, $report_content, $user_id);
        $report_type = explode('|', $report_atr, 3)[0];
        $report_user_id = explode('|', $report_atr, 3)[1];
        $report_id = explode('|', $report_atr, 3)[2];
        $data['member_user_id'] = $user_id;
        $data['source'] = CreditLog::$creditQuestion;
        $data['content'] = $report_content;
        $data['reported_user_id'] = $report_user_id;
        if ($report_type == 'question') {
            $q_model = Question::model()->find('id=:id', array('id' => $report_id));
            $q_model->is_valid = 0;
            $data['type'] = 1;
            $data['reported_id'] = $report_id;
            if ($q_model->save()) {
                MemberReported::model()->reportedSave($data);
                $arrMag['is_reported'] = true;
            } else {
                $arrMag['is_reported'] = false;
            }
        } else {
            $a_model = Answer::model()->findByPk($report_id);
            $a_model->is_valid = 0;
            $data['type'] = 2;
            $data['reported_id'] = $report_id;
            if ($a_model->save()) {
                MemberReported::model()->reportedSave($data);
                $arrMag['is_reported'] = true;
            } else {
                $arrMag['is_reported'] = true;
            }
        }
        echo json_encode($arrMag);
    }

    /*
     * 问题详情页中添加关注
     * */

    public function actionFollow() {
        $user_id = $this->checkLogin();
        $answer_id = intval(Yii::app()->request->getParam('answer_id'));
        $expert_id = intval(Yii::app()->request->getParam('user_id'));
        if (!$answer_id && !$expert_id) {
            $msg['is_followed'] = false;
            $msg['msg'] = "关注失败";
        } elseif ($answer_id) {
            $msg = Follow::model()->AddFollow($answer_id, $user_id);
        } elseif ($expert_id) {
            $msg = Follow::model()->AddExpertFollow($expert_id, $user_id);
        }
        echo json_encode($msg);
    }

    public function actionGood_answer() {
        $current_user_id = $this->checkLogin();
        $answer_id = intval(Yii::app()->request->getParam('answer_id'));
        if (!$answer_id) {
            $msg['is_praised'] = false;
            $msg['msg'] = "点赞失败";
        } else {
            $msg = GoodAnswer::model()->setGoodAnswer($answer_id, $current_user_id);
        }
        echo json_encode($msg);
    }

    public function actionAdopt_answer() {
        $user_id = $this->checkLogin();
        $answer_id = intval(Yii::app()->request->getParam('answer_id'));
        if (!$answer_id) {
            $msg['is_adopt'] = false;
            $msg['msg'] = "采纳失败";
        } else {
            $answerInfo = Answer::model()->findByPk($answer_id);
            $question = Question::model()->find(array('condition' => 'id=:id', 'params' => array(':id' => $answerInfo->question_id)));
            //$question = Question::model()->findByPk($answerInfo->question_id);
            if (strtotime($question->_create_time) < strtotime('2016-11-09 00:00:00')) {
                $msg['is_adopt'] = false;
                $msg['msg'] = "采纳失败";
                echo json_encode($msg);
                exit;
            }
            $msg = Adopt::model()->adoptAnswer($answer_id, $user_id);
            if ($msg['is_adopt']) {
                //$answerInfo = Answer::model()->findByPk($answer_id);
                $prizeUrl = PrizeLog::addPrizeLog(PrizeLog::$prizeQuestion, PrizeLog::$typeKey['question_adopt'], $answer_id, BRANCH_ID);
                $disallowed = PrizeLog::addPrizeLog(PrizeLog::$prizeQuestion, PrizeLog::$typeKey['question_disallowed'], $answer_id, BRANCH_ID, $answerInfo->user_id);
                if (!empty($prizeUrl)) {
                    $msg['prize'] = 1;
                    $msg['prizeurl'] = $prizeUrl;
                    echo json_encode($msg);
                    exit;
                }
                $msg['prize'] = 0;
            }
        }
        echo json_encode($msg);
    }

    public function actionAnswer_edit() {
        $this->layout = false;
        $answer_id = intval(Yii::app()->request->getParam('answer_id'));
        if (empty($answer_id)) {
            return array();
        }
        $answerObj = Answer::model()->findByPK($answer_id);
        $this->render('/question/answer_edit', array("answer" => $answerObj,));
    }

    public function actionUpdate() {
        try {
            $answer_id = intval(Yii::app()->request->getParam('answer_id'));
            $answer_content = trim(Yii::app()->request->getParam('answer_content'));
            $data = array(
                'id' => $answer_id,
                'content' => $answer_content,
            );
            $updateFlag = Answer::model()->updateAnswer($data);
            if ($updateFlag) {
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo CJSON::encode(array('status' => $msgNo, 'msg' => $this->msg[$msgNo]));
    }

    //删除答案
    public function actionAnswer_del(){
        $id = Yii::app()->request->getParam( 'id' );
        $data = Answer::model()->delAnswer($id);
        if($data['data']){
            $msgNo = 'Y';
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        } else {
            $msgNo = 1;
            $msg = $this->msg[$msgNo];
            echo $this->encode($msgNo, $msg);
        }
    }

}
