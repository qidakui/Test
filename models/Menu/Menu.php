<?php

/**
 * This is the model class for table "{{menu}}".
 *
 * The followings are the available columns in table '{{menu}}':
 * @property string $id
 * @property string $menu_name
 * @property string $menu_link
 * @property string $unique
 * @property integer $status_is
 * @property string $parent_id
 * @property string $sort_order
 * @property integer $target
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Menu;
use application\models\Menu\MenuFiliale;
use application\models\ServiceRegion;
class Menu extends \CActiveRecord
{
        private $status_is_key = array(
            0 => '显示',
            1 => '不显示',
        );
        private $target_key = array(
            0 => '当前窗口',
            1 => '新窗口'
        );
        /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{menu}}';
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
			array('status_is, target, _delete', 'numerical', 'integerOnly'=>true),
			array('menu_name', 'length', 'max'=>30),
			array('menu_link', 'length', 'max'=>250),
			array('unique', 'length', 'max'=>20),
			array('parent_id, sort_order', 'length', 'max'=>10),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, menu_name, menu_link, unique, status_is, parent_id, sort_order, target, _delete, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'menu_name' => '导航菜单',
			'menu_link' => '导航链接',
			'unique' => '唯一标示',
			'status_is' => '是否显示 0:显示,1:不显示',
			'parent_id' => '上级菜单',
			'sort_order' => '排序',
			'target' => '新窗口打开 0:当前窗口,1:新窗口',
			'_delete' => '是否已经删除，0 ，否； 1 ，已删除',
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
		$criteria->compare('menu_name',$this->menu_name,true);
		$criteria->compare('menu_link',$this->menu_link,true);
		$criteria->compare('unique',$this->unique,true);
		$criteria->compare('status_is',$this->status_is);
		$criteria->compare('parent_id',$this->parent_id,true);
		$criteria->compare('sort_order',$this->sort_order,true);
		$criteria->compare('target',$this->target);
		$criteria->compare('_delete',$this->_delete);
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
	 * @return Menu the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 导航列表
         */
        public function ger_Menu_list($con, $orderBy, $order, $limit, $offset){
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
                $data[$k]['status_name'] =!empty($this->status_is_key[$data[$k]['status_is']]) ? $this->status_is_key[$data[$k]['status_is']] : '';
                $data[$k]['target_name'] = $this->target_key[$data[$k]['target']];
                $data[$k]['open_name'] = $this->getopenArea($data[$k]['id']);
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);            
        }
        /**
         * 添加导航
         */
        public function menuSave($data = null){
            if(!empty($data)){
                $model = new Menu();
                $model->attributes=$data;
                if($model->save())
                  $id  = $model->primaryKey;
                return $id;
            }
        }
        /**
         * 编辑导航
         */
        public function editsave($data = null){
            if(!empty($data)){
                unset($data['_create_time']);
                $this->attributes=$data;
                if($this->save()){
                   return  $this->primaryKey;
                }
            }
        }
        /**
         * 获取开通地区名称 
         */
        public function getopenArea($menu_id){
            $areaName = array();
            $getmenuList = MenuFiliale::model()->findAll('menu_id=:menu_id',array('menu_id'=>$menu_id));
            foreach ($getmenuList as $key=>$item){
                if($item->filiale_id == QG_BRANCH_ID){
                    $areaName[] = '全国';
                }else{
                    $cityname = ServiceRegion::model()->getBranchToCity($item->filiale_id);
                    $areaName[] = $cityname[0]->region_name;
                }
            }
            return implode(",", $areaName);
        }
        /**
         * 获取导航上限
         */
        public function getMenuCeiling($open_area = array()){
            $total = true;
            if(!empty($open_area)){
                foreach ($open_area as $key=>$item){
                    $counttotal = MenuFiliale::model()->count("filiale_id=:filiale_id",array(':filiale_id'=>$item));
                    if($counttotal>=10){
                        $total = false;
                        break;
                    }
                }
                return $total;
            }else{
                return  false;
            }
        }
}
