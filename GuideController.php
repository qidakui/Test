<?php

use application\models\Guide\Guide;
use application\models\ServiceRegion;
use application\models\Admin\AdminRole;
class GuideController extends Controller {

    public $msg = array(
        'Y' => '成功',
        1 => '向导页名称不能为空',
        2 => '向导页不存在',
        3 => 'ID不能为空',
        4 => '状态不能为空'
    );

    //向导页的列表页
    public function actionIndex() {
        $branch_id = Yii::app()->user->branch_id;
        $user_id = Yii::app()->user->user_id;
        $role = AdminRole::model()->find('user_id=:user_id',array('user_id' => $user_id) );
        $can_delete = !empty($user_id) && !empty($role) && $role->role_id == 2;
        $option = '';
        if ($branch_id == QG_BRANCH_ID) {
            $res = ServiceRegion::model()->getCityList();
            $option = '<option value="'. QG_BRANCH_ID.'">全国</option>';
        } else {
            $province_id = ServiceRegion::model()->getRegionIdByBranch($branch_id);
            $region_name = ServiceRegion::model()->getRegionName($province_id);
            $res = array(array('region_id' => $province_id, 'region_name' => $region_name));
        }

        foreach ($res as $val) {
            $option .= '<option value="' . $val['region_id'] . '">' . $val['region_name'] . '</option>';
        }
        $this->render('index', array('branch_option' => $option, 'can_delete' => $can_delete));
    }

    //向导页新建或编辑导向页基本信息
    public function actionInfo(){
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            if(!empty($id)){
                $guide = Guide::model()->findByPk($id);
                if(empty($guide)){
                    throw new Exception(2);
                }
            }else{
                $guide = new Guide();
            }
            $this->render('info', array( 'guide' => $guide));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    //编辑导向页图文详情
    public function actionDescribe(){
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            if(!empty($id)){
                $guide = Guide::model()->findByPk($id);
                if(empty($guide)){
                    throw new Exception(2);
                }
            }else{
                throw new Exception(3);
            }
            $this->render('describe', array('column_id' => $id, 'callback' => $this->createUrl('guide/index')));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    //编辑导向页banner
    public function actionBanner(){
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            if(!empty($id)){
                $guide = Guide::model()->findByPk($id);
                if(empty($guide)){
                    throw new Exception(2);
                }
            }else{
                throw new Exception(3);
            }
            $this->render('banner', array('column_id' => $id, 'callback' => $this->createUrl('guide/describe', array('id' => $id))));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    //创建或修改向导页基本信息
    public function actionCreate_or_update_info(){
        $msgNo = 'Y';
        $guide_info = array();
        $guide_id = '';
        try{
            $guide_info['id'] = trim(Yii::app()->request->getParam('id'));
            $guide_info['name'] = trim(Yii::app()->request->getParam('name'));
            $guide_info['branch_id'] = Yii::app()->user->branch_id;
            //非空检查
            if(empty($guide_info['name']))
                throw new Exception('1');
            $guide_id = Guide::model()->create_or_update_record($guide_info);
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $result = $this->encode($msgNo, $this->msg[$msgNo]);
        echo CJSON::encode(array_merge(CJSON::decode($result),array('url' => $this->createUrl('guide/banner', array('id' => $guide_id)))));

    }

    //获取向导页列表
    public function actionGet_list(){
        $search_branch_id = trim(Yii::app()->request->getParam('branch_id'));
        $branch_id = Yii::app()->user->branch_id;
        $start_time = trim(Yii::app()->request->getParam('start_time'));
        $end_time = trim(Yii::app()->request->getParam('end_time'));
        $limit = trim(Yii::app()->request->getParam('length'));
        $offset = trim(Yii::app()->request->getParam('start'));
        //offset默认为0,limit默认为20
        $offset = !empty($offset) ? $offset : 0;
        $limit = !empty($limit) ? $limit : 20;
        //如果当前用户的分支ID等于QG_BRANCH_ID,则有权限进行筛选,否则只能查看本分支数据
        if($branch_id == QG_BRANCH_ID){
            if(empty($search_branch_id) || $search_branch_id == QG_BRANCH_ID ){
                $con['branch_id'] = '';
            }else{
                $con['branch_id'] = $search_branch_id;
            }
        }else{
            $con['branch_id'] = $branch_id;
        }
        if(!empty($start_time) && !empty($end_time)){
            $end_time = date("Y-m-d", strtotime("$end_time   +1   day"));
            $con['_create_time'] = array('between',$start_time,$end_time);
        }
        $guide_list = Guide::model()->getList($con, 'id desc', $limit,$offset);
        echo CJSON::encode($guide_list);exit;
    }

    //修改向导页状态,上线or下线
    public function actionChange_status(){
        $msgNo = 'Y';
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            $status = trim(Yii::app()->request->getParam('status'));

            //非空检查
            if(empty($status) && $status != 0)
                throw new Exception('4');
            if(empty($id))
                throw new Exception('3');
            $guide = Guide::model()->findByPk($id);
            if(empty($guide))
                throw new Exception('2');
            $guide->updateRecord(array('status' => $status));
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);

    }

    //删除向导页
    public function actionDelete(){
        $msgNo = 'Y';
        try{
            $id = trim(Yii::app()->request->getParam('id'));

            //非空检查
            if(empty($id))
                throw new Exception('3');
            $guide = Guide::model()->findByPk($id);
            if(empty($guide))
                throw new Exception('2');
            $guide->updateRecord(array('_delete' => 1));
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);

    }


}
