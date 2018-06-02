<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/17
 * Time: 11:13
 */
namespace application\models\Activity;
class ActivitySetDrawLots extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{activity_set_draw_lots}}';
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
     * @return ActivitySetDrawLots the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function setDrawLotsSave($data){
        $model = new self();
        $model->activity_id     = $data['activity_id'];
        $model->passwd          = $data['passwd'];
        $model->source          = $data['source'];
        $model->type            = $data['type'];
        $model->online_time     = $data['online_time'];
        $model->offline_time    = $data['offline_time'];
        $model->_create_time    = date('Y-m-d H:i:s');
        $model->_update_time    = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function setDrawLotsUpdate($data){
        $activity_id = $data['activity_id'];
        $source = isset($data['source']) ? $data['source'] : 0;
        $model = ActivitySetDrawLots::model()->find('activity_id=:activity_id and source=:source', array('activity_id' => $data['activity_id'], 'source' => $source));
        if(empty($model)){
            return false;
        }
        $model->offline_time    = $data['offline_time'];
        $model->_update_time    = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }


    public function setDrawLotsTypeUpdate($data){
        $id = $data['id'];
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->type   = $data['type'];
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

	//调研管理通知预览接口
	/*
	*column_id	栏目ID
	* column_type	 栏目类型: 1:同城活动,2:培训报名
	* trigger_config	触发设置: 1:查看内容时,2:签到完成后,3:报名完成后
	* member_id	用户ID private
	*/
	public function research($activity_id, $column_type, $trigger_config, $member_id ){
		$sign = \FwUtility::createSign(array('column_id'=>$activity_id,'column_type'=>$column_type,'trigger_config'=>$trigger_config,'member_id'=>$member_id),array('signkey'=>APIKEY));
		
		$getHostInfo = YII_ENV=='dev' ? EHOME : \Yii::app()->request->getHostInfo();
        $url = $getHostInfo.'/index.php?r=Research/ResearchNotice&column_id='.$activity_id.'&column_type='.$column_type.'&trigger_config='.$trigger_config.'&member_id='.$member_id.'&sign='.$sign;
        $research = \Yii::app()->curl->get($url);
		
		return $research;
	}
}