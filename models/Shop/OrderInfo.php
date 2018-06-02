<?php

/**
 * This is the model class for table "{{order_info}}".
 *
 * The followings are the available columns in table '{{order_info}}':
 * @property string $id
 * @property string $order_sn
 * @property string $member_id
 * @property string $invoice_no
 * @property integer $order_status
 * @property integer $is_pay
 * @property string $consignee
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $address
 * @property string $zipcode
 * @property string $tel
 * @property string $mobile
 * @property string $postscript
 * @property integer $inv_id
 * @property string $pay_name
 * @property string $goods_amount
 * @property string $money_paid
 * @property string $integral_money
 * @property string $order_amount
 * @property string $create_time
 * @property string $pay_time
 * @property string $shipping_time
 * @property string $extension_code
 * @property string $pay_note
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Shop;
use OperationLog;
use application\models\Shop\OrderGoods;
use application\models\Shop\PayLog;
use application\models\ServiceRegion;
use application\models\Member\CommonMember;
use application\models\Invoice\Invoice;
class OrderInfo extends \CActiveRecord
{

	public $orderStatusKey = array(
			10 => '未付款',
			11 => '付款中',
			12 => '已支付',
			13 => '支付超时',
			14 => '付款失败',
			20 => '待发货',
			21 => '已发货',
			22 => '已收货',
			23 => '退货',
	);

	public $invoiceTypeKey = array(
			'SF' => '顺丰快递',
			'STO' => '申通快递',
			'YD' => '韵达快递',
			'YTO' => '圆通速递',
			'ZTO' => '中通速递',
			'EMS' => 'EMS',
			'HHTT' => '天天快递',
			'QFKD' => '全峰快递',
	);

	public function tableName()
	{
		return '{{order_info}}';
	}

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array(
				'order_goods' => array(self::HAS_MANY,get_class(OrderGoods::model()), 'order_id', 'join_with_in' => true),
				'pay_log' => array(self::HAS_ONE,get_class(PayLog::model()), 'order_id', 'join_with_in' => true),
				'province_region' => array(self::BELONGS_TO,get_class(ServiceRegion::model()), 'province', 'join_with_in' => true),
				'city_region' => array(self::BELONGS_TO,get_class(ServiceRegion::model()), 'city', 'join_with_in' => true),
				'user' => array(self::BELONGS_TO,get_class(CommonMember::model()), array('member_id', 'primaryKey' => 'member_user_id'), 'join_with_in' => true),
				'inv' => array(self::BELONGS_TO,get_class(Invoice::model()), 'inv_id', 'join_with_in' => true),
		);
	}

	public function attributeLabels()
	{
		return array(
			'id' => '流水号',
			'order_sn' => '订单号，唯一',
			'member_id' => '用户id',
			'invoice_no' => '发货单号',
			'invoice_type' => '快递公司代码',
			'order_status' => '10，未付款；11，付款中；12:已支付;13：支付超时；14: 付款失败;20：待发货；21：已发货;22:已收货；23:退货;',
			'is_pay' => '支付状态；0，未付款；1，已支付',
			'consignee' => '收货人的姓名',
			'province' => '收货人的省份',
			'city' => '收货人的城市',
			'address' => '收货人的详细地址',
			'zipcode' => '收货人的邮编',
			'mobile' => '收货人的手机',
			'postscript' => '订单附言，由用户提交订单前填写',
			'inv_id' => '发票ID，取值表e_payment',
			'pay_name' => '用户选择的支付方式的名称',
			'goods_amount' => '商品总金额',
			'money_paid' => '现金支付金额',
			'integral_money' => '使用广币金额',
			'order_amount' => '应付款金额',
			'create_time' => '订单生成时间',
			'pay_time' => '订单支付时间',
			'shipping_time' => '订单配送时间',
			'extension_code' => '通过活动购买的商品的代号 GROUP_BUY是团购 正常为空',
			'pay_note' => '付款备注',
			'status' => '状态',
			'_delete' => '是否已经删除，0 ，否； 1 ，已删除',
			'_create_time' => '添加时间',
			'_update_time' => '更新时间',
		);
	}

	public function search()
	{

	}


	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public $old_date = '';

	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->_delete = 0;
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				$this->_update_time = date('y-m-d H:m:s');
			}
			return true;
		}else{
			return false;
		}
	}

	protected function afterSave(){
		$column_name = 'OrderInfo';
		if($this->isNewRecord){
			$column_name = '新增OrderInfo';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除OrderInfo';
				$operate = 'del';
			}else{
				$column_name = '编辑OrderInfo';
				$operate = 'edit';
			}
		}
		OperationLog::addLog(OperationLog::$operationOrderInfo, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		$this->old_date = '';
	}

	public function defaultScope()
	{
		return array(
		'condition' => "_delete=0",
		);
	}

	public function createOrderInfo($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
	}

	public function updateOrderInfo($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function deleteOrderInfo(){
		$this->_delete=1;
		return $this->save();
	}

	public function getList($con, $order, $limit=-1, $offset=0, $index=''){
		$criteria = new \CDbCriteria;
		if(!empty($con)){
			foreach ($con as $key => $val) {
				if(is_array($val) && isset($val[0])){
					switch($val[0]){
						case 'search_like':
							$criteria->compare($key, $val[1],true);
							break;
						case 'between':
							$criteria->addBetweenCondition('create_time',$val[1],$val[2]);
							break;
						case 'not_in':
							$criteria->addNotInCondition($key,$val[1]);
							break;
						default:
							$criteria->compare($key, $val);
					}
				}else{
					$criteria->compare($key, $val);
				}
			}
		}
		$criteria->addCondition('(order_sn=999999 or status=1)');
		if(!empty($index))
			$criteria->index = $index;
		$criteria->order = $order;
		$criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
		$criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
		$criteria->with = array('order_goods','pay_log','province_region','city_region', 'user','inv');

		$ret = self::model()->findAll($criteria);
		$count = self::model()->count($criteria);
		foreach($ret as $k => $v){
			$data[$k] = $v->attributes;
			$goods_name = '';
			$goods_attr = '';
			$goods_num = '';

			foreach($v->order_goods as $goods){
				$goods_name .= ($goods->goods_name . '</br>');
				$goods_attr .= ($goods->goods_attr . '</br>');
				$goods_num .= ($goods->goods_number . '</br>');
			}
			$data[$k]['goods_amount'] = sprintf("%.2f", $data[$k]['goods_amount']/100);
			$data[$k]['goods_name'] = substr($goods_name,0,-5);
			$data[$k]['goods_attr'] = substr($goods_attr,0,-5);
			$data[$k]['goods_number'] = substr($goods_num,0,-5);
			$data[$k]['status_name'] = $v->getStatus();
			$data[$k]['invoce_type_name'] = $v->invoiceTypeName();
			$data[$k]['pay_status'] = $v->payStatus();
			$data[$k]['pay_type'] = $v->getPayType();
			$data[$k]['gly_order_id'] = empty($v->pay_log) ? '' : $v->pay_log->gly_order_id;
			$data[$k]['province_name'] = empty($v->province_region) ? '' : $v->province_region->region_name;
			$data[$k]['city_name'] = empty($v->city_region) ? '' : $v->city_region->region_name;
			$data[$k]['member_user_name'] = empty($v->user) ? $v->member_id : $v->user->member_user_name;
			$data[$k]['order_goods'] = array();
			foreach ($v->order_goods as $goods) {
				$data[$k]['order_goods'][] = $goods->attributes;
			}
			$data[$k]['gb_money'] = $v->integral_money/100;
			$data[$k]['real_money'] = $v->money_paid/100;
			$data[$k]['inv_title'] = empty($v->inv) ? '' : $v->inv->inv_payee;
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

	public function getStatus(){
		return $this->orderStatusKey[$this->order_status];
	}

	public function payStatus(){
		return ($this->order_status == 12 || $this->order_status >= 20) ? '已支付' : $this->orderStatusKey[$this->order_status];
	}

	public function invoiceTypeName(){
		$name = '';
		if(!empty($this->invoiceTypeKey[$this->invoice_type]))
			$name = $this->invoiceTypeKey[$this->invoice_type];
		return $name;
	}

	public function getPayType(){
		if(!empty($this->integral_money) && !empty($this->money_paid)){
			return '混合支付';
		}elseif(!empty($this->integral_money)){
			return '广币支付';
		}elseif(!empty($this->money_paid)){
			return '现金支付';
		}else{
			return '未知支付方式';
		}
	}

	public function searchOrder($content){
		$result = array();
		$criteria = new \CDbCriteria;
		$criteria->compare('order_sn', $content);
		$order = self::model()->find($criteria);
		if(!empty($order))
			array_push($result, $order->id);
		$criteria = new \CDbCriteria;
		$criteria->addSearchCondition('goods_name', $content);
		$order_goods = OrderGoods::model()->findAll($criteria);
		if(!empty($order_goods)){
			foreach($order_goods as $v){
				array_push($result, $v->order_id);
			}
		}
		return $result;
	}

}
