<?php

/**
 * This is the model class for table "{{order_goods}}".
 *
 * The followings are the available columns in table '{{order_goods}}':
 * @property string $id
 * @property string $order_id
 * @property string $goods_id
 * @property string $goods_name
 * @property integer $goods_number
 * @property string $market_price
 * @property string $goods_price
 * @property string $goods_attr
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Shop;
use OperationLog;
class OrderGoods extends \CActiveRecord
{
	public function tableName()
	{
		return '{{order_goods}}';
	}

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array();
	}

	public function attributeLabels()
	{
		return array(
			'id' => '订单商品信息自增id',
			'order_id' => '订单商品信息对应的详细信息id，取值order_info的order_id',
			'goods_id' => '商品的的id',
			'goods_name' => '商品的名称',
			'goods_number' => '商品的购买数量',
			'market_price' => '商品的市场售价',
			'goods_price' => '商品的本店售价',
			'goods_attr' => '购买该商品时所选择的属性',
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
				$this->status = 1;
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
		$column_name = 'OrderGoods';
		if($this->isNewRecord){
			$column_name = '新增OrderGoods';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除OrderGoods';
				$operate = 'del';
			}else{
				$column_name = '编辑OrderGoods';
				$operate = 'edit';
			}
		}
		OperationLog::addLog(OperationLog::$operationOrderGoods, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		$this->old_date = '';
	}

	public function defaultScope()
	{
		return array(
		'condition' => "_delete=0",
		);
	}

	public function createOrderGoods($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
	}

	public function updateOrderGoods($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function deleteOrderGoods(){
		$this->_delete=1;
		return $this->save();
	}

}
