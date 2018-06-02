<?php

/**
 * This is the model class for table "{{question_user}}".
 *
 * The followings are the available columns in table '{{question_user}}':
 * @property string $id
 * @property integer $member_user_id
 * @property integer $search_count
 * @property integer $question_count
 * @property integer $answer_count
 * @property integer $m_level
 * @property integer $r_level
 * @property string $last_operate_time
 * @property string $last_update_level_time
 * @property string $_update_time
 * @property string $_create_time
 */
namespace application\models\Question;
use application\models\Member\CommonMember;
use application\models\User\UserBrief;
class QuestionUser extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{question_user}}';
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
				'member' => array(self::HAS_ONE,get_class(CommonMember::model()), array('member_user_id' => 'member_user_id')),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
				'id' => 'ID',
				'member_user_id' => '用户ID',
				'search_count' => '搜索次数',
				'question_count' => '提问次数',
				'answer_count' => '回答次数',
				'm_level' => 'm等级',
				'r_level' => 'r等级',
				'last_operate_time' => '最后操作时间',
				'last_update_r_level_time' => 'R等级最后更新时间',
				'last_update_m_level_time' => 'M等级最后更新时间',
				'last_update_info_time' => '最后更新用户信息时间',
				'_update_time' => '更新时间',
				'_create_time' => '创建时间',
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
	 * @return QuestionUser the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				$this->_update_time = date('y-m-d H:m:s');
			}
			return true;
		}else{
			return false;
		}
	}

	public function find_by_member_user_id($member_user_id){
		return self::model()->findByAttributes(array('member_user_id' => $member_user_id));
	}


	public function get_list($con, $order='', $limit=-1, $offset=0){
		$alias = $this->getTableAlias(false,false);
		$criteria = new \CDbCriteria;
		if(!empty($con)){
			foreach ($con as $key => $val) {
				$key = "$alias.$key";
				if(is_array($val) && isset($val[0])){
					switch($val[0]){
						case 'search_like':
							$criteria->compare($key, $val[1],true);
							break;
						case 'between':
							$criteria->addBetweenCondition($key,$val[1],$val[2]);
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
		if(!empty($order))
			$criteria->order = $order;
		$criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
		$criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
		$criteria->with='member';
		$criteria->index='member_user_id';
		$ret = self::model()->findAll($criteria);
		$count = self::model()->count($criteria);
		$user_ids = array_keys($ret);
		$users = $this->get_user($user_ids);
		$empty_member = new CommonMember();
		$empty_user = new UserBrief();
		foreach($ret as $k => $v){
			if(empty($v['member']))
				$v['member'] = $empty_member;
			if(empty($users[$k])){
				$user = $empty_user;
				$register_day_count = '0天';
			}else{
				$user = $users[$k];
				$register_day_count = intval((strtotime(date("Y-m-d 00:00:00")) - strtotime($user->nRegisterTime))/86400) . '天';
			}
			$v['member']->onlinetime = intval($v['member']->onlinetime / 86400) . '天';

			$data[] = array_merge($user->attributes,$v['member']->attributes,$v->attributes,array('register_day_count' => $register_day_count));
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

	public function get_user($user_ids){
		if(empty($user_ids))
			return array();
		$criteria = new \CDbCriteria;
		$criteria->compare('UIN',$user_ids);
		$criteria->index='UIN';
		return UserBrief::model()->findAll($criteria);
	}

}
