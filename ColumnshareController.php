<?php
use application\models\ColumnShare\ColumnShare;
class ColumnshareController extends Controller {
 
    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '请先登录',
		5 => '参数错误',
        6 => '操作过于频繁'
    );
    
    public function actionShare_statistics(){
        $column_id = intval(Yii::app()->request->getParam( 'column_id' ));
        if(!isset($_GET['iDisplayLength'])){
            $this->render('share_statistics', array('column_id'=>$column_id));exit;
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
        
        
        $con['column_id'] = $column_id;
        $con['column_type'] = 1;
        $list = ColumnShare::model()->getList($con, $ord, $field, $limit, $page);
        //print_r($list);
        echo CJSON::encode($list);
    }
    
    public function actionExcel_data(){
        $column_id = intval(Yii::app()->request->getParam( 'column_id' ));
        $con['column_id'] = $column_id;
        $con['column_type'] = 1;
        $list = ColumnShare::model()->getList($con, 'desc', 'id', 100000, 0);
        $data = array();
        if(!empty($list['data'])){
            foreach( $list['data'] as $v){
                $tmp['id'] = $v['id'].' ';
                $tmp['global_id'] = $v['global_id'].' ';
                $tmp['member_user_id'] = $v['member_user_id'].' ';
                $tmp['member_user_name'] = $v['member_user_name'];
                $tmp['title'] = $v['title'];
                $tmp['share_code'] = $v['share_code'];
                $tmp['share_num'] = $v['share_num'].' ';
                $tmp['amount'] = $v['amount'].' ';
                $tmp['participate_num'] = $v['participate_num'].' ';
                $tmp['participate_sign_num'] = $v['participate_sign_num'].' ';
                $data[] = $tmp;
            }
        }
         
        $header = array('ID','global ID','user ID','用户名','分享活动','分享码','分享次数','分享链接点击次数','报名人次','签到人数');
 
        FwUtility::exportExcel($data, $header,'分享统计','分享统计_'.date('Y-m-d'));
    }
}
