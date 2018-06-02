<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/24
 * Time: 10:02
 */
use application\models\Home\Notice;
use application\models\ServiceRegion;
class NoticeController extends Controller
{
    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '参数错误',
        1001 => '标题错误',
        1002 => '类型错误',
        1003 => '请选择分支',
    );

    public function actionNotice(){
        $this->render('notice_list');
    }

    public function actionNotice_list(){
        $title      = trim(Yii::app()->request->getParam( 'title' ));
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
        if(!empty($title)){
            $con['title'] = $title;
        }
        if(Yii::app()->user->branch_id != BRANCH_ID){
            $branchId = Yii::app()->user->branch_id;
            $con['branch_id'] = $branchId;
        }

        $con['_delete'] = 0;
        $list = Notice::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionNotice_add()
    {
        if(Yii::app()->user->branch_id == BRANCH_ID){
            $cityList = ServiceRegion::model()->getBranchList();
        } else {
            $branchId = Yii::app()->user->branch_id;
            $cityList = ServiceRegion::model()->getBranchInfo($branchId);
        }
        $this->render('notice_add', array('cityList' => $cityList));
    }

    public function actionNotice_add_op()
    {
        try{
            $title 	    = trim(Yii::app()->request->getParam( 'title'));
            $link           = trim(Yii::app()->request->getParam( 'link'));
            $content        = trim(Yii::app()->request->getParam( 'content'));
            $notice_desc    = trim(Yii::app()->request->getParam( 'notice_desc'));
            $branch_id 	    = Yii::app()->user->branch_id;
            $sort 	    = trim(Yii::app()->request->getParam( 'sort' ));

            if(empty($title)){
                throw new Exception('1001');
            }
            if(empty($branch_id)){
                throw new Exception('1003');
            }
            $data = array(
                'title'	 	    => $title,
                'link'	 	    => $link,
                'content'	    => $content,
                'branch_id'	    => $branch_id,
                'notice_desc'       => $notice_desc,
                'sort'	 	    => $sort,
                'create_user_id'=> Yii::app()->user->user_id,
            );


            $indexId = Notice::model()->noticeSave($data);
            if($indexId){
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationNotice, 'add', '公告管理', $indexId, array(), $data);
            } else {
                throw new Exception('1');
            }

        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionNotice_edit()
    {
        $id 			= trim(Yii::app()->request->getParam( 'id' ));
        $id             = is_numeric($id) ? intval($id) : 0;
        $info 			= Notice::model()->findbypk($id);
        if(Yii::app()->user->branch_id == BRANCH_ID){
            $cityList = ServiceRegion::model()->getBranchList();
        } else {
            $branchId = Yii::app()->user->branch_id;
            $cityList = ServiceRegion::model()->getBranchInfo($branchId);
        }
        $this->render('notice_edit', array('info' => $info, 'cityList' => $cityList));
    }

    public function actionNotice_edit_op()
    {
        try{
            $title 	    = trim(Yii::app()->request->getParam( 'title'));
            $link 	    = trim(Yii::app()->request->getParam( 'link'));
            $content 	    = trim(Yii::app()->request->getParam( 'content'));
            $notice_desc    = trim(Yii::app()->request->getParam( 'notice_desc'));
            $branch_id 	    = Yii::app()->user->branch_id;
            $sort 	    = trim(Yii::app()->request->getParam( 'sort' ));
            $id 	    = trim(Yii::app()->request->getParam( 'id' ));

            if(empty($title)){
                throw new Exception('1001');
            }
            if(empty($branch_id)){
                throw new Exception('1003');
            }
            $oldData  = Notice::model()->findbypk($id);
            $oldData  = $oldData->attributes;
            $data = array(
                'title'	 	    => $title,
                'link'	 	    => $link,
                'content'	    => $content,
                'notice_desc'	    => $notice_desc,
                'branch_id'	    => $branch_id,
                'sort'	 	    => $sort,
                'create_user_id'=> Yii::app()->user->user_id,
            );

            $indexId = Notice::model()->noticeUpdate($id, $data);
            if($indexId){
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationNotice, 'edit', '公告管理', $indexId, $oldData, $data);
            } else {
                throw new Exception('1');
            }

        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionNotice_del(){
        try {
            $id = Yii::app()->request->getParam('id');
            if (empty($id)) {
                throw new Exception('1006');
            }
            $model = Notice::model()->findbypk($id);
            $model->_delete = 1;
            $flag = $model->save();
            if ($flag) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationNotice, 'del', '公告管理', $id, array(), array());
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
}