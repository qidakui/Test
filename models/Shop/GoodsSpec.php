<?php

/**
 * This is the model class for table "{{goods_spec}}".
 *
 * The followings are the available columns in table '{{goods_spec}}':
 * @property string $id
 * @property string $goods_id
 * @property string $goods_spec_name
 * @property string $goods_price
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Shop;
use OperationLog;
use application\models\Shop\GoodsAttr;
class GoodsSpec extends \CActiveRecord
{
	public function tableName()
	{
		return '{{goods_spec}}';
	}

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array(
				'goods_info' => array(self::HAS_MANY,get_class(GoodsInfo::model()), 'goods_spec_id', 'join_with_in' => true),
		);
	}

	public function attributeLabels()
	{
		return array(
			'id' => 'id',
			'goods_id' => '商品ID',
			'goods_spec_name' => '商品规格名称',
			'goods_price' => '商品价格',
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
		$column_name = 'GoodsSpec';
		if($this->isNewRecord){
			$column_name = '新增GoodsSpec';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除GoodsSpec';
				$operate = 'del';
			}else{
				$column_name = '编辑GoodsSpec';
				$operate = 'edit';
			}
		}
		OperationLog::addLog(OperationLog::$operationGoodsSpec, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		$this->old_date = '';
	}

	public function defaultScope()
	{
		return array(
		'condition' => "_delete=0",
		);
	}

	public function createGoodsSpec($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
	}

	public function updateGoodsSpec($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function deleteGoodsSpec(){
		$this->_delete=1;
		return $this->save();
	}

	public function getSpecByGoodsId($goods_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('goods_id', $goods_id);
		$criteria->order = 'id';
		$criteria->index = 'id';
		return self::model()->findAll($criteria);
	}

}
