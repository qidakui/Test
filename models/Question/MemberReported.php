<?php

namespace application\models\Question;
use application\models\Member\CommonMember;
class MemberReported extends \CActiveRecord
{
    private $typeKey = array(
        '1' => '问题',
        '2' => '答案',
    );

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{member_reported}}';
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

    public function getlist($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        //举报用户
        $memberUserIds   = columnToArr($ret, 'member_user_id');

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('member_user_id', $memberUserIds);
        $commonMemberObj = CommonMember::model()->findAll($criteria);

        $commonMemberArr = objectToKeywordArr($commonMemberObj, 'member_user_id', 'member_user_name');

        //被举报用户
        $reportedUserIds = columnToArr($ret, 'reported_user_id');

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('member_user_id', $reportedUserIds);
        $reportedMemberObj = CommonMember::model()->findAll($criteria);

        $reportedMemberArr = objectToKeywordArr($reportedMemberObj, 'member_user_id', 'member_user_name');

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['member_user_name']   = !empty($commonMemberArr[$data[$k]['member_user_id']]) ? $commonMemberArr[$data[$k]['member_user_id']] : '';
            $data[$k]['reported_user_name'] = !empty($reportedMemberArr[$data[$k]['reported_user_id']]) ? $reportedMemberArr[$data[$k]['reported_user_id']] : '';
            $data[$k]['type_name']          = !empty($this->typeKey[$data[$k]['type']]) ? $this->typeKey[$data[$k]['type']] : '';
            if($data[$k]['type'] == 1){
                $questionObj = Question::model()->find('id=:id', array('id' => $data[$k]['reported_id']));
                $data[$k]['title'] = !empty($questionObj) ? $questionObj->title : '';
            } elseif($data[$k]['type'] == 2){
                $answerObj = Answer::model()->findbypk($data[$k]['reported_id']);
                $data[$k]['title'] = !empty($answerObj) ? \CHtml::decode($answerObj->content) : '';
            }
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

}
