<?php
/**
 * Created by PhpStorm.
 * User: wenlh
 * Date: 2016/5/12
 * Time: 18:11
 */
/**
 * This is the model class for table "{{local_video}}".
 *
 * The followings are the available columns in table '{{local_video}}':
 * @property integer $id
 * @property string $branch_id
 * @property integer $is_show_all
 * @property integer $status
 * @property integer $is_delete
 * @property integer $create_user
 * @property integer $change_user
 * @property string $create_time
 * @property string $update_time
 */
namespace application\models\OnlineStudy;
use application\models\Admin\Admin;
class StudentDynamicManage extends \CActiveRecord
{
    public $status_name;
    private $statusKey = array(
        0 => '启用',
        1 => '停用',
    );

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{student_dynamic_manage}}';
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
            'id' => 'ID',
            'branch_id' => '分支ID',
            'is_show_all' => '是否显示全部',
            'create_user' => '创建人',
            'change_user' => '修改人',
            'status' => '状态',
            'is_delete' => '是否删除',
            'update_time' => '更新时间',
            'create_time' => '创建时间',
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
     * @return LocalVideo the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    //获取指定分支是否显示全国学员动态
    public function is_show_all_by_branch_id($branch_id){
        $criteria = new \CDbCriteria;
        $criteria->compare('branch_id', $branch_id);
        $criteria->compare('is_delete', 0);
        if (!empty($orderBy) && !empty($order)) {
            $criteria->order = sprintf('%s %s', $order, $orderBy);//排序条件
        }
        $ret = self::model()->find($criteria);
        return empty($ret) || $ret->is_show_all;
    }

    //修改指定分支是否显示全国学员动态
    public function change_show_by_branch_id($branch_id, $user_id, $is_show_all){
        $criteria = new \CDbCriteria;
        $criteria->compare('branch_id', $branch_id);
        $criteria->compare('is_delete', 0);
        $model = self::model()->find($criteria);
        if(empty($model)){
            $model = new self();
            $model->create_user = $user_id;
            $model->create_time = date('Y-m-d H:i:s');
            $model->save();
        }
        $model->branch_id = $branch_id;
        $model->is_show_all = $is_show_all == 'true';
        $model->change_user = $user_id;
        $model->status = 1;
        $model->is_delete = false;
        $model->update_time = date('Y-m-d H:i:s');
        $model->save();
        $id = $model->primaryKey;
        return $id;
    }

    //根据branch_id查询结果并返回数组
    public function get_array_list_by_branch_id($branch_id){
        $criteria = new \CDbCriteria;
        $criteria->compare('branch_id', $branch_id);
        $criteria->compare('is_delete', 0);
        $ret = self::model()->find($criteria);
        return $ret->attributes;
    }



}
