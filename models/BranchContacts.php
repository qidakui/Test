<?php

/**
 * This is the model class for table "{{branch_contacts}}".
 *
 * The followings are the available columns in table '{{branch_contacts}}':
 * @property string $id
 * @property integer $branch_id
 * @property string $title
 * @property string $e_title
 * @property string $address
 * @property string $e_address
 * @property integer $zip_code
 * @property string $telephone
 * @property string $facsimile
 * @property string $email
 * @property string $map_point
 * @property integer $_status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models;
class BranchContacts extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{branch_contacts}}';
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
			'id' => 'ID',
			'branch_id' => '分支ID',
			'title' => '标题',
			'e_title' => '英文标题',
			'address' => '地址',
			'e_address' => '英文地址',
			'zip_code' => '邮编',
			'telephone' => '电话',
			'facsimile' => '传真',
			'email' => '邮箱',
			'map_point' => '百度地图坐标',
			'status' => '状态(预留)',
			'_delete' => '是否删除,0:未删除,1:已删除',
			'_create_time' => '创建时间',
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
	 * @return BranchContacts the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

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

	public function defaultScope()
	{
		$alias = $this->getTableAlias(false,false);
		return array(
				'condition' => "{$alias}._delete=0"
		);
	}

	public function createRecord($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
		return $model;
	}

	public function updateRecord($info){
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
		return $this;
	}

	public function create_or_update_record($info){
		if(isset($info['id'])){
			$id = $info['id'];
			unset($info['id']);
		}
		if(!empty($id)){
			$record = $this->findByPk($id);
			if(empty($record)){
				return false;
			}
			$record->updateRecord($info);
			return $record;
		}else{
			$this->createRecord($info);
			return $this;
		}
	}

	public function deleteRecordByPK($id){
		$record = $this->findByPk($id);
		if(!empty($record)){
			$record->_delete = true;
			$record->save();
			return $record;
		}
		return false;
	}

	public function find_by_branch_id($branch_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('branch_id', $branch_id);
		return $this->find($criteria);
	}

}
