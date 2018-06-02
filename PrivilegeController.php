<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/12
 * Time: 8:50
 */
use application\models\Privilege\Privilege;
class PrivilegeController extends Controller
{
    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        1001 => '栏目名称不能为空',
        1002 => '模块名称不能为空',
        1003 => '链接地址不能为空',
        1004 => '上级栏目不能为空',
        1005 => '参数错误',
    );

    public function actionPrivilege()
    {
        $privilege_name = trim(Yii::app()->request->getParam( 'privilege_name' ));
        $page   	= trim(Yii::app()->request->getParam( 'page' ));
        $page		= !empty($page) ? $page : 0;
        $limit		= 20;
        if(!empty($privilege_name)){
            $con['privilege_name'] = $privilege_name;
        }
        $con['parent_id'] = 0;
        $con['_delete']   = 0;
        $con['status']    = 0;
        $list = Privilege::model()->getlist($con, 'Asc', 'id', $limit, $page);

        $this->render('privilege', array('list' => $list));
    }

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionPrivilege_list()
    {
        $privilege_name = trim(Yii::app()->request->getParam( 'privilege_name' ));
        $parent_id      = trim(Yii::app()->request->getParam( 'parent_id' ));
        $page   	= trim(Yii::app()->request->getParam( 'page' ));
        $page		= !empty($page) ? $page : 0;
        $parent_id  = !empty($parent_id) ? $parent_id : 0;
        $limit		= 20;
        if(!empty($privilege_name)){
            $con['privilege_name'] = $privilege_name;
        }
        $con['parent_id'] = $parent_id;
        $con['_delete']   = 0;
        $list = Privilege::model()->getlists($con, 'Asc', 'id', $limit, $page);

        $this->render('privilege_list', array('list' => $list));
    }

    public function actionPrivilege_add()
    {
        $info = Privilege::model()->getParentList(array('parent_id' => 0, '_delete' => 0));
        $this->render('privilege_add', array('info' => $info));
    }

    public function actionPrivilege_add_op()
    {
        try{
            $privilege_name 	= trim(Yii::app()->request->getParam( 'privilege_name' ));
            $privilege_module 	= trim(Yii::app()->request->getParam( 'privilege_module' ));
            $privilege_link 	= trim(Yii::app()->request->getParam( 'privilege_link' ));
            $sort 		        = trim(Yii::app()->request->getParam( 'sort' ));
            $parent_id 	        = trim(Yii::app()->request->getParam( 'parent_id' ));

            if(empty($privilege_name)){
                throw new Exception('1001');
            }
            if(empty($privilege_module)){
                throw new Exception('1002');
            }
            if(!isset($parent_id)){
                throw new Exception('1004');
            }

            $info = Privilege::model()->find('privilege_name=:privilege_name', array('privilege_name' => $privilege_name));
            if(!empty($info)){
                throw new Exception('2');
            }
            $data = array(
                'privilege_name'	=> $privilege_name,
                'privilege_module'	=> $privilege_module,
                'privilege_link'	=> $privilege_link,
                'sort'	 		    => $sort,
                'parent_id'	 		=> $parent_id,
            );
            $adminId = Privilege::model()->privilegeSave($data);
            if($adminId){
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

    public function actionPrivilege_update_status()
    {
        try{
            $id 		= trim(Yii::app()->request->getParam( 'id' ));
            $status 	= trim(Yii::app()->request->getParam( 'status' ));
            if(empty($id)){
                throw new Exception('1005');
            }

            $info 		= Privilege::model()->findbypk($id);
            if(empty($info)){
                throw new Exception('3');
            }
            $flag = Privilege::model()->privilegeUpdateStatus($id, $status);
            if($flag){
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

    public function actionPrivilege_edit()
    {
        $id 			= trim(Yii::app()->request->getParam( 'id' ));
        $categoryInfo 	= Privilege::model()->findbypk($id);
        $info           = Privilege::model()->getParentList(array('parent_id' => 0));
        $this->render('privilege_edit', array('category' => $categoryInfo, 'info' => $info));
    }

    public function actionPrivilege_edit_op()
    {
        try{
            $id 	            = trim(Yii::app()->request->getParam( 'id' ));
            $privilege_name 	= trim(Yii::app()->request->getParam( 'privilege_name' ));
            $privilege_module 	= trim(Yii::app()->request->getParam( 'privilege_module' ));
            $privilege_link 	= trim(Yii::app()->request->getParam( 'privilege_link' ));
            $sort 		        = trim(Yii::app()->request->getParam( 'sort' ));
            $parent_id 	        = trim(Yii::app()->request->getParam( 'parent_id' ));
            if(empty($id)){
                throw new Exception('1005');
            }
            if(empty($privilege_name)){
                throw new Exception('1001');
            }
            if(empty($privilege_module)){
                throw new Exception('1002');
            }
            if(!isset($parent_id)){
                throw new Exception('1004');
            }

            $data = array(
                'id'	            => $id,
                'privilege_name'	=> $privilege_name,
                'privilege_module'	=> $privilege_module,
                'privilege_link'	=> $privilege_link,
                'sort'	 		    => $sort,
                'parent_id'	 		=> $parent_id,
            );
            $adminId = Privilege::model()->privilegeUpdate($data);
            if($adminId){
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


    public function actionPrivilege_del(){
        $id = Yii::app()->request->getParam( 'id' );
        if(empty($id)){
            throw new Exception('1005');
        }
        $model = Privilege::model()->findbypk($id);
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