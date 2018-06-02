<?php

/**
 * This is the model class for table "{{goods_recommend}}".
 *
 * The followings are the available columns in table '{{goods_recommend}}':
 * @property string $id
 * @property string $name
 * @property string $link
 * @property string $pic_path
 * @property integer $goods_id
 * @property integer $sort
 * @property integer $position
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Shop;
use OperationLog;
class GoodsRecommend extends \CActiveRecord
{

	public $positionKey = array(
			1 => '商城banner',
			2 => '特惠推荐',
			5 => '首页推荐',
			3 => '同城精选',
			4 => 'VIP优惠购',
	);
	public $statusKey = array(
			1 => '上架',
			2 => '下架',
	);

	public function tableName()
	{
		return '{{goods_recommend}}';
	}

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array();
	}

	public function attributeLabels()
	{
		return array(
			'id' => 'id',
			'name' => '名称',
			'link' => '链接',
			'pic_path' => '链接',
			'goods_id' => '商品ID',
			'color_num' => '背景色值',
			'sort' => '排序',
			'position' => '位置,1,banner;2,特惠推荐;3,楼层1;4,楼层2;',
			'status' => '状态，1 ，上架； 2 ，下架',
			'_delete' => '是否已经删除，0 ，否； 1 ，已删除',
			'_create_time' => '添加时间',
			'_update_time' => '更新时间',
		);
	}

	public function search()
	{

	}


	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public $old_date = '';

	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				//新增banner或特惠推荐默认为下架状态,排序为9999;其他推荐直接上架
				if($this->position == 1 || $this->position == 2){
					$this->sort = 9999;
					$this->status = 2;
				}else{
					$this->sort = self::model()->getMaxSortNum($this->position) + 1;
					$this->status = 1;
				}
				$this->_delete = 0;
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				$this->_update_time = date('y-m-d H:m:s');
				//如果修改后状态为下架排序为9999
				if($this->status == 2)
					$this->sort = 9999;
				//如果是从下架修改为上架,排序为所有上架推荐最后一位
				if($this->status == 1 && $this->old_date['status'] == 2)
					$this->sort = self::model()->getMaxSortNum($this->position) + 1;
				//如果修改后为删除状态,排序修改为10000
				if($this->_delete == 1)
					$this->sort = 10000;
			}
			return true;
		}else{
			return false;
		}
	}

	protected function afterSave(){
		$column_name = 'GoodsRecommend';
		if($this->isNewRecord){
			$column_name = '新增GoodsRecommend';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除GoodsRecommend';
				$operate = 'del';
			}else{
				$column_name = '编辑GoodsRecommend';
				$operate = 'edit';
			}
		}
		OperationLog::addLog(OperationLog::$operationGoodsRecommend, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		$this->old_date = '';
	}

	public function defaultScope()
	{
		return array(
		'condition' => "_delete=0",
		);
	}

	public function createGoodsRecommend($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
	}

	public function updateGoodsRecommend($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function deleteGoodsRecommend(){
		$this->old_date = $this->attributes;
		$this->_delete=1;
		return $this->save();
	}

	public function getMaxSortNum($postion){
		$criteria = new \CDbCriteria;
		$criteria->compare('position', $postion);
		$criteria->compare('status', 1);
		$criteria->order = 'sort desc';
		$max_recommend = self::model()->find($criteria);
		return empty($max_recommend) ? 0 : $max_recommend->sort;
	}

	public function getList($con, $order, $limit=-1, $offset=0)
	{
		$criteria = new \CDbCriteria;
		if (!empty($con)) {
			foreach ($con as $key => $val) {
				$criteria->compare($key, $val);
			}
		}
		$criteria->order = $order;
		$criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
		$criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
		$ret = self::model()->findAll($criteria);
		$count = self::model()->count($criteria);
		foreach($ret as $k => $v) {
			$data[$k] = $v->attributes;
			$data[$k]['status_name'] = $v->getStatusName();
			$data[$k]['pic_path'] = UPLOADURL . $v->pic_path;
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}

	public function getStatusName(){
		return $this->statusKey[$this->status];
	}

}
