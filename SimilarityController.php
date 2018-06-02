<?php

use application\models\Similarity\SimilarityMatch;
use application\models\Similarity\UserSimilarity;
use application\models\Similarity\SimilarityWhiteList;

class SimilarityController extends Controller {

    /**
     * 派单列表
     */
    public function actionindex() {
        if (!isset($_GET['iDisplayLength'])) {
            $this->render('index');
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
        $starttime = trim(Yii::app()->request->getParam('starttime'));
        $endtime = trim(Yii::app()->request->getParam('endtime'));
        $search_type = trim(Yii::app()->request->getParam('search_type'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));
        if ($starttime) {
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime . ' 23:59:59' : $starttime . ' 23:59:59';
        }
        if ($search_type == 'question_id') {
            $con['question_id'] = $search_content; 
        }
        $con['delivery_type'] = 1;
        $con['_delete'] = 0;
        $list = UserSimilarity::model()->getList($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }

    /**
     * 池塘列表
     */
    public function actionpondList() {
        if (!isset($_GET['iDisplayLength'])) {
            $this->render('pondlist');
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
        $starttime = trim(Yii::app()->request->getParam('starttime'));
        $endtime = trim(Yii::app()->request->getParam('endtime'));
        $search_type = trim(Yii::app()->request->getParam('search_type'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));        
        if ($starttime) {
            $con['_create_time>'] = $starttime;
            $con['_create_time<'] = $endtime ? $endtime . ' 23:59:59' : $starttime . ' 23:59:59';
        }
        if ($search_type == 'question_id') {
            $con['question_id'] = $search_content; 
        }        
        $con['_delete'] = 0;
        $list = SimilarityMatch::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);        
    }

}
