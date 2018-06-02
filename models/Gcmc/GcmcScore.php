<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/13
 * Time: 14:32
 */
/**
 * This is the model class for table "{{training}}".
 *
 * The followings are the available columns in table '{{admin}}':
 * @property string $id
 * @property string $user_name
 * @property string $password
 * @property string $phone
 * @property string $email
 * @property string $random
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Gcmc;
class GcmcScore extends \CActiveRecord
{
    public $status_name;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_score}}';
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
        return array();
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
     * @return Admin the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
  
    /*
     * 根据栏目统计评分
     */
    public function statistical($column_type, $column_id){
        $criteria = new \CDbCriteria;
        $criteria->select = 'stars_num ,count(member_user_id) as member_user_id';
        $criteria->compare('column_type', $column_type);
        $criteria->compare('column_id', $column_id);
        $criteria->group = 'stars_num';
        $list = self::model()->findAll($criteria);
        $data = array('stars1'=>0,'stars2'=>0, 'stars3'=>0, 'stars4'=>0, 'stars5'=>0);
        foreach($list as $k=>$v){
            $data['stars'.$v['stars_num']] = $v['member_user_id'];
        }
        $total = array_sum($data);
        $data['total'] = $data['score'] = 0;
        if( $total ){
            $data['total'] = $total;
            $data['score'] = ($data['stars5']*10 + $data['stars4']*8 + $data['stars3']*6 + $data['stars2']*4 + $data['stars1'] * 2) / $total;
            $data['score'] = round($data['score'], 2);
        }
        return $data;
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