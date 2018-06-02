<?php

/**
 * This is the model class for table "{{user_similarity}}".
 *
 * The followings are the available columns in table '{{user_similarity}}':
 * @property string $id
 * @property integer $question_id
 * @property string $title
 * @property integer $similarity_id
 * @property integer $answer_id
 * @property integer $is_get
 * @property integer $_delete
 * @property string $_get_time
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Similarity;
use application\models\Common\CommonMember;
use application\models\Question\Answer;
use application\models\Question\UserQuestion;
use application\models\Question\Question;
use application\models\User\UserBrief;
class UserSimilarity extends \CActiveRecord {

    private $pushKey = array(
            '1' => '被采纳',
            '2' => '点赞高',
            '3' => '随机',
    );

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{user_similarity}}';
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
    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return UserSimilarity the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    protected function beforeSave() {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->_delete = 0;
                $this->_update_time = date('y-m-d H:m:s');
                $this->_create_time = date('y-m-d H:m:s');
            } else {
                $this->_update_time = date('y-m-d H:m:s');
            }
            return true;
        } else {
            return false;
        }
    }

    public function createRecord($info) {
        $model = new self();
        foreach ($info as $k => $v) {
            $model->$k = $v;
        }
        $model->save();
        return $model;
    }

    public function updateRecord($info) {
        foreach ($info as $k => $v) {
            $this->$k = $v;
        }
        $this->save();
        return $this;
    }

    //查询单条
    public function getOne($con = array(), $nums = null) {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
        }
        $ret = !empty($nums) ? self::model()->findAll($criteria) : self::model()->find($criteria);
        return $ret;
    }

    public function getList($con, $orderBy, $order, $limit, $offset) {
        $questionArr =  array();
        $userArr =  array();
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
        }
        if (!empty($orderBy) && !empty($order)) {
            $criteria->order = sprintf('%s %s', $order, $orderBy); //排序条件
        }
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        
        $userIds = columnToArr($ret, 'answer_id');
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('UIN', $userIds);
        $userObj = UserBrief::model()->findAll($criteria);
        foreach($userObj as $user){
            $userArr[$user->UIN] = $user->attributes;
        }
        $questionIds = columnToArr($ret, 'question_id');
        
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('id', $questionIds);
        $questionObj = Question::model()->findAll($criteria);
        foreach($questionObj as $item){
            $questionArr[$item->id] = $item->attributes;
        }
        $similarityIds = columnToArr($ret, 'similarity_id');
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('id', $similarityIds);
        $similarityObj = Question::model()->findAll($criteria);
        foreach($similarityObj as $item){
            $similarityArr[$item->id] = $item->attributes;
        }
        foreach ($ret as $k => $v) {
            $data[$k] = $v->attributes;
            $data[$k]['push_name']         = !empty($this->pushKey[$data[$k]['push_status']]) ? $this->pushKey[$data[$k]['push_status']] : '';
            $data[$k]['question_name']    = !empty($questionArr[$data[$k]['question_id']]['title']) ? $questionArr[$data[$k]['question_id']]['title'] : ''; //问题
            $data[$k]['similarity_name']    = !empty($similarityArr[$data[$k]['similarity_id']]['title']) ? $similarityArr[$data[$k]['similarity_id']]['title'] : ''; //相似问题
            $data[$k]['user_name']       = !empty($userArr[$data[$k]['answer_id']]['UserName']) ? $userArr[$data[$k]['answer_id']]['UserName'] : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);
    }

}
