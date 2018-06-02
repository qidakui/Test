<?php

/**
 * This is the model class for table "{{similarity_match}}".
 *
 * The followings are the available columns in table '{{similarity_match}}':
 * @property string $id
 * @property integer $question_id
 * @property string $similarity_id
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */

namespace application\models\Similarity;
use application\models\Common\CommonMember;
use application\models\Question\Answer;
use application\models\Question\UserQuestion;
use application\models\Question\Question;
use application\models\User\UserBrief;
use application\models\Similarity\UserSimilarity;
class SimilarityMatch extends \CActiveRecord {

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '参数错误',
    );
    private $allocation_type = array(
            0=>'未分配',
            1=>'已分配'
    );
    private $acquire_type = array(
            0=>'未领取',
            1=>'已领取',
            2=>'已退回'
    );
    
    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{similarity_match}}';
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
     * @return SimilarityMatch the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    protected function beforeSave() {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->_delete = 0;
                $this->_update_time = date('Y-m-d H:i:s');
                $this->_create_time = date('Y-m-d H:i:s');
            } else {
                $this->_update_time = date('Y-m-d H:i:s');
                $this->_draw_time = date('Y-m-d H:i:s');
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

    /**
     * desc:返回列表
     * author:besttaowenjing@163.com
     * date:2016-07-13
     * select参数为字段名字
     */
    public function getList($con, $orderBy, $order, $limit, $offset) {
        $questionArr = array();
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
        
//        $matchids = columnToArr($ret, 'id');
//        $criteria = new \CDbCriteria;
//        $criteria->addInCondition('match_id', $matchids);
//        $userSimiinfo = UserSimilarity::model()->findAll($criteria);
//        print_r($userSimiinfo);exit;
//        foreach($userObj as $user){
//            $userArr[$user->UIN] = $user->attributes;
//        }
        foreach ($ret as $k => $v) {
             $data[$k] = $v->attributes;
             $data[$k]['allocation_name']         = !empty($this->allocation_type[$data[$k]['is_allocation']]) ? $this->allocation_type[$data[$k]['is_allocation']] : '';
             $data[$k]['acquire_name']            = !empty($this->acquire_type[$data[$k]['is_acquire']]) ? $this->acquire_type[$data[$k]['is_acquire']] : '';
             $data[$k]['similarity']              = !empty($data[$k]['similarity_id']) ? implode(",",unserialize($data[$k]['similarity_id'])): '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);        
    }

    /**
     * 修改信息
     */
    public function edit_match($match_id = null, $attributes) {
        $editres = $this->updateByPk($match_id, $attributes);
        return $editres;
    }

}
