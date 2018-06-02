<?php

/**
 * desc:产品
 * author:besttaowenjing@163.com
 * date:2016-10-26
 */

namespace application\models\Prize;
use application\models\ServiceRegion;
class Prize extends \CActiveRecord {
    
    //抽奖栏目
    public $column = array(
        0 => '无关联栏目',
        1 => '答疑解惑',
        2 => '学习资料',
        3 => '同城活动',
        4 => '广联达产品',
        5 => '培训报名',
        6 => '广币商城',
        8 => '网络调研',
    );
    public $rules = array(
        0 => array('0000' => '无限制'),
        1 => array( //分支不见
            '1000' => '答疑解惑：回答问题，提交答案后',
            '1001' => '答疑解惑：在线提问，提交问题后',
            '1002' => '答疑解惑：采纳答案，点击采纳后', 
            '1003' => '答疑解惑：回答被采纳'
        ),
        2 => array('2000' => '学习资料：网友评论，提交评论后'),
        3 => array(
            '3000' => '同城活动：在线报名，报名成功后',
            '3001' => '同城活动：在线调研，提交调研后',
            '3002' => '同城活动：网友评论，提交评论后',
        ),
        4 => array(
            '4000' => '广联达产品：产品反馈，提交建议后',
            '4001' => '广联达产品：网友评论，提交评论后',
        ),
        5 => array(
            '5000' => '培训报名：在线报名，报名成功后',
            '5001' => '培训报名：在线调研，提交调研后',
            '5002' => '培训报名：网友评论，提交评论后',
        ),
        6 => array(//分支不见
            '6000' => '广币商城：在线购买，付款成功后',
            //'6001' => '广币商城：在线充值，充值成功后'
        ),
        8 => array(
            '8000' => '网络调研：在线调研，提交调研后',
        ),
    );
    /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{prize}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
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
	 * @return Category the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}


	public function get_list($con, $orderBy, $order, $limit, $offset){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                if($key=='title'){
					$criteria->addSearchCondition('title', $val);
				}elseif($key=='filiale_id' && is_array($val) ){
                    $criteria->addInCondition('filiale_id',$val);
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
        foreach($ret as $k => $v){
            $v = $v->attributes;
            $v['starttime'] = $v['starttime']==0 ? '': $v['starttime'];
            $v['endtime'] = $v['endtime']==0 ? '': $v['endtime'];
            if($v['filiale_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($v['filiale_id']);
            }
            $v['region_name'] = $city[0]['region_name']; //分支
              //适用地
            if($v['apply_province_code']==BRANCH_ID){
                $v['apply_province_name'] = '全国';
            }else{
                $v['apply_province_name'] = ServiceRegion::model()->getRedisCityList($v['apply_province_code']);
            }
            $v['column_type_name'] = $this->column[$v['column_type']].($v['column_id']?'—'.$v['column_id']:'');
            $v['rules_name'] = isset($this->rules[$v['column_type']][$v['rules_id']]) ? $this->rules[$v['column_type']][$v['rules_id']] : $v['rules_id'];
            $v['rules_name'] = $v['rules_name'] ? : '';
            
            //将已过时的下线
            if( $v['status']==1 && $v['endtime']<=date('Y-m-d H:i:s') ){
                self::model()->updateByPk($v['id'], array('status'=>0));
            }
            
            $v['status_txt'] = $v['status']==1 ? '上线' : '下线';
            
            //YII_ENV=='dev' &&
            if(  $v['column_type']!=0 ){
                $in_redis = $this->getRedis($v['apply_province_code'], $v['rules_id'], $v['column_id']);
                $v['in_redis'] = $v['status']==1 && $in_redis ? '<font color="red">&开始</font>' : '';
            }else{
                $v['in_redis'] = '';
            }
            
            $v['url'] = $v['status']==0?'':EHOME.'/index.php?r=prize/index&id='.$v['encryption_id'].$v['id'];
            $data[] = $v;
        }
        $data = !empty($data) ? $data : array();
		return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
    }

	//保存/修改
    public function PrzieSave($data ){
        if(isset($data['id'])){
            $model = self::model()->findbypk($data['id']);
            $oldData = $model->attributes;
            $model->_update_time = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
        }
		
        foreach($data as $k=>$v){
            $model->$k = \CHtml::encode($v);
        }
        if($model->save()){
            $id  = intval($model->primaryKey);
            if(isset($data['id'])){
                \OperationLog::addLog(\OperationLog::$operationPrize, 'edit', '编辑抽奖任务', $id, array(), array());
            }else{
                \OperationLog::addLog(\OperationLog::$operationPrize, 'add', '创建抽奖任务', $id, array(), array());
            }
            
            return $id;
        }
    }
    
    //复制
    public function CopyData($id){
        $Prize = self::model()->findByPk($id);
        $model = new self();
        foreach($Prize as $k=>$v){
            if( $k=='id' ){
                continue;
            }
            $model->$k = $v;
            $model->filiale_id = \Yii::app()->user->branch_id;
            $model->column_id = 0;
            $model->column_type = 0;
            $model->rules_id = 0;
            $model->minus_credits = 0;
            $model->num = 1;
            $model->starttime = null;
            $model->endtime = null;
            $model->url = '';
            $model->encryption_id = '';
            $model->_create_time = date('Y-m-d H:i:s');
            $model->_update_time = null;
            $model->user_id = \Yii::app()->user->user_id;
            $model->status = 0;
            $model->add_redis = 2; //新复制
        }
        $Connection = $model->dbConnection->beginTransaction();
        if($model->insert()){
            $insert_id  = intval($model->primaryKey);
            \OperationLog::addLog(\OperationLog::$operationPrize, 'add', '复制抽奖任务', $insert_id, array(), array());    
            
            $encryption_id = $this->getEncryption_key($insert_id);
            $this->PrzieSave(array('id'=>$insert_id,'encryption_id'=>$encryption_id));
            
            $PrizeReward = PrizeReward::model()->get_list(array('prize_id'=>$id));
            $rewardData['id'] = $insert_id;
            //print_r($PrizeReward);die;
            if($PrizeReward){
                foreach($PrizeReward as $rk=>$rv){
                    $rewardData['prize_type'][$rv['prize_sort']] = $rv['prize_type'];
                    $rewardData['prize_name'][$rv['prize_sort']] = $rv['prize_name'];
                    $rewardData['prize_num'][$rv['prize_sort']] = $rv['prize_num'];
                    $rewardData['prize_total'][$rv['prize_sort']] = $rv['prize_num'];
                    $rewardData['probability'][$rv['prize_sort']] = $rv['probability'];
                    $rewardData['prize_type'][$rv['prize_sort']] = $rv['prize_type'];
                    $rewardData['prize_type'][$rv['prize_sort']] = $rv['prize_type'];
                }
                $ids = PrizeReward::model()->PrzieRewardSave($rewardData);
                if($ids){
                    $Connection->commit();
                    return true;
                }else{
                    $Connection->rollBack();
                    return false;
                }
            }else{
                $Connection->commit();
                return true;
            }
       }else{
           $Connection->rollBack();
           return false;
       }
    }
    
    /*
     * 验证已存在的重复任务
     */
    public function checkRepeatPrize($column_type, $rules_id, $column_id, $starttime, $apply_province_code, $prize_id=0){
        if($column_type==0 && $rules_id==0){
            return 0;
        }
        $nowtime = date('Y-m-d H:i:s');
		$criteria = new \CDbCriteria;
		//$criteria->select = 'id,filiale_id,apply_province_code,column_type,rules_id,column_id,starttime,endtime,status';
        if($prize_id){
            $criteria->compare('id!', $prize_id);
        }
        $criteria->compare('status', 1);
        $criteria->compare('column_type', $column_type);
        $criteria->compare('rules_id', $rules_id);
        $criteria->compare('column_id', $column_id);
        $criteria->compare('filiale_id', \Yii::app()->user->branch_id ); 
        $criteria->compare('apply_province_code', $apply_province_code); 
        
		//$criteria->addCondition('starttime');
		//$criteria->params = array(':nowtime'=>$nowtime);
		$data = Prize::model()->find($criteria);
        if( empty($data) ){
            $res = 0;
        }else{
            if($data['filiale_id']==BRANCH_ID){
                $city[0]['region_name'] = '全国';
            }else{
                $city = ServiceRegion::model()->getBranchToCity($data['filiale_id']);
            }
            $res = ' 与“'.$city[0]['region_name'].'创建”已上线的抽奖 ID:'.$data['id'].'【'.$data['title'].'】冲突';
        }
        return $res;
    }
    
    public function getEncryption_key(){
        $code = mt_rand(10000,99999);
        while ($code){
            $count = self::model()->getCount(array('encryption_id'=>$code));
            if( $count ){
                $code = mt_rand(10000,99999);
            }else{
                break;
            }
        }
        return $code;
    }
    
    public function getFind($con=array()){
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $data = self::model()->find($criteria);
        return $data;
    }
    
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
    
    /*
	* 设置redis 
	* $del = true 
	*/
	public function setRedis( $apply_province_code, $rules_id, $column_id, $endtime=0, $id ){
		$nowtime = date('Y-m-d H:i:s');
		$key = 'Prize:prize_'.$apply_province_code.'_'.$rules_id;
		$key = empty($column_id) ? $key : $key.'_'.$column_id;
		$keydata = \Yii::app()->redis->getClient()->get($key);
		
		$endtime = $endtime==0 ? 0 : strtotime($endtime) - time();
		$res = \Yii::app()->redis->getClient()->set($key ,$id , $endtime);
		if($res){
			self::model()->updateByPk($id, array('add_redis'=>1, '_update_time'=>$nowtime));
			writeIntoLog('PrizeStatus', "----------" . $nowtime . " prize_id=".$id ." key=".$key." 存入redis\r\n");
		}else{
			writeIntoLog('PrizeStatus', "----------" . $nowtime . " prize_id=".$id ." key=".$key." 存入redis失败\r\n");
		}
		return $res;
	}
    
    /*
	* 移除redis 
	*  
	*/
	public function delRedis($apply_province_code, $rules_id, $column_id, $prize_id){
		$nowtime = date('Y-m-d H:i:s');
		$key = 'Prize:prize_'.$apply_province_code.'_'.$rules_id;
		$key = empty($column_id) ? $key : $key.'_'.$column_id;
		$keydata = \Yii::app()->redis->getClient()->get($key);
		if( $keydata ){
			$res = \Yii::app()->redis->getClient()->del($key);
			if($res){
				writeIntoLog('PrizeStatus', "----------" . $nowtime . " prize_id=".$prize_id ." key=".$key." 移除redis\r\n");
			}else{
				writeIntoLog('PrizeStatus', "----------" . $nowtime . " prize_id=".$prize_id ." key=".$key." 移除redis失败\r\n");
			}
		}
	}
    
    /**
     * 获取redis是否存在
     */
    public function getRedis($apply_province_code, $rules_id, $column_id){
        $key = 'Prize:prize_'.$apply_province_code.'_'.$rules_id;
		$key = empty($column_id) ? $key : $key.'_'.$column_id;
        return \Yii::app()->redis->getClient()->get($key);
    }

}
