<?php

/**
 * This is the model class for table "{{pay_log}}".
 *
 * The followings are the available columns in table '{{pay_log}}':
 * @property string $id
 * @property string $order_id
 * @property string $gly_order_id
 * @property string $order_amount
 * @property integer $order_type
 * @property integer $is_paid
 */
namespace application\models\Shop;
use OperationLog;
class PayLog extends \CActiveRecord
{
	public function tableName()
	{
		return '{{pay_log}}';
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
			'id' => '支付记录自增id流水号',
			'order_id' => '订单ID',
			'gly_order_id' => '广联云订单ID',
			'order_amount' => '支付金额',
			'order_type' => '支付类型；0，订单支付；',
			'is_paid' => '是否已支付，0，否；1，是',
		);
	}

	public function search()
	{

	}

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
