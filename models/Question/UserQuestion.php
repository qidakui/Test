<?php

/**
 * This is the model class for table "{{user_question}}".
 *
 * The followings are the available columns in table '{{user_question}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $global_id
 * @property integer $answer_count
 * @property integer $answer_good_count
 * @property string $nick
 * @property integer $answer_adopted
 * @property integer $reply_question_count
 * @property integer $question_count
 * @property integer $adopt_reply
 * @property integer $collected_count
 * @property integer $followed_count
 * @property integer $is_expert
 * @property integer $total_followed_count
 * @property string $_create_time
 * @property string $_update_time
 */

namespace application\models\Question;
class UserQuestion extends \CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{user_question}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array();
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
    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return UserQuestion the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

   public function getUserQuestion($con=array()){
       $criteria = new \CDbCriteria;
       if(!empty($con)){
           foreach($con as $key => $val){
               $criteria->compare($key, $val);
           }
       }
       $ret = self::model()->find($criteria);
       return empty($ret)?array():$ret;
   }

    public function getUserQuestionObjByUIds($userIds){
        $criteria = new \CDbCriteria();
        $criteria->addInCondition('user_id', $userIds);
        $userQuestionObj = UserQuestion::model()->findAll($criteria);
        return !empty($userQuestionObj) ? $userQuestionObj : array();
    }
    public function expertUpdateCommon($data){
        $user_id    = !empty($data['user_id']) ? $data['user_id'] : 0;
        $model = self::model()->find('user_id=:user_id', array('user_id' => $user_id));
        if(empty($model)){
            return false;
        }
        foreach($data as $key => $val){
            $model->$key = $val;
        }
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function updateExpert($data){
        $user_id = !empty($data['user_id']) ? $data['user_id'] : 0;
        $is_expert = isset($data['is_expert']) ? $data['is_expert'] : 1;
        $model = self::model()->find('user_id=:user_id', array('user_id' => $user_id));
        if(empty($model)){
            $model = new self();
        }
        $model->user_id           = $user_id;
        $model->is_expert      = $is_expert;
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function setIsExpert($user_id){
        $userQuestionData = array(
            'is_expert'  => 1,
            'user_id'       => $user_id,
        );
        return self::model()->updateExpert($userQuestionData);
    }

    public function cancleIsExpert($user_id){
        $userQuestionData = array(
            'is_expert'  => 0,
            'user_id'       => $user_id,
        );
        return self::model()->updateExpert($userQuestionData);
    }

}
