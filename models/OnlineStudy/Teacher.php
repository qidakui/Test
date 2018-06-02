<?php
/**
 * Created by PhpStorm.
 * User: wenlh
 * Date: 2016/5/12
 * Time: 18:11
 */
/**
 * This is the model class for table "{{study_document}}".
 *
 * The followings are the available columns in table '{{study_document}}':
 * @property string $id
 * @property string $study_document_name
 * @property integer $branch_id
 * @property integer $create_user_id
 * @property string $detail
 * @property string $document_name
 * @property integer $document_type
 * @property string $document_img
 * @property string $document_src
 * @property string $document_swf_src
 * @property integer $software_id
 * @property integer $business_id
 * @property integer $area
 * @property integer $open_count
 * @property integer $up_count
 * @property integer $down_count
 * @property integer $favourite_count
 * @property integer $share_count
 * @property integer $status
 * @property integer $is_delete
 * @property string $create_time
 * @property string $update_time
 * @property string $page_count
 */
namespace application\models\OnlineStudy;
use application\models\Admin\Admin;
class Teacher extends \CActiveRecord
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
        return '{{teacher}}';
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
            'province_id' => '省ID',
            'teacher_pic_name' => '讲师图片名称',
            'teacher_pic' => '讲师图片地址',
            'teacher_url' => '讲师介绍URL',
            'create_user' => '创建人',
            'update_user' => '更新人',
            'status' => '状态',
            'is_delete' => '是否删除',
            'create_time' => '创建时间',
            'update_time' => '修改时间',
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
     * @return StudyDocument the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    //新增讲师
    public function add_teacher_info($data){
        $info = new self();
        $info->branch_id = $data['branch_id'];
        $info->province_id = $data['province_id'];
        $info->teacher_pic = $data['teacher_pic'];
        $info->teacher_pic_name = $data['teacher_pic_name'];
        $info->teacher_url = $data['teacher_url'];
        $info->status = 1;
        $info->is_delete = 0;
        $info->create_user = \Yii::app()->user->user_id;
        $info->update_user = \Yii::app()->user->user_id;
        $info->create_time = date('Y-m-d H:i:s');
        $info->update_time = date('Y-m-d H:i:s');
        $info->save();
        return $info->primaryKey;
    }

    //删除讲师
    public function del_teacher_info($id){
        $teacher_info = $this->model()->findByPk($id);
        if(empty($teacher_info)){
            return false;
        }else{
            $teacher_info->is_delete = 1;
            $teacher_info->update_user = \Yii::app()->user->user_id;
            $teacher_info->update_time = date('Y-m-d H:i:s');
            $teacher_info->save();
            return true;
        }
    }

    //获取讲师列表
    public function get_list($con, $orderBy, $order, $limit=0, $offset=0){
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
        }
        $criteria->compare('is_delete', 0);
        if (!empty($orderBy) && !empty($order)) {
            $criteria->order = sprintf('%s %s', $order, $orderBy);//排序条件
        }
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        foreach ($ret as $k => $v) {
            $data[$k] = $v->attributes;
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
}
