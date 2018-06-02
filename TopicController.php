<?php
use application\models\Topic\Topic;
use application\models\ServiceRegion;
class TopicController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        0 => '参数错误',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '标题字数不可超过22个',
        5 => '内容不可为空',
        6 => '内容字数不可超过200'
    );
    public $filiale_id;
    public function init(){
        parent::init();
        $this->filiale_id = Yii::app()->user->branch_id;
    }

    /*
     * 话题列表
     */
    public function actionIndex(){
        $column_type = 1; //目前仅支持活动
        $column_id = intval(Yii::app()->request->getParam( 'column_id' ));
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('topic_list',array('getCityList'=>$getCityList,'column_type'=>$column_type, 'column_id'=>$column_id));exit;
        }

        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $filiale_id   	= intval(Yii::app()->request->getParam( 'filiale_id' ));
        $title   	= trim(Yii::app()->request->getParam( 'search_content' ));
        $con['status'] = 1;   
        $con['column_id'] = $column_id;
        if($this->filiale_id!=BRANCH_ID){
            $con['filiale_id'] = $this->filiale_id;
        }elseif($filiale_id!=BRANCH_ID){
            $con['filiale_id'] = $filiale_id;
        }
        if(!empty($title)){
            $con['title'] = $title;
        }
        $list = Topic::model()->getlist($con, $ord, $field, $limit, $page);
		//print_r($list);
        echo CJSON::encode($list);
    }
   
    /*
     * 添加 修改话题
     */
    public function actionSave_topic(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'topic_id' ));
		$column_id	= intval(Yii::app()->request->getParam( 'column_id' ));
        $column_type	= trim(Yii::app()->request->getParam( 'column_type' ));
		$title = trim(Yii::app()->request->getParam( 'title' ));
		$info = trim(Yii::app()->request->getParam( 'info' )); 
		try {
            if(empty($column_id) || empty($column_type) ){
                throw new Exception(0);
            }
            if( mb_strlen($title,'utf-8')>22 ){
                throw new Exception(4);
            }
            if( empty($info) ){
                throw new Exception(5);
            }     
            if( mb_strlen($info,'utf-8')>200 ){
                throw new Exception(6);
            } 
			if($id){
				$data['id'] = $id;
            }else{
                $data['user_id'] = Yii::app()->user->user_id;
                $data['filiale_id'] = $this->filiale_id;
                $data['user_name'] = Yii::app()->user->user_name;
                $data['column_type'] = $column_type;
                $data['column_id'] = $column_id;
            } 
            $data['title'] = $title;
            $data['info'] = $info;
            $save = Topic::model()->saveData($data);      
            if(!$save){
                throw new Exception(1);
            } 
			
		} catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 删除话题
     */
    public function actionDel_topic(){
        $msgNo = 'Y';
        $ids = Yii::app()->request->getParam( 'ids' );
        try{
            if( empty($ids) || !is_array($ids) ){
                throw new Exception(0);
            }
            $res = Topic::model()->updateStatus($ids);
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
     
}




     
        