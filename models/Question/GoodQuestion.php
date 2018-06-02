<?php

/**
 * This is the model class for table "{{good_question}}".
 *
 * The followings are the available columns in table '{{good_question}}':
 * @property integer $id
 * @property integer $question_id
 * @property integer $user_id
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Question;
class GoodQuestion extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{good_question}}';
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
	 * @return GoodQuestion the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	public function changeGoodQuestionStatus($questionId){
		$criteria = new \CDbCriteria;
		$criteria->compare('question_id',$questionId);
		$ret = self::model()->find($criteria);
		if($ret){
			$isGoodQuestion = true;
			$isUpdate = GoodQuestion::model()->updateAll(array('_deleted'=>1),$criteria);
			if($isUpdate){
				$is_update = true;
			}else{
				$is_update = false;
			}
		}else{
			$isGoodQuestion = false;
			$is_update = true;
		}
		return array('is_update'=>$is_update,'is_good_question'=>$isGoodQuestion);
	}
}
