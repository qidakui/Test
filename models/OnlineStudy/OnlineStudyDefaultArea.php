<?php

/**
 * This is the model class for table "{{online_study_default_area}}".
 *
 * The followings are the available columns in table '{{online_study_default_area}}':
 * @property string $id
 * @property integer $branch_id
 * @property integer $is_show_all
 * @property integer $status
 * @property integer $is_delete
 * @property string $_update_time
 * @property string $_create_time
 */
namespace application\models\OnlineStudy;
use OperationLog;
class OnlineStudyDefaultArea extends \CActiveRecord
{
	public function tableName()
	{
		return '{{online_study_default_area}}';
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
			'id' => 'ID',
			'branch_id' => '分支ID',
			'is_show_all' => '是否显示全部',
			'status' => '状态',
			'_delete' => '是否删除',
			'_update_time' => '更新时间',
			'_create_time' => '创建时间',
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

	public function defaultScope()
	{
		return array(
		'condition' => "_delete=0",
		);
	}

	public function createOnlineStudyDefaultArea($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
	}

	public function updateOnlineStudyDefaultArea($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function deleteOnlineStudyDefaultArea(){
		$this->_delete=1;
		return $this->save();
	}

	public function is_show_all_by_branch_id($branch_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('branch_id', $branch_id);
		$ret = self::model()->find($criteria);
		return empty($ret) || $ret->is_show_all;
	}

	public function change_show_by_branch_id($branch_id, $is_show_all){
		$criteria = new \CDbCriteria;
		$criteria->compare('branch_id', $branch_id);
		$model = self::model()->find($criteria);
		if(empty($model))
			$model = new self();
		$model->branch_id = $branch_id;
		$model->is_show_all = $is_show_all == 'true';
		$model->save();
		$id = $model->primaryKey;
		return $id;
	}

}
