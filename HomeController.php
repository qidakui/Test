<?php

/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/24
 * Time: 10:02
 */
use application\models\Home\IndexBanner;
use application\models\Home\IndexBannerTotal;
use application\models\ServiceRegion;
use application\models\Admin\AdminRole;

class HomeController extends Controller {

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '参数错误',
        1001 => '标题错误',
        1002 => '类型错误',
        1003 => '请选择分支',
        1004 => '图片上传失败',
        1005 => '背景颜色错误',
        1006 => '图片不能大于1M',
        1007 => '请选择地区',
        1008 => '已同时上架3张广告图的地区，请核对后再上架',
        1009 => '本地区最多同时显示3张广告图，请下架本地区上传的其他广告图后，再上架本广告图',
    );
    private $maxsize = '1000000';

    public function actionBanner() {
        $res = ServiceRegion::model()->getCityList();
        //判断是否为超级管理员
        $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id' => Yii::app()->user->user_id));
        $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
        $role_id = $adminrole['role_id'];        
        $this->render('banner_list', array('getBranchList' => $res,'role_id'=>$role_id));
    }

    public function actionBanner_list() {
        $title = trim(Yii::app()->request->getParam('title'));
        $province = trim(Yii::app()->request->getParam('province'));
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'desc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        if (!empty($statr_time) && !empty($end_time)) {
            $con['time'] = array('statr_time' => $statr_time, 'end_time' => $end_time);
        }
        if (!empty($title)) {
            $con['title'] = $title;
        }
        if (!empty($province)) {
            $con['branch_id'] = $province;
        }
        if (Yii::app()->user->branch_id != BRANCH_ID) {
            $branchId = Yii::app()->user->branch_id;
            $con['branch_id'] = $branchId;
        }
        if(in_array($this->getsuper(), array(2))){
            $con['is_admin'] = array(1,0);
        }else{
           $con['is_admin'] = 0; 
           $con['is_claim'] = 0; 
        }
        $con['_delete'] = 0;
        $list = IndexBanner::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionBanner_add() {
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            $cityList = ServiceRegion::model()->getBranchList();
        } else {
            $branchId = Yii::app()->user->branch_id;
            $cityList = ServiceRegion::model()->getBranchInfo($branchId);
        }
        $rs = objectToArr(ServiceRegion::model()->getProvinceList());
        $province_list = IndexBannerTotal::model()->disposeInfo($rs);
        //判断是否为超级管理员
        $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id' => Yii::app()->user->user_id));
        $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
        $role_id = $adminrole['role_id'];
        $this->render('banner_add', array('cityList' => $cityList, 'province_list' => $province_list, 'role_id' => $role_id));
    }

    public function actionBanner_add_op() {
        try {
            $is_claim = 0;
            $title = trim(Yii::app()->request->getParam('title'));
            $type = trim(Yii::app()->request->getParam('type'));
            $link = trim(Yii::app()->request->getParam('link'));
            $content = trim(Yii::app()->request->getParam('content'));
            $branch_id = Yii::app()->user->branch_id;
            $sort = trim(Yii::app()->request->getParam('sort'));
            $colour_number = trim(Yii::app()->request->getParam('colour_number'));
            $checkboxAreaAll = trim(Yii::app()->request->getParam('checkboxAreaAll'));
            $open_area = Yii::app()->request->getParam('open_area');
            $role_id = trim(Yii::app()->request->getParam('role_id'));
            if (empty($title)) {
                throw new Exception('1001');
            }
            if (empty($type)) {
                throw new Exception('1002');
            }
            if (empty($branch_id)) {
                throw new Exception('1003');
            }
            if (empty($colour_number)) {
                throw new Exception('1005');
            }
            if (in_array($role_id, array(2))) {
                if (empty($open_area)) {
                    throw new Exception('1007');
                }
            }
            if(!empty($open_area)){
                if(count($open_area)>=2){
                    $is_claim=2;
                }else if(count($open_area)>2){
                    $is_claim=1;
                }else{
                    $is_claim=3;
                }
                $verify = IndexBannerTotal::model()->verifyInfo($open_area);
                if($verify===true){
                    throw new Exception('1008');
                }
            }
            if(!in_array($this->getsuper(), array(2))){
                $findInfo = IndexBanner::model()->BannerCount(Yii::app()->user->branch_id);
                if($findInfo>=3){
                    throw new Exception('1009');
                }               
            }
            $data = array(
                'title' => $title,
                'type' => $type,
                'link' => $link,
                'content' => $content,
                'branch_id' => $branch_id,
                'sort' => $sort,
                'colour_number' => $colour_number,
                'is_claim'      =>isset($is_claim)?$is_claim:'0',
                'branch_list'      =>  serialize($open_area),
                'create_user_id' => Yii::app()->user->user_id,
            );
            if (isset($_FILES['pic_path'])) {
                $upload = new Upload();
                $flag = $upload->uploadFile('pic_path');
                $errorMsg = $upload->getErrorMsg();
                if (empty($errorMsg)) {
                    $data['pic_path'] = $flag;
                } else {
                    throw new Exception('1004');
                }
            }
            $indexId = IndexBanner::model()->bannerSave($data);
            if ($indexId) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationIndexBanner, 'add', 'banner管理', $indexId, array(), $data);
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionBanner_edit() {
        $id = trim(Yii::app()->request->getParam('id'));
        $id = is_numeric($id) ? intval($id) : 0;
        $info = IndexBanner::model()->findbypk($id);
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            $cityList = ServiceRegion::model()->getBranchList();
        } else {
            $branchId = Yii::app()->user->branch_id;
            $cityList = ServiceRegion::model()->getBranchInfo($branchId);
        }
        $rs = objectToArr(ServiceRegion::model()->getProvinceList());
        $province_list = IndexBannerTotal::model()->disposeInfo($rs);
        //判断是否为超级管理员
        $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id' => Yii::app()->user->user_id));
        $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
        $role_id = $adminrole['role_id'];        
        $this->render('banner_edit', array('info' => $info, 'cityList' => $cityList,'role_id'=>$role_id,'province_list'=>$province_list));
    }

    public function actionBanner_edit_op() {
        try {
            $title = trim(Yii::app()->request->getParam('title'));
            $type = trim(Yii::app()->request->getParam('type'));
            $link = trim(Yii::app()->request->getParam('link'));
            $content = trim(Yii::app()->request->getParam('content'));
            $branch_id = Yii::app()->user->branch_id;
            $sort = trim(Yii::app()->request->getParam('sort'));
            $colour_number = trim(Yii::app()->request->getParam('colour_number'));
            $id = trim(Yii::app()->request->getParam('id'));
            $pic_path_old = trim(Yii::app()->request->getParam('pic_path_old'));
            $checkboxAreaAll = trim(Yii::app()->request->getParam('checkboxAreaAll'));
            $open_area = Yii::app()->request->getParam('open_area');
            $olo_open_area = explode(',', Yii::app()->request->getParam('olo_open_area'));
            $role_id = trim(Yii::app()->request->getParam('role_id'));
            if (empty($title)) {
                throw new Exception('1001');
            }
            if (empty($type)) {
                throw new Exception('1002');
            }
            if (empty($branch_id)) {
                throw new Exception('1003');
            }
            if (empty($colour_number)) {
                throw new Exception('1005');
            }
            if (in_array($role_id, array(2))) {
                if (empty($open_area)) {
                    throw new Exception('1007');
                }
            }
            if(!empty($open_area)){
                $diff = array_diff($olo_open_area,$open_area);
                if(count($open_area)>=2){
                    $is_claim=2;
                }else if(count($open_area)>2){
                    $is_claim=1;
                }else{
                    $is_claim=3;
                }
                if(!empty($diff)){
                    $verify = IndexBannerTotal::model()->verifyInfo($open_area,$diff,$id);
                    if($verify===true){
                        throw new Exception('1008');
                    }
                }
            }           
            $oldData = IndexBanner::model()->findbypk($id);
            $oldData = $oldData->attributes;

            $data = array(
                'title' => $title,
                'type' => $type,
                'link' => $link,
                'content' => $content,
                'branch_id' => $branch_id,
                'sort' => $sort,
                'is_claim'      =>isset($is_claim)?$is_claim:'0',
                'branch_list'      =>  serialize($open_area),                
                'colour_number' => $colour_number,
                'create_user_id' => Yii::app()->user->user_id,
            );
            if (isset($_FILES['pic_path'])) {
                $upload = new Upload();
                $flag = $upload->uploadFile('pic_path');
                $errorMsg = $upload->getErrorMsg();
                if (empty($errorMsg)) {
                    $data['pic_path'] = $flag;
                } else {
                    throw new Exception('1004');
                }
            } else {
                $data['pic_path'] = $pic_path_old;
            }

            $indexId = IndexBanner::model()->bannerUpdate($id, $data);
            if ($indexId) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationIndexBanner, 'edit', 'banner管理', $indexId, $oldData, $data);
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionBanner_del() {
        try {
            $id = Yii::app()->request->getParam('id');
            if (empty($id)) {
                throw new Exception('1006');
            }
            $model = IndexBanner::model()->findbypk($id);
            $model->_delete = 1;
            $flag = $model->save();
            $delBannerTotal = IndexBannerTotal::model()->updateAll(array('_delete'=>1,'_update_time'=>date('Y-m-d H:i:s')),'banner_id=:banner_id',array(':banner_id'=>$id));
            if ($flag) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationIndexBanner, 'del', 'banner管理', $id, array(), array());
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionaudit() {
        try {
            $banner_id = intval(Yii::app()->request->getParam('banner_id'));
            $oper = trim(Yii::app()->request->getParam('oper'));
            $operate_status = trim(Yii::app()->request->getParam('operate_status'));
            if (empty($banner_id))
                throw new Exception('4');
            if (empty($operate_status))
                throw new Exception('4');
            if(Yii::app()->user->branch_id!=BRANCH_ID){
                if($operate_status == 1){
                    $findInfo = IndexBanner::model()->BannerCount(Yii::app()->user->branch_id);
                    if($findInfo>=3){
                        throw new Exception('1009');
                    }                      
                }
            }else{
                if($operate_status == 1){
                    $res = IndexBannerTotal::model()->getInfo($banner_id);
                    if(!empty($res)){
                        $verify = IndexBannerTotal::model()->getConunt($res);
                        if($verify===true){
                            throw new Exception('1008');
                        }
                    }else{
                        $findPk = IndexBanner::model()->findByPk($banner_id);
                        if(!empty($findPk)){
                             $findInfo = IndexBanner::model()->BannerCount($findPk['branch_id']);
                        }
                        if($findInfo>=3){
                            throw new Exception('1009');
                        }                         
                    }
                }                
            }             
            switch ($oper) {
                case 'shelves':
                    $editarray['status'] = $operate_status;
                    break;
                default :
                    $editarray = array();
            }
            $flag = IndexBanner::model()->auditdatum($banner_id, $editarray);
            if ($flag) {
                OperationLog::addLog(OperationLog::$operationIndexBanner, 'edit', 'banner管理', $banner_id, array(), array());
                $msgNo = 'Y';
            } else {
                throw new Exception('1');
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    /**
     * 获取是否是超管
     */
    public function  getsuper(){
        //判断是否为超级管理员
        $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id' => Yii::app()->user->user_id));
        $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
        $role_id = $adminrole['role_id'];
        return $role_id;
    }
}
