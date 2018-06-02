<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2017/10/16
 * Time: 16:58
 */
namespace application\models\ColumnShare;
use application\models\Activity\Activity;
use application\models\Activity\ActivityParticipate;
class ColumnShare extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{column_share}}';
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
        return array();
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
     * @return ServiceRegion the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
     

    /*
     * 保存分享
     */
    public function getList($con, $orderBy, $order, $limit, $offset){
        $column_id = isset($con['column_id']) ? $con['column_id'] : 0;
        $column_type = isset($con['column_type']) ? $con['column_type'] : 1;
		
		$criteria = new \CDbCriteria;
		$criteria->compare('column_type', $column_type);
		$criteria->compare('column_id', $column_id);
		$criteria->addCondition('share_num>0');
		$count = self::model()->count($criteria);
        $data = array('data' => array(), 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        if(empty($count)){
            return $data;
        }

        $activity = Activity::model()->findByPk($column_id, array('select'=>array('title')));
        //$count = self::model()->count($criteria);
        $dataList = \Yii::app()->db->createCommand()
            ->select("s.id,m.global_id,s.member_user_id,m.member_user_name,s.share_num,s.amount,s.share_code")
            ->from("e_column_share s")
            ->leftJoin('e_common_member m', 's.member_user_id = m.member_user_id')
            ->where('s.column_type='.$column_type.' AND s.column_id='.$column_id.' AND s.share_num>0')
            ->offset($offset)//分页查询起始位置
            ->limit($limit) //每次查询条数
            ->order('s.'.$order.' '.$orderBy)  
            ->queryAll();
        $shareCodeArr = array();
        if( !empty($dataList) ){
			$shareCodeArr = $this->_participateShareCode($column_id);
		}
        foreach($dataList as $k=>$v){
            $dataList[$k]['title'] = $activity['title'];
            $dataList[$k]['participate_num'] = ShareCodeUseLog::model()->getCount(
                    ['column_type'=>$column_type,'column_id'=>$column_id,'share_code'=>$v['share_code']]);
            $dataList[$k]['participate_sign_num'] = 0;
            if(!empty($dataList[$k]['participate_num']) && !empty($shareCodeArr) ){
                $dataList[$k]['participate_sign_num'] = isset($shareCodeArr[$v['share_code']]) ? $shareCodeArr[$v['share_code']] : 0;
            }
        }
        $data['data'] = $dataList; 
        $data['iTotalRecords'] = $data['iTotalDisplayRecords'] = $count;
        return $data;
    }


	private function _participateShareCode($column_id){
		$criteria = new \CDbCriteria;
		$criteria->select = 'extend';
		$criteria->compare('activity_id', $column_id);
		$criteria->compare('status', 1);
		$criteria->compare('type', 2);
		$criteria->addCondition('extend IS NOT NULL AND extend LIKE "%share_code%"');
		$list = ActivityParticipate::model()->findAll($criteria);
		$data = array();
		if($list){
			foreach($list as $v){
				$extend = unserialize($v->extend);
				if( !empty($extend) && is_array($extend) ){
					if( isset($extend['share_code']) && !empty($extend['share_code']) ){
						$share_code = $extend['share_code'];
						if( isset($data[$share_code]) ){
							$data[$share_code] ++;
						}else{
							$data[$share_code] = 1;
						}
					}
				}
			}
		}
		return $data;
	}
     
    
}