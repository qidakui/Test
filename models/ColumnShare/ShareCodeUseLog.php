<?php
namespace application\models\ColumnShare;
use application\models\Activity\ActivityParticipate;
class ShareCodeUseLog extends \CActiveRecord {

    public function tableName() {
        return '{{share_code_use_log}}';
    }

    public function rules() {
        return array();
    }

    public function relations() {
        return array(
        );
    }

    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'column_id' => '栏目id',
            'column_type' => '栏目类型',
            'share_code' => 'Share Code',
            'use_member_user_id' => '分享码 使用人id',
            'mobile' => '报名人手机号',
            'replace' => '1 代报名',
            'ip' => '报名人ip',
            '_create_time' => '使用时间',
        );
    }

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /*
     * 获取报名人次
     */
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
 

}
