<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/13
 * Time: 14:54
 */
class AuthController extends CController
{
    private $msg = array(
        0 => '成功',
        1001 => '用户名错误',
        1002 => '密码错误',
        1003 => '用户名不能为空',
        1004 => '密码不能为空',
        1005 => '验证码不能为空',
        1006 => '验证码错误',
        100  => '系统错误',
    );
    /**
     * Displays the login page
     */
    public function actionLogin()
    {

        try{
            $msg = '';
            $model=new LoginForm;
            if(empty($_POST['verifyCode'])){
                throw new Exception('1005');
            }

            if(!$this->createAction('captcha')->validate(CHtml::encode($_POST['verifyCode']), false)){
                throw new Exception('1006');
            }
            if(empty($_POST['user_name'])){
                throw new Exception('1003');
            }
            if(empty($_POST['password'])){
                throw new Exception('1004');
            }
            // collect user input data
            if(!empty($_POST))
            {
                $model->user_name = CHtml::encode($_POST['user_name']);
                $model->password  = CHtml::encode($_POST['password']);
                // validate user input and redirect to the previous page if valid
                $validate   = $model->validate();
                $flag       = $model->login();


                if(($validate === true) && ($flag === true)){
                    $this->redirect('index.php');
                } else {
                    throw new Exception($flag);
                }
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }

        $msg = $this->msg[$msgNo];
        // display the login form
        $this->render('login',array('model'=>$msg));
    }

    public function actions()
    {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha'=>array(
                'class'=>'CCaptchaAction',
                'backColor'=>0xFFFFFF,
                'maxLength'=>'4',       // 最多生成几个字符
                'minLength'=>'2',       // 最少生成几个字符
                'height'=>'40'
            ),
        );
    }

    public function actionLogout()
    {
        Yii::app()->user->logout();
        $this->redirect('index.php?r=auth/login');
    }
}