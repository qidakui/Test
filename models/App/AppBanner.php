<?php

/**
 * This is the model class for table "{{app_banner}}".
 *
 * The followings are the available columns in table '{{app_banner}}':
 * @property string $id
 * @property string $name
 * @property integer $position
 * @property integer $type
 * @property string $banner_url
 * @property integer $object_id
 * @property string $info
 * @property integer $status
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\App;
class AppBanner extends \CActiveRecord
{

	public $positionName = array(
		1 => '在线学习',
		2 => '线下培训',
		3 => '同城活动',
		4 => '答疑解惑',
		5 => '启动弹窗'

	);

	public $typeName = array(
		1 => '无',
		2 => '指定URL',
		3 => '在线视频',
		4 => '指定活动',
		5 => '指定培训',
		6 => '自定义图文'
	);

	public $statusName = array(
		1 => '上线',
		0 => '下线'
	);

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{app_banner}}';
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
			'name' => 'banner名称',
			'img_url' => '图片URL',
			'position' => 'banner位置',
			'type' => 'banner打开类型',
			'banner_url' => 'banner跳转url',
			'object_id' => 'banner打开对象ID',
			'info' => 'banner图文内容',
			'order' => '排序',
			'status' => '状态',
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
	 * @return AppBanner the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function defaultScope()
	{
		$alias = $this->getTableAlias(false,false);
		return array(
				'condition' => "{$alias}._delete=0"
		);
	}

	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->status = 1;
				$this->status = 0;
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				$this->_update_time = date('y-m-d H:m:s');
			}
			switch($this->type){
				case 1:
					$this->banner_url = null;
					$this->object_id = null;
					$this->info = null;
					break;
				case 2:
					$this->object_id = null;
					$this->info = null;
					break;
				case 3:
				case 4:
				case 5:
					$this->banner_url = null;
					$this->info = null;
					break;
				case 6:
					$this->banner_url = null;
					$this->object_id = null;
					break;
			}
			return true;
		}else{
			return false;
		}
	}


	public function createBanner($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
		return $model;
	}

	public function updateBanner($info){
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}


	public function getList($con=array(),$order='position,status desc, order_no',$limit=-1,$offset=0){
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
			$data[$k]['type_name'] = $v->getTypeName();
			$data[$k]['position_name'] = $v->getPositionName();
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}


	public function getStatusName(){
		return $this->statusName[$this->status];
	}

	public function getTypeName(){
		return $this->typeName[$this->type];
	}

	public function getPositionName(){
		return $this->positionName[$this->position];
	}
}
