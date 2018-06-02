<?php

/**
 * This is the model class for table "{{credits_template}}".
 *
 * The followings are the available columns in table '{{credits_template}}':
 * @property string $id
 * @property string $name
 * @property integer $experience
 * @property integer $credits
 * @property integer $day_max_experience
 * @property integer $day_max_credits
 * @property integer $status
 * @property integer $last_operate_user
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\CreditsTemplate;
use OperationLog;
class CreditsTemplate extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{credits_template}}';
	}

	public $statusKey = array(
			1 => '启用',
			0 => '禁用'
	);

	public $nameKey = array(
			'submit_issue' => '答疑解惑:提交问题的用户',
			'submit_questions' => '答疑解惑:回答问题的用户',
			'accept' => '答疑解惑:被提问者采纳的用户',
			'accept_ta' => '答疑解惑:采纳他人答案的提问用户',
			'money' => '答疑解惑:提问给赏金（尚未开发）',
			'remark' => '全站:发表评论的用户',
			'sign' => '全站:现场签到的用户',
			'collect' => '全站:收藏任意页面的用户',
			'give' => '全站:点赞任意页面的用户',
			'share' => '全站:分享任意页面的用户',
			'research' => '全站:参与调研的用户',
			'firsttime_login' => '日常操作:首次登陆的用户',
			'everyday_login' => '日常操作:每日登陆的用户',
			'expert_apply' => '答疑解惑:申请专家成功的用户',
			'expert_upgrade' => '答疑解惑:专家等级升级的用户',
			'product_accept' => '广联达产品:提建议的用户',
			'inform' => '答疑解惑:举报成功的用户',
			'inform_reported' => '答疑解惑:被举报成功的用户',
			'ask_delete' => '答疑解惑:被删除提问的提问者',
			'question_noaccept_delete' => '答疑解惑:删除问题后答案未被采纳的回答用户',
			'question_accept_delete' => '答疑解惑:删除问题后答案已被采纳的回答用户'
	);

	protected function beforeSave()
	{
		if (parent::beforeSave()) {
			if ($this->isNewRecord) {
				$this->status = 1;
				$this->last_operate_user = \Yii::app()->user->user_name;
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			} else {
				$this->last_operate_user = \Yii::app()->user->user_name;
				$this->_update_time = date('y-m-d H:m:s');
				OperationLog::addLog(OperationLog::$operationCreditsTemplate , 'edit', '修改模板',$this->id,
						CreditsTemplate::model()->findByPk($this->id)->attributes, $this->attributes);
			}
			return true;
		} else {
			return false;
		}
	}

	public function createRecord($info)
	{
		$model = new self();
		foreach ($info as $k => $v) {
			$model->$k = $v;
		}
		$model->save();
		$id = $model->primaryKey;
		return $id;
	}

	public function updateRecord($info)
	{
		foreach ($info as $k => $v) {
			$this->$k = $v;
		}
		$this->save();
		return $this;
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
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
			'id' => 'ID',
			'name' => '类型名称',
			'experience' => '经验值',
			'credits' => '积分',
			'day_max_experience' => '单日最大经验值',
			'day_max_credits' => '单日最大积分',
			'status' => '状态',
			'last_operate_user' => '最后操作人',
			'_create_time' => '创建时间',
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
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return CreditsTemplate the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getList(){
		$ret = self::model()->findAll();
		$count = self::model()->count();
		foreach($ret as $k => $v){
			$data[$k] = $v->attributes;
			$data[$k]['status_name'] = $v->getStatusName();
			$data[$k]['type_name'] = $v->getTypeName();
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

	public function getStatusName(){
		return $this->statusKey[$this->status];
	}

	public function getTypeName(){
		return isset($this->nameKey[$this->name]) ? $this->nameKey[$this->name] : '未定义';
	}

	public function reloadTemplateRedis(){
		$templates = self::model()->findAll();
		foreach($templates as $template){
			$info = array();
			\Yii::app()->redis->getClient()->delete('credits_template_' . $template->name,serialize($info));
			if($template->status == 1){
				$info['experience'] = $template->experience;
				$info['credits'] = $template->credits;
				$info['day_max_experience'] = $template->day_max_experience;
				$info['day_max_credits'] = $template->day_max_credits;
			}
			\Yii::app()->redis->getClient()->set('credits_template_' . $template->name,serialize($info));
		}
	}
}
