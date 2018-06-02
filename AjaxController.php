<?php
use application\models\ServiceRegion;
class AjaxController extends Controller
{
    private $msg = array(
            'Y' => '成功',
            1 => '操作数据库错误',
            2 => '数据已经存在',
            3 => '数据不存在',
            4 => '不可为空',
            5 => '不可过长',
            1001 => '手机号错误',
            1002 => '邮箱错误',
            1003 => '管理员不能为空',
            1004 => '密码不能为空',
            1005 => '请选择角色',
            1006 => '参数错误',
            1007 => '参加人数不能为0',
            1008 => '标题不能为空',
            1009 => '开始时间不能为空',
            1010 => '结束时间不能为空',
            1011 => '嘉宾讲师不能为空',
            1012 => '活动地点不能为空',
            1013 => '详细内容不能为空',
            1014 => '图片上传错误，请重试',
            1015 => '上传文件过大',
            1016 => '地区不能不选',
            1017 => '此手机号已存在',
            1018 => '没有文件被上传',
            1019 => '图片格式不支持',
            1020 => '图片宽高过小，请上传合适宽高的图片',
            1021 => '活动已结束',
            1022 => '此活动已被管理员删除，请刷新页面重试',
            1023 => '此活动尚未完善，请先补充活动内容再进行复制'

    );
    
    //上传图片 
    public function actionUpload_Image(){
        $msgNo = 'Y';
        $type = trim(Yii::app()->request->getParam( 'type' )); //是否为邀请函
        
        try{
            $upload = new Upload();
            if(empty($_FILES)){
                throw new Exception('3');
            }
            
            $key = array_keys($_FILES);
            $filevalue = $_FILES[$key[0]];
            if(intval($filevalue['error'])===1){
                throw new Exception('1015');
            }
            
            $size = $filevalue['size']/1024/1024;
            if($size>1){
                throw new Exception('1015');
            }
            
            $image_path = $upload->uploadFile($key[0]); //image为上传框name
            $getErrorMsg = $upload->getErrorMsg();
            if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                throw new Exception('1014');
            }
            $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
            $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
            
            if($imagetype=='.jpg' || $imagetype=='.jpeg') {
                $model_im = @imagecreatefromjpeg($image_all_path);
            }elseif($imagetype=='.png') {
                $model_im = @imagecreatefrompng($image_all_path);
            }elseif($imagetype=='.gif') {
                $model_im = @imagecreatefromgif($image_all_path);
            }else{
                unlink($image_all_path);
                throw new Exception('1019');
            }
            if(!$model_im){
                unlink($image_all_path);
                throw new Exception('1019');
            }
            
            //邀请函判断图片是否过小
            if($type==='yqh'){
                $model_im_width = imagesx($model_im); //模板图片宽度
                $model_im_height = imagesy($model_im); //高度
                if($model_im_width<580 || $model_im_height<400){
                    unlink($image_all_path);
                    throw new Exception('1020');
                }
            }
            
            $this->msg[$msgNo] = $image_path;
            
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }

        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //详情定时保存redis
    public function actionSaveBakRedis(){
        $id = intval(Yii::app()->request->getParam('id'));
        $type = trim(Yii::app()->request->getParam('type'));
        $content = trim(Yii::app()->request->getParam('content'));
        if ($id && $type && $content) {
            $bak = new ARedisHash($type.'_draft_bak_' . $id);
            $bak->add('data', $content);
        }
        echo 1;
    }
    
    //异步上传抽奖转盘图片
    public function actionUploadImage(){
        $msgNo = 'Y';
        try{
            if(empty($_FILES)){
                throw new Exception('3');
            }
            $key = array_keys($_FILES);
            $filevalue = $_FILES[$key[0]];
            if(intval($filevalue['error'])===1){
                throw new Exception('1015');
            }
            $size = $filevalue['size']/1024/1024;
            if($size>1){
                throw new Exception('1015');
            }
            $upload = new Upload();
            $path = '/uploads/prize/'.Yii::app()->user->user_id.'/';
            $upload->set('path',$path);
            $image_path = $upload->uploadFile($key[0]);
            
            $getErrorMsg = $upload->getErrorMsg();
            if(!empty($getErrorMsg) || !strstr($image_path,'/uploads/') ){
                throw new Exception('1014');
            }
            $imagetype = strtolower(substr($image_path,strrpos($image_path, '.'))); 
            $image_all_path = Yii::getPathOfAlias('webroot').'/../..'.$image_path;
            
            if($imagetype=='.png') {
                $model_im = @imagecreatefrompng($image_all_path);
            }else{
                unlink($image_all_path);
                throw new Exception('1019');
            }
            if(!$model_im){
                unlink($image_all_path);
                throw new Exception('1019');
            }
            $this->msg[$msgNo] = $image_path;
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
	* 读取redis列表
	*/
    public function actionRedis(){
        header("Content-type: text/html; charset=utf-8");            
		$getHostInfo = Yii::app()->request->getHostInfo();
		if( strstr($getHostInfo, 'http://e.fwxgx.com') ){ //正式环境禁止访问
			exit;
		}
        
        $getkey = Yii::app()->request->getParam('key');
		
        if( !$getkey ){
			echo "<title>Redis列表</title>";
			$word = Yii::app()->request->getParam('word');
            
			$from = '<p><form method="post" action="">';
			$from .= '<input type="text" name="word" placeholder="关键词" value="'.$word.'"> <input type="submit">（区分大小写）';
			$from .= '</form></p>';
			echo $from;
			
            if( $word===NULL ){
                exit;
            }
			$word = empty($word) ? '*' : '*'.trim($word).'*';
            $keys = Yii::app()->redis->getClient()->keys( $word );
			
			asort($keys);
            foreach($keys as $key){
				$key = str_replace('Yii.redis.', '', $key);
                echo '<p><a target="_blank" href="index.php?r=ajax/Redis&key='.$key.'">'.$key.'</a></p>';
            }
            exit;
        }

		//新页面
		echo "<title>". $getkey ."</title>";
		echo "<pre>";
		$s = Yii::app()->request->getParam('s'); //unserialize反序列化
		$data = Yii::app()->redis->getClient()->get($getkey);
		if( $data ){
			echo "<p>String</p>";
			if( $s ){
				 print_r(unserialize($data));die;
			}
			print_r($data);die;
		}
        $data = new ARedisHash($getkey);
		if( $data->data ){
			echo "<p>ARedisHash（个数：".count($data->data)."）</p>";
			print_r($data->data);die;
		}
		$data = new ARedisList($getkey);
		if( $data->data ){
			echo "<p>ARedisList（个数：".count($data->data)."）</p>";
			print_r($data->data);die;
		}
		$data = new ARedisSortedSet($getkey);
		if( $data->data ){
			echo "<p>ARedisSortedSet（个数：".count($data->data)."）</p>";
			print_r($data->data);die;
		}
    }
    
}