<?php

/**
 * desc:用户反馈
 * author:besttaowenjing@163.com
 * date:2016-7-7
 */
namespace application\models\Comment;
use application\models\ServiceRegion;
class Comment extends \CActiveRecord {

    public function tableName() {
        return '{{comment}}';
    }

    public function rules() {
        return array(
            array('_delete', 'numerical', 'integerOnly' => true),
            array('branch_id, user_id', 'length', 'max' => 11),
            array('column_id', 'length', 'max' => 20),
            array('user_name', 'length', 'max' => 55),
            array('ip', 'length', 'max' => 16),
            array('_create_time', 'safe'),
            array('id, branch_id, column_id, user_id, user_name, IP, _create_time, _delete', 'safe', 'on' => 'search'),
        );
    }

    public function relations() {
        return array(
        );
    }

    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'branch_id' => '分支id',
            'column_id' => '栏目',
            'user_id' => '用户id',
            'user_name' => '用户名',
            'ip' => 'IP',
            '_create_time' => '反馈时间',
            '_delete' => '0为正常1为删除',
        );
    }

    public function search() {
        
    }

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * desc:查询留言列表
     */
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
        $data = self::model()->findAll($criteria);
        foreach($data as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['user_name'] = urldecode($v['user_name']);
            if($v['branch_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($v['branch_id']);
            }
            $data[$k]['city_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
        }
        $count = self::model()->count($criteria);
	return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count);
    }
}
