<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/7/13
 * Time: 17:24
 */


namespace application\models\Question;
use application\models\ServiceRegion;
class ExpertApply extends \CActiveRecord
{
    private $statusKey = array(
        '0' => '待审核',
        '1' => '通过',
        '2' => '未通过',
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{expert_apply}}';
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
     * @return Expert the static model class
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
        foreach ($provinceListObj as $key => $val) {
            if ($val->filiale_id != 999999) {
                $provinceListObj[$key]->filiale_id = substr($val->filiale_id, 0, 2);
            }
        }
        $provinceListArr = objectToKeywordArr($provinceListObj, 'filiale_id', 'region_name');

        $user_ids = value_to_array($ret,'member_user_id');
        //如果$user_ids === array(),$answer_criteria->compare('user_id', $user_ids)相当于没执行
        if(!empty($user_ids)){
            $answer_criteria = new \CDbCriteria;
            $answer_criteria->compare('user_id', $user_ids);
            $answer_criteria->select = 'user_id,
                                        count(id) answer_count,
                                        sum(
                                            CASE
                                            WHEN `status` = 1 THEN 1
                                            ELSE
                                                NULL
                                            END
                                        ) adopt_count';
            $answer_criteria->index = 'user_id';
            $answer_criteria->group = 'user_id';
            $answer_info = Answer::model()->findAll($answer_criteria);
        }
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['region_name']        = !empty($provinceListArr[$data[$k]['filiale_id']]) ? $provinceListArr[$data[$k]['filiale_id']] : '';
            $data[$k]['status_name']        = !empty($this->statusKey[$data[$k]['status']]) ? $this->statusKey[$data[$k]['status']] : '';
            $data[$k]['answer_count']       = isset($answer_info[$v->member_user_id]) && !empty($answer_info[$v->member_user_id]->answer_count) ? $answer_info[$v->member_user_id]->answer_count : 0;
            $data[$k]['adopt_count']       = isset($answer_info[$v->member_user_id]) && !empty($answer_info[$v->member_user_id]->adopt_count) ? $answer_info[$v->member_user_id]->adopt_count : 0;
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

    public function expertUpdate($data){
        $id    = !empty($data['id']) ? $data['id'] : 0;
        $model = self::model()->findByPk($id);
        if(empty($model)){
            return false;
        }
        foreach($data as $key => $val){
            $model->$key = $val;
        }
        $model->_update_time      = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }
}