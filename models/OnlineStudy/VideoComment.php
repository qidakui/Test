<?php
/**
 * This is the model class for table "{{local_video}}".
 *
 * The followings are the available columns in table '{{local_video}}':
 * @property integer $id
 * @property integer $video_id
 * @property integer $parent_id
 * @property integer $user_id
 * @property string $comment
 * @property integer $up_count
 * @property integer $down_count
 * @property integer $reply_count
 * @property integer $status
 * @property integer $is_delete
 * @property string $create_time
 * @property string $update_time
 */
namespace application\models\OnlineStudy;
use application\models\User\UserBrief;
use OperationLog;
class VideoComment extends \CActiveRecord
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
        return '{{video_comment}}';
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
     * @return LocalVideo the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return array
     * 增加默认查询条件
     * by:wenlh
     */
    public function defaultScope()
    {
        return array(
            'condition'=>'status=1',
        );
    }

    /**
     * @param $id
     * @throws \CDbException
     * 删除评论
     * by:wenlh
     */
    public function del_comment($id){
        $comment = $this->model()->findByPk($id);
        if(!empty($comment)){
            $comment->is_delete = 1;
            $comment->save();
            if($comment->parent_id)
                $this->model()->updateCounters(array('reply_count'=>-1),':id=id',array(':id'=>$comment->parent_id));
            $sub_comment_count = $this->updateAll(array('is_delete'=> 1),'parent_id=:parent_id',array(':parent_id'=>$id));
            LocalVideo::model()->updateCounters(array('reply_count'=>(-1-$sub_comment_count)),':id=id',array(':id'=>$comment->video_id));
            OperationLog::addLog(OperationLog::$operationVideoComment, 'del', '删除评论', $id, array(), array());
            \CreditLog::addCreditLog(\CreditLog::$creditVideo,\CreditLog::$typeKey[11],$id,'subtract','',$comment->user_id);
            return true;
        }
    }

    public function getlist($con, $orderBy, $order, $limit, $offset)
    {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            if( !isset($con['is_delete']) ){
                $con['is_delete'] = 0;
            }
            if(isset($con['time'])){
                $criteria->addBetweenCondition('create_time',$con['time'][0],$con['time'][1]);
                unset($con['time']);
            }
            foreach ($con as $key => $val) {
                if($key=='comment'){
                    $criteria->compare($key, $val, true);
                }else{
                    $criteria->compare($key, $val);
                }
            }
        }
        if (!empty($orderBy) && !empty($order)) {
            $criteria->order = sprintf('%s %s', $order, $orderBy);//排序条件
        }
        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        foreach ($ret as $k => $v) {
            $data[$k] = $v->attributes;
            //计算回复数
            if(empty($v['parent_id'])){
                $data[$k]['reply_count'] = $this->getCount(array('ancestor_comment_id'=>$v['id'],'is_delete'=>0));
            }else{
                $data[$k]['reply_count'] = $this->getCount(array('parent_id'=>$v['id'],'is_delete'=>0));
            }
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
    
    //统计评论数
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
