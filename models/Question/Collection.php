<?php

/**
 * This is the model class for table "{{collection}}".
 *
 * The followings are the available columns in table '{{collection}}':
 * @property integer $id
 * @property integer $question_id
 * @property integer $user_id
 * @property string $collection_time
 */
namespace application\models\Question;
use application\models\Question\Question;
use application\models\Question\UserQuestion;
class Collection extends \CActiveRecord
{
	private $errorMsg = array(
			'F' => '收藏出错,请刷新重试',
			'Y'=> '收藏成功',
	);
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{collection}}';
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
	 * @return Collection the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/*
	 * 判断用户是否已经收藏过此问题
	 * */
	public function getCollectionRecord($question_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('question_id',$question_id);
		$criteria->compare('status',0);
		$collection = self::model()->findAll($criteria);
		if($collection){
		 $isUpdate = Collection::model()->updateAll(array('status'=>1),'question_id=:qid',array(':qid'=>$question_id));
			if($isUpdate){
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
	}
}
