<?php

/**
 * This is the model class for table "{{user_research}}".
 *
 * The followings are the available columns in table '{{user_research}}':
 * @property string $id
 * @property string $research_id
 * @property string $column_id
 * @property integer $issue_id
 * @property string $answer_content
 * @property integer $member_id
 * @property string $member_ip
 * @property string $member_area
 * @property integer $column_type
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Research;
class UserResearch extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{user_research}}';
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
			array('issue_id, member_id, column_type', 'numerical', 'integerOnly'=>true),
			array('research_id, column_id', 'length', 'max'=>11),
			array('answer_content', 'length', 'max'=>240),
			array('member_ip', 'length', 'max'=>60),
			array('member_area', 'length', 'max'=>120),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, research_id, column_id, issue_id, answer_content, member_id, member_ip, member_area, column_type, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'research_id' => '调研ID',
			'column_id' => '栏目ID',
			'issue_id' => '问题id',
			'answer_content' => '回答内容',
			'member_id' => '用户标识id',
			'member_ip' => '用户IP',
			'member_area' => '用户所在地区名称',
			'column_type' => '栏目类型: 1:同城活动,2:培训报名',
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
		$criteria->compare('research_id',$this->research_id,true);
		$criteria->compare('column_id',$this->column_id,true);
		$criteria->compare('issue_id',$this->issue_id);
		$criteria->compare('answer_content',$this->answer_content,true);
		$criteria->compare('member_id',$this->member_id);
		$criteria->compare('member_ip',$this->member_IP,true);
		$criteria->compare('member_area',$this->member_area,true);
		$criteria->compare('column_type',$this->column_type);
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
	 * @return UserResearch the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
    
    /*
     * 统计调研人次
     */
	 public function getResearchMemberNum($column_type, $column_ids, $trigger_config=array()){
		if(is_array($column_ids)){
			$count = \Yii::app()->db->createCommand()
				->select("sum(c.v) total")
				->from("(select count( distinct u.member_id) v from e_research as r left join e_user_research as u on u.research_id=r.id where u.column_id in(".implode(',',$column_ids).") and u.column_type=".$column_type." and r.trigger_config in(".implode(',',$trigger_config).") group by u.column_id) c")
				->queryRow();
		}elseif(is_numeric($column_ids)){
			$sql = 'u.column_id ='.$column_ids.' AND u.column_type='.$column_type.' AND r.trigger_config in('.implode(',',$trigger_config).')';
			$count = \Yii::app()->db->createCommand()
				->select("count( distinct u.member_id) total")
				->from("e_research as r")
				->leftJoin('e_user_research as u', 'u.research_id=r.id')
				->where($sql)
				->queryRow();
		}
        
        return (isset($count['total']) && !empty($count['total'])) ? $count['total'] : 0;
    }

	/*
     * 调研人数
     */
    public function getResearchDistinctMemberNum($column_type, $column_ids, $trigger_config=array() ){
        $sql = 'u.column_id in('.implode(',',$column_ids).') AND u.column_type='.$column_type.' AND r.trigger_config in('.implode(',',$trigger_config).')';
        $count = \Yii::app()->db->createCommand()
            ->select("count( distinct u.member_id) total")
            ->from("e_research as r")
            ->leftJoin('e_user_research as u', 'u.research_id=r.id')
            ->where($sql)
            ->queryRow();
        return (isset($count['total']) && !empty($count['total'])) ? $count['total'] : 0;
    }
}
