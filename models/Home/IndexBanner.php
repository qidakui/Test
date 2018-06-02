<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/24
 * Time: 11:27
 */
/**
 * This is the model class for table "{{index_banner}}".
 *
 * The followings are the available columns in table '{{index_banner}}':
 * @property string $id
 * @property string $title
 * @property string $link
 * @property string $pic_path
 * @property integer $type
 * @property string $content
 * @property string $srot
 * @property string $branch_id
 * @property string $create_user_id
 * @property integer $_delete
 * @property string $_update_time
 * @property string $_create_time
 */
namespace application\models\Home;
use application\models\Admin\Admin;
use application\models\ServiceRegion;
use application\models\Admin\AdminRole;
class IndexBanner extends \CActiveRecord
{
    private $typeKey = array(
        1 => '链接跳转',
        2 => '图文混排',
    );
    private $claimKey = array(
        0 => '地区名称',
        1 => '全部地区',
        2 => '部分地区',
        3 => '单个地区',
    );
    private $statusKey = array(
        1 => '上架',
        2 => '下架'
    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{index_banner}}';
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
            'pic_path' => '图片路径',
            'type' => '类型1链接跳转2图文混排',
            'content' => '内容',
            'sort' => '排序',
            'branch_id' => '分之id',
            'create_user_id' => '创建人',
            'colour_number' => 'banner背景色值',
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
     * @return IndexBanner the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function bannerSave($data){
        $model = new self();
        $model->title           = $data['title'];
        $model->type            = $data['type'];
        $model->link            = $data['link'];
        $model->content         = $data['content'];
        $model->branch_id       = $data['branch_id'];
        $model->pic_path        = $data['pic_path'];
        $model->sort            = $data['sort'];
        $model->create_user_id  = $data['create_user_id'];
        $model->colour_number   = $data['colour_number'];
        $model->is_claim        = isset($data['is_claim'])?$data['is_claim']:'0';
        $model->_create_time    = date('Y-m-d H:i:s');
        $model->_update_time    = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        $addBranch = IndexBannerTotal::model()->addBanner($id,$data['branch_list']);
        return $id;
    }

    public function bannerUpdate($id, $data){
        if(empty($id)){
            return false;
        }
        $model = self::model()->findbypk($id);
        $model->title           = $data['title'];
        $model->type            = $data['type'];
        $model->link            = $data['link'];
        $model->content         = $data['content'];
        $model->branch_id       = $data['branch_id'];
        $model->pic_path        = $data['pic_path'];
        $model->sort            = $data['sort'];
        $model->colour_number   = $data['colour_number'];
        $model->is_claim        = isset($data['is_claim'])?$data['is_claim']:'0';
        $model->create_user_id  = $data['create_user_id'];
        $model->_create_time    = date('Y-m-d H:i:s');
        $model->_update_time    = date('Y-m-d H:i:s');
        $model->save();
        $id  = $model->primaryKey;
        $editBranch = IndexBannerTotal::model()->editBanner($id,$data['branch_list']);
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
        //判断是否为超级管理员
        $adminrole = AdminRole::model()->find('user_id=:user_id', array('user_id' => \Yii::app()->user->user_id));
        $adminrole = empty($adminrole) ? array() : $adminrole->attributes;
        $role_id = $adminrole['role_id'];
        foreach($ret as $k => $v){
            $data[$k] = $v->attributes;
            if(in_array($role_id, array(2))){
                if(in_array($data[$k]['is_claim'], array(0))){
                     $data[$k]['city_name'] = !empty($regionArr[$data[$k]['branch_id']]) ? $regionArr[$data[$k]['branch_id']] : '全国';
                }else{
                     $data[$k]['city_name'] = isset($this->claimKey[$data[$k]['is_claim']]) ? $this->claimKey[$data[$k]['is_claim']] : '';
                }
            }else{
                $data[$k]['city_name']   = !empty($regionArr[$data[$k]['branch_id']]) ? $regionArr[$data[$k]['branch_id']] : '全国';
            }
            $data[$k]['user_name']   = !empty($adminArr[$data[$k]['create_user_id']]) ? $adminArr[$data[$k]['create_user_id']] : '';
            $data[$k]['type_name']   = isset($this->typeKey[$data[$k]['type']]) ? $this->typeKey[$data[$k]['type']] : '';
            $data[$k]['claim_name']   = isset($this->claimKey[$data[$k]['is_claim']]) ? $this->claimKey[$data[$k]['is_claim']] : '';
            $data[$k]['sta_name']   = isset($this->statusKey[$data[$k]['status']]) ? $this->statusKey[$data[$k]['status']] : '';
        }
        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
    public function auditdatum($company_id,$editarray,$oper = null){
        $flag = $this->updateByInfo($company_id,$editarray);
        $editTotal = IndexBannerTotal::model()->updateByInfo($company_id,$editarray);
        return $flag;
    }
    /**
     * 修改信息
     */
     public function updateByInfo($company_id = null,$attributes = null){
        $editres = $this->updateByPk($company_id,$attributes);
        return $editres;
    }
    /**
     * 获取广告图数量
     */
    public function  BannerCount($branch_id){
        $findInfo = IndexBanner::model()->count('branch_id=:branch_id and _delete=:_delete and status=:status and is_admin=:is_admin and is_claim=:is_claim',array('branch_id'=>$branch_id,'_delete'=>0,'status'=>1,'is_admin'=>0,'is_claim'=>0));
        return $findInfo;  
    }
}