<?php
use application\models\ExportTask\ExportTask;
class DownloadController extends Controller{

    
    
    public function actionindex(){  
        $this->render('index');
     }
     public function actionDownloadList(){       
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord            = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field          = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord            = !empty($ord) ? $ord : 'asc';
        $field          = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $con = array();
        $list = ExportTask::model()->getlist($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);                 
     }
     /**
      * 导出模板
      */
     public function actionDowdnloadView(){
        $starttime = trim(Yii::app()->request->getParam( 'starttime'));
        $endtime = trim(Yii::app()->request->getParam( 'endtime'));
        $province_code = trim(Yii::app()->request->getParam( 'province_code'));         
        $category_id = trim(Yii::app()->request->getParam( 'category_id'));         
        $title = trim(Yii::app()->request->getParam( 'title'));
        $status = trim(Yii::app()->request->getParam( 'status'));
        $this->render('export_view',array('starttime'=>$starttime,'endtime'=>$endtime,'province_code'=>$province_code,'title'=>$title,'category_id'=>$category_id,'status' => $status));
     }
}
