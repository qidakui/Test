<?php
namespace application\models\Prize;
class PrizeTotal extends \CActiveRecord {

    public function tableName() {
        return '{{prize_total}}';
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
            'member_user_id' => '抽奖用户id',
            'rules_id' => '规则id',
            'prize_id' => '抽奖表主键id',
            'total' => '剩余抽奖次数',
        );
    }

    public function search() {
        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('member_user_id', $this->member_user_id);
        $criteria->compare('rules_id', $this->rules_id);
        $criteria->compare('prize_id', $this->prize_id);
        $criteria->compare('total', $this->total);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function getFind($con = array()) {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
        }
        $ret = self::model()->find($criteria);
        if (empty($ret)) {
            return array();
        } else {
            return $ret;
        }
    }
    
    /**
     * 增加或修改
     */
    public function saveTotal($prizeTotal) {
        $obj = (object) null;
        $result = $prizeTotal->save();
        return $result;
    }

}
