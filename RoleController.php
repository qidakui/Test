<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/12
 * Time: 18:11
 */

use application\models\Privilege\Role;
use application\models\Privilege\Privilege;
class RoleController extends Controller
{
    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        1001 => '角色名称不能为空',
        1002 => '角色不能为空',
        1005 => '参数错误',
    );

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionRole_list()
    {
        $page   	= trim(Yii::app()->request->getParam( 'page' ));
        $page		= !empty($page) ? $page : 0;
        $limit		= 20;

        $con['_delete']   = 0;
        $list = Role::model()->getlist($con, 'desc', 'id', $limit, $page);

        $this->render('role_list', array('list' => $list));
    }

    public function actionRole_add()
    {
        $con['parent_id'] = 0;
        $con['_delete']   = 0;
        $list = Privilege::model()->getlist($con, 'Asc', 'id', '', '');
        $this->render('role_add', array('list' => $list));
    }

    public function actionRole_add_op()
    {
        try{
            $role_name 	= trim(Yii::app()->request->getParam( 'role_name' ));
            $role_text 	= Yii::app()->request->getParam( 'role_text' );
            $describe 	= trim(Yii::app()->request->getParam( 'describe' ));

            if(empty($role_name)){
                throw new Exception('1001');
            }
            if(empty($role_text)){
                throw new Exception('1002');
            }

            $info = Role::model()->find('role_name=:role_name', array('role_name' => $role_name));
            if(!empty($info)){
                throw new Exception('2');
            }
            $data = array(
                'role_name'	=> $role_name,
                'role_text'	=> serialize($role_text),
                'describe'	=> $describe,
            );

            $roleId = Role::model()->roleSave($data);
            if($roleId){
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }

        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionRole_edit()
    {

        $id 			= trim(Yii::app()->request->getParam( 'id' ));
        $roleinfo 	    = Role::model()->findbypk($id);
        $roleinfo->role_text = unserialize($roleinfo->role_text);

        $con['parent_id'] = 0;
        $con['_delete']   = 0;
        $list = Privilege::model()->getlist($con, 'Asc', 'id', '', '');
        $this->render('role_edit', array('roleinfo' => $roleinfo, 'privilege' => $list));
    }

    public function actionRole_edit_op()
    {
        try{
            $id 	    = trim(Yii::app()->request->getParam( 'id' ));
            $role_name 	= trim(Yii::app()->request->getParam( 'role_name' ));
            $role_text 	= Yii::app()->request->getParam( 'role_text' );
            $describe 	= trim(Yii::app()->request->getParam( 'describe' ));

            if(empty($id)){
                throw new Exception('1005');
            }
            if(empty($role_name)){
                throw new Exception('1001');
            }
            if(empty($role_text)){
                throw new Exception('1002');
            }

            $data = array(
                'id'        => $id,
                'role_name'	=> $role_name,
                'role_text'	=> serialize($role_text),
                'describe'	=> $describe,
            );
            $roleId = Role::model()->roleUpdate($data);
            if($roleId){
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }

        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }


    public function actionRole_del(){
        $id = Yii::app()->request->getParam( 'id' );
        if(empty($id)){
            throw new Exception('1005');
        }
        $model = Role::model()->findbypk($id);
        $model->_delete = 1;
        $flag = $model->save();
        if($flag){
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