<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/15
 * Time: 14:16
 */
namespace application\models;
use application\models\Admin\Admin;
use application\models\Home\IndexBanner;
use application\models\Home\Notice;
use application\models\Member\CommonMember;

class AdminOperationLog extends \CActiveRecord
{
    private $operationKey = array(
        'add' => '新增',
        'edit'=> '修改',
        'del' => '删除',
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{admin_operation_log}}';
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
     * @return AdminOperationLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param $action 来源
     * @param $userId 用户id
     * @param string $operation 操作内容 'del','edit','add'
     * @param $ip
     * @param $column 栏目
     * @param $relatedId 来源id
     * @param $oldData 老数据
     * @param $newData 新数据
     * @return mixed
     */
    public function addLog($action , $userId, $userName, $operation = 'add', $ip, $column, $relatedId, $oldData, $newData){
        $model = new self();
        $model->source      = !empty($action) ? $action : '';
        $model->user_id     = $userId;
        $model->user_name   = $userName;
        $model->operation   = $operation;
        $model->related_id  = $relatedId;
        $model->ip          = $ip;
        $model->column      = $column;
        $model->old_data      = $oldData;
        $model->new_data      = $newData;
        $model->_create_time  = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        return $id;
    }

    public function findLog($con, $orderBy, $order, $limit, $offset){
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

        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            $data[$k]['operation_name'] = !empty($this->operationKey[$data[$k]['operation']]) ? $this->operationKey[$data[$k]['operation']] : '';

            $oldData = unserialize($data[$k]['old_data']);
            $newData = unserialize($data[$k]['new_data']);
            if(isset($oldData['_delete']) || isset($newData['_delete'])){
                unset($oldData['_delete'], $newData['_delete']);
            }
            
            $data[$k]['content'] = $this->_operation($data[$k]['related_id'], $data[$k]['operation'], $data[$k]['source'], $oldData, $newData);
            $data[$k]['new_data'] = unserialize($data[$k]['new_data']);
            $branch_name = Admin::model()->getAdminId(array('id'=>$data[$k]['user_id']));
            $data[$k]['branchName'] = $branch_name['branchName'];
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

    private function _operation($relatedId, $val, $source, $oldData, $newData){
        switch($val){
            case 'add':
                return $this->operationKey[$val] . ' id:' .$relatedId;
                break;
            case 'edit':
                return $this->operationKey[$val].' '.$this->_disposeData($source, $oldData, $newData);
                break;
            case 'del':
                return $this->operationKey[$val] . ' id:' .$relatedId;
                break;
        }
    }

    /**
     * 处理数据
     * @param $source 数据来源
     */
    private function _disposeData($source, $oldData, $newData){
        switch($source){
            case \OperationLog::$operationIndexBanner:
                //$diffData = $this->_diffData($oldData, $newData);
                $indexBannerArr = IndexBanner::model()->attributeLabels();
                return $this->_retrunDisposeData($oldData, $newData, $indexBannerArr);
                break;
            case \OperationLog::$operationNotice:
                //$diffData = $this->_diffData($oldData, $newData);
                $indexBannerArr = Notice::model()->attributeLabels();
                return $this->_retrunDisposeData($oldData, $newData, $indexBannerArr);
                break;
            case \OperationLog::$operationMember:
                //$diffData = $this->_diffData($oldData, $newData);
                $indexBannerArr = CommonMember::model()->attributeLabels();
                return $this->_retrunDisposeData($oldData, $newData, $indexBannerArr);
                break;
        }
    }

    /**
     * 对比数据
     * @param $oldData 老数据
     * @param $newData 新数据
     * @return array
     */
    private function _diffData($oldData, $newData){
        return array_diff_assoc($oldData, $newData);
    }

    /**
     * @param $diffData  返回的对比数据
     * @param $indexArr KEY => VALUE 对应关系
     * @return string
     */
    private function _retrunDisposeData($oldData, $newData, $indexArr){
        $oldTemp = '';
        $newTemp = '';
        if(!empty($newData)){
            foreach($newData as $key => $val){
                $newTemp .= isset($indexArr[$key]) ? $indexArr[$key] . '：'  : '';
                $newTemp .= isset($val) ? $val. ' ' : '';
            }
        }
        if(!empty($oldData)){
            foreach($oldData as $key => $val){
                $oldTemp .= isset($indexArr[$key]) ? $indexArr[$key] . '：'  : '';
                $oldTemp .= isset($val) ? $val. ' ' : '';
            }
        }
        return '<br />旧数据：'.$oldTemp.' <br />新数据：'.$newTemp;
    }
}