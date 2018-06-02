<?php

/**
 * This is the model class for table "{{title}}".
 *
 * The followings are the available columns in table '{{title}}':
 * @property string $id
 * @property string $title
 * @property string $content
 */
namespace application\models\Question\BigData;
class MemberStatistic extends \CActiveRecord
{
    public $search_count,$question_count,$answer_count,$show_question_count,$lasttime;
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'memberstatistic';
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
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.
    }

    public function getDbConnection() {
        return \Yii::app()->big_data_db;
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Title the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function get_analysis_data(){
        $begin_date = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
        $end_date = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - 1, date("Y")));
        $sql = <<<sql
SELECT
	users.member_user_id,users.trigertime,COALESCE(search.num,0) search_count,COALESCE(question.num,0) question_count,COALESCE(answer.num,0) answer_count,COALESCE(show_question.num,0) show_question_count,last_time.lasttime
FROM
	(((((
		(
			SELECT
				member_user_id,trigertime
			FROM
				memberstatistic
			WHERE
				trigertime >= '$begin_date' and trigertime <= '$end_date'
			GROUP BY
				member_user_id,trigertime
		) AS users
		LEFT JOIN memberstatistic AS search ON users.member_user_id = search.member_user_id and search.fncode = '10001' and search.trigertime >= '$begin_date' and search.trigertime <= '$end_date'
	) LEFT JOIN memberstatistic as question on users.member_user_id = question.member_user_id and question.fncode = '10002' and question.trigertime >= '$begin_date' and question.trigertime <= '$end_date')
	 LEFT JOIN memberstatistic as answer on users.member_user_id = answer.member_user_id and answer.fncode = '10003' and answer.trigertime >= '$begin_date' and answer.trigertime <= '$end_date')
	 LEFT JOIN memberstatistic as show_question on users.member_user_id = show_question.member_user_id and show_question.fncode = '10004' and show_question.trigertime >= '$begin_date' and show_question.trigertime <= '$end_date')
	 LEFT JOIN memberlasttime as last_time on users.member_user_id = last_time.member_user_id and last_time.trigertime >= '$begin_date' and last_time.trigertime <= '$end_date')
sql;


        return self::model()->findAllBySql($sql);
    }

}