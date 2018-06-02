<?php

/**
 * This is the model class for table "{{product_advance_son_template}}".
 *
 * The followings are the available columns in table '{{product_advance_son_template}}':
 * @property string $id
 * @property integer $template_id
 * @property integer $product_id
 * @property integer $type
 * @property integer $animation_type
 * @property string $title
 * @property string $sub_title
 * @property string $desc
 * @property integer $font_color
 * @property string $background_son_pic
 * @property integer $sort
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Product;
class ProductAdvanceSonTemplate extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{product_advance_son_template}}';
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
		// @todo Please modify the following code to remove attributes that should not be searched.
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ProductAdvanceSonTemplate the static model class
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
                    ProductAdvanceTemplate::model()->updateByPk($info['template_id'],array('is_son_pic'=>2));   
                    return $this;
		}
	}
 	public function find_by_product_id($template_id,$product_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('product_id', $product_id);
		$criteria->compare('template_id', $template_id);
		$criteria->index = 'id';
		return $this->findAll($criteria);
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
}
