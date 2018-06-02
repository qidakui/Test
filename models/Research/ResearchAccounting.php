<?php

/**
 * This is the model class for table "{{research_accounting}}".
 *
 * The followings are the available columns in table '{{research_accounting}}':
 * @property string $id
 * @property string $research_id
 * @property integer $trigger_config
 * @property integer $column_type
 * @property string $trigger_number
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Research;
class ResearchAccounting extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{research_accounting}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('_create_time, _update_time', 'safe'),
			array('trigger_config, column_type', 'numerical', 'integerOnly'=>true),
			array('research_id, trigger_number', 'length', 'max'=>11),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, research_id, trigger_config, column_type, trigger_number, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'research_id' => '调研ID',
			'trigger_config' => '触发设置: 1:查看内容时,2:签到完成后,3:报名完成后',
			'column_type' => '栏目类型: 1:同城活动,2:培训报名',
			'trigger_number' => '发送量',
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
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('research_id',$this->research_id,true);
		$criteria->compare('trigger_config',$this->trigger_config);
		$criteria->compare('column_type',$this->column_type);
		$criteria->compare('trigger_number',$this->trigger_number,true);
		$criteria->compare('_create_time',$this->_create_time,true);
		$criteria->compare('_update_time',$this->_update_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ResearchAccounting the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 新增统计量
         */
        public function addsave($data){
            if(!empty($data)){
                $model = new self();
                $model->research_id     = $data['research_id']; 
                $model->trigger_config  = $data['trigger_config']; 
                $model->trigger_number  = $data['trigger_number']; 
                $model->column_type     = $data['column_type']; 
                $model->_create_time    = date('Y-m-d H:i:s');
                $model->_update_time    = date('Y-m-d H:i:s'); 
                $model->save();
                $id  = $model->primaryKey;
                return $id;
                
            }
        }        
}
