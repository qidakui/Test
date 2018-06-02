<?php

/**
 * This is the model class for table "{{product_renewal_detail}}".
 *
 * The followings are the available columns in table '{{product_renewal_detail}}':
 * @property string $id
 * @property string $member_user_id
 * @property integer $obj_id
 * @property string $title
 * @property string $_create_time
 */
namespace application\models\Product;
use application\models\User\UserBrief;
use application\models\ServiceRegion;
use application\models\Member\CommonMember;
class ProductRenewalDetail extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{product_renewal_detail}}';
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

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ProductRenewalDetail the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 查询导出列表
         */
        public function getlist($con, $orderBy, $order, $limit, $offset){
            $regionIds = array();
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
            $userIds = columnToArr($ret, 'member_user_id');
            $criteria = new \CDbCriteria;
            $criteria->addInCondition('UIN', $userIds);
            $userObj = UserBrief::model()->findAll($criteria);
            foreach($userObj as $user){
                $userArr[$user->UIN] = $user->attributes;
            }

            foreach($userObj as $user){
                $regionIds[] = !empty($user->regionID) ? substr($user->regionID, 0 ,2) : '';
            }
            $criteria = new \CDbCriteria;
            $criteria->addInCondition('left(region_id,2)', $regionIds);
            $criteria->compare('is_parent', 0);
            $ServiceRegionObj = ServiceRegion::model()->findAll($criteria);
            foreach($ServiceRegionObj as $serviceRegion){
                if(!empty($serviceRegion)){
                    $serviceRegionArr[$serviceRegion->region_id] = $serviceRegion->region_name;
                }
            }
            foreach($ret as $k => $v){
                $data[$k] = $v->attributes;
                $region_id = !empty($userArr[$data[$k]['member_user_id']]['regionID']) ? substr($userArr[$data[$k]['member_user_id']]['regionID'],0 , 2) : '';
                $data[$k]['city_name']       = !empty($serviceRegionArr[$region_id]) ? $serviceRegionArr[$region_id] : '';
                $data[$k]['user_name']       = !empty($userArr[$data[$k]['member_user_id']]['UserName']) ? $userArr[$data[$k]['member_user_id']]['UserName'] : '';
                $data[$k]['nike_name']       = !empty($userArr[$data[$k]['member_user_id']]['Nick']) ? $userArr[$data[$k]['member_user_id']]['Nick'] : '';
                $data[$k]['mail']            = !empty($userArr[$data[$k]['member_user_id']]['email']) ? $userArr[$data[$k]['member_user_id']]['email'] : '';
                $data[$k]['mobile']          = !empty($userArr[$data[$k]['member_user_id']]['sMobile']) ? $userArr[$data[$k]['member_user_id']]['sMobile'] : '';
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        }
        /**
         * 导出列表
         */
        function exportrenewal($filiale_id,$starttime,$endtime){
             header("Content-type: text/html; charset=utf-8");
            if($filiale_id==BRANCH_ID || empty($filiale_id)){
		$city[0]['region_name'] = '全国';
            }else{
		$city = ServiceRegion::model()->getBranchToCity($filiale_id);
            }
		$filiale_name = isset($city[0]['region_name'])?$city[0]['region_name']:'';
                $con = array();
                if($starttime){
                    $con['_create_time>'] = $starttime;
                    $con['_create_time<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
                } 
                if ($filiale_id == BRANCH_ID) {
                    $findAll = Product::model()->findInfo(array('template_type'=>1),'all');
                    foreach ($findAll as $key=>$item){
                        $findIds [] = $item->id;
                    }
                    $con['obj_id'] = $findIds;
                } else {
                    $find_branch_All = Product::model()->findInfo(array('template_type'=>1,'filiale_id'=>Yii::app()->user->branch_id),'all');
                    foreach ($findAll as $key=>$item){
                        $findIds [] = $item->id;
                    }            
                    $con['obj_id'] = $findIds;
                }
             $list = $this->getlist($con, 'desc', 'id','-1','-1');
             $data = $tmp = array();
             foreach($list['data'] as $k=>$v){
                 $tmp['city_name']       = $v['city_name'];
                 $tmp['user_name']       = $v['user_name'];
                 $tmp['nike_name']       = $v['nike_name'];
                 $tmp['mobile']          = $v['mobile'];
                 $tmp['mail']            = $v['mail'];
                 $tmp['create_time']     = $v['_create_time'];
                 $data[] = $tmp;
             }
            $headerstr = '地区,账号,昵称,手机号,邮箱,下载时间';
            $header = explode(',',$headerstr);
            \FwUtility::exportExcel($data, $header,'产品-续费统计','产品-续费统计_'.$filiale_name.'-'.substr($starttime,0,7));             
        }        
}
