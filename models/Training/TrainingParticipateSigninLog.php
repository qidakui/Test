<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/20
 * Time: 10:13
 */
namespace application\models\Training;
class TrainingParticipateSigninLog extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{training_participate_signin_log}}';
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
     * @return ActivityParticipate the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //查询列表
    public function getlist($con, $orderBy='desc', $order='id', $limit=1, $offset=0){
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
        $data = array();
        foreach($ret as $v){
            $v = $v->attributes;
            $data[] = $v;
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        //return array('data' => $data, 'count' => $count);
    }

    //写入日志
    public function SaveLog_bak($data){
        $model = new TrainingParticipateSigninLog();
        $data['_create_time'] = date('Y-m-d H:i:s');
        $model->_attributes = $data;
        return $model->insert();
    }
    //写入日志
    public function SaveLog($data){
        $model = new self();
        foreach($data as $k=>$v){
            $model->$k = $v;
        }
        $model->_create_time = date('Y-m-d H:i:s');
        return $model->save();
    }
    
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return $count;
    }
    
   
    //听课人数
    public function getListenNum($con){
        $criteria = new \CDbCriteria;
        $criteria->group = 'participate_id';
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return $count;
    }

	public function getGroupCount($training_id){
		$sql = 'tp.training_id '.$training_id.' and tp.participate_way=1 and tp.status=1 and ts.id IS NOT NULL';
        $count = \Yii::app()->db->createCommand()
            ->select("count( tp.mobile) as c ")
            ->from("e_training_participate as tp")
            ->leftJoin('e_training_participate_signin_log as ts', 'tp.id=ts.participate_id')
            ->where($sql)
            ->group('tp.mobile')
            ->queryAll();
		return empty($count) ? 0 : count($count);
	}

	//听课（签到）人次
	public function getParticipateSigninCount($training_ids){
		$sql = 'tp.training_id in('.implode(',',$training_ids).') and tp.participate_way=1 and tp.status=1 and ts.id IS NOT NULL';
        $count = \Yii::app()->db->createCommand()
            ->select("count(tp.mobile) as c ")
            ->from("e_training_participate as tp")
            ->leftJoin('e_training_participate_signin_log as ts', 'tp.id=ts.participate_id')
            ->where($sql)
            ->queryAll();
		return isset($count[0]['c']) ? $count[0]['c'] : 0;
	}

	//听课(签到)人数
	public function getDistinctMobileSigninCount($training_ids){
		$sql = 'tp.training_id in('.implode(',',$training_ids).') and tp.participate_way=1 and tp.status=1 and ts.id IS NOT NULL';
        $count = \Yii::app()->db->createCommand()
            ->select("count(distinct tp.mobile) as c ")
            ->from("e_training_participate as tp")
            ->leftJoin('e_training_participate_signin_log as ts', 'tp.id=ts.participate_id')
            ->where($sql)
            ->queryAll();
		return isset($count[0]['c']) ? $count[0]['c'] : 0;
	}

}