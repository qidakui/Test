<?php

/**
 * This is the model class for table "{{follow}}".
 *
 * The followings are the available columns in table '{{follow}}':
 * @property integer $id
 * @property integer $follower_id
 * @property integer $followed_id
 * @property string $follow_time
 * @property integer $status
 */
namespace application\models\Question;
class Adopt extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{adopt}}';
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
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Follow the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function updateStatus($con = array()){
		$criteria = new \CDbCriteria;
		foreach ($con as $key => $val) {
			$criteria->compare($key, $val);
		}
		$adoptModel = self::model()->find($criteria);
		if($adoptModel){
			$adoptModel->_deleted =1;
			$adoptModel->_update_time =  date('Y-m-d H:i:s');
			if($adoptModel->save()){
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}

	}

}
