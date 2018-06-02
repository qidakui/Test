<?php

/**
 * This is the model class for table "{{goods}}".
 *
 * The followings are the available columns in table '{{goods}}':
 * @property string $id
 * @property string $category_id
 * @property string $goods_name
 * @property string $goods_brief
 * @property integer $goods_attr_type
 * @property integer $is_real
 * @property string $click_count
 * @property string $sale_count
 * @property string $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Shop;
use OperationLog;
use application\models\Shop\GoodsSaleArea;
use application\models\Shop\GoodsDescribe;
use application\models\Shop\GoodsInfo;
use application\models\Shop\Category;
use application\models\ServiceRegion;
class Goods extends \CActiveRecord
{

	public $goodsSaleTypeKey = array(
		1 => '快递邮寄',
		2 => '激活码',
		3 => '线下跟进',
		4 => '其他',
	);

	public $goodsAttrTypeKey = array(
		1 => '颜色',
		2 => '版本',
		3 => '书籍',
	);

	public $goodsStatusKey = array(
		1 => '上架',
		2 => '下架'
	);

	public function tableName()
	{
		return '{{goods}}';
	}

	public function rules()
	{
		return array();
	}

	public function relations()
	{
		return array(
			'category' => array(self::BELONGS_TO,get_class(Category::model()), 'category_id', 'join_with_in' => true, 'with' => 'parent_category'),
			'sale_area' => array(self::HAS_MANY,get_class(GoodsSaleArea::model()), 'goods_id', 'join_with_in' => true),
			'goods_spec' => array(self::HAS_MANY,get_class(GoodsSpec::model()), 'goods_id', 'join_with_in' => true),
			'goods_attr' => array(self::HAS_MANY,get_class(GoodsAttr::model()), 'goods_id', 'join_with_in' => true),
			'goods_info' => array(self::HAS_MANY,get_class(GoodsInfo::model()), 'goods_id', 'join_with_in' => true),
			'goods_describe' => array(self::HAS_ONE,get_class(GoodsDescribe::model()), 'goods_id', 'join_with_in' => true),
			'goods_info_with_name' => array(self::HAS_MANY,get_class(GoodsInfo::model()), 'goods_id', 'join_with_in' => true, 'with' => array('goods_spec', 'goods_attr')),
			'goods_attr_and_pic' => array(self::HAS_MANY,get_class(GoodsAttr::model()), 'goods_id', 'join_with_in' => true, 'with' => 'goods_pic'),
			'goods_all_num' => array(self::STAT, get_class(GoodsInfo::model()),'goods_id','select' => 'sum(goods_num)'),
			'goods_price_range' => array(self::STAT, get_class(GoodsSpec::model()),'goods_id','select' => "CONCAT(min(goods_price/100),'~',max(goods_price/100))"),
			'goods_recommend' => array(self::HAS_MANY, get_class(GoodsRecommend::model()),'goods_id', 'join_with_in' => true),
		);
	}

	public function attributeLabels()
	{
		return array(
			'id' => 'id',
			'category_id' => '商品所属商品分类id',
			'goods_name' => '商品的名称',
			'goods_brief' => '商品简短描述描述',
			'goods_attr_type' => '商品属性类型: 1,颜色;2,版本;3,书籍;',
			'is_real' => '是否是实物，1 ，是； 0 ，否',
			'click_count' => '商品点击数',
			'sale_count' => '商品销量',
			'init_click_count' => '初始化浏览量',
			'status' => '状态',
			'_delete' => '是否删除',
			'_create_time' => '商品的添加时间',
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
				$this->click_count = 0;
				$this->sale_count = 0;
				$this->status = 2;
				$this->_delete = 0;
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				if($this->old_date == $this->attributes){
					return false;
				}
				$this->_update_time = date('y-m-d H:m:s');
			}
			return true;
		}else{
			return false;
		}
	}

	protected function afterSave(){
		$column_name = 'Goods';
		if($this->isNewRecord){
			$column_name = '新增Goods';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除Goods';
				$operate = 'del';
			}else{
				$column_name = '编辑Goods';
				$operate = 'edit';
			}
		}
		OperationLog::addLog(OperationLog::$operationGoods, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		$goods_describe = $this->goods_describe;
		if(!empty($goods_describe)){
			$info = array();
			if($this->old_date['goods_attr_type'] == 3 && $this->goods_attr_type != 3){
				$goods_params = unserialize($goods_describe->goods_params);
				$info[1] = $goods_params[1];
				$info[2] = $goods_params[2];
				$info[3] = array('name' => '商品产地', 'params' => '');
				$info[4] = array('name' => '产品功能', 'params' => '');
				$info[5] = array('name' => '', 'params' => '');
				$info[6] = array('name' => '', 'params' => '');
				$goods_describe->updateGoodsDescribe(array('goods_params' => serialize($info)));
			}
			if($this->goods_attr_type == 3 && $this->old_date['goods_attr_type'] != 3){
				$goods_params = unserialize($goods_describe->goods_params);
				$info[1] = $goods_params[1];
				$info[2] = $goods_params[2];
				$info[3] = array('name' => '出版社', 'params' => '');
				$info[4] = array('name' => 'ISBN编号', 'params' => '');
				$info[5] = array('name' => '', 'params' => '');
				$info[6] = array('name' => '', 'params' => '');
				$goods_describe->updateGoodsDescribe(array('goods_params' => serialize($info)));
			}
		}
		$this->old_date = '';
	}

	public function defaultScope()
	{
		return array(
		'condition' => "_delete=0",
		);
	}

	public function createGoods($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->is_real = 1;
		$model->save();
		return $model;
	}

	public function updateGoods($info){
		$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
	}

	public function deleteGoods(){
		$this->_delete=1;
		return $this->save();
	}

	public function getStatus(){
		return $this->goodsStatusKey[$this->status];
	}

	public function getSaleTypeName(){
		return $this->goodsSaleTypeKey[$this->sale_type];
	}

	//后台商品管理页面获取列表,由于关联了较多的表,其他地方参考实际情况使用
	public function getList($con, $order, $limit=-1, $offset=0, $index=''){
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
		$criteria->with = array('category','goods_all_num','goods_price_range','sale_area');

		$ret = self::model()->findAll($criteria);
		$count = self::model()->count($criteria);
		$province_list = ServiceRegion::model()->getCityList();
		$sale_area = array();
		foreach($province_list as $province){
			$sale_area[$province['region_id']] = $province['region_name'];
		}
		foreach($ret as $k => $v){
			$data[$k] = $v->attributes;
			$data[$k]['status_name'] = $v->getStatus();
			$data[$k]['sale_type_name'] = $v->getSaleTypeName();
			$data[$k]['second_category_name'] = $v->category->category_name;
			$data[$k]['first_category_name'] = $v->category->parent_category->category_name;
			$data[$k]['goods_num'] = $v->goods_all_num;
			$data[$k]['goods_price'] = $v->goods_price_range;
			$data[$k]['sale_area'] = array();
			foreach($v->sale_area as $area){
				$data[$k]['sale_area'][]= $sale_area[$area->area_id];
			}
			if($data[$k]['begin_sale_time'] == '0000-00-00 00:00:00'){
				$data[$k]['begin_sale_time'] = '';
			}
		}
		$data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
	}
}
