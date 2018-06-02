<?php
/**
 * Created by PhpStorm.
 * User: xinggx
 * Date: 2016/6/23
 * Time: 9:17
 */
namespace application\models\Question;
use application\models\ServiceRegion;
use application\models\User\UserBrief;
use application\models\Question\GoodQuestion;
class Question extends \CActiveRecord
{
    public $minute_5_answer,$minute_15_answer,$minute_30_answer,$hour_1_answer,$hour_24_answer,$hour_48_answer,
        $hour_72_answer,$hour_72_no_answer,$question_count,$analysis_question_count,$analysis_answer_time;

    private $questionStatus = array(
        '1' => '未采纳',
        '2' => '已采纳',
        '3' => '赏广币'
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{question}}';
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
        return array();
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array();
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
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
     * @return Admin the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }


    //查询列表
    public function getQuestionList($con, $orderBy, $order, $limit = null, $offset = null){
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                if(is_array($val) && isset($val[0])){
                    switch($val[0]){
                        case 'search_like':
                            $criteria->compare($key, $val[1],true);
                            break;
                        case 'between':
                            $criteria->addBetweenCondition('_create_time',$val[1],$val[2]);
                            break;
                        case 'not_in':
                            $criteria->addNotInCondition($key,$val[1]);
                            break;
                        default:
                            $criteria->compare($key, $val);
                    }
                }else{
                    $criteria->compare($key, $val);
                }
            }
        }

        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }

        if($limit != null || $offset != null){
            $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
            $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        }

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);

        $userIds = columnToArr($ret, 'user_id');
        $userArr = UserBrief::model()->getQuestionUserArr($userIds);

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['new_title'] = trim(htmlspecialchars_decode($data[$k]['title']));
            $data[$k]['new_description'] = trim(strip_tags(htmlspecialchars_decode($data[$k]['description'])));
            $getName = getCategoryName($v['category_id']);
            $data[$k]['category_name']  = !empty($getName)? $getName['son']:'';
            $data[$k]['status_name']    = !empty($this->questionStatus[$data[$k]['status']])? $this->questionStatus[$data[$k]['status']] : '';
            $data[$k]['UserName']       = isset($userArr[$data[$k]['user_id']])? (!empty($userArr[$data[$k]['user_id']]['UserName'])?$userArr[$data[$k]['user_id']]['UserName']:$userArr[$data[$k]['user_id']]['Nick']) : $data[$k]['user_id'];
            $data[$k]['sMobile']        = isset($userArr[$data[$k]['user_id']]['sMobile'])? $userArr[$data[$k]['user_id']]['sMobile'] : $data[$k]['user_id'];
            $data[$k]['email']          = isset($userArr[$data[$k]['user_id']]['email'])? $userArr[$data[$k]['user_id']]['email'] : $data[$k]['user_id'];
            $data[$k]['area']           = !empty($data[$k]['area_code']) ? (($data[$k]['area_code'] == -1) ? '全国': ServiceRegion::model()->getRegionName($data[$k]['area_code'])):'';
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

    public function getQuestion($question_id){
        if(empty($question_id)){
            return array();
        }
        $criteria = new \CDbCriteria;
        $criteria->compare('id',$question_id );
        $ret = self::model()->find($criteria);
        return !empty($ret)?$ret:array();
    }
    
    public function delQuestion($question_id){
        //补充删除问题扣减积分
        $isGoodQuestion = false;
        $model = Question::model()->find('id=:id', array('id' => $question_id));
        $model->_deleted = 1;
        $model->_update_time = date('Y-m-d H:i:s');
        $user_id = $model->user_id;
        $transaction = $model->dbConnection->beginTransaction();
        if($model->save()){
            $answerObj = Answer::model()->delQuestionAnswer($question_id,$user_id);
            if($answerObj){
                $userQuestionObj = UserQuestion::model()->getUserQuestion(array('user_id'=>$user_id));
                if($userQuestionObj){
                    $userQuestionObj->question_count--;
                    $userQuestionObj->_update_time =date('Y-m-d H:i:s');
                    if($userQuestionObj->save()){
                        $collectionObj = Collection::model()->getCollectionRecord($question_id);
                        if($collectionObj){
                            $changeGoodQuestion = GoodQuestion::model()->changeGoodQuestionStatus($question_id);
                            if($changeGoodQuestion['is_update']){
                                $isGoodQuestion = $changeGoodQuestion['is_good_question'];
                                $transaction->commit();
                                //惩罚积分操作
                                $flag = \FwUtility::informCredit('question','ask_delete',$question_id,'subtract','提问被删除:'.$model->title,$model->user_id);
                                $msgNo = 'true';
                            }else{
                                $transaction->rollBack();
                                $msgNo = 'false';
                            }
                        }else{
                            $transaction->rollBack();
                            $msgNo = 'false';
                        }
                    }else{
                        $transaction->rollBack();
                        $msgNo = 'false';
                    }
                }else{
                    $transaction->commit();
                    $msgNo = 'true';
                }
            }else{
                $transaction->commit();
                $msgNo = 'true';
            }
        }else{
            $transaction->rollBack();
            $msgNo = 'false';
        }
        $data = array('data'=>$msgNo,'isGoodQuestion'=>$isGoodQuestion,'questionUserId'=>$user_id);
        return $data;
    }
    /**
     * 获取批量导出总的记录条数
     */
    public function getExportTotal($con){
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                if(is_array($val) && isset($val[0])){
                    switch($val[0]){
                        case 'search_like':
                            $criteria->compare($key, $val[1],true);
                            break;
                        case 'between':
                            $criteria->addBetweenCondition('_create_time',$val[1],$val[2]);
                            break;
                        case 'not_in':
                            $criteria->addNotInCondition($key,$val[1]);
                            break;
                        default:
                            $criteria->compare($key, $val);
                    }
                }else{
                    $criteria->compare($key, $val);
                }
            }
            $total = self::model()->count($criteria);
            return $total;
        }       
    }
    /**
     * 获取分类树
     */
    public function getCateName(){
        $newarray = array();
        foreach (\Yii::app()->params['first_category'] as $key=>$item){
            if(!empty(\Yii::app()->params['category'][$key])){
                $newarray[$key]['name']=  $item;
                $newarray[$key]['son'] = \Yii::app()->params['category'][$key];
            }
        }
        return $newarray;
    }

}