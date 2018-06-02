<?php

/**
 * This is the model class for table "{{information}}".
 *
 * The followings are the available columns in table '{{information}}':
 * @property string $id
 * @property string $title
 * @property string $abstract
 * @property string $information_desc
 * @property string $sort
 * @property string $branch_id
 * @property string $browse_count
 * @property string $create_user_id
 * @property integer $_delete
 * @property string $_update_time
 * @property string $_create_time
 */
namespace application\models\Home;
use application\models\ServiceRegion;
class Information extends \CActiveRecord
{
	private $state_type_name = array(
                    '1'=>'上架',
                    '2'=>'下架'
        );
        /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{information}}';
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

		
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Information the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        public function InformationSave($data){
            $model = new self();
            $model->title               = $data['title'];
            $model->link                = $data['link'];
            $model->abstract            = $data['abstract'];
            $model->information_desc    = $data['information_desc'];
            $model->branch_id           = $data['branch_id'];
            $model->sort                = $data['sort'];
            $model->author              = $data['author'];
            $model->cat_id              = $data['cat_id'];
            $model->_create_time        = date('Y-m-d H:i:s');
            $model->_update_time        = date('Y-m-d H:i:s');
            $model->save();
            $id  = $model->primaryKey;
            return $id;
        }
        public function informationUpdate($id, $data){
            if(empty($id)){
                return false;
            }
            $editInforamtion = $this->updateByPk($id, $data);
            return $editInforamtion?$editInforamtion:true;
        }
        public function getlist($con, $orderBy, $order, $limit, $offset){
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
        
        $branchIds = columnToArr($ret, 'branch_id');
        
        $serviceRegionObj   = ServiceRegion::model()->getBranchToCity($branchIds);
        foreach($serviceRegionObj as $region){
            $branch_id = !empty($region->filiale_id) ? substr($region->filiale_id,0 , 2) : 0;
            $regionArr[$branch_id] = $region->region_name;
        }

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['city_name']  = !empty($regionArr[$data[$k]['branch_id']]) ? $regionArr[$data[$k]['branch_id']] : '全国';
            $data[$k]['state_name'] = isset($this->state_type_name[$data[$k]['status']]) ? $this->state_type_name[$data[$k]['status']] : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return intval($count);
    }    
}
