<?php
/**
 * Created by PhpStorm.
 * User: wenlh
 * Date: 2016/5/12
 * Time: 18:11
 */

use application\models\OnlineStudy\LocalVideo;
use application\models\OnlineStudy\LocalVideoCustomerSetting;
use application\models\OnlineStudy\StudentDynamicManage;
use application\models\OnlineStudy\Teacher;
use application\models\ServiceRegion;
use application\models\OnlineStudy\StudyDocument;
use application\models\AdminOperationLog;
use application\models\OnlineStudy\VideoComment;
use application\models\OnlineStudy\DocumentComment;
use application\models\OnlineStudy\OnlineStudyDefaultArea;
use application\models\OnlineStudy\VideoLog;
use application\models\OnlineStudy\DocumentLog;

class OnlineStudyController extends Controller
{
    /**
     * 出错提示信息
     * by:wenlh
    */
    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        1001 => '名称不能为空',
        1002 => '描述不能为空',
        1003 => '视频类型不能为空',
        1004 => '上传图片错误',
        1005 => '视频地址不能为空',
        1006 => '上传图片不能为空',
        1007 => '文档原文件不能为空',
        1008 => '文档阅读文件不能为空',
        1009 => '讲师URL不能为空',
        1010 => '保存成功',
        1011 => '保存失败',
        1012 => '最多只能添加五个讲师',
    );

    /**
     * 方法名,对应到管理员操作日志中展示
     * by:wenlh
     */
    private $action_info = array(
        'change_dynamic_show' => '修改学员动态管理',
        'update_teacher_info' => '修改推荐讲师',
        'add_teacher_info' => '新增推荐讲师',
        'del_teacher_info' => '删除推荐讲师',
        'information_close' => '关闭资料',
        'information_del' => '删除资料',
        'set_position' => '设置资料显示位置',
        'video_edit' => '编辑视频',
        'video_add' => '新增视频',
        'document_edit' => '编辑文档',
        'document_add' => '新增文档',
    );

    /**
     * 学员操作记录管理列表,分支学员记录显示本分支or全国
     * by:wenlh
     */
    public function actionStudent_dynamic_manage()
    {
        $branch_id = Yii::app()->user->branch_id;
        $is_show_all = StudentDynamicManage::model()->is_show_all_by_branch_id($branch_id);
        $this->render('student_dynamic_manage', array('is_show_all' => $is_show_all));
    }

    /**
     * 修改学员操作记录管理
     * by:wenlh
     */
    public function actionChange_dynamic_show()
    {
        $branch_id = Yii::app()->user->branch_id;
        $user_id = Yii::app()->user->user_id;
        $is_show_all = trim(Yii::app()->request->getParam('is_show_all'));
        $old_data = StudentDynamicManage::model()->get_array_list_by_branch_id($branch_id);
        $record_id = StudentDynamicManage::model()->change_show_by_branch_id($branch_id, $user_id, $is_show_all);
        $new_data = StudentDynamicManage::model()->findByPk($record_id)->attributes;
        if($record_id > 0){
            $this->_save_operate_log('edit',$record_id,$old_data,$new_data);
        }
        echo CJSON::encode(array('success' => true));
        exit;
    }

    /**
     * 资料默认显示地区管理列表,本分支or全国
     * by:wenlh
     */
    public function actionDefault_area()
    {
        $branch_id = Yii::app()->user->branch_id;
        $is_show_all = OnlineStudyDefaultArea::model()->is_show_all_by_branch_id($branch_id);
        $this->render('default_area', array('is_show_all' => $is_show_all));
    }

    /**
     * 修改资料默认显示地区
     * by:wenlh
     */
    public function actionChange_default_area()
    {
        $branch_id = Yii::app()->user->branch_id;
        $is_show_all = trim(Yii::app()->request->getParam('is_show_all'));
        if($is_show_all != 'true'){
            $video_count = count(LocalVideo::model()->by_branch_id($branch_id)->activity()->online()->findAll());
            $document_count = count(StudyDocument::model()->by_branch_id($branch_id)->activity()->online()->findAll());
            if($video_count < 9){
                echo CJSON::encode(array('success' => true, 'info' => '视频资料数小于9个,不能修改为默认显示本地'));
                exit;
            }
            if($document_count < 6) {
                echo CJSON::encode(array('success' => true, 'info' => '图文资料数小于6个,不能修改为默认显示本地'));
                exit;
            }
        }
        OnlineStudyDefaultArea::model()->change_show_by_branch_id($branch_id, $is_show_all);
        echo CJSON::encode(array('success' => true, 'info' => '修改成功'));
        exit;
    }

    /**
     * 讲师列表,编辑讲师图片信息及对应URL,在前台进行轮播展示,每个分支只能创建5个讲师信息,总部账号可以修改及编辑分支信息,
     * 属性为全国的讲师会在分支进行轮播展示
     * by:wenlh
     */
    public function actionTeacher_index()
    {
        $branch_id = Yii::app()->user->branch_id;
        $option = '';
        if ($branch_id == QG_BRANCH_ID) {
            $res = ServiceRegion::model()->getCityList();
            $option = '<option value="'. QG_BRANCH_ID .'">全国</option>';
        } else {
            $province_id = ServiceRegion::model()->getRegionIdByBranch($branch_id);
            $region_name = ServiceRegion::model()->getRegionName($province_id);
            $res = array(array('region_id' => $province_id, 'region_name' => $region_name));
        }
        foreach ($res as $val) {
            $option .= '<option value="' . $val['region_id'] . '">' . $val['region_name'] . '</option>';
        }
        $this->render('teacher_list', array('provice_option' => $option));
    }

    /**
     * 异步获取讲师列表
     * by:wenlh
     */
    public function actionGet_teacher_list()
    {
        $province_id = trim(Yii::app()->request->getParam('province_id'));
        $con = array('status' => 1, 'is_delete' => 0);
        $con['province_id'] = $province_id;
        $teacher_info = Teacher::model()->get_list($con, 'asc', 'id', 5, 0);
        echo CJSON::encode($teacher_info);
    }

    /**
     * 更新讲师信息
     * by:wenlh
     */
    public function actionUpdate_teacher_info(){
        $data = [];
        $id = trim(Yii::app()->request->getParam('id'));
        $teacher_url = trim(Yii::app()->request->getParam('teacher_url'));
        if(empty($id)){
            echo $this->encode('N', $this->msg['1011']);exit;
        }
        if (isset($_FILES['teacher_pic']) && $_FILES['teacher_pic']['size'] > 0) {
            $upload = new Upload();
            $flag = $upload->uploadFile('teacher_pic');
            $data['teacher_pic_name'] = $_FILES['teacher_pic']['name'];
            if (empty($upload->getErrorMsg())) {
                $data['teacher_pic'] = $flag;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        }
        if(!empty($teacher_url)) $data['teacher_url'] = $teacher_url;
        $data['update_time'] = date('Y-m-d H:i:s');
        $data['update_user'] = \Yii::app()->user->user_id;
        $old_data = Teacher::model()->findByPk($id)->attributes;
        $update_count = Teacher::model()->updateByPk($id,$data);
        $new_data = Teacher::model()->findByPk($id)->attributes;
        if($update_count > 0){
            $this->_save_operate_log('edit',$id,$old_data,$new_data);
            echo $this->encode('Y', $this->msg['1010']);exit;
        }else{
            echo $this->encode('N', $this->msg['1011']);exit;
        }
    }

    /**
     * 创建讲师,一个分支最多只能创建5个讲师
     * by:wenlh
     */
    public function actionAdd_teacher_info(){
        $data = [];
        $branch_id = Yii::app()->user->branch_id;
        $teacher_url = trim(Yii::app()->request->getParam('teacher_url'));
        $province_id = trim(Yii::app()->request->getParam('province_id'));
        $con = array('status' => 1, 'is_delete' => 0);
        $con['province_id'] = $province_id;
        $teacher_info = Teacher::model()->get_list($con, 'asc', 'id');
        if($teacher_info['iTotalRecords'] >= 5){
            echo $this->encode('1012', $this->msg['1012']);exit;
        }
        if (isset($_FILES['teacher_pic']) && $_FILES['teacher_pic']['size'] > 0) {
            $upload = new Upload();
            $flag = $upload->uploadFile('teacher_pic');
            $data['teacher_pic_name'] = $_FILES['teacher_pic']['name'];
            if (empty($upload->getErrorMsg())) {
                $data['teacher_pic'] = $flag;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        }  else {
            echo $this->encode('1004', $this->msg['1004']);exit;
        }
        $data['province_id'] = $province_id;
        $data['branch_id'] = $branch_id;
        if(empty($teacher_url)){
            echo $this->encode('1009', $this->msg['1009']);exit;
        }else{
            $data['teacher_url'] = $teacher_url;
        }
        $id = Teacher::model()->add_teacher_info($data);
        if($id > 0){
            $this->_save_operate_log('add',$id,'',$data);
            echo $this->encode('Y', $this->msg['1010']);exit;
        }else{
            echo $this->encode('N', $this->msg['1011']);exit;
        }
    }

    /**
     * 删除讲师
     * by:wenlh
     */
    public function actionDel_teacher_info(){
        $id = trim(Yii::app()->request->getParam('id'));
        if(empty($id)){
            echo $this->encode('N', $this->msg['1011']);exit;
        }else{
            $result = Teacher::model()->del_teacher_info($id);
            if($result){
                $this->_save_operate_log('del',$id,array(),array());
                echo $this->encode('Y', '删除成功');exit;
            }else{
                echo $this->encode('N', '删除失败');exit;
            }
        }
    }

    /**
     * 资料列表:包含视频资料及图文资料,在表格中分页签展示,分支账号只可以查看本分支的资料,全国账号可以查看全部资料
     * by:wenlh
     */
    public function actionInformation_index()
    {
        $branch_id = Yii::app()->user->branch_id;
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
        $this->render('information_index', array('provice_option' => $option));
    }

    /**
     * 获取视频列表
     * by:wenlh
     */
    public function actionGet_video_list()
    {
        $search_arr = $this->_get_search_str('video_name');
        $list = LocalVideo::model()->getlist($search_arr[0], $search_arr[1], $search_arr[2], $search_arr[3], $search_arr[4]);
        echo CJSON::encode($list);
    }

    /**
     * 导出资料列表,导出方式为同步导出,图文资料与视频资料写入同一个sheet
     * by:wenlh
     */
    public function actionExport_information(){
        $video_search_arr = $this->_get_search_str('video_name');
        $video_list = LocalVideo::model()->getlist($video_search_arr[0], $video_search_arr[1], $video_search_arr[2], -1, 0)['data'];
        $document_search_arr = $this->_get_search_str('study_document_name');
        $documentlist = StudyDocument::model()->getlist($document_search_arr[0], $document_search_arr[1], $document_search_arr[2], -1, 0)['data'];
        $header = array('资料名称','资料类型','省','市','软件类型','业务类型','推送位置','推送位置序号','创建时间','更新时间',
            '显示状态','图片地址','视频类型','资料地址','价格','简介', '直播开始时间','直播结束时间','讲师名称','创建人',
            '审核总监','备注','观看次数','点赞次数','收藏次数','评论次数', '分享次数');
        $data = array();

        //写入视频资料
        foreach($video_list as $k=>$v){
            switch(intval($v['video_type'])){
                case 1:
                case 2:
                case 5:
                    $preview_page = EHOME . $this->createUrl('onlinestudy/video_view',array('video_id'=> $v['id']));
                    break;
                default:
                    $preview_page = $v['video_src'];
            }
            $img_src = UPLOADURL . $v['video_img'];
            $data[] = array($v['video_name'],$v['is_live_name'],$v['provice_name'],$v['city_name'], $v['software_name'],$v['business_name'],
                $v['position_name'],$v['position_num'],$v['create_time'],$v['update_time'],$v['status_name'],$img_src,
                $v['video_type_name'],$preview_page,$v['price'],$v['detail'],$v['live_begin_time'], $v['live_end_time'],
                $v['teacher_name'],$v['create_user_name'],$v['sh_user_name'],$v['comment'],$v['open_count'],$v['up_count'],
                $v['favourite_count'], $v['reply_count'],$v['share_count'],
                );
        }
        //写入图文资料
        foreach($documentlist as $k=>$v){
            $preview_page = EHOME . $this->createUrl('onlinestudy/document_view',array('document_id'=> $v['id']));
            $img_src = UPLOADURL . $v['document_img'];
            $data[] = array($v['study_document_name'],'图文',$v['provice_name'],$v['city_name'],$v['software_name'],$v['business_name'],
                $v['position_name'],$v['position_num'],$v['create_time'],$v['update_time'],$v['status_name'],$img_src,'',$preview_page,
                $v['price'],$v['detail'],'', '',$v['teacher_name'],$v['create_user_name'],$v['sh_user_name'],$v['comment'],
                $v['open_count'],$v['up_count'],$v['favourite_count'], $v['reply_count'],$v['share_count'],);
        }
        FwUtility::exportExcel($data, $header, '资料列表','在线学习资料导出'.date('Ymd'));
    }

    /**
     * 资料的form,用于新增资料或修改资料(只是一个页面,但是包含两个form,如果修改根据用户选择进行显示或隐藏,如果是修改资料则默
     * 认为原资料类型,且不可以修改)
     * by:wenlh
     */
    public function actionInfomation_form()
    {
        $id = trim(Yii::app()->request->getParam('id'));
        $type = trim(Yii::app()->request->getParam('type'));
        $video_type = LocalVideo::model()->get_video_type();
        $document_type = StudyDocument::model()->documentTypeKey;
        $branch_id = Yii::app()->user->branch_id;
        $branch_info = ServiceRegion::model()->getBranchInfo($branch_id);
        $province_id = ServiceRegion::model()->getRegionIdByBranch($branch_id);
        $region_name = ServiceRegion::model()->getRegionName($province_id);
        $city_list = array();
        //如果$id不为空,则这是一个修改操作获取form,查询出对应的资料信息进行展示
        if (!empty($id)) {
            $video_url = 'Onlinestudy/video_edit';
            $document_url = 'Onlinestudy/document_edit';
            if ($type == 'video') {
                $video_info = LocalVideo::model()->findbypk($id);
                $document_info = array('province_id' => $province_id);
                $default_form_type = 'video';
                $city_list = ServiceRegion::model()->getCityByProvince($video_info->province_id);
            } else {
                $document_info = StudyDocument::model()->findbypk($id);
                $video_info = array('province_id' => $province_id);
                $default_form_type = 'document';
                $city_list = ServiceRegion::model()->getCityByProvince($document_info->province_id);
            }
        } else {
            //新增操作
            $video_url = 'Onlinestudy/video_add';
            $document_url = 'Onlinestudy/document_add';
            $video_info = array('province_id' => $province_id);
            $document_info = array('province_id' => $province_id);
            $default_form_type = '';
        }

        //如果用户的分支ID为QG_BRANCH_ID,则可以将该资料创建到指定省或全国
        if ($branch_id == QG_BRANCH_ID) {
            $province_list = ServiceRegion::model()->getProvinceArr();
            $province_list[$branch_info[0]->region_id] = '全国';
        } else {
            //分支用户只能将资料创建到所属省
            $province_list = array($province_id => $region_name);
            $city_list = empty($city_list) ? ServiceRegion::model()->getCityByProvince($province_id) : $city_list;
        }


        $this->render('information_form', Array('video_info' => $video_info, 'document_info' => $document_info,
            'video_type' => $video_type, 'document_type' => $document_type,
            'video_url' => $video_url, 'document_url' => $document_url, 'province_list' => $province_list,
            'city_list' => $city_list, 'default_form_type' => $default_form_type));
    }

    /**
     * 讲师新建或编辑页面
     * by:wenlh
     */
    public function actionTeacher_form(){
        $id = trim(Yii::app()->request->getParam('id'));
        $province_id = trim(Yii::app()->request->getParam('province_id'));
        //$id为空则是新创建,否则为编辑
        if (!empty($id)) {
            $teacher_info = Teacher::model()->findbypk($id);
            $url = 'Onlinestudy/update_teacher_info';
        } else {
            $teacher_info = array();
            $url = 'Onlinestudy/add_teacher_info';
        }
        $this->render('teacher_form', Array('teacher_info' => $teacher_info, 'url' => $url, 'province_id' => $province_id));
    }

    /**
     * 动态获取省下属的城市列表
     * by:wenlh
     */
    public function actionGet_city_list()
    {
        $province_id = trim(Yii::app()->request->getParam('province_id'));
        $city_list = ServiceRegion::model()->getCityByProvince(substr($province_id,0,2));
        echo CJSON::encode($city_list);
        exit;
    }

    /**
     * 添加视频资料
     * by:wenlh
     */
    public function actionVideo_add()
    {
        try {
            $data = $this->_check_and_get_video_params();
            $id = LocalVideo::model()->localVideoSave($data);
            $msgNo = 'Y';
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        if(!empty($id)){
            $this->_save_operate_log('add',$id,'',$data);
        }
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 编辑视频资料
     * by:wenlh
     */
    public function actionVideo_edit()
    {
        try {
            $data = $this->_check_and_get_video_params();
            $data['id'] = trim(Yii::app()->request->getParam('id'));
            $old_data = LocalVideo::model()->findByPk($data['id'])->attributes;
            $id = LocalVideo::model()->localVideoUpdate($data);
            $msgNo = 'Y';
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        if(!empty($id)){
            $new_data = LocalVideo::model()->findByPk($id)->attributes;
            $this->_save_operate_log('edit',$id,$old_data,$new_data);
        }
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 添加图文资料
     * by:wenlh
     */
    public function actionDocument_add()
    {
        try {
            $data = $this->_check_and_get_document_params();
            $id = StudyDocument::model()->studyDocumentSave($data);
            $msgNo = 'Y';
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        if(!empty($id)){
            $this->_save_operate_log('add',$id,'',$data);
        }
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 编辑图文资料
     * by:wenlh
     */
    public function actionDocument_edit()
    {
        try {
            $data = $this->_check_and_get_document_params();
            $data['id'] = trim(Yii::app()->request->getParam('id'));
            $old_data = StudyDocument::model()->findByPk($data['id'])->attributes;
            $id = StudyDocument::model()->studyDocumentUpdate($data);
            $msgNo = 'Y';
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        if(!empty($id)){
            $new_data = StudyDocument::model()->findByPk($id)->attributes;
            $this->_save_operate_log('edit',$id,$old_data,$new_data);
        }
        echo $this->encode($msgNo, $msg);
    }

    /**
     * 异步获取图文资料列表
     * by:wenlh
     */
    public function actionGet_document_list()
    {
        $search_arr = $this->_get_search_str('study_document_name');
        $list = StudyDocument::model()->getlist($search_arr[0], $search_arr[1], $search_arr[2], $search_arr[3], $search_arr[4]);
        echo CJSON::encode($list);
    }

    /**
     * 动态获取省下属的城市列表
     * by:wenlh
     */
    public function actionGetCityList()
    {
        $criteria = new \CDbCriteria;
        if (!isset($_POST['province_code']) && !isset($_POST['city_code'])) {
            $criteria->compare('is_parent', 0);
        } elseif (isset($_POST['province_code'])) {
            $criteria->compare('region_id>', intval($_POST['province_code']) * 100);
            $criteria->compare('region_id<', intval($_POST['province_code'] + 1) * 100);
        }

        $res = ServiceRegion::model()->findAll($criteria);
        $option = '<option value="0">全部</option>';
        foreach ($res as $val) {
            $option .= '<option value="' . $val['region_id'] . '">' . $val['region_name'] . '</option>';
        }
        echo $option;
    }

    /**
     * 删除资料,包含图文资料和视频资料,支持批量删除,已删除资料在前台及后台都不显示
     * by:wenlh
     */
    public function actionInformation_del(){
        $information_ids = Yii::app()->request->getParam('ids');
        $type_id = Yii::app()->request->getParam('type_id');
        if($type_id == 'video'){
            $count = LocalVideo::model()->updateByPk($information_ids,array('is_delete'=>true,'update_time' => date('Y-m-d H:i:s')));
        }elseif($type_id == 'document'){
            $count = StudyDocument::model()->updateByPk($information_ids,array('is_delete'=>true,'update_time' => date('Y-m-d H:i:s')));
        }
        if($count > 0){
            $model_name = ($type_id == 'video')? OperationLog::$operationLocalVideo : OperationLog::$operationStudyDocument;
            $this->_save_operate_log('del',$information_ids,array(), array(), $model_name);
            echo CJSON::encode(array('ret' => 0, 'message' => '删除成功'));exit;
        }else{
            echo CJSON::encode(array('ret' => -1, 'message' => '删除失败'));exit;
        }
    }

    /**
     * 关闭资料,已关闭资料在前台不显示,在后台显示
     * by:wenlh
     */
    public function actionInformation_close(){
        $information_ids = Yii::app()->request->getParam('ids');
        $type_id = Yii::app()->request->getParam('type_id');
        if($type_id == 'video'){
            $count = LocalVideo::model()->updateByPk($information_ids,array('status'=>0,'update_time' => date('Y-m-d H:i:s')));
        }else{
            $count = StudyDocument::model()->updateByPk($information_ids,array('status'=>0,'update_time' => date('Y-m-d H:i:s')));
        }
        if($count > 0){
            $model_name = ($type_id == 'video')? OperationLog::$operationLocalVideo : OperationLog::$operationStudyDocument;
            $this->_save_operate_log('del',$information_ids,array(), array(), $model_name);
            echo CJSON::encode(array('ret' => 0, 'message' => '关闭成功'));exit;
        }else{
            echo CJSON::encode(array('ret' => -1, 'message' => '关闭失败'));exit;
        }
    }

    public function actionInformation_copy(){
        $information_id = Yii::app()->request->getParam('id');
        $type_id = Yii::app()->request->getParam('type_id');
        if(empty($information_id)){
            echo CJSON::encode(array('ret' => -1, 'message' => '资料源不能为空'));exit;
        }
        if($type_id == 'video'){
            $new_id = LocalVideo::model()->copyLocalVideo($information_id);
        }else{
            $new_id = StudyDocument::model()->copyStudyDocument($information_id);
        }
        if($new_id > 0){
            echo CJSON::encode(array('ret' => 0, 'message' => '复制成功'));exit;
        }else{
            echo CJSON::encode(array('ret' => -1, 'message' => '复制失败'));exit;
        }

    }

    /**
     * 设置资料显示位置页面,根据视频类型判断资料可以显示的位置
     * by:wenlh
     */
    public function actionSet_position_page(){
        $information_id = Yii::app()->request->getParam('id');
        $type_id = Yii::app()->request->getParam('type');
        $this->render('set_position_page',array('type_id' => $type_id, 'information_id' => $information_id));
    }

    /**
     * 设置资料显示位置,
     * by:wenlh
     */
    public function actionSet_position(){
        $information_id = Yii::app()->request->getParam('id');
        $position_type = Yii::app()->request->getParam('position_type');
        $position_num = Yii::app()->request->getParam('position_num');
        if($position_type == 'branch_video'){
            $old_data = LocalVideo::model()->findByPk($information_id)->attributes;
            $count = LocalVideo::model()->set_position($information_id,1,$position_num);
            $new_data = LocalVideo::model()->findByPk($information_id)->attributes;
        }elseif($position_type == 'more_video'){
            $old_data = LocalVideo::model()->findByPk($information_id)->attributes;
            $count = LocalVideo::model()->set_position($information_id,2,$position_num);
            $new_data = LocalVideo::model()->findByPk($information_id)->attributes;
        }else{
            $old_data = StudyDocument::model()->findByPk($information_id)->attributes;
            $count = StudyDocument::model()->set_position($information_id,1,$position_num);
            $new_data = StudyDocument::model()->findByPk($information_id)->attributes;
        }
        if($count == 1){
            $this->_save_operate_log('edit',$information_id,$old_data, $new_data);
            echo CJSON::encode(array('ret' => 0, 'message' => '设置成功'));exit;
        }else{
            echo CJSON::encode(array('ret' => -1, 'message' => '设置失败'));exit;
        }
    }

    /**
     * 统计信息展示页面
     * by:wenlh
     */
    public function actiontongji_page(){
        $information_id = Yii::app()->request->getParam('id');
        $type_id = Yii::app()->request->getParam('type');
        if($type_id == 'video'){
            $info = LocalVideo::model()->findByPk($information_id);
        }else{
            $info = StudyDocument::model()->findByPk($information_id);
        }
        $this->render('tongji_page',array('info' => $info, 'id' => $information_id, 'type' => $type_id));
    }

    public function actionGet_video_view_info(){
        $information_id = Yii::app()->request->getParam('id');
        $type_id = Yii::app()->request->getParam('type');
        $offset = Yii::app()->request->getParam('start');
        $limit = Yii::app()->request->getParam('length');
        $export = Yii::app()->request->getParam('export');
        if(empty($export)){
            if($type_id == 'video'){
                $info = VideoLog::model()->get_info_with_user($information_id,$limit,$offset);
            }else{
                $info = DocumentLog::model()->get_info_with_user($information_id,$limit,$offset);
            }
            echo CJSON::encode($info);exit;
        }else{
            if($type_id == 'video'){
                $info = VideoLog::model()->get_info_with_user($information_id,-1,0);
            }else{
                $info = DocumentLog::model()->get_info_with_user($information_id,-1,0);
            }
            $header = array('用户ID','昵称','用户名','手机号','邮箱','地区','观看时间');
            $data = array();
            foreach($info['data'] as $k=>$v){
                $data[] = array($v['member_user_id'],$v['member_nick_name'],$v['member_user_name'],$v['mobile'],$v['mail'],$v['city_name'],$v['create_time']);
            }
            FwUtility::exportExcel($data, $header, '用户观看列表',$type_id . $information_id . '观看记录' .date('Ymd'));
        }

    }

    /**
     * 评论管理页面
     * by:wenlh
     */
    public function actioncomment_manage(){
        $information_id = Yii::app()->request->getParam('id');
        $type = Yii::app()->request->getParam('type');
        $this->render('comment_list',array('information_id' => $information_id, 'type' => $type));
    }

    /**
     * 异步获取资料对应评论列表
     * by:wenlh
     */
    public function actionget_comment_list(){
        $information_id = Yii::app()->request->getParam('information_id');
        $type = Yii::app()->request->getParam('type');
        $offset = Yii::app()->request->getParam('start');
        $limit = Yii::app()->request->getParam('length');
        if(empty($offset))
            $offset=0;
        if(empty($limit))
            $limit=20;

        if($type == 'video'){
            $infos = VideoComment::model()->getlist(array('video_id'=>$information_id), 'DESC', 'create_time', $limit, $offset);
        }else{
            $infos = DocumentComment::model()->getlist(array('document_id'=>$information_id), 'DESC', 'create_time', $limit, $offset);
        }
        echo CJSON::encode($infos);exit;
    }

    public function actiondel_comment(){
        $comment_id = Yii::app()->request->getParam('id');
        $type = Yii::app()->request->getParam('type');
        if($type == 'video'){
            $result = VideoComment::model()->del_comment($comment_id);
        }else{
            $result = DocumentComment::model()->del_comment($comment_id);
        }
        if($result === true){
            echo $this->encode('Y', '删除成功');exit;
        }else{
            echo $this->encode('N', $result);exit;
        }
    }

    /**
     * 获取查询参数,视频及文档通用,
     * by:wenlh
     */
    private function _get_search_str($name)
    {
        $start_time = trim(Yii::app()->request->getParam('start_time'));
        $end_time = trim(Yii::app()->request->getParam('end_time'));
        $province_id = trim(Yii::app()->request->getParam('province'));
        $search_type = trim(Yii::app()->request->getParam('search_type'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));
        $limit = trim(Yii::app()->request->getParam('length'));
        $offset = trim(Yii::app()->request->getParam('start'));
        $ord = 'desc';
        $field = 'id';
        $offset = !empty($offset) ? $offset : 0;
        $limit = !empty($limit) ? $limit : 20;

        if (!empty($start_time) && !empty($end_time)) {
            $end_time = date("Y-m-d", strtotime("$end_time   +1   day"));
            $con['create_time'] = array('between', $start_time, $end_time);
        }
        if (!empty($search_content) && !empty($search_type)) {
            switch ($search_type) {
                case 1:
                    $con[$name] = array('search_like', $search_content);
                    break;
                case 2:
                    $con['teacher_name'] = array('search_like', $search_content);
                    break;
                case 3:
                    $con['create_user_name'] = array('search_like', $search_content);
                    break;
            }

        }
        if($province_id != QG_BRANCH_ID){
            $con['province_id'] = $province_id;
        }

        $con['is_delete'] = 0;
        $con['branch_id'] = ['not_in',array(JZKT_ID)];
        return array($con, $ord, $field, $limit, $offset);
    }

    /**
     * 获取并检查视频参数,新增or修改
     * by:wenlh
     */
    private function _check_and_get_video_params()
    {
        $video_name = trim(Yii::app()->request->getParam('video_name'));
        $detail = trim(Yii::app()->request->getParam('detail'));
        $video_type = trim(Yii::app()->request->getParam('video_type'));
        $video_src = trim(Yii::app()->request->getParam('video_src'));
        $software_id = trim(Yii::app()->request->getParam('software_id'));
        $business_id = trim(Yii::app()->request->getParam('business_id'));
        $video_img_old = trim(Yii::app()->request->getParam('video_img_old'));
        $price = trim(Yii::app()->request->getParam('price'));
        $teacher_name = trim(Yii::app()->request->getParam('teacher_name'));
        $create_user_name = trim(Yii::app()->request->getParam('create_user_name'));
        $sh_user_name = trim(Yii::app()->request->getParam('sh_user_name'));
        $province_id = trim(Yii::app()->request->getParam('province_id'));
        $city_id = trim(Yii::app()->request->getParam('city_id'));
        $comment = trim(Yii::app()->request->getParam('comment'));
        $live_begin_time = trim(Yii::app()->request->getParam('live_begin_time'));
        $live_end_time = trim(Yii::app()->request->getParam('live_end_time'));
        $service_auth_check = trim(Yii::app()->request->getParam('service_auth_check'));
        if (empty($video_name)) {
            throw new Exception('1001');
        }
        if (empty($detail)) {
            throw new Exception('1002');
        }
        if (empty($video_type)) {
            throw new Exception('1003');
        }
        if (empty($video_src)) {
            throw new Exception('1005');
        }

        $data = array(
            'video_name' => $video_name,
            'detail' => $detail,
            'video_type' => $video_type,
            'video_src' => $video_src,
            'software_id' => $software_id,
            'business_id' => $business_id,
            'price' => $price,
            'teacher_name' => $teacher_name,
            'create_user_name' => $create_user_name,
            'sh_user_name' => $sh_user_name,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'comment' => $comment,
            'live_begin_time' => $live_begin_time,
            'live_end_time' => $live_end_time,
            'service_auth_check' => $service_auth_check,
        );

        if (isset($_FILES['video_img'])) {
            $upload = new Upload();
            $flag = $upload->uploadFile('video_img');
            if (empty($upload->getErrorMsg())) {
                $data['video_img'] = $flag;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        } elseif (!empty($video_img_old)) {
            $data['video_img'] = $video_img_old;
        } else {
            throw new Exception('1004');
        }
        return $data;
    }

    /**
     * 获取并检查图文参数,新增or修改
     * by:wenlh
     */
    private function _check_and_get_document_params()
    {
        $study_document_name = trim(Yii::app()->request->getParam('study_document_name'));
        $detail = trim(Yii::app()->request->getParam('detail'));
        $document_type = trim(Yii::app()->request->getParam('document_type'));
        $software_id = trim(Yii::app()->request->getParam('software_id'));
        $business_id = trim(Yii::app()->request->getParam('business_id'));
        $document_img_old = trim(Yii::app()->request->getParam('document_img_old'));
        $document_src_old = trim(Yii::app()->request->getParam('document_src_old'));
        $document_swf_src_old = trim(Yii::app()->request->getParam('document_swf_src_old'));
        $teacher_name = trim(Yii::app()->request->getParam('teacher_name'));
        $create_user_name = trim(Yii::app()->request->getParam('create_user_name'));
        $sh_user_name = trim(Yii::app()->request->getParam('sh_user_name'));
        $province_id = trim(Yii::app()->request->getParam('province_id'));
        $city_id = trim(Yii::app()->request->getParam('city_id'));
        $comment = trim(Yii::app()->request->getParam('comment'));
        $price = trim(Yii::app()->request->getParam('price'));
        $service_auth_check = trim(Yii::app()->request->getParam('service_auth_check'));

        if (empty($study_document_name)) {
            throw new Exception('1001');
        }
        if (empty($detail)) {
            throw new Exception('1002');
        }


        $data = array(
            'study_document_name' => $study_document_name,
            'detail' => $detail,
            'document_type' => $document_type,
            'software_id' => $software_id,
            'business_id' => $business_id,
            'price' => $price,
            'teacher_name' => $teacher_name,
            'create_user_name' => $create_user_name,
            'sh_user_name' => $sh_user_name,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'comment' => $comment,
            'service_auth_check' => $service_auth_check,
        );

        if (isset($_FILES['document_img'])) {
            $upload = new Upload();
            $flag = $upload->uploadFile('document_img');
            if (empty($upload->getErrorMsg())) {
                $data['document_img'] = $flag;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        } elseif (!empty($document_img_old)) {
            $data['document_img'] = $document_img_old;
        } else {
            throw new Exception('1006');
        }


        if (isset($_FILES['document_src'])) {
            $upload = $this->get_document_upload();
            $flag = $upload->uploadFile('document_src');
            $data['document_name'] = $_FILES['document_src']['name'];
            if (empty($upload->getErrorMsg())) {
                $data['document_src'] = $flag;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        } elseif (!empty($document_src_old)) {
            $data['document_src'] = $document_src_old;
        } else {
            throw new Exception('1007');
        }


        if (isset($_FILES['document_swf_src'])) {
            $upload = $this->get_document_upload();
            $flag = $upload->uploadFile('document_swf_src');
            if (empty($upload->getErrorMsg())) {
                $data['document_swf_src'] = $flag;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        } elseif (!empty($document_swf_src_old)) {
            $data['document_swf_src'] = $document_swf_src_old;
        } else {
            throw new Exception('1008');
        }
        return $data;
    }

    /**
     * 获取文档上传对象,修改文档类型及文件大小限制
     * by:wenlh
     */
    public function get_document_upload()
    {
        $result = new Upload();
        $result->set('allowtype', array('pdf','swf'));
        $result->set('maxsize', 30000000);
        return $result;
    }

    public function actionGet_video_select(){
        $this->render('localvideo_list');
    }

    /**
     * 保存管理员操作日志
     * by:wenlh
     */
    private function _save_operate_log($operate,$relatedId,$old_data='',$new_data='', $model_name = ''){
        $column_id = $this->getAction()->getId();
        $column_name = isset($this->action_info[$column_id]) ? $this->action_info[$column_id] : '在线学习';
        if(empty($model_name)){
            switch($column_id){
                case 'change_dynamic_show':
                    $model_name = OperationLog::$operationStudentDynamicManage;
                    break;
                case 'update_teacher_info':
                case 'add_teacher_info':
                case 'del_teacher_info':
                    $model_name = OperationLog::$operationTeacher;
                    break;
                case 'video_edit':
                case 'video_add':
                    $model_name = OperationLog::$operationLocalVideo;
                    break;
                case 'document_edit':
                case 'document_add':
                    $model_name = OperationLog::$operationStudyDocument;
                    break;
            }
        }


        if(is_array($relatedId)){
            foreach($relatedId as $val){
                OperationLog::addLog($model_name, $operate, $column_name, $val, $old_data, $new_data);
            }
        }else{
            OperationLog::addLog($model_name, $operate, $column_name, $relatedId, $old_data, $new_data);
        }
    }
}