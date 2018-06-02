<?php

/**
 * This is the model class for table "{{goods_attr}}".
 *
 * The followings are the available columns in table '{{goods_attr}}':
 * @property string $id
 * @property string $goods_id
 * @property string $goods_attr_name
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Shop;
use OperationLog;
use application\models\Shop\GoodsPic;
class GoodsAttr extends \CActiveRecord
{
	public function tableName()
	{
		return '{{goods_attr}}';
	}

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array(
				'goods_pic' => array(self::HAS_MANY,get_class(GoodsPic::model()), 'goods_attr_id', 'join_with_in' => true),
				'goods_info' => array(self::HAS_MANY,get_class(GoodsInfo::model()), 'goods_attr_id', 'join_with_in' => true),
		);
	}

	public function attributeLabels()
	{
		return array(
			'id' => 'id',
			'goods_id' => '商品ID',
			'goods_attr_name' => '商品属性名称',
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
				if($this->old_date == $this->attributes){
					return false;
				}
				$this->_update_time = date('y-m-d H:m:s');
			}
			return true;
		}else{
			return false;
		}
	}

	protected function afterSave(){
		$column_name = 'GoodsAttr';
		if($this->isNewRecord){
			$column_name = '新增GoodsAttr';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除GoodsAttr';
				$operate = 'del';
			}else{
				$column_name = '编辑GoodsAttr';
				$operate = 'edit';
			}
		}
		OperationLog::addLog(OperationLog::$operationGoodsAttr, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		$this->old_date = '';
	}

	public function defaultScope()
	{
		return array(
		'condition' => "_delete=0",
		);
	}

	public function createGoodsAttr($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
		return $model;
	}

	public function updateGoodsAttr($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
		return $this;
	}

	public function deleteGoodsAttr(){
		$this->_delete=1;
		return $this->save();
	}

	public function getAttrByGoodsId($goods_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('goods_id', $goods_id);
		$criteria->order = 'id';
		$criteria->index = 'id';
		return self::model()->findAll($criteria);
	}

}
