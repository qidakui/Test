<?php

/**
 * This is the model class for table "{{menu_filiale}}".
 *
 * The followings are the available columns in table '{{menu_filiale}}':
 * @property string $id
 * @property string $menu_id
 * @property string $filiale_id
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Menu;
class MenuFiliale extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{menu_filiale}}';
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
			array('menu_id', 'length', 'max'=>11),
			array('filiale_id', 'length', 'max'=>10),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, menu_id, filiale_id, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'menu_id' => '菜单id',
			'filiale_id' => '分之id',
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
		$criteria->compare('menu_id',$this->menu_id,true);
		$criteria->compare('filiale_id',$this->filiale_id,true);
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
	 * @return MenuFiliale the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 新增导航分支关系
         */
        public function saveFiliale($saveFiliale,$addinfo){
            foreach ($addinfo as $key=>$item){
                $model = new self();
                $model->menu_id           = $saveFiliale;
                $model->filiale_id        = $item;
                $model->_create_time    = date('Y-m-d H:i:s');
                $model->_update_time    = date('Y-m-d H:i:s');
                $model->save();
            }
            return true;
        }
}
