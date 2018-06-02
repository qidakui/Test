<?php
use application\models\Search\SearchKeyword;
use application\models\ServiceRegion;
class KeywordController extends Controller{
    
    
    public function actionindex(){
        $this->render('keyword_list');
    }
    public function actionKeyword_list(){
        $keyword              = trim(Yii::app()->request->getParam( 'keyword' ));
        $province            = trim(Yii::app()->request->getParam( 'province' ));
        $limit              = trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page               = trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index              = trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord                = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field              = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord                = !empty($ord) ? $ord : 'desc';
        $field              = !empty($field) ? $field : 'id';
        $page		    = !empty($page) ? $page : 0;
        $limit		    = !empty($limit) ? $limit : 20;
        if(!empty($keyword)){
            $con['keyword'] = $keyword;
        }else{
            $con = array();   
        }
        $list = SearchKeyword::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }    
}

