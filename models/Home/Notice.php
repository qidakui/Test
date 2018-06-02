<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/27
 * Time: 16:56
 */
namespace application\models\Home;
use application\models\Admin\Admin;
use application\models\ServiceRegion;
class Notice extends \CActiveRecord
{
    private $typeKey = 0;
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{notice}}';
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
            'id' => 'id',
            'title' => '标题',
            'link' => '跳转链接',
            'type' => '类型1链接跳转',
            'content' => '内容',
            'sort' => '排序',
            'branch_id' => '分之id',
            'create_user_id' => '创建人',
            '_delete' => '是否删除0正常1删除',
            '_update_time' => '更新时间',
            '_create_time' => '创建时间',
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
     * @return Notice the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function noticeSave($data){
        $model = new self();
        $model->title           = $data['title'];
        $model->type            = $this->typeKey;
        $model->link            = $data['link'];
        $model->content         = $data['content'];
        $model->notice_desc     = $data['notice_desc'];
        $model->branch_id       = $data['branch_id'];
        $model->sort            = $data['sort'];
        $model->create_user_id  = $data['create_user_id'];
        $model->_create_time    = date('Y-m-d H:i:s');
        $model->_update_time    = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function noticeUpdate($id, $data){
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->title           = $data['title'];
        $model->type            = $this->typeKey;
        $model->link            = $data['link'];
        $model->content         = $data['content'];
        $model->notice_desc     = $data['notice_desc'];
        $model->branch_id       = $data['branch_id'];
        $model->sort            = $data['sort'];
        $model->create_user_id  = $data['create_user_id'];
        $model->_create_time    = date('Y-m-d H:i:s');
        $model->_update_time    = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
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

        $userIds = columnToArr($ret, 'create_user_id');
        $branchIds = columnToArr($ret, 'branch_id');

        $criteria = new \CDbCriteria;
        $criteria->addInCondition('id', $userIds);
        $adminObj = Admin::model()->findAll($criteria);

        $adminArr           = objectToKeywordArr($adminObj, 'id', 'user_name');
        $serviceRegionObj   = ServiceRegion::model()->getBranchToCity($branchIds);
        foreach($serviceRegionObj as $region){
            $branch_id = !empty($region->filiale_id) ? substr($region->filiale_id,0 , 2) : 0;
            $regionArr[$branch_id] = $region->region_name;
        }

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['city_name']   = !empty($regionArr[$data[$k]['branch_id']]) ? $regionArr[$data[$k]['branch_id']] : '全国';
            $data[$k]['user_name']   = !empty($adminArr[$data[$k]['create_user_id']]) ? $adminArr[$data[$k]['create_user_id']] : '';
            $data[$k]['type_name']   = isset($this->typeKey[$data[$k]['type']]) ? $this->typeKey[$data[$k]['type']] : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
}