<?php

/**
 * This is the model class for table "{{app_version}}".
 *
 * The followings are the available columns in table '{{app_version}}':
 * @property string $id
 * @property integer $app_type
 * @property string $download_url
 * @property string $version
 * @property integer $force_update
 * @property integer $status
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\App;
class AppVersion extends \CActiveRecord
{

	public $appTypeInfo = array(
			1 => 'android',
			2 => 'ios'
	);

	public $forceUpdateInfo = array(
			0 => '否',
			1 => '是'
	);

	public $statusName = array(
		0 => '下线',
		1 => '上线'
	);

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{app_version}}';
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
			'id' => 'ID',
			'app_type' => 'APP类型,1:android,2ios',
			'download_url' => '下载地址',
			'version' => '版本号,用来判断是否最新版本',
			'force_update' => '是否强制更新,0:不强制更新,1:强制更新',
			'status' => '状态,0:下线,1:上线',
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
	 * @return AppVersion the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->status = 1;
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				$this->_update_time = date('y-m-d H:m:s');
			}
			if($this->status == 1){
				self::model()->updateAll(array('status'=>0),'app_type = :app_type',array(':app_type'=>$this->app_type));
			}
			return true;
		}else{
			return false;
		}
	}

	public function getStatusName(){
		return $this->statusName[$this->status];
	}

	public function getAppTypeName(){
		return $this->appTypeInfo[$this->app_type];
	}

	public function getForceUpdateName(){
		return $this->forceUpdateInfo[$this->force_update];
	}

	public function createVersion($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
		return $model;
	}

	public function updateVersion($info){
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function getList($con=array(),$order='_create_time desc',$limit=-1,$offset=0){
		$criteria = new \CDbCriteria;
		if(!empty($con)){
			foreach ($con as $key => $val) {
				if(is_array($val) && isset($val[0])){
					switch($val[0]){
						case 'search_like':
							$criteria->compare($key, $val[1],true);
							break;
						case 'between':
							$criteria->addBetweenCondition('create_time',$val[1],$val[2]);
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
		foreach($ret as $k => $v){
			$data[$k] = $v->attributes;
			$data[$k]['status_name'] = $v->getStatusName();
			$data[$k]['app_type_name'] = $v->getAppTypeName();
			$data[$k]['force_update_name'] = $v->getForceUpdateName();
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}
}
