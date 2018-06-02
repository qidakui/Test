<?php
use application\models\App\AppVersion;
use application\models\App\AppBanner;
class AppController extends Controller
{

	private $msg = array(
			'Y' => '操作成功',
			1 => '数据库操作错误',
			2 => '版本不存在',
			3 => '操作系统不能为空',
			5 => '版本号不能为空',
			6 => '安装包不能为空',
			7 => '已下线版本不允许修改',
			8 => '下载地址不能为空',
			9 => 'banner不存在',
			10 => 'banner位置不能为空',
			11 => 'banner跳转类型不能为空',
			12 => 'banner图片不能为空',
			13 => 'banner名称不能为空',
			14 => '状态不能为空',
			15 => 'ID不能为空',
			16 => '排序不能为空',
	);

	public function actionVersion_info(){
		try{
			$id = trim(Yii::app()->request->getParam('id'));
			if(!empty($id)){
				$version = AppVersion::model()->findByPk($id);
				if(empty($version)){
					throw new Exception(2);
				}
				if($version->status == 0){
					throw new Exception(7);
				}
			}else{
				$version = new AppVersion();
				$version->app_type = 1;
				$version->force_update = 0;
			}
			$this->render('version_info', array( 'version' => $version));exit;
		}catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);

	}

	public function actionCreate_or_update_version_info(){
		$msgNo = 'Y';
		$version_info = array();
		try{
			$id = trim(Yii::app()->request->getParam('id'));
			$version_info['app_type'] = trim(Yii::app()->request->getParam('app_type'));
			$version_info['version'] = trim(Yii::app()->request->getParam('version'));
			$version_info['force_update'] = trim(Yii::app()->request->getParam('force_update'));
			//非空检查
			if(empty($version_info['app_type']))
				throw new Exception('3');
			if(empty($version_info['version']))
				throw new Exception('5');
			if($version_info['app_type'] == 1){
				//兼容IE增加判断!
				if(isset($_FILES['android_download_url']) && !empty($_FILES['android_download_url']) && !empty($_FILES['android_download_url']['name'])) {
					$upload = new Upload();
					$upload->set('allowtype',array('apk'));
					$upload->set('maxsize',30*1024*1024);
					$flag = $upload->uploadFile('android_download_url');
					if (empty($upload->getErrorMsg())) {
						$version_info['download_url'] = $flag;
					} else {
						echo $this->encode('N', $upload->getErrorMsg());exit;
					}
				}else{
					$version_info['download_url'] = trim(Yii::app()->request->getParam('old_android_download_url'));
					if(empty($version_info['download_url'])){
						throw new Exception('6');
					}
				}
			}else{
				$version_info['download_url'] = trim(Yii::app()->request->getParam('ios_download_url'));
				if(empty($version_info['download_url'])){
					throw new Exception('8');
				}
			}


			//$id为空则创建,否则为更新
			if(empty($id)){
				$version = AppVersion::model()->createVersion($version_info);
			}else{
				$version = AppVersion::model()->findByPk($id);
				if(empty($version))
					throw new Exception('2');
				$version->updateVersion($version_info);
			}
		} catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);

	}

	public function actionVersion_index(){
		$this->render('version_index');
	}

	public function actionGet_version_list(){
		$version_list = AppVersion::model()->getList();
		echo CJSON::encode($version_list);exit;
	}

	public function actionBanner_info(){
		try{
			$id = trim(Yii::app()->request->getParam('id'));
			if(!empty($id)){
				$banner = AppBanner::model()->findByPk($id);
				if(empty($banner)){
					throw new Exception(9);
				}
			}else{
				$banner = new AppBanner();
			}
			$this->render('banner/banner_info', array( 'banner' => $banner));exit;
		}catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);
	}


	public function actionCreate_or_update_banner_info(){
		$msgNo = 'Y';
		$banner_info = array();
		try{
			$id = trim(Yii::app()->request->getParam('id'));
			$banner_info['name'] = trim(Yii::app()->request->getParam('name'));
			$banner_info['position'] = trim(Yii::app()->request->getParam('position'));
			$banner_info['type'] = trim(Yii::app()->request->getParam('type'));
			$banner_info['object_id'] = trim(Yii::app()->request->getParam('object_id'));
			$banner_info['info'] = \CHtml::encode(trim(Yii::app()->request->getParam('info')));
			$banner_info['banner_url'] = trim(Yii::app()->request->getParam('banner_url'));
			$banner_info['order_no'] = trim(Yii::app()->request->getParam('order_no'));
			//非空检查
			if(empty($banner_info['position']))
				throw new Exception('10');
			if(empty($banner_info['type']))
				throw new Exception('11');
			if(empty($banner_info['name']))
				throw new Exception('13');
			if(empty($banner_info['order_no']))
				throw new Exception('16');
			//兼容IE增加判断!
			if(isset($_FILES['img_url']) && !empty($_FILES['img_url']) && !empty($_FILES['img_url']['name'])) {
				$upload = new Upload();
				$upload->set('allowtype',array('jpg','jpeg','png'));
				$upload->set('maxsize',1024*1024);
				$flag = $upload->uploadFile('img_url');
				if (empty($upload->getErrorMsg())) {
					$banner_info['img_url'] = $flag;
				} else {
					echo $this->encode('N', $upload->getErrorMsg());exit;
				}
			}else{
				$banner_info['img_url'] = trim(Yii::app()->request->getParam('old_img_url'));
				if(empty($banner_info['img_url'])){
					throw new Exception('12');
				}
			}

			//$id为空则创建,否则为更新
			if(empty($id)){
				$banner = AppBanner::model()->createBanner($banner_info);
			}else{
				$banner = AppBanner::model()->findByPk($id);
				if(empty($banner))
					throw new Exception('9');
				$banner->updateBanner($banner_info);
			}
		} catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);

	}


	public function actionBanner_index(){
		$this->render('banner/banner_index');
	}

	public function actionGet_banner_list(){
		$banner_list = AppBanner::model()->getList();
		echo CJSON::encode($banner_list);exit;
	}


	public function actionChange_banner_status(){
		$msgNo = 'Y';
		try{
			$id = trim(Yii::app()->request->getParam('id'));
			$status = trim(Yii::app()->request->getParam('status'));

			//非空检查
			if(empty($status) && $status != 0)
				throw new Exception('14');
			if(empty($id))
				throw new Exception('15');
			$banner = AppBanner::model()->findByPk($id);
			if(empty($banner))
				throw new Exception('9');
			$banner->updateBanner(array('status' => $status));
		}catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);

	}

	public function actionDelete_banner(){
		$msgNo = 'Y';
		try{
			$id = trim(Yii::app()->request->getParam('id'));

			//非空检查
			if(empty($id))
				throw new Exception('15');
			$banner = AppBanner::model()->findByPk($id);
			if(empty($banner))
				throw new Exception('9');
			$banner->updateBanner(array('_delete' => 1));
		}catch(Exception $e){
			$msgNo = $e->getMessage();
		}
		$msg = $this->msg[$msgNo];
		echo $this->encode($msgNo, $msg);

	}

}