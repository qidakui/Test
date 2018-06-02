<?php

/**
 * This is the model class for table "{{product_advance_template}}".
 *
 * The followings are the available columns in table '{{product_advance_template}}':
 * @property string $id
 * @property integer $product_id
 * @property integer $type
 * @property integer $animation_type
 * @property string $title
 * @property string $sub_title
 * @property string $desc
 * @property string $background_pic
 * @property integer $sort
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Product;
use application\models\Product\Product;
class ProductAdvanceTemplate extends \CActiveRecord
{
	public $typeKey = array(
			1 => '文字居上',
			2 => '文字居左',
			3 => '文字居右',
	);
	public $animationTypeKey = array(
			1 => '文字从下向上',
	);
	public $fontColorTypeKey = array(
			0 => '黑色',
			1 => '白色',
	);
        public $is_son_pic_name = array(
                        1=>'否',
                        2=>'是'
        );
        protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->_delete = 0;
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

	public function defaultScope()
	{
		$alias = $this->getTableAlias(false,false);
		return array(
				'condition' => "{$alias}._delete=0",
				'order' => "{$alias}.sort asc",
		);
	}

	public function createRecord($info){
		$model = new self();
		foreach($info as $k=>$v){
			$model->$k = $v;
		}
		$model->save();
                $id  = $model->primaryKey;
		return $id;
	}

	public function updateRecord($info){
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
		return $this;
	}

	public function saveRecord($info){
		foreach($info as $k=>$v){
			$this->$k = $v;
		}
		$this->save();
		return $this;
	}

	public function create_or_update_record($info){
		if(isset($info['id'])){
			$id = $info['id'];
			unset($info['id']);
		}
		if(!empty($id)){
			$record = $this->findByPk($id);
			if(empty($record)){
				return false;
			}
			$record->updateRecord($info);
			return $record;
		}else{
			$addid = $this->createRecord($info);
			return $addid;
		}
	}

	public function deleteRecordByPK($id){
		$record = $this->findByPk($id);
		if(!empty($record)){
			$record->_delete = true;
                        $record->is_son_pic = true;
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
		return '{{product_advance_template}}';
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
			'product_id' => '产品ID',
			'type' => '模板类型;1:文字居上;2:文字居左;3:文字居右',
			'animation_type' => '模板类型;1:文字从下向上',
			'title' => '标题',
			'sub_title' => '子标题',
			'desc' => '描述',
			'background_pic' => '背景图',
			'sort' => '排序',
			'status' => '状态',
			'_delete' => '是否删除,0:未删除,1:已删除',
			'_create_time' => 'Create Time',
			'_update_time' => 'Update Time',
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
	 * @return ProductAdvanceTemplate the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function find_by_product_id($product_id){
		$criteria = new \CDbCriteria;
		$criteria->compare('product_id', $product_id);
		$criteria->index = 'id';
		return $this->findAll($criteria);
	}
    public function get_recruitment_list($con, $orderBy, $order, $limit, $offset){
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
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['type_name']             = isset($this->typeKey[$data[$k]['type']]) ? $this->typeKey[$data[$k]['type']] : '';
            $data[$k]['animation_type_name']   = isset($this->animationTypeKey[$data[$k]['animation_type']]) ? $this->animationTypeKey[$data[$k]['animation_type']] : '';
            $data[$k]['font_color_name']       = isset($this->fontColorTypeKey[$data[$k]['font_color']]) ? $this->fontColorTypeKey[$data[$k]['font_color']] : '';
            $data[$k]['pic_name']              = isset($this->is_son_pic_name[$data[$k]['is_son_pic']]) ? $this->is_son_pic_name[$data[$k]['is_son_pic']] : '';
            $data[$k]['is_video']              = !empty($data[$k]['video_url'])?'是':'否';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }        
}
