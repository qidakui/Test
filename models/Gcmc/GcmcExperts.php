<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/6/20
 * Time: 10:13
 */
namespace application\models\Gcmc;
use application\models\ServiceRegion;
class GcmcExperts extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gcmc_experts}}';
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
     * @return ActivityParticipate the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    //查询列表
    public function getlist($con, $orderBy='desc', $order='id', $limit=1, $offset=0){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='name'){
                    $criteria->compare($key, $val,true);
                }else{
                    $criteria->compare($key, $val);
                }
            }
        }

        if(!empty($orderBy) && !empty($order)){
            $criteria->order = sprintf('%s %s', $order, $orderBy) ;//排序条件
        }

        $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
        $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10

        $ret = self::model()->findAll($criteria);
        $count = self::model()->count($criteria);
        if($count){
            if(isset($con['filiale_id'])){
                $city = ServiceRegion::model()->getBranchToCity($con['filiale_id']);
                $region_name = isset($city[0]['region_name'])?$city[0]['region_name']:'';
            }
        }
        $data = array();
        $show_location_arr = [0>'不显示', 1=>'首页', 2=>'地区课程讲师', 3=>'案例坐镇'];
        foreach($ret as $v){
            $v = $v->attributes;
            if(isset($con['filiale_id'])){
                $v['city_name'] = $region_name;
            }else{
                if($v['filiale_id']==BRANCH_ID){
                    $city[0]['region_name'] = '全国';
                }else{
                    $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
                }
                $v['city_name'] = isset($city[0]['region_name'])?$city[0]['region_name']:'';
            }
            $v['show_location_txt'] = $show_location_arr[$v['show_location']];
            $data[] = $v;
        }

        $data = !empty($data) ? $data : array();
        return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }


    public function saveExperts($data){
		if( isset($data['id']) && !empty($data['id']) ){
            $model = self::model()->findByPk($data['id']);
			$model->_update_time = date('Y-m-d H:i:s');
			unset($data['id']);
		}else{
			$model = new self();
			$model->_create_time = date('Y-m-d H:i:s');
			$model->_new = true;
			$model->status = 1;
		}
		
		foreach($data as $k=>$v){
			$v = in_array($k,['photo_wide','photo_vertical','photo_square']) ? $v : \CHtml::encode($v);	
			$model->$k = $v;
		}

		if( $model->save() ){
			return intval($model->primaryKey);
		}else{
			return false;
		}
	}
    
    public function getCount($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $count = self::model()->count($criteria);
        return $count;
    }


}