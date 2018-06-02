<?php

/**
 * This is the model class for table "{{recruitment_user}}".
 *
 * The followings are the available columns in table '{{recruitment_user}}':
 * @property string $id
 * @property string $user_name
 * @property string $company_name
 * @property string $company_contacts
 * @property string $mobile_phone
 * @property string $email
 * @property string $password
 * @property string $ec_salt
 * @property integer $last_login
 * @property string $last_ip
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Recruitment;
use application\models\Recruitment\RecruitmentCompany;
use application\models\ServiceRegion;
use application\models\Admin\Admin;
class RecruitmentUser extends \CActiveRecord
{
        private $audit_status_name = array(
                1 => '待审核',
                2 => '审核驳回',
                3=>  '审核通过'
        );
        private $status_name = array(
                0 => '正常',
                1 => '冻结'
        );
        public $cat_value = array(
            1 => '房地产公司',
            2 => '其他甲方',
            3 => '施工大系统',
            4 => '施工企业',
            5 => '中介',
            6 => '监理',
            7 => '设计院',
            8 => '学校',
            9 => '劳务公司',
            10 => '其它'
        );        
        /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{recruitment_user}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			
		);
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
		// @todo Please modify the following code to remove attributes that should not be searched.
	}
        public function getDbConnection() {
            return \Yii::app()->recruitmentdb;
        } 
	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return RecruitmentUser the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        public function get_recruitment_list($con, $orderBy, $order, $limit, $offset){
            $criteria = new \CDbCriteria;
            if(!empty($con['province'])){
                $province = new \CDbCriteria;
                $province->addInCondition('province', array($con['province']));
                $province->compare('_delete', 0);
                $companyDetalObj = RecruitmentCompany::model()->findAll($province); 
                foreach ($companyDetalObj as $key=>$item){
                    $company_id[] = $item['company_id'];
                }
                $con['id'] = $company_id;
                unset($con['province']);
            }
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
            
            $company_id = columnToArr($ret, 'id');
            $criteria = new \CDbCriteria;
            $criteria->addInCondition('company_id', $company_id);
            $companyDetalObj = RecruitmentCompany::model()->findAll($criteria); 
            foreach ($companyDetalObj as $key=>$item){
                 $serviceRegionObj   = ServiceRegion::model()->getRegionName($item['province']);
                 $regionArr[$item['company_id']] = $serviceRegionObj;
            }
            $audit_id = columnToArr($ret, 'audit_id');
            $criteria = new \CDbCriteria;
            $criteria->addInCondition('id', $audit_id);
            $adminInfo = Admin::model()->find($criteria);
            foreach($ret as $k => $v){
                $data[$k] = $v->attributes;
                $data[$k]['city_name']  = !empty($regionArr[$data[$k]['id']]) ?$regionArr[$data[$k]['id']] : '';
                $data[$k]['status_name']= isset($this->status_name[$data[$k]['status']]) ? $this->status_name[$data[$k]['status']] : '';
                $data[$k]['audit_name'] = isset($this->audit_status_name[$data[$k]['audit_status']]) ? $this->audit_status_name[$data[$k]['audit_status']] : '';
                $data[$k]['admin_name'] = !empty($adminInfo['user_name']) ? $adminInfo['user_name'] : '';
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }
    /**
     * 获取公司信息
     */
    public function findCompanyInfo($con){
        $criteria = new \CDbCriteria;
            if(!empty($con)){
                foreach($con as $key => $val){
                    $criteria->compare($key, $val);
                }
            }
         $ret = self::model()->findAll($criteria);
         $company_id = columnToArr($ret, 'id');
         $criteria = new \CDbCriteria;
         $criteria->addInCondition('company_id', $company_id);
         $companyDetalObj = RecruitmentCompany::model()->findAll($criteria);
         $provinceIds = columnToArr($companyDetalObj, 'province');
         $cityIds = columnToArr($companyDetalObj, 'city');
         $serviceRegionObj   = ServiceRegion::model()->getBranchToCity($provinceIds);
         $cityobj =  ServiceRegion::model()->getBranchInfo($cityIds);
         foreach($serviceRegionObj as $key=>$region){
                 $region_id = !empty($region->region_id) ? substr($region->region_id,0 , 2) : 0;
                 $regionArr[$region_id]['province'] = $region->region_name;
                 $regionArr[$region_id]['city'] = $cityobj[$key]['region_name'];
         }
        foreach ($ret as $k=>$v){ 
            $data = $v->attributes;
            $data['province_name']  = !empty($regionArr[$region_id]['province']) ? $regionArr[$region_id]['province'] : '';
            $data['city_name']      = !empty($regionArr[$region_id]['city']) ? $regionArr[$region_id]['city'] : '';
            $data['status_name']    = isset($this->status_name[$data['status']]) ? $this->status_name[$data['status']] : '';
            $data['audit_name']     = isset($this->audit_status_name[$data['audit_status']]) ? $this->audit_status_name[$data['audit_status']] : '';
            $data['cat_name']       = !empty($companyDetalObj)?isset($this->cat_value[$companyDetalObj[$k]->cat_id]) ? $this->cat_value[$companyDetalObj[$k]->cat_id] : '':'';
            $data['detail']         = !empty($companyDetalObj)?objectToArr($companyDetalObj)[$k]:array();
        }

        return !empty($data)?$data:array();
    }
    /**
     * 审核资料
     */
    public function auditdatum($company_id, $oper,$editarray){
        if($oper == 'through' || $oper == 'blacklist'){
            $flag = $this->updateByInfo($company_id,$editarray);
        }else{
            $flag = RecruitmentCompany::model()->updateByInfo($company_id,$editarray);
        }
        return $flag;
    }
    /**
     * 修改信息
     */
    public function updateByInfo($company_id = null,$attributes = null){
         $editres = $this->updateByPk($company_id,$attributes);
         return $editres;
    }    
}
