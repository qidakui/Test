<?php

use application\models\Home\Information;
use application\models\Home\InformationCat;
use application\models\Activity\ActivityComment;
use application\models\ServiceRegion;

class InformationController extends Controller {

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '参数错误',
        1001 => '标题错误',
        1002 => '详细内容不能为空',
        1003 => '请选择分支',
        1004 => '请输入作者',
        1005 => '分类名称不能为空',
        1006 => '分类已存在',
        1007 => '分类不存在',
        1008 => '请先删除此分类下的子分类',
        1009 => '此分类下有课程存在，不可删除',
        1010 => '请正确选择分类',    
        1011 => '分类不能为空',    
        
    );
    public $user_id;
    public $user_name;
    public $filiale_id;

    public function init() {
        parent::init();
        $this->user_id = Yii::app()->user->user_id;
        $this->user_name = Yii::app()->user->user_name;
        $this->filiale_id = Yii::app()->user->branch_id;
    }

    public function actionindex() {
        $this->render('information_list');
    }

    public function actionInformation_add() {
        $tcon['_delete'] = 0;   
        $tcon['parent_id'] = 0;
        $categoryInfo = InformationCat::model()->getlist($tcon, 'desc', 'sort', 200, 0);
        $this->render('information_add',array('categoryInfo'=>$categoryInfo));
    }

    public function actionInformation_list() {

        $title = trim(Yii::app()->request->getParam('title'));
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
        if (Yii::app()->user->branch_id != BRANCH_ID) {
            $branchId = Yii::app()->user->branch_id;
            $con['branch_id'] = $branchId;
        }

        $con['_delete'] = 0;
        $list = Information::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    public function actionInformation_add_op() {
        try {
            $title = trim(Yii::app()->request->getParam('title'));
            $link = trim(Yii::app()->request->getParam('link'));
            $abstract = trim(Yii::app()->request->getParam('abstract'));
            $information_desc = trim(Yii::app()->request->getParam('information_desc'));
            $branch_id = Yii::app()->user->branch_id;
            $sort = trim(Yii::app()->request->getParam('sort'));
            $author = trim(Yii::app()->request->getParam('author'));
            $category_id = intval(Yii::app()->request->getParam( 'type_parent_id' ));
            if (empty($title))
                throw new Exception('1001');
            if (empty($information_desc))
                throw new Exception('1002');
            if (empty($branch_id))
                throw new Exception('1003');
            if (empty($author))
                throw new Exception('1004');
            if(empty($category_id))
                 throw new Exception('1011');
            $data = array(
                'title' => $title,
                'link' => $link,
                'abstract' => $abstract,
                'branch_id' => $branch_id,
                'information_desc' => $information_desc,
                'author' => $author,
                'sort' => $sort,
                'cat_id' =>$category_id,
            );
            $addId = Information::model()->InformationSave($data);
            if ($addId) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationInformation, 'add', '资讯管理', $addId, array(), $data);
            } else {
                throw new Exception('1');
            }
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actioninformation_edit() {
        $id = trim(Yii::app()->request->getParam('id'));
        $id = is_numeric($id) ? intval($id) : 0;
        $info = Information::model()->findbypk($id);
        $tcon['_delete'] = 0;   
        $tcon['parent_id'] = 0;        
        $categoryInfo = InformationCat::model()->getlist($tcon, 'desc', 'sort', 200, 0);
        if (Yii::app()->user->branch_id == BRANCH_ID) {
            $cityList = ServiceRegion::model()->getBranchList();
        } else {
            $branchId = Yii::app()->user->branch_id;
            $cityList = ServiceRegion::model()->getBranchInfo($branchId);
        }
        $this->render('information_edit', array('info' => $info, 'cityList' => $cityList,'categoryInfo'=>$categoryInfo));
    }

    public function actionInformation_edit_op() {
        try {
            $title = trim(Yii::app()->request->getParam('title'));
            $link = trim(Yii::app()->request->getParam('link'));
            $abstract = trim(Yii::app()->request->getParam('abstract'));
            $information_desc = trim(Yii::app()->request->getParam('information_desc'));
            $branch_id = Yii::app()->user->branch_id;
            $sort = trim(Yii::app()->request->getParam('sort'));
            $author = trim(Yii::app()->request->getParam('author'));
            $category_id = intval(Yii::app()->request->getParam( 'type_parent_id' ));
            $id = trim(Yii::app()->request->getParam('information_id'));
            if (empty($title)) {
                throw new Exception('1001');
            }
            if (empty($branch_id)) {
                throw new Exception('1003');
            }
            if (empty($author)) {
                throw new Exception('1004');
            }
            if (empty($author))
                throw new Exception('1004');
            if(empty($category_id))
                 throw new Exception('1011');            
            $oldData = Information::model()->findbypk($id);
            $oldData = $oldData->attributes;
            $data = array(
                'title' => $title,
                'link' => $link,
                'abstract' => $abstract,
                'branch_id' => $branch_id,
                'information_desc' => $information_desc,
                'author' => $author,
                'sort' => $sort,
                'cat_id' =>$category_id,
            );
            $InformationId = Information::model()->informationUpdate($id, $data);
            if ($InformationId) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationInformation, 'edit', '资讯管理', $InformationId, $oldData, $data);
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actioninformation_del() {
        try {
            $id = Yii::app()->request->getParam('id');
            if (empty($id)) {
                throw new Exception('1006');
            }
            $model = Information::model()->findbypk($id);
            $model->_delete = 1;
            $flag = $model->save();
            if ($flag) {
                $msgNo = 'Y';
                OperationLog::addLog(OperationLog::$operationInformation, 'del', '资讯管理', $id, array(), array());
            } else {
                throw new Exception('1');
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actioninformationRelease() {
        try {
            $information_id = intval(Yii::app()->request->getParam('information_id'));
            $status = intval(Yii::app()->request->getParam('status'));
            if (empty($information_id))
                throw new Exception('1');
            if (empty($status))
                throw new Exception('1');
            $informationInfo = Information::model()->findByPk($information_id);
            if (empty($informationInfo))
                throw new Exception('1');
            $flag = Information::model()->informationUpdate($information_id, array('status' => $status,'start_time'=>date('Y-m-d H:i:s')));
            if ($flag) {
                $this->msg['Y'] = '操作成功';
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

    //资讯评论列表
    public function actioninformationComment() {

        $id = intval(Yii::app()->request->getParam('id'));
        if (!isset($_GET['iDisplayLength'])) {
            $this->render('information_comment', array('id' => $id));
            exit;
        }
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'desc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $con = array('_delete' => 0, 'activity_id' => $id, 'status' => 4);
        $list = ActivityComment::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    //删除评论
    public function actioncomment_del() {
        $msgNo = 'Y';
        $ids = Yii::app()->request->getParam('ids');
        $informationi_id = intval(Yii::app()->request->getParam('id'));
        $Pinformationobj = Information::model()->findByPk($informationi_id, array('select' => array('branch_id')));
        $branch_id = $Pinformationobj['branch_id'];
        try {
            if (!is_array($ids)) {
                throw new Exception('4');
            }
            $del = ActivityComment::model()->updateByPk(array_keys($ids), array('_delete' => 1), 'status=4');
            if (!$del) {
                throw new Exception('1');
            }
            //评论是否被删完 删完要还要删除redis中的用户user_id 就当他没评论过
            //$count = ActivityComment::model()->getCount(array('activity_id'=>$activity_id,'user_id'=>$this->user_id,'_delete'=>0));
            //$test=Yii::app()->cache->set("abc" ,"1234567");
            //记录日志
            foreach ($ids as $id => $user_id) {
                CreditLog::addCreditLog(CreditLog::$craditInformation, CreditLog::$typeKey[11], $informationi_id, 'subtract', '删除评论', $user_id, $branch_id);
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    public function actionInformationCat() {
        $category_info = InformationCat::model()->get_list();
        $this->render('information_cat', array('category_info' => $category_info));
    }

    //新建一级分类、新建子分类、修改分类，id为空时新增,否则为修改
    public function actionCreate_or_edit_category() {
        $msgNo = 'Y';
        try {
            $id = trim(Yii::app()->request->getParam('id'));
            $parent_id = trim(Yii::app()->request->getParam('parent_id'));
            $category_name = trim(Yii::app()->request->getParam('category_name'));
            $sort = Yii::app()->request->getParam('sort');
            if (empty($category_name)) {
                throw new Exception(1005);
            }
            $data = array('filiale_id' => $this->filiale_id, 'parent_id' => $parent_id, 'name' => $category_name, '_delete' => 0);
            if (empty($id)) {
                //查询分类名称是否已存在
                $count = InformationCat::model()->getCount($data);
                if ($count) {
                    throw new Exception(1006);
                }
                if ($sort === '') {
                    $sort = InformationCat::model()->get_big_sort($parent_id);
                }
                $data['sort'] = $sort;
                $insert = InformationCat::model()->createCategory($data);
                if (!$insert) {
                    throw new Exception(1);
                }
            } else {
                $data['id!'] = $id;
                $count = InformationCat::model()->getCount($data);
                if ($count) {
                    throw new Exception(1006);
                }
                $category = InformationCat::model()->findByPk($id);
                if (empty($category)) {
                    throw new Exception(1007);
                } else {
                    $up = $category->updateCategory(array('name' => $category_name, 'sort' => $sort));
                    if (!$up) {
                        throw new Exception(1);
                    }
                }
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    //删除
    public function actionDel_category() {
        $msgNo = 'Y';
        try {
            $id = intval(Yii::app()->request->getParam('id'));
            if (empty($id)) {
                throw new Exception(3);
            }
            $category = InformationCat::model()->findByPk($id);
            if ($category['parent_id'] == 0) { //.一级分类
                $CategoryCount = InformationCat::model()->getCount(
                        array(
                            'parent_id' => $id,
                            'filiale_id' => $this->filiale_id,
                            '_delete' => 0));
                if ($CategoryCount) {
                    throw new Exception(1008);
                } else {
                    $informationCount = Information::model()->getCount(array('cat_id' => $id));
                    if ($informationCount) {
                        throw new Exception(1009);
                    }
                    $up = $category->updateCategory(array('_delete' => 1));
                    if (!$up) {
                        throw new Exception(1);
                    }
                }
            } else { //二级分类
                $productCount = Information::model()->getCount(array('cat_id' => $id));
                if ($productCount) {
                    throw new Exception(1009);
                }
                $up = $category->updateCategory(array('_delete' => 1));
                if (!$up) {
                    throw new Exception(1);
                }
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    /*
     * 获取分类
     */
    public function actionGetGcmcType(){
        $tcon['_delete'] = 0;   
        $tcon['parent_id'] = Yii::app()->request->getParam( 'id' );
        $gcmctype = InformationCat::model()->getlist($tcon, 'desc', 'sort', 200, 0);
        echo CJSON::encode($gcmctype);
    }
}
