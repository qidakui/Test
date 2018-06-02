<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2018/1/17
 * Time: 10:13
 */
namespace application\models\Training;
use application\models\ServiceRegion;
class TrainingGenseePlan extends \CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{gensee_plan}}';
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
    public function getlist($con, $orderBy='desc', $order='date_time', $limit=1, $offset=0){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='time'){
                    $criteria->addBetweenCondition('date_time',$con['time'][0],$con['time'][1] );
                }else{
                    $criteria->compare($key, $val);
                }
            }
        }
        $criteria->select = 'date_time';
        $criteria->limit = $limit;  
        $criteria->offset = $offset;  
        $criteria->group = 'date_time';
        $criteria->order = $order.' '.$orderBy;
        $count = self::model()->count($criteria);
        if(empty($count)){
            return array('data' => array(), 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,); 
        }
        $list = self::model()->findAll($criteria);

        $date_time_arr = array();
        foreach($list as $v){
            $date_time_arr[] = $v['date_time'];
        }
        
        $date_time_filiale = array();
        if($date_time_arr){
            $criteria = new \CDbCriteria;
            $criteria->select = 'date_time,filiale_id,limit_number';
            //$criteria->select = 'DISTINCT plan_id';
            $criteria->addInCondition('date_time',$date_time_arr);
            $PlanData = self::model()->findAll($criteria);
            foreach($PlanData as $pv){
                $date_time_filiale[$pv['date_time']][$pv['filiale_id']] = isset($date_time_filiale[$pv['date_time']][$pv['filiale_id']]) ? $date_time_filiale[$pv['date_time']][$pv['filiale_id']]+$pv['limit_number'] : $pv['limit_number'];
                $date_time_filiale[$pv['date_time']][$pv['filiale_id']] = $date_time_filiale[$pv['date_time']][$pv['filiale_id']] ? $date_time_filiale[$pv['date_time']][$pv['filiale_id']] : '';
            } 
        }
        //print_r($date_time_filiale);
        $BranchList = $this->_getBranchList(false);
        $k = 0;
        $MaxValue = array();
        foreach($date_time_filiale as $_date_time=>$_v){
            
            $date_time_filiale[$k] = $date_time_filiale[$_date_time]+$BranchList;
            //日期
            $date_time_filiale[$k][0] = substr($_date_time,0,10);
            //时段(左开右闭)
            $date_time_filiale[$k][1] = '('.substr($_date_time,11,5) .'-'. date('H:i',strtotime($_date_time)+900).']';
            //合计
            $date_time_filiale[$k][2] = array_sum($date_time_filiale[$_date_time]);
            //剩余量
            $residual = 0;
            if( !isset($MaxValue[$date_time_filiale[$k][0]]) ){
                $MaxValue[$date_time_filiale[$k][0]] = TrainingGenseeMaxValue::model()->getMaxValue($date_time_filiale[$k][0]);
            }
            $residual = $MaxValue[$date_time_filiale[$k][0]] - $date_time_filiale[$k][2];
            if( $limit<=32 ){
                if( $residual<100 ){
                    $residual = '<div style="background:red;">'.$residual.'</div>';
                }elseif( $residual>=100 && $residual<400 ){
                    $residual = '<div style="background:yellow;">'.$residual.'</div>';
                }elseif( $residual>=400 ){
                    $residual = '<div style="background:#33ff00;">'.$residual.'</div>';
                }
            }
            $date_time_filiale[$k][3] = $residual;

            unset($date_time_filiale[$_date_time]);
            ksort($date_time_filiale[$k]);
            $k++;
        }

        $data = !empty($date_time_filiale) ? $date_time_filiale : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,); 
    }
    
    /*
     * 获取指定时段的剩余量
     */
    public function getResidual($starttime, $endtime, $training_id=0){
        $divideTime = $this->_divideTime($starttime, $endtime);
        //已占用
        $tmp_sql = 'select distinct training_id, limit_number from e_gensee_plan where training_id!=0 and status=1 and date_time>\''.$divideTime[0].'\' and date_time<=\''.end($divideTime).'\'';
        $tmp_sql = $training_id ? $tmp_sql.' and training_id!='.$training_id : $tmp_sql;
        $sql = 'SELECT SUM(limit_number) limit_number FROM('.$tmp_sql.') as t';
        $sum_data = self::model()->findBySql($sql);
        $used_total = empty($sum_data) ? 0 : intval($sum_data->limit_number);
        //总允许数
        $total = TrainingGenseeMaxValue::model()->getMinMaxValue($starttime, $endtime);
        return $total-$used_total;
    }
    
    public function _getBranchList($region_name=true){
        $BranchList = ServiceRegion::model()->getProvinceList();   
        $arr = array(0=>'日期',1=>'时段',2=>'合计人数',3=>'剩余人数');
        foreach($BranchList as $v){
            $filiale_id = $v['filiale_id']==QG_BRANCH_ID ? QG_BRANCH_ID : substr($v['filiale_id'],0,2);
            $arr[$filiale_id] = $region_name ? $v['region_name'] : '';
        }
        ksort($arr);
        return $arr;
    }

    
    public function setPlan($training=array()){
        $select = 'id,filiale_id,apply_province_code,starttime,endtime,limit_number,status';
        if(empty($training)){
            $criteria = new \CDbCriteria;
            $criteria->select = 'id,filiale_id,apply_province_code,starttime,endtime,limit_number,status';
            $criteria->addCondition('is_create_gensee=1 AND way=2 AND cancel=0');
            $list = Training::model()->findAll($criteria); 
        }else{
            $list[] = $training;
        }
        //print_r($list);die;
        foreach($list as $v){
            $id = $v['id'];
            
            $criteria = new \CDbCriteria;
            $criteria->addCondition('training_id='.$id);
            $count = self::model()->count($criteria);
            if($count){
                self::model()->deleteAll('training_id='.$id);
            }
            
            $filiale_id = $v['filiale_id'];
            $province_code = $v['apply_province_code'];
            $starttime = $v['starttime'];
            $endtime = $v['endtime'];
            $limit_number = $v['limit_number'];
            $datetimeArr = $this->_divideTime($starttime, $endtime);
            $status = $v['status']==1 ? 1 : 0;
            
            $inster_data = array();
            foreach($datetimeArr as $_time){
                $inster_data[] = array(
                        'training_id'=>$id,
                        'filiale_id'=>$filiale_id,
                        'province_code' => $province_code,
                        'limit_number' => $limit_number,
                        'status' => $status,
                        '_create_time' => date('Y-m-d H:i:s'),
                        'date_time' => $_time
                    );
            } 
            //print_r($inster_data);die;
            $builder = \Yii::app()->db->schema->commandBuilder;
            $command = $builder->createMultipleInsertCommand('{{gensee_plan}}', $inster_data);
            $command->execute();
        }
    }
    
    /*
     * 删除指定培训的计划数据
     */
    public function updatePlan($training){
        $criteria = new \CDbCriteria;
        $criteria->addCondition('training_id='.$training['id']);
        $criteria->limit = 1;  
        $criteria->offset = 0;  
        $data = self::model()->find($criteria);
        if($data){
            if( strtotime($training['endtime'])<=time() ){
                self::model()->deleteAll('training_id='.$training['id']);
            }else{
                if( $data['status']==0 ){
                    self::model()->updateAll('status=1', array('training_id'=>$training['id']));
                }
            }            
        }
    }
 
    /*
     * 初始化"全国分支"一年数据
     */
    public function initializationData(){
        ini_set('memory_limit', '1024M');
        $datetimeArr = $this->_divideTime('2017-10-01 00:00:00', '2018-12-31 23:59:00');
        $inster_data = array();
        foreach($datetimeArr as $_time){
            $inster_data[] = array(
                    'training_id' => 0,
                    'filiale_id' => QG_BRANCH_ID,
                    'province_code' => QG_BRANCH_ID,
                    'limit_number' => 0,
                    'status' => 1,
                    '_create_time' => date('Y-m-d H:i:s'),
                    'date_time' => $_time
                );
        } 
        //print_r($inster_data);die;
        $builder = \Yii::app()->db->schema->commandBuilder;
        $command = $builder->createMultipleInsertCommand('{{gensee_plan}}', $inster_data);
        echo $command->execute();
    }
    
    /*
     * 将起始时间划分为15分钟一段
     */
    public function _divideTime($starttime,$endtime){
        $strtotime_starttime = strtotime($starttime);
        $strtotime_endtime = strtotime($endtime);
        //时间跨度不可超过一年
        if($strtotime_endtime-$strtotime_starttime > 31536000){
            //return array();
        }
        $YmdH_strtotime = strtotime(date('Y-m-d H:00:00',$strtotime_starttime));
        $YmdHi_strtotime = strtotime($starttime);
        $subtract = $YmdHi_strtotime-$YmdH_strtotime;
        if( $subtract<900 ){
            $for_strtotime = $YmdH_strtotime;
        }elseif( $subtract>=900 && $subtract<1800 ){
            $for_strtotime = $YmdH_strtotime+900;
        }elseif( $subtract>=1800 && $subtract<2700 ){
            $for_strtotime = $YmdH_strtotime+1800;
        }elseif( $subtract>=2700 && $subtract<3600 ){
            $for_strtotime = $YmdH_strtotime+2700;
        }
        $arr[] = date('Y-m-d H:i:s',$for_strtotime);
        while($for_strtotime<$strtotime_endtime){
            $for_strtotime+=900;
            $arr[] = date('Y-m-d H:i:s',$for_strtotime);
        }
        return $arr;
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