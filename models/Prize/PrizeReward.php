<?php

/**
 * desc:产品
 * author:besttaowenjing@163.com
 * date:2016-10-26
 */
namespace application\models\Prize;
class PrizeReward extends \CActiveRecord {
    
    //奖品类型
    public $prize_type = array(
        0 => '自建奖品',
        1 => '积分',
        2 => '广币',
        3 => '谢谢参与'
    );
    /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{prize_reward}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
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
	 * @return Category the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    /*
     * 查询奖品列表
     * $to_name 是否转为名称
     */
    public function get_list($con){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $data = self::model()->findAll($criteria);
        $res = [];
        if($data){
            foreach($data as $val){
                $val = $val->attributes;
                if($val['prize_type']!=0){
                    $val['prize_name_txt'] = $this->prize_type[$val['prize_type']].$val['prize_name'];
                }else{
                    $val['prize_name_txt'] = $val['prize_name'];
                }
                $res[$val['prize_sort']] = $val;
            }
        }
        return $res;
    }
	
	//保存/修改
    public function PrzieRewardSave($data ){
        $prize_id = $data['id'];
        unset($data['id']);
        $criteria = new \CDbCriteria;
        $criteria->compare('prize_id', $prize_id);
        $count = self::model()->count($criteria);
        $idok = [];
        if( $count ){
            foreach($data['prize_reward_id'] as $sort=>$prize_reward_id){
                $model = self::model()->findByPk($prize_reward_id);
                if( empty($model) || $model->prize_id!=$prize_id ){
                    continue;
                }
                if( isset($data['prize_type']) ){
                    if( $model->prize_type != $data['prize_type'][$sort] ){
                        $model->prize_type = $data['prize_type'][$sort];
                    }
                    if( $model->prize_name != $data['prize_name'][$sort] ){
                        $model->prize_name = $data['prize_name'][$sort];
                    }
                }
                
                if( $model->prize_total!=$data['prize_total'][$sort] ){
                    $prize_num = intval($data['prize_total'][$sort]) - intval($model->prize_total) + intval($model->prize_num);
                    if( $prize_num<0 ){
                        return 'prize_total_error';
                    }
                    $model->prize_total = $data['prize_total'][$sort];
                    $model->prize_num = $prize_num;
                }
                
                if( $model->probability != $data['probability'][$sort] ){
                    $model->probability = $data['probability'][$sort];
                }
                $model->_update_time = date('Y-m-d H:i:s');
                if($model->save()){
                    $idok[] = intval($model->primaryKey);
                }
            } 
        }else{
            foreach($data['prize_type'] as $sort=>$prize_type){
                $model = new self();
                $model->prize_id = $prize_id;
                $model->prize_sort = $sort;
                $model->prize_type = $prize_type;
                $model->prize_name = $data['prize_name'][$sort];
                $model->prize_total = $data['prize_total'][$sort];
                $model->prize_num = $data['prize_total'][$sort];
                $model->probability = $data['probability'][$sort];
                $model->_create_time = date('Y-m-d H:i:s');
                if($model->save()){
                    $idok[] = intval($model->primaryKey);
                }
            }
        }
        if($idok){
            \OperationLog::addLog(\OperationLog::$operationPrize, 'edit', '编辑抽奖奖品', $prize_id, array(), array());    
        }
        return $idok;
    }
      
    
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $criteria->addCondition('prize_name!=""');
        $count = self::model()->count($criteria);
        return intval($count);
    }
    

}
