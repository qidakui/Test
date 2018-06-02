<?php
/**
 * 客服管理
 * @author hd
 */
use application\models\Course\OrderCourseInfo;
class OrderCourseController extends Controller{

    public function actionIndex(){
        $order = new OrderCourseInfo();
        $this->render('index',array('order' => $order));
    }

    public function actionGet_order_course_list(){
        $start_time = trim(Yii::app()->request->getParam( 'start_time' ));
        $end_time = trim(Yii::app()->request->getParam( 'end_time' ));
        $order_status = trim(Yii::app()->request->getParam( 'order_status' ));
        $course_id = trim(Yii::app()->request->getParam( 'course_id' ));
        $global_id = trim(Yii::app()->request->getParam( 'global_id' ));
        $is_export = trim(Yii::app()->request->getParam( 'is_export' ));

        //处理查询条件
        $con = array();
        if(!empty($start_time) && !empty($end_time)){
            $end_time = date("Y-m-d",strtotime("$end_time +1 day"));
            $con['add_time'] = array('between', $start_time, $end_time);
        }
        if(!empty($order_status))
            $con['order_status'] = $order_status;
        if(!empty($course_id))
            $con['course_id'] = $course_id;
        if(!empty($global_id))
            $con['global_id'] = $global_id;

        //处理分页
        $limit = trim(Yii::app()->request->getParam('length'));
        $offset = trim(Yii::app()->request->getParam('start'));
        if(empty($limit))
            $limit = 20;
        if(empty($offset))
            $offset = 0;


        //判断导出还是查询
        if(empty($is_export)){
            $order_list = OrderCourseInfo::model()->getList($con,'id desc',$limit,$offset);
            echo CJSON::encode($order_list);exit;
        }else{
            $order_list = OrderCourseInfo::model()->getList($con,'id desc');
            $header = array('ID', '订单号', '用户GlobalID', '课程ID', '课程名称', '课程原价', '折扣价格', '支付价格',
                '订单状态', '广联云交易号', '广联云订单ID', '建筑课堂订单ID', '订单生成时间', '订单支付时间', '是否同步');
            $data = array();
            foreach ($order_list['data'] as $order) {
                $data[]= array($order["id"],$order["order_sn"],$order["global_id"],$order["course_id"],
                    $order["course_name"],$order["course_amount"],$order["coupon_amount"],$order["amount"],
                    $order["order_status"],$order["global_tradeno"],$order["global_order_id"],$order["course_order_id"],
                    $order["add_time"],$order["pay_time"],$order["is_sync"]);
            }
            FwUtility::exportExcel($data, $header, '订单列表','订单列表'.date('Ymd'));
        }

    }
}

