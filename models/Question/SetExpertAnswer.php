<?php

namespace application\models\Question;

use application\models\Member\CommonMember;
use application\models\ServiceRegion;

class SetExpertAnswer extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{set_expert_answer}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
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

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Follow the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
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
        $provinceListObj = ServiceRegion::model()->getProvinceList();
        $provinceListArr = objectToKeywordArr($provinceListObj, 'region_id', 'region_name');

        $memberUserIds   = columnToArr($ret, 'member_user_id');

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('member_user_id', $memberUserIds);
        $commonMemberObj = CommonMember::model()->findAll($criteria);

        $commonMemberArr = objectToKeywordArr($commonMemberObj, 'member_user_id', 'member_user_name');

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['region_name']        = !empty($provinceListArr[$data[$k]['filiale_id']]) ? $provinceListArr[$data[$k]['filiale_id']] : '';
            $data[$k]['member_user_name']   = !empty($commonMemberArr[$data[$k]['member_user_id']]) ? $commonMemberArr[$data[$k]['member_user_id']] : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

    /**
     * 添加
     */
    public function addSave($data = null){
        if(!empty($data)){
            $model = new SetExpertAnswer();
            $model->user_id = $data['user_id'];
            $model->filiale_id = $data['filiale_id'];
            $model->member_user_id = $data['member_user_id'];
            $model->_create_time = date('Y-m-d H:i:s');
            $model->_update_time = date('Y-m-d H:i:s');
            if($model->save())
                $id  = $model->primaryKey;
            return $id;
        }
    }
    /**
     * 编辑
     */
    public function editsave($data = null){
        if(!empty($data)){
            unset($data['_create_time']);
            $this->user_id = $data['user_id'];
            $this->filiale_id = $data['filiale_id'];
            $this->member_user_id = $data['member_user_id'];
            $this->_update_time = date('Y-m-d H:i:s');
            if($this->save()){
                return  $this->primaryKey;
            }
        }
    }

}
