<?php
use application\models\Admin\Admin;
use application\models\Admin\AdminRole;
use application\models\Privilege\Role;
use application\models\ServiceRegion;
class AdminController extends Controller
{
	private $msg = array(
			'Y' => '成功',
			1 => '操作数据库错误',
			2 => '数据已经存在',
			3 => '数据不存在',
			1001 => '手机号错误',
			1002 => '邮箱错误',
			1003 => '管理员不能为空',
			1004 => '密码不能为空',
			1005 => '请选择角色',
			1006 => '参数错误',
			1007 => '请选择分之',
			1008 => '设置已生效，请退出重新登录',
	);

	public function actionIndex(){
		$this->render('admin_list');
	}
	/**
	 * This is the default 'index' action that is invoked
	 * when an action is not explicitly requested by users.
	 */
	public function actionAdmin_list()
	{
		$statr_time = trim(Yii::app()->request->getParam( 'statr_time' ));
		$end_time 	= trim(Yii::app()->request->getParam( 'end_time' ));
		$user_name 	= trim(Yii::app()->request->getParam( 'user_name' ));
		$sSearch   	= trim(Yii::app()->request->getParam( 'sSearch' ));
		$limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
		$page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
                $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
                $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
                $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
                $ord        = !empty($ord) ? $ord : 'desc';
                $field      = !empty($field) ? $field : 'id';
		$page		= !empty($page) ? $page : 0;
		$limit		= !empty($limit) ? $limit : 20;
		if(!empty($statr_time) && !empty($end_time)){
			$con['time'] = array('statr_time' => $statr_time, 'end_time' => $end_time);
		}
		if(!empty($sSearch)){
			$con['phone'] = $sSearch;
		}
		$con['user_name'] = $user_name;
		$con['_delete'] = 0;

		$list = Admin::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
	}

	public function actionAdmin_add()
	{
        $roleArr = Role::model()->findAll('_delete=0');
		$cityList = ServiceRegion::model()->getBranchList();
		$this->render('admin_add', array('roleArr' => $roleArr, 'cityList' => $cityList));
	}

	public function actionAdmin_add_op()
	{
		$fn = new Fn();
		try{
			$user_name 	= trim(Yii::app()->request->getParam( 'user_name' ));
			$password 	= trim(Yii::app()->request->getParam( 'password' ));
			$phone 		= trim(Yii::app()->request->getParam( 'phone' ));
			$email 		= trim(Yii::app()->request->getParam( 'email' ));
			$role_id 	= trim(Yii::app()->request->getParam( 'role_id' ));
			$branch_id 	= trim(Yii::app()->request->getParam( 'branch_id' ));
			$random 	= $fn->uniqueRand();
			$info = Admin::model()->findByAdminInfo($user_name, $phone);
			if($info){
				throw new Exception('2');
			}
			if(empty($user_name)){
				throw new Exception('1003');
			}
			if(empty($password)){
				throw new Exception('1004');
			}
			if(!$fn->validatePhone($phone)){
				throw new Exception('1001');
			}
			if(!$fn->validateMail($email)){
				throw new Exception('1002');
			}
			if(empty($role_id)){
				throw new Exception('1005');
			}
			if(empty($branch_id)){
				throw new Exception('1007');
			}
			$data = array(
				'user_name'	 	=> $user_name,
				'password'	 	=> md5($password.$random),
				'phone'	 		=> $phone,
				'email'	 		=> $email,
				'random'	 	=> $random,
				'branch_id'	 	=> $branch_id,
			);
			$adminId = Admin::model()->adminSave($data);
			if($adminId){
				$msgNo = 'Y';
				AdminRole::model()->adminRoleSave(array('user_id' => $adminId, 'role_id' => $role_id));
			} else {
				throw new Exception('1');
			}

		} catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);
	}

	public function actionAdmin_update_status()
	{
		try{
			$id 		= trim(Yii::app()->request->getParam( 'id' ));
			$status 	= trim(Yii::app()->request->getParam( 'status' ));
			if(empty($id)){
				throw new Exception('1006');
			}

			$info 		= Admin::model()->findbypk($id);
			if(empty($info)){
				throw new Exception('3');
			}
			$flag = Admin::model()->adminUpdateStatus($id, $status);
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

	public function actionAdmin_edit()
	{
		$id 			= trim(Yii::app()->request->getParam( 'id' ));
		$info 			= Admin::model()->findbypk($id);
		$AdminRoleInfo 	= AdminRole::model()->find('user_id=:user_id', array('user_id' => $id));
        $roleArr        = Role::model()->findAll('_delete=0');
		$cityList = ServiceRegion::model()->getBranchList();
		$this->render('admin_edit', array('admin' => $info, 'AdminRole' => $AdminRoleInfo, 'roleArr' => $roleArr, 'cityList' => $cityList));
	}

	public function actionAdmin_edit_op()
	{
		$fn = new Fn();
		try{
			$id 		= trim(Yii::app()->request->getParam( 'id' ));
			$adminRoleId= trim(Yii::app()->request->getParam( 'adminRoleId' ));
			$user_name 	= trim(Yii::app()->request->getParam( 'user_name' ));
			$phone 		= trim(Yii::app()->request->getParam( 'phone' ));
			$email 		= trim(Yii::app()->request->getParam( 'email' ));
			$role_id 	= trim(Yii::app()->request->getParam( 'role_id' ));
			$branch_id 	= trim(Yii::app()->request->getParam( 'branch_id' ));
            if(empty($id)){
                throw new Exception('1006');
            }
			$model 		= Admin::model()->findbypk($id);
			if(empty($model)){
				throw new Exception('3');
			}
			if(empty($user_name)){
				throw new Exception('1003');
			}
			if(!$fn->validatePhone($phone)){
				throw new Exception('1001');
			}
			if(!$fn->validateMail($email)){
				throw new Exception('1002');
			}
			if(empty($role_id)){
				throw new Exception('1005');
			}
			if(empty($branch_id)){
				throw new Exception('1007');
			}
			$data = array(
					'id'	 		=> $id,
					'user_name'	 	=> $user_name,
					'phone'	 		=> $phone,
					'email'	 		=> $email,
					'branch_id'	 	=> $branch_id,
			);
			$adminId = Admin::model()->adminUpdate($data);
			if($adminId){
				$msgNo = 'Y';
				AdminRole::model()->adminRoleUpdate(array('id' => $adminRoleId, 'user_id' => $adminId, 'role_id' => $role_id));
			} else {
				throw new Exception('1');
			}

		} catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);
	}

	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
			else
				$this->render('error', $error);
		}
	}
	
	public function actionAdmin_del()
	{
		try {
			$id = Yii::app()->request->getParam('id');
			if (empty($id)) {
				throw new Exception('1006');

			}

			$model = Admin::model()->findbypk($id);
			$model->_delete = 1;
			$flag = $model->save();
			if ($flag) {

				//@TODO 加上角色，删除角色
				$AdminRolemodel = AdminRole::model()->findAll('user_id=:user_id', array(':user_id' => $id));
				foreach($AdminRolemodel as $role){
					$role->_delete = 1;
					$role->save();
				}

				$msgNo = 'Y';
			} else {
				throw new Exception('1');
			}
		} catch (Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);
	}

	/**
	 * 操作记录
	 */
	public function actionOperation_log()
	{
		$id			= trim(Yii::app()->request->getParam( 'id' ));
		$this->render('operation_log', array('data' => $id));
	}

	public function actionOperation_log_list(){
		$id			= trim(Yii::app()->request->getParam( 'id' ));
		$limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
		$page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
		$index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
		$ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
		$field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
		$ord        = !empty($ord) ? $ord : 'desc';
		$field      = !empty($field) ? $field : 'id';
		$page		= !empty($page) ? $page : 0;
		$limit		= !empty($limit) ? $limit : 50;

		$con['user_id'] = $id;
		$con['_delete'] = 0;

		$list = OperationLog::findLog($con, $ord, $field, $limit, $page);
		echo CJSON::encode($list);
	}

	public function actionReset_passwd()
	{
		$id = trim(Yii::app()->request->getParam( 'id' ));
		$this->render('reset_passwd', array('id' => $id));
	}

	public function actionReset_passwd_op()
	{
		$fn = new Fn();
		try{
			$id 		= trim(Yii::app()->request->getParam( 'id' ));
			$password 	= trim(Yii::app()->request->getParam( 'password' ));
			$random 	= $fn->uniqueRand();

			if(empty($password)){
				throw new Exception('1004');
			}

			$data = array(
					'id'	 		=> $id,
					'password'	 	=> md5($password.$random),
					'random'	 	=> $random,
			);
			$adminId = Admin::model()->adminResetPasswd($data);
			if($adminId){
				$msgNo = '1008';
			} else {
				throw new Exception('1');
			}

		} catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);
	}
}