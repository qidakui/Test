<?php

/**
 * This is the model class for table "{{invoice}}".
 *
 * The followings are the available columns in table '{{invoice}}':
 * @property string $id
 * @property string $member_id
 * @property string $inv_payee
 * @property string $tax_number
 * @property string $register_address
 * @property string $register_tel
 * @property string $open_bank
 * @property string $bank_account
 * @property integer $inv_status
 * @property integer $payee_status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Invoice;
class Invoice extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{invoice}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array();
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '流水号',
			'member_id' => '用户id',
			'inv_payee' => '发票抬头',
			'tax_number' => '纳税人识别号',
			'register_address' => '注册地址',
			'register_tel' => '注册电话',
			'open_bank' => '开户银行',
			'bank_account' => '开户银行',
			'inv_status' => '发票类型。0，普通发票；1：增值税专用发票',
			'payee_status' => '抬头类型 0:个人,1:公司',
			'_delete' => '是否已经删除，0 ，否； 1 ，已删除',
			'_create_time' => '创建时间',
			'_update_time' => '修改时间',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Invoice the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
