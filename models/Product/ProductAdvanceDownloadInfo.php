<?php

/**
 * This is the model class for table "{{product_advance_download_info}}".
 *
 * The followings are the available columns in table '{{product_advance_download_info}}':
 * @property string $id
 * @property integer $product_id
 * @property string $title
 * @property string $desc
 * @property string $product_pic
 * @property string $download_count
 * @property string $download_url
 * @property string $download_pic
 * @property string $download_type
 * @property string $sort
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Product;
use application\models\Product\Product;
class ProductAdvanceDownloadInfo extends \CActiveRecord
{

	public $downloadTypeKey = array(
			1 => 'PC端',
			2 => '移动端',
	);

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{product_advance_download_info}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(

		);
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
			'product_id' => '产品ID',
			'title' => '下载标题',
			'desc' => '下载描述',
			'product_pic' => '产品图片',
			'download_count' => '下载次数',
			'download_url' => '下载URL地址',
			'download_pic' => '下载二维码地址',
			'download_type' => '下载类型,1:PC端,2:手机端',
			'sort' => '排序',
			'status' => 'Status',
			'_delete' => '是否删除,0:未删除,1:已删除',
			'_create_time' => 'Create Time',
			'_update_time' => 'Update Time',
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
	 * @return ProductAdvanceDownloadInfo the static model class
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
				'condition' => "{$alias}._delete=0",
				'order' => "{$alias}.sort asc",
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

	public function saveRecord($info){
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

	public function find_by_product_id($product_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('product_id', $product_id);
		$criteria->index = 'id';
		return $this->findAll($criteria);
	}

}
