<?php

/**
 * This is the model class for table "{{category}}".
 *
 * The followings are the available columns in table '{{category}}':
 * @property string $id
 * @property string $category_name
 * @property string $parent_id
 * @property integer $sort_order
 * @property string $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Shop;

use OperationLog;
use application\models\Shop\Goods;
class Category extends \CActiveRecord
{

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{category}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
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
			'parent_category' => array(self::BELONGS_TO,get_class(self::model()), 'parent_id', 'join_with_in' => true),
			'child_category_count' => array(self::STAT,get_class(self::model()),'parent_id'),
			'goods_num' => array(self::STAT,get_class(Goods::model()), 'category_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'id',
			'category_name' => '分类名称',
			'parent_id' => '父ID',
			'sort_order' => '分类排序',
			'status' => '状态',
			'_delete' => '是否删除',
			'_create_time' => '添加时间',
			'_update_time' => '更新时间',
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
	 * @return Category the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public $old_date = '';

	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->sort_order = 0;
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
		$column_name = '商城分类';
		if($this->isNewRecord){
			$column_name = '新增商城分类';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除商城分类';
				$operate = 'del';
			}else{
				$column_name = '编辑商城分类';
				$operate = 'edit';
			}
		}
		OperationLog::addLog(OperationLog::$operationCategory, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		$this->old_date = '';
	}

	public function defaultScope()
	{
		return array(
				'condition' => "status=1 and _delete=0",
		);
	}

	public function createCategory($category){
		$model = new self();
		foreach($category as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
	}

	public function updateCategory($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function logical_delete(){
		$this->_delete=1;
		return $this->save();
	}

	public function getAllCategory(){
		$result = array();
		$categorys = self::model()->findAll();
		foreach($categorys as $c){
			$result[]= array('id'=> $c->id,'parent_id' => $c->parent_id, 'category_name' => $c->category_name);
		}
		return $result;
	}

	public function getParentCategory($id){
		$parent_id = self::model()->findByPk($id)->parent_id;
		return self::model()->findByPk(intval($parent_id));
	}

	public function getChildCategory($parent_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('parent_id', $parent_id);
		$criteria->index = 'id';
		return self::findAll($criteria);
	}

}
