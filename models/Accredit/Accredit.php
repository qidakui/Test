<?php

/**
 * This is the model class for table "{{accredit}}".
 *
 * The followings are the available columns in table '{{accredit}}':
 * @property string $id
 * @property string $filiale_id
 * @property integer $status
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Accredit;
use application\models\ServiceRegion;
class Accredit extends \CActiveRecord
{
        private $is_accredit = array(
            0 => '未开通',
            1 => '已开通'
        );        
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{accredit}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('_create_time, _update_time', 'required'),
			array('status', 'numerical', 'integerOnly'=>true),
			array('filiale_id', 'length', 'max'=>10),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, filiale_id, status, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'filiale_id' => '分之id',
			'status' => '是否设置:0未开通 1:已开通',
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
		$criteria->compare('filiale_id',$this->filiale_id,true);
		$criteria->compare('status',$this->status);
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
	 * @return Accredit the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        public function ger_Accredit_list($con, $orderBy, $order, $limit, $offset){
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
            
            $filialIds = columnToArr($ret, 'filiale_id');
            
            $serviceRegionObj   = ServiceRegion::model()->getBranchToCity($filialIds);
            foreach($serviceRegionObj as $region){
                $branch_id = !empty($region->filiale_id) ? substr($region->filiale_id,0 , 2) : 0;
                $regionArr[$branch_id] = $region->region_name;
            }            
            foreach($ret as $k => $v){
                 $data[$k] = $v->attributes; 
                 $data[$k]['city_name']   = !empty($regionArr[$data[$k]['filiale_id']]) ? $regionArr[$data[$k]['filiale_id']] : '全国';                  
                 $data[$k]['is_accredit'] = $this->is_accredit[$data[$k]['status']];
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        }
        /**
         * 开通授权
         * @param type $data 数据源
         * @return type
         */
        public function open_accredit($data){
            if(!empty($data)){
                $checkinfo = $this->model()->find('filiale_id=:filiale_id',array('filiale_id'=>\Yii::app()->user->branch_id));
                if(!empty($checkinfo)){
                    $this->model()->updateAll(array('status'=>$data['status'],'is_all'=>$data['is_all']),'filiale_id=:filiale_id',array(':filiale_id'=>\Yii::app()->user->branch_id));
                    return true;
                }else{
                    $model = new Accredit();
                    $data['_create_time'] = date('Y-m-d H:i:s');
                    $data['_update_time'] = date('Y-m-d H:i:s');
                    $model->attributes=$data;
                    if($model->save()){
                        $id  = $model->primaryKey;
                    }
                        return $id;                   
                }
            }
        }
}
