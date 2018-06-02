<?php

/**
 * This is the model class for table "{{guide}}".
 *
 * The followings are the available columns in table '{{guide}}':
 * @property string $id
 * @property string $name
 * @property integer $branch_id
 * @property integer $view_count
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Guide;
use application\models\ServiceRegion;
class Guide extends \CActiveRecord
{
	public $statusKey = array(
			1 => '上线',
			2 => '下线'
	);

	protected function beforeSave()
	{
		if (parent::beforeSave()) {
			if ($this->isNewRecord) {
				$this->_delete = 0;
				$this->status = 1;
				$this->view_count = 0;
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

	public function defaultScope()
	{
		$alias = $this->getTableAlias(false, false);
		return array(
				'condition' => "{$alias}._delete=0"
		);
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

	public function saveRecord($info)
	{
		foreach ($info as $k => $v) {
			$this->$k = $v;
		}
		$this->save();
		return $this;
	}

	public function create_or_update_record($info)
	{
		if (isset($info['id'])) {
			$id = $info['id'];
			unset($info['id']);
		}
		if (!empty($id)) {
			$record = $this->findByPk($id);
			if (empty($record)) {
				return false;
			}
			$record->updateRecord($info);
			return $record->id;
		} else {
			$addid = $this->createRecord($info);
			return $addid;
		}
	}

	public function deleteRecordByPK($id)
	{
		$record = $this->findByPk($id);
		if (!empty($record)) {
			$record->_delete = true;
			$record->save();
			return $record;
		}
		return false;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{guide}}';
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
			'id' => 'ID',
			'name' => '向导页名称',
			'branch_id' => '分支ID',
			'view_count' => '浏览次数',
			'status' => '状态,1:下线,2:上线',
			'_delete' => '是否删除',
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
	 * @return Guide the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function getStatus(){
		return $this->statusKey[$this->status];
	}

	public function getList($con, $order='id desc', $limit=-1, $offset=0, $index=''){
		$criteria = new \CDbCriteria;
		if(!empty($con)){
			foreach ($con as $key => $val) {
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
		if(!empty($index))
			$criteria->index = $index;
		$criteria->order = $order;
		$criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
		$criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

		$ret = self::model()->findAll($criteria);
		$count = self::model()->count($criteria);
		$province_list = ServiceRegion::model()->getProvinceArr();
		foreach($ret as $k => $v){
			$data[$k] = $v->attributes;
			$data[$k]['status_name'] = $v->getStatus();
			$data[$k]['branch_name'] = isset($province_list[$v->branch_id]) ? $province_list[$v->branch_id] : '';
			$data[$k]['show_link'] = EHOME . '/index.php?r=guide/index&id=' . $v->id;

		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}
}
