<?php

/**
 * This is the model class for table "{{title}}".
 *
 * The followings are the available columns in table '{{title}}':
 * @property string $id
 * @property string $title
 * @property string $content
 */
namespace application\models\User;
use application\models\ServiceRegion;
class UserBrief extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'UserBrief';
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
        // @todo Please modify the following code to remove attributes that should not be searched.
    }

    public function getDbConnection() {
        return \Yii::app()->serdb;
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Title the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }


    public function getQuestionUserArr($userIds){
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('UIN', $userIds);
        $userObj = self::model()->findAll($criteria);
        $userArr = $this->getQuestionUserId($userObj);//获取答案对应的问题id与用户id的二维数组 arr[question_id][user_id]
        return $userArr;
    }

    public function getUserBriefObjByUIds($userIds){
        $criteria = new \CDbCriteria;
        $criteria->addInCondition('UIN', $userIds);
        $userObj = self::model()->findAll($criteria);
        return !empty($userObj)?$userObj:array();
    }

    public function getUserInfo($con){
        if(!isset($con['Nick']) && !isset($con['UserName']) && !isset($con['left(regionID,2)'])){
            return array();
        }
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                if($key == 'UIN'){
                    $criteria->addInCondition($key, $val);
                } else if(is_array($val) && isset($val[0])){
                    $criteria->compare($key, $val[1],true);
                } else {
                    $criteria->compare($key, $val);
                }
            }
        }

        $ret = self::model()->findAll($criteria);
        return !empty($ret) ? $ret : array();
    }

    private function getQuestionUserId($userObj){
        if(empty($userObj)){
            return array();
        }

        foreach($userObj as $user){
            $data[$user->UIN]['UserName']   = !empty($user->UserName) ? $user->UserName : '';
            $data[$user->UIN]['Nick']       = !empty($user->Nick) ? $user->Nick : '';
            $data[$user->UIN]['sMobile']    = !empty($user->sMobile) ? $user->sMobile : '';
            $data[$user->UIN]['email']      = !empty($user->email) ? $user->email : '';
        }
        return !empty($data) ? $data : array();
    }
    
    public function getList($con, $orderBy, $order, $limit, $offset){
        $data = array();
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
        if(!empty($ret)){
            foreach($ret as $user){
                $regionIds[] = !empty($user->regionID) ? substr($user->regionID, 0 ,2) : '';
            }        
            $criteria = new \CDbCriteria;
            $criteria->addInCondition('left(region_id,2)', $regionIds);
            $criteria->compare('is_parent', 0);
            $ServiceRegionObj = ServiceRegion::model()->findAll($criteria);
            foreach($ServiceRegionObj as $serviceRegion){
                if(!empty($serviceRegion)){
                    $serviceRegionArr[$serviceRegion->region_id] = $serviceRegion->region_name;
                }
            }
            foreach($ret as $k => $v){
                $data[$k] = $v->attributes;
                $region_id = !empty($data[$k]['regionID']) ? substr($data[$k]['regionID'],0 , 2) : '';
                $data[$k]['city_name']       = !empty($serviceRegionArr[$region_id]) ? $serviceRegionArr[$region_id] : '';
            }
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);        
    }
}