<?php
/**
 * Created by PhpStorm.
 * User: wenlh
 * Date: 2016/8/15
 */

use application\models\CreditsTemplate\CreditsTemplate;
class CreditsTemplateController extends Controller
{

    private $msg = array(
        'Y' => '操作成功',
          1 => '模板ID不能为空',
          2 => '不存在',
          3 => '积分操作类型不能为空',
          4 => '经验操作类型不能为空',

    );

    public function actionCreate_template(){
        $infos = array(
            'share' => array( //分享网站
                'experience' => '5',
                'credits' => '1',
            ),
            'submit_issue' => array(//提交问题
                'experience' => '10',
                'credits' => '1',
            ),
            'submit_questions' => array(//提交回答
                'experience' => '10',
                'credits' => '1',
            ),
            'accept' => array(//被提问者采纳
                'experience' => '10',
                'credits' => '20',
            ),
            'accept_ta' => array(//采纳ta人答案
                'experience' => '10',
                'credits' => '20',
            ),
            'money' => array(//提问给赏金（广币）
                'experience' => '50',
                'credits' => '50',
            ),
            'remark' => array(//网友评论
                'experience' => '5',
                'credits' => '1',
            ),
            'sign' => array(//所有现场签到
                'experience' => '10',
                'credits' => '10',
            ),
            'collect' => array(//收藏
                'experience' => '5',
                'credits' => '1',
            ),
            'give' => array(//点赞
                'experience' => '5',
                'credits' => '1',
            ),
            'research' => array(//调研
                'experience' => '0',
                'credits' => '10',
            ),
            'firsttime_login' => array(//首次登陆
                'experience' => '20',
                'credits' => '5',
            ),
            'everyday_login' => array(//每日登陆
                'experience' => '5'
            ),
            'expert_apply' => array(//答疑专家申请
                'experience' => '200',
                'credits' => '200',
            ),
            'expert_upgrade' => array(//答疑专家升级
                'experience' => '100',
                'credits' => '100',
            ),
            'product_accept' => array(//产品建议采纳
                'experience' => '10',
                'credits' => '5',
            ),
            'inform' => array(//举报
                'experience' => '5',
                'credits' => '3',
            ),
            'inform_reported' => array(//被举报
                'experience' => '5',
                'credits' => '3',
            ),
            'ask_delete' => array(//提问删除
                'experience' => '5',
                'credits' => '1',
            ),
            'question_noaccept_delete' => array(//未采纳被删除
                'experience' => '5',
                'credits' => '1',
            ),
            'question_accept_delete' => array(//采纳被删除
                'experience' => '5',
                'credits' => '21',
            )
        );
        foreach($infos as $k => $v){
            $info['name'] = $k;
            $info['experience'] = isset($v['experience']) ? $v['experience'] : 0;
            $info['credits'] = isset($v['credits']) ? $v['credits'] : 0;
            $info['day_max_experience'] = isset($v['day_max_experience']) ? $v['day_max_experience'] : 0;
            $info['day_max_credits'] = isset($v['day_max_credits']) ? $v['day_max_credits'] : 0;
            CreditsTemplate::model()->createRecord($info);
        }
    }

    public function actionIndex(){
        $this->render('index');
    }

    public function actionTemplate_list(){
        $order_list = CreditsTemplate::model()->getList();
        echo CJSON::encode($order_list);exit;
    }

    public function actionTemplate_info(){
        $msgNo = 'Y';
        try{
            $template_id = Yii::app()->request->getParam('template_id');
            if(empty($template_id))
                throw new Exception('1');
            $template = CreditsTemplate::model()->findByPk($template_id);
            if(empty($template))
                throw new Exception('2');
            $this->render('template_info',array('template' => $template));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);

    }

    public function actionUpdate_template(){
        $msgNo = 'Y';
        $info = array();
        try{
            $template_id = Yii::app()->request->getParam('id');
            if(empty($template_id))
                throw new Exception('1');
            $template = CreditsTemplate::model()->findByPk($template_id);
            if(empty($template))
                throw new Exception('2');
            $credits_operate = Yii::app()->request->getParam('credits_operate');
            $experience_operate = Yii::app()->request->getParam('experience_operate');
            if(empty($credits_operate))
                throw new Exception('3');
            if(empty($experience_operate))
                throw new Exception('4');
            $info['credits'] = intval(Yii::app()->request->getParam('credits'));
            $info['experience'] = intval(Yii::app()->request->getParam('experience'));
            if($credits_operate != 1)
                $info['credits'] = 0 - $info['credits'];
            if($experience_operate != 1)
                $info['experience'] = 0 - $info['experience'];
            $info['status'] = intval(Yii::app()->request->getParam('status'));
            $info['day_max_experience'] = intval(Yii::app()->request->getParam('day_max_experience'));
            $info['day_max_credits'] = intval(Yii::app()->request->getParam('day_max_credits'));
            $template->updateRecord($info);
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionReload_template_redis(){
        $name = Yii::app()->request->getParam('name');
        if($name == 'wenlh'){
            CreditsTemplate::model()->reloadTemplateRedis();
            echo $this->encode('Y', 'reloadRedis success');
        }else{
            echo $this->encode('N', 'permission denied');
        }
    }

    public function actionGet_template(){
        $name = Yii::app()->request->getParam('name');
        print_r(unserialize(Yii::app()->redis->getClient()->get($name)));
    }
}