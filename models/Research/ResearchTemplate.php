<?php

/**
 * This is the model class for table "{{research_template}}".
 *
 * The followings are the available columns in table '{{research_template}}':
 * @property string $id
 * @property string $research_id
 * @property string $template_name
 * @property string $template_title
 * @property string $filiale_id
 * @property integer $template_type
 * @property integer $user_id
 * @property string $select_count
 * @property integer $status
 * @property string $start_time
 * @property string $end_time
 * @property string $explain
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Research;
use application\models\ServiceRegion;
class ResearchTemplate extends \CActiveRecord
{
        private $default_name = array(
                    '1'=>'是',
                    '2'=>'否'
        ); 
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{research_template}}';
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
	 * @return ResearchTemplate the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->_delete = 0;
				$this->_update_time = date('Y-m-d H:i:s');
				$this->_create_time = date('Y-m-d H:i:s');
			}else{
				$this->_update_time = date('Y-m-d H:i:s');
			}
			return true;
		}else{
			return false;
		}
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
        public function updateRecord($info)
        {
            foreach ($info as $k => $v) {
                $this->$k = $v;
            }
            $this->save();
            return $this;
        }
        public function create_or_update_record($info){
            
            $info['filiale_id'] = \Yii::app()->user->branch_id;
            $info['user_id'] = \Yii::app()->user->user_id; 
            $info['is_default'] = in_array($info['template_type'],array(1))?'1':'2';
            if (isset($info['template_id'])) {
                $template_id = $info['template_id'];
                unset($info['template_id']);
                unset($info['from']);
            }
            if (!empty($template_id)) {
                $record = $this->findByPk($template_id);
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
        //查询记录
        public function getdata($con = array(),$nums = null) {
            $criteria = new \CDbCriteria;
            if (!empty($con)) {
                foreach ($con as $key => $val) {
                    $criteria->compare($key, $val);
                }
            }
            $ret = !empty($nums)?self::model()->findAll($criteria):self::model()->find($criteria);
            return $ret;
        }
        /**
         * 获取调研列表
         */
        public function get_template_list($con, $orderBy, $order, $limit, $offset){
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
            foreach ($ret as $k=>$v){
                $data[$k] = $v->attributes;
                $areaName = ServiceRegion::model()->getBranchToCity($data[$k]['filiale_id']);
                $data[$k]['area_name']   = !empty($areaName[0]['region_name'])?$areaName[0]['region_name']:'全国';
                $data[$k]['default_name'] =!empty($this->default_name [$data[$k]['is_default']]) ? $this->default_name [$data[$k]['is_default']] : '';
            }
            
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);   
        }        
}
