<?php
use application\models\Prize\Prize;
use application\models\Prize\PrizeReward;
use application\models\Prize\PrizeWinningLog;
use application\models\Prize\PrizeTotal;
use application\models\Activity\Activity;
use application\models\Training\Training;
use application\models\Product\Product;
use application\models\Research\Research;
use application\models\ServiceRegion;
class PrizeController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        0 => '参数错误',
        1 => '操作数据库错误',
        2 => '数据已经存在',
        3 => '数据不存在',
        4 => '不可为空',
        5 => '不可过长',
        7 => '积分数额不可为空',
        8 => '广币数额不可为空',
        9 => '请上传奖品转盘图',
        10 => '起始时间不可为空',
        11 => '开始时间不可早于当前时间',
        12 => '结束时间不可早于当前时间',
        13 => '起始时间间隔过短',
        14 => '请先设置奖品',
        15 => '删除前必须先下线',
        16 => '此抽奖任务已结束，不可重新发布',
        17 => '奖品剩余数量不可为负数',
        18 => '已处于“上线”状态，不可重复发布',
        19 => '已有相同类型的抽奖处于“上线”状态',
        20 => '请选择栏目',
        21 => '冲突',
        22 => '请确保抽奖概率总和为100%',
        23 => '奖品名称不可为空',
        24 => '奖品名称不可超过10个中文',
        25 => '请正确填写积分额度',
        26 => '请正确填写广币额度',
        27 => '额度不可超过999',
        28 => '请正确填写奖品份数',
        29 => '份数不可超过99999'
    );
            
    public $user_id;
    public $user_name;
    public $filiale_id;

    public function init(){
        parent::init();
        $this->user_id = Yii::app()->user->user_id;
        $this->user_name = Yii::app()->user->user_name;
        $this->filiale_id = Yii::app()->user->branch_id;
    }

    //列表
    public function actionIndex(){
        if(!isset($_GET['iDisplayLength'])){
            $getCityList = ServiceRegion::model()->getCityList();
            $this->render('list',array('getCityList'=>$getCityList));exit;
        }

        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); //分支id前两位
        $title = trim(Yii::app()->request->getParam( 'title' ));
        $con = array();
        if($starttime){
            $con['starttime>'] = $starttime;
            $con['starttime<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }

        if($this->filiale_id==BRANCH_ID){
            if($province_code){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
        if($title){
            $con['title'] = $title;
        }

        $list = Prize::model()->get_list($con, $ord, $field, $limit, $page);
       // print_r($con);
        echo CJSON::encode($list);
    }
    
    /*
     *导出抽奖任务列表
     * return array
     */
    public function actionPrize_excel(){
        $starttime = trim(Yii::app()->request->getParam( 'starttime' ));
        $endtime = trim(Yii::app()->request->getParam( 'endtime' ));
        $province_code = intval(Yii::app()->request->getParam( 'province_code' )); //分支id前两位
        $title = trim(Yii::app()->request->getParam( 'title' ));
        $con = array();
        if($starttime){
            $con['starttime>'] = $starttime;
            $con['starttime<'] = $endtime ? $endtime.' 23:59:59' : $starttime.' 23:59:59';
        }

        if($this->filiale_id==BRANCH_ID){
            if($province_code){
                $con['filiale_id'] = ServiceRegion::model()->getProvinceToFiliale($province_code);
            }
        }else{
            $con['filiale_id'] = $this->filiale_id;
        }
        if($title){
            $con['title'] = $title;
        }

        $list = Prize::model()->get_list($con, 'desc', 'id', 50000, 0);
       
        $data = array();
        foreach($list['data'] as $v){
            $tmp['id'] = $v['id'];
            $tmp['region_name'] = $v['region_name'];
            $tmp['apply_province_name'] = $v['apply_province_name'];
            $tmp['column_type_name'] = $v['column_type_name'];
            $tmp['rules_name'] = $v['rules_name'];
            $tmp['title'] = $v['title'];
            $tmp['num'] = $v['num'];
            $tmp['_create_time'] = $v['_create_time'];
            $tmp['starttime'] = $v['starttime'];
            $tmp['endtime'] = $v['endtime'];
            $tmp['url'] = $v['url'];
            $tmp['status_txt'] = $v['status_txt'];
            $data[] = $tmp;
        }
        $headerstr = 'ID,分支,地区,关联栏目,触发规则,抽奖标题,抽奖次数,建立时间,开始时间,结束时间,地址URL,状态';
        $header = explode(',',$headerstr);
        FwUtility::exportExcel($data, $header,'抽奖任务列表','抽奖任务列表_'.date('Y-m-d'));
    }
    
    //选择栏目
    public function actionColumn_list(){
        $column_type = intval(Yii::app()->request->getParam( 'column_type' ));
        $_list = Yii::app()->request->getParam( 'list' );
        if( $_list===NULL ){
            $this->render('column_list',array('column_type'=>$column_type));die;
        }
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $title = trim(Yii::app()->request->getParam( 'title' ));
        $con['status'] = 1;
        if($title){
            $con['title'] = $title;
        }
        if( $this->filiale_id!=BRANCH_ID ){
            $con['filiale_id'] = $this->filiale_id;
        }
        switch ($column_type){
            case 3 : //活动
                $list = Activity::model()->getlist($con, $ord, $field, $limit, $page);
            break; 
            case 4 : //产品
                $list = Product::model()->get_list($con, $ord, $field, $limit, $page);
            break; 
            case 5 : //报名
                $list = Training::model()->getlist($con, $ord, $field, $limit, $page);
            break; 
            case 8 : //调研
                unset($con['status']);
                $con['_delete'] = 0;
                $con['status!'] = 0;
                if($title){
                    $con['research_title'] = $title;
                    unset($con['title']);
                }
                $list = Research::model()->get_research_list($con, $ord, $field, $limit, $page);
                if($list['data']){
                    foreach($list['data'] as $k=>$research){
                        $list['data'][$k]['filiale_name'] = $research['area_name'];
                        $list['data'][$k]['title'] = $research['research_title'];
                        $list['data'][$k]['status_txt'] = $research['status']==1?'上线':'已下线';
                    }
                }
            break; 
            default : 
                echo 'data not';
            break; 
        }
        echo CJSON::encode($list);
    }
   
    public function actionAdd(){
        if( $this->filiale_id == BRANCH_ID ){
            $getCityList = ServiceRegion::model()->getCityList();
        }else{
            $getCityList = ServiceRegion::model()->getBranchToCity($this->filiale_id);
        }
        $column = Prize::model()->column;
        $rules = Prize::model()->rules;
        foreach($rules as $c_id=>$r_val){
            $rules_key[$c_id] = array_keys($r_val);
        }
        
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $region_name = '';
        $region_id = '';
        $nowtime = date('Y-m-d H:i:s');
        if($id){
            $data = Prize::model()->findByPk($id);
            if(empty($data)){
                exit('NOT DATA');
            }
            $data = $data->attributes;
            if($data['column_type']==3){
                $title = Activity::model()->findByPk($data['column_id']);
            }elseif($data['column_type']==4){
                $title = Product::model()->findByPk($data['column_id']);
            }elseif($data['column_type']==5){
                $title = Training::model()->findByPk($data['column_id']);
            }elseif($data['column_type']==8){
                $title = Research::model()->findByPk($data['column_id'])->attributes;
                $title['title'] = $title['research_title'];
                $title = (object)$title;
                //echo "<pre>"; print_r($title);
            }else{
                $title = '';
            }
            $data['column_name'] = empty($title)?'':$title->title;
            $data['column_id'] =  empty($title)?'':$data['column_id'];
            $data['starttime'] = $data['starttime']==0 ? '': $data['starttime'];
            $data['endtime'] = $data['endtime']==0 ? '': $data['endtime'];
            //是否已经开始
            $data['start'] = !empty($data['starttime']) && $data['starttime']<=$nowtime ? true : false;
            //是否已借宿
            $data['end'] = !empty($data['endtime']) && $data['endtime']<=$nowtime ? true : false;
            $data['encryption_id'] = $data['encryption_id'].$id;
        }else{
            $data = array(
                'id'=>0,
                'title'=>'',
                'note'=>'', 
                'column_type'=>'', 
                'column_id'=>'', 
                'rules_id' => 0,
                'column_name'=>'', 
                'minus_credits'=>'', 
                'starttime'=>'', 
                'endtime'=>'', 
                'num' => 1,
                'apply_province_code'=>'',
                'start' => false,
                'end' => false,
                'show_winning_type' => 0
            );
        }
        
        $this->render(
            'add1',
            array(
                'column'=>$column,
                'rules'=>$rules,
                'rules_key'=>$rules_key,
                'getCityList'=>$getCityList, 
                'id'=>$id, 
                'data'=>$data
            )
        );
    }
    
    public function actionAdd_op(){
        $msgNo = 'Y';
        $nowtime = date('Y-m-d H:i:s');
        $data = array();
        try{
            $data['column_type'] = intval(Yii::app()->request->getParam( 'column_type' ));
            $data['column_id'] = intval(Yii::app()->request->getParam( 'column_id' ));
            $data['rules_id'] = intval(Yii::app()->request->getParam( 'rules_id' ));
            $data['minus_credits'] = intval(Yii::app()->request->getParam( 'minus_credits' ));
            $data['num'] = intval(Yii::app()->request->getParam( 'num' ));
            $data['starttime'] = trim(Yii::app()->request->getParam( 'starttime' ));
            $data['starttime'] = empty($data['starttime']) ? '' : $data['starttime'].':00';
            $data['endtime'] = trim(Yii::app()->request->getParam( 'endtime' ));
            $data['endtime'] = empty($data['endtime']) ? '' : $data['endtime'].':00';
            $data['filiale_id'] = $this->filiale_id;
            $data['apply_province_code'] = intval(Yii::app()->request->getParam( 'apply_province_code' ));
            $column_filiale_id = intval(Yii::app()->request->getParam( 'column_filiale_id' ));
            $data['title'] = trim(Yii::app()->request->getParam( 'title' ));
            $data['note'] = trim(Yii::app()->request->getParam( 'note' ));//print_r($data);die;
            $data['show_winning_type'] = intval(Yii::app()->request->getParam( 'show_winning_type' ));
            if( in_array($data['column_type'],array(3,4,5,8)) ){
                if( $data['column_id']==0 ){
                    //请选择栏目
                    throw new Exception(20);
                }
                $data['apply_province_code'] = ServiceRegion::model()->getRegionIdByBranch($column_filiale_id);
            }elseif( in_array($data['column_type'],array(1,6)) ){ //答疑解惑  广币商城 只针对全国
                $data['apply_province_code'] = BRANCH_ID;
            } 
            
            if( empty($data['starttime']) || empty($data['endtime']) ){
                //起始时间不可为空
                throw new Exception(10);
            }elseif( $data['endtime'] <=$nowtime ){
                //结束时间不可早于当前时间
                throw new Exception(12);
            }elseif( $data['starttime']==$data['endtime'] ){
                //起始时间间隔过短
                throw new Exception(13);
            }
            
            //验证已存在的重复任务
            $checkRepeatPrize = Prize::model()->checkRepeatPrize(
                $data['column_type'], 
                $data['rules_id'], 
                $data['column_id'], 
                $data['starttime'], 
                $data['apply_province_code']);
            if( $checkRepeatPrize ){
                $msgNo = 21;
                $this->msg[$msgNo] = $checkRepeatPrize;
                echo $this->encode($msgNo, $checkRepeatPrize);die;
            }
            
            if( $data['apply_province_code']==0 ){
                throw new Exception('1');
            }
            
            
            $ins_id = Prize::model()->PrzieSave($data);
            if($ins_id){
                $encryption_id = Prize::model()->getEncryption_key($ins_id);
                Prize::model()->PrzieSave(array('id'=>$ins_id,'encryption_id'=>$encryption_id));
                $rewardData = array(
                    'id' => $ins_id,
                    'prize_reward_id' => array(1=>0),
                    'prize_type' => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0),
                    'prize_name' => array(1=>'',2=>'',3=>'',4=>'',5=>'',6=>''),
                    'prize_total' => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0),
                    'probability' => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0),
                );
                PrizeReward::model()->PrzieRewardSave($rewardData);
                echo $this->encode($msgNo, $ins_id);die;
            } else {
                throw new Exception('1');
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    


    /*
     * 保存编辑内容
     */
    public function actionEdit_op(){
        $msgNo = 'Y';
        $data = array();
        try{
            $id = intval(Yii::app()->request->getParam( 'id' ));
            $prize = Prize::model()->findByPk($id);
            $starttime = $prize['starttime'];
            $endtime = $prize['endtime'];
            $nowtime = date('Y-m-d H:i:s');
        
            $data['column_type'] = intval(Yii::app()->request->getParam( 'column_type' ));
            $data['column_id'] = intval(Yii::app()->request->getParam( 'column_id' ));
            $data['rules_id'] = intval(Yii::app()->request->getParam( 'rules_id' ));
            $data['minus_credits'] = intval(Yii::app()->request->getParam( 'minus_credits' ));
            $data['num'] = intval(Yii::app()->request->getParam( 'num' ));
            $data['starttime'] = trim(Yii::app()->request->getParam( 'starttime' ));
            $data['starttime'] = empty($data['starttime']) ? '' : $data['starttime'].':00';
            $data['endtime'] = trim(Yii::app()->request->getParam( 'endtime' ));
            $data['endtime'] = empty($data['endtime']) ? '' : $data['endtime'].':00';
            $data['apply_province_code'] = intval(Yii::app()->request->getParam( 'apply_province_code' ));
            $data['show_winning_type'] = intval(Yii::app()->request->getParam( 'show_winning_type' ));
            $status = 0;
            //$starttime=0 是新复制的
            if( $starttime==0 ){
                if( empty($data['starttime']) || empty($data['endtime']) ){
                    //起始时间不可为空
                    throw new Exception(10);
                }elseif( $data['endtime'] <=$nowtime ){
                    //结束时间不可早于当前时间
                    throw new Exception(12);
                }elseif( $data['starttime']==$data['endtime'] ){
                    //起始时间间隔过短
                    throw new Exception(13);
                }
            }else{
                if( $nowtime < $starttime ){    //未开始
                    $status = 0; 
                    if( empty($data['starttime']) || empty($data['endtime']) ){
                        //起始时间不可为空
                        throw new Exception(10);
                    }elseif( $data['endtime'] <=$nowtime ){
                        //结束时间不可早于当前时间
                        throw new Exception(12);
                    }elseif( $data['starttime']==$data['endtime'] ){
                        //起始时间间隔过短
                        throw new Exception(13);
                    }
                }elseif( $nowtime >= $starttime && $nowtime < $endtime ){   //进行中
                    $status = 1;
                    if( $data['endtime'] <=$nowtime ){
                        //结束时间不可早于当前时间
                        throw new Exception(12);
                    }elseif( $data['starttime']==$data['endtime'] ){
                        //起始时间间隔过短
                        throw new Exception(13);
                    }
                    unset($data['column_type']);
                    unset($data['column_id']);
                    unset($data['rules_id']);
                    unset($data['minus_credits']);
                    unset($data['num']);
                    unset($data['starttime']);
                    unset($data['apply_province_code']);
                    
                }elseif( $nowtime >= $endtime ){    //已结束
                    $status = 3; 
                    unset($data);
                }
            }
            
            if( $starttime==0 || $status==0 ){
                if( in_array($data['column_type'],array(3,4,5,8)) ){
                    if( $data['column_id']==0 ){
                        //请选择栏目
                        throw new Exception(20);
                    }
                    $column_filiale_id = intval(Yii::app()->request->getParam( 'column_filiale_id' ));
                    $data['apply_province_code'] = ServiceRegion::model()->getRegionIdByBranch($column_filiale_id);
                    
                }elseif( in_array($data['column_type'],array(1,6)) ){ //答疑解惑  广币商城 只针对全国
                    $data['apply_province_code'] = BRANCH_ID;
                }
                //验证已存在的重复任务
                $checkRepeatPrize = Prize::model()->checkRepeatPrize(
                    $data['column_type'], 
                    $data['rules_id'], 
                    $data['column_id'], 
                    $data['starttime'], 
                    $data['apply_province_code'],
                    $id);
                if( $checkRepeatPrize ){
                    $msgNo = 21;
                    $this->msg[$msgNo] = $checkRepeatPrize;
                    echo $this->encode($msgNo, $checkRepeatPrize);die;
                }
            }
            
            $data['id'] = $id;
            $data['title'] = trim(Yii::app()->request->getParam( 'title' ));
            $data['note'] = trim(Yii::app()->request->getParam( 'note' ));
            
            if( !isset($data['apply_province_code']) || $data['apply_province_code']==0 ){
                $data['apply_province_code'] = $prize['apply_province_code'];
                //throw new Exception('1');
            }
            $ins_id = Prize::model()->PrzieSave($data);
            if($ins_id){
                if($status==1){ //已经开始尚未结束的延长redis时间
                    if( $data['endtime'] != $endtime ){
                        Prize::model()->setRedis(
                            $prize['apply_province_code'], 
                            $prize['rules_id'], 
                            $prize['column_id'],
                            $data['endtime'],
                            $id);
                    }
                }
                echo $this->encode($msgNo, $ins_id);die;
            } else {
                throw new Exception('1');
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    
    //复制
    public function actionCopy(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $res = Prize::model()->CopyData($id);
        if(!$res){
            $msgNo = 1;
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    //编辑奖品
    public function actionReward(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $reward = PrizeReward::model()->get_list(array('prize_id'=>$id));
        $nowtime = date('Y-m-d H:i:s');
        $disabled = 'disabled="disabled"';
   
        $Prize = Prize::model()->findByPk($id); 
        //新复制的可改， 开始时间未到可改
        if( $Prize['starttime']==0 || $Prize['starttime']>$nowtime ){
            $disabled = '';
        }
        
        //奖品没设置可改
        $count = PrizeReward::model()->getCount( array('prize_id'=>$id) );
        if( $count==0 ){
            $disabled = '';
        }
        //新复制的可改
        if( $Prize['add_redis']==2 ){
            $disabled = '';
        }
        
        $image = $Prize->image;
        $status_val = intval($Prize->status);
        $is_online = ($status_val!=0 && $Prize['starttime']<=$nowtime) ? 1 : 0;
        $this->render('add2', array(
            'id'=>$id,
            'disabled'=>$disabled,
            'reward'=>$reward,
            'image'=>$image,
            'is_online' => $is_online,
            'status_val' => $status_val,
            'encryption_id' => $Prize->encryption_id.$id
            )
        );
    }
    
    //保存奖品
    public function actionReward_save(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $prize_reward_id = Yii::app()->request->getParam( 'prize_reward_id' );
        $prize_type = Yii::app()->request->getParam( 'prize_type' );
        $prize_name = Yii::app()->request->getParam( 'prize_name' );
        $prize_total = Yii::app()->request->getParam( 'prize_total' );
        $probability = Yii::app()->request->getParam( 'probability' );
        $image = trim(Yii::app()->request->getParam( 'image' ));
        $nowtime = date('Y-m-d H:i:s');
        try{
            $prize = Prize::model()->findByPk($id);
            if( !is_array($prize_reward_id) ){
                throw new Exception(0);
            }
            //奖品没设置可改
            $count = PrizeReward::model()->getCount( array('prize_id'=>$id) );
            foreach($prize_reward_id as $sort=>$val){
                if( $prize['starttime']==0 || $prize['add_redis']==2 || $prize['starttime']>$nowtime || $count==0 ){
                    if( $prize_type[$sort]==0 && empty($prize_name[$sort]) ){
                        $msgNo = 'prize_name_'.$sort;
                        $this->msg[$msgNo] = $this->msg[23]; //份数不可超过99999
                        throw new Exception($msgNo);
                    }if( $prize_type[$sort]==0 && strlen($prize_name[$sort])>30 ){
                        $msgNo = 'prize_name_'.$sort;
                        $this->msg[$msgNo] = $this->msg[24];//'奖品名称不可超过10个中文';
                        throw new Exception($msgNo);
                    }elseif( $prize_type[$sort]==1 && intval($prize_name[$sort])<=0  ){
                        $msgNo = 'prize_name_'.$sort;
                        $this->msg[$msgNo] = $this->msg[25];//'请正确填写积分额度';
                        throw new Exception($msgNo);
                    }elseif( $prize_type[$sort]==2 && intval($prize_name[$sort])<=0 ){
                        $msgNo = 'prize_name_'.$sort;
                        $this->msg[$msgNo] = $this->msg[26];//'请正确填写广币额度';
                        throw new Exception($msgNo);
                    }elseif( ($prize_type[$sort]==1 || $prize_type[$sort]==2) && $prize_name[$sort]>999 ){
                        $msgNo = 'prize_name_'.$sort;
                        $this->msg[$msgNo] = $this->msg[27];//'额度不可超过999';
                        throw new Exception($msgNo);
                    }elseif( !is_numeric($prize_total[$sort]) || intval($prize_total[$sort])<0 ){
                        $msgNo = 'prize_total_'.$sort;
                        $this->msg[$msgNo] = $this->msg[28];//'请正确填写奖品份数';
                        throw new Exception($msgNo);
                    }elseif( is_numeric($prize_total[$sort]) && intval($prize_total[$sort])>99999 ){
                        $msgNo = 'prize_total_'.$sort;
                        $this->msg[$msgNo] = $this->msg[29];//'份数不可超过99999';
                        throw new Exception($msgNo);
                    }
                }
                
            }
            if( array_sum($probability)!=100 ){
                //请确保抽奖概率总和为100%
                throw new Exception(22);
            }
            if( empty($image) ){
                throw new Exception(9);
            }
            $data = array(
                'id' => $id,
                'prize_reward_id' => $prize_reward_id,
                'prize_type' => $prize_type,
                'prize_name' => $prize_name,
                'prize_total' => $prize_total,
                'probability' => $probability
            );
            if( $count!=0 && $prize['starttime']<=$nowtime && $prize['add_redis']!=2 ){
                unset($data['prize_type'], $data['prize_name']);
            }
      
            $ids = PrizeReward::model()->PrzieRewardSave($data);
            if( empty($ids) ){
                throw new Exception(1);
            }elseif($ids=='prize_total_error'){
                throw new Exception(17);
            }
            
            $old_image = $prize['image'];
            if( $old_image!=$image ){
                Prize::model()->PrzieSave(array('id'=>$id, 'image'=>$image));
                $old_image = Yii::getPathOfAlias('webroot').'/../..'.$old_image;
                //unlink($old_image);
            }

        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    /*
     * 下载psd
     */
    public function actionDownpsd(){
        $file = Yii::getPathOfAlias('webroot').'/images/psd.psd';
        header("Content-type: octet/stream");
        header("Content-disposition:attachment;filename=抽奖转盘psd文件.psd");
        header("Content-Length:" . filesize($file));
        readfile($file);
    }
    
    /*
     * 正式发布页面
     */
    public function actionSend(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $send = Yii::app()->request->getParam( 'send' );
        $prize = Prize::model()->findByPk($id)->attributes;
        $nowdate = date('Y-m-d H:i:s');
        if( !$send ){
            $prize['encryption_id'] = $prize['encryption_id'].$id;
            $this->render('add3',array('id'=>$id, 'data'=>$prize)); exit;
        }
        
        $msgNo = 'Y';
        try{
            if( $prize['status']==1 ){
                throw new Exception(18);
            }
            if( $prize['starttime']==0 || $prize['endtime']==0 ){
                throw new Exception(10);
            }
            //是否设置奖了品
            $count = PrizeReward::model()->getCount( array('prize_id'=>$id) );
            if($count==0){
                throw new Exception(14);
            }
            if( $prize['endtime']<=$nowdate ){
                throw new Exception(16);
            }
            
            //验证已存在的重复任务
            $checkRepeatPrize = Prize::model()->checkRepeatPrize(
                $prize['column_type'], 
                $prize['rules_id'], 
                $prize['column_id'], 
                $prize['starttime'], 
                $prize['apply_province_code'],
                $id);
            if( $checkRepeatPrize ){
                $msgNo = 21;
                $this->msg[$msgNo] = $checkRepeatPrize;
                echo $this->encode($msgNo, $checkRepeatPrize);die;
            }

            $up = Prize::model()->PrzieSave(array('id'=>$id,'status' => 1, 'add_redis'=>0));
            if(!$up){
                throw new Exception(1);
            }
            //设置redis
            if( $prize['starttime']<=$nowdate ){ //如果已经开始
                Prize::model()->setRedis(
                        $prize['apply_province_code'], 
                        $prize['rules_id'], 
                        $prize['column_id'],
                        $prize['endtime'],
                        $id);
            }
            \OperationLog::addLog(\OperationLog::$operationPrize, 'edit', '发布抽奖', $id, array(), array());    
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //下线
    public function actionOffline(){
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        
        try{
            $prize = Prize::model()->findByPk($id);
            $data = array(
                'id'=> $id,
                'add_redis' => 0,
                'status' => 0
            );
            $edit = Prize::model()->PrzieSave($data);
            if(!$edit){
                throw new Exception(1);
            }
            //移除redis
            Prize::model()->delRedis($prize['apply_province_code'], $prize['rules_id'], $prize['column_id'], $prize['id']);
            \OperationLog::addLog(\OperationLog::$operationPrize, 'edit', '抽奖任务下线', $id, $prize, $data);    
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
        
    }
    
    //删除 用于清理数据
    public function actionDelete(){
        if($this->user_name!='qidakui'){
            exit;
        }
        $msgNo = 'Y';
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $prize = Prize::model()->findByPk($id);
        if( $prize['status']==1 ){
            $msgNo = 15;
            echo $this->encode($msgNo, $this->msg[$msgNo]);
            exit;
        }

        $Connection = $prize->dbConnection->beginTransaction();
        $del_1 = PrizeReward::model()->deleteAll('prize_id=:prize_id',array(':prize_id'=>$id));
        $del_2 = PrizeTotal::model()->deleteAll('prize_id=:prize_id',array(':prize_id'=>$id));
        $del_3 = PrizeWinningLog::model()->deleteAll('prize_id=:prize_id',array(':prize_id'=>$id));
        $del_4 = Prize::model()->deleteByPk($id);
        if( $del_1!==false && $del_2!==false && $del_3!==false && $del_4!==false ){
            $Connection->commit();
            \OperationLog::addLog(\OperationLog::$operationPrize, 'del', '删除抽奖任务', $id, array(), array()); 
            //移除redis
            Prize::model()->delRedis($prize['apply_province_code'], $prize['rules_id'], $prize['column_id'], $prize['id']);
        }else{
            $msgNo = 1;
            $Connection->rollBack();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }
    
    //统计分析
    public function actionStatistical(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $getlist = Yii::app()->request->getParam( 'getlist' );
                
        if(!$getlist){
            $con['prize_id'] = $id;
            $data = PrizeReward::model()->get_list($con);
            if( empty($data) ){
                $data = array(
                    1 => array('prize_num'=>'', 'prize_total'=>'', 'prize_name'=>''),
                    2 => array('prize_num'=>'', 'prize_total'=>'', 'prize_name'=>''),
                    3 => array('prize_num'=>'', 'prize_total'=>'', 'prize_name'=>''),
                    4 => array('prize_num'=>'', 'prize_total'=>'', 'prize_name'=>''),
                    5 => array('prize_num'=>'', 'prize_total'=>'', 'prize_name'=>''),
                );
            }
            $count = PrizeWinningLog::model()->getCount(array('_delete'=>0, 'prize_id'=>$id));
            $prize = Prize::model()->findByPk($id);
            $this->render(
                'statistical',
                array(
                    'id' => $id,
                    'data' => $data,
                    'prize' => $prize,
                    'count' => $count
                )
            );
            exit;
        }
        
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord   	    = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field      = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord        = !empty($ord) ? $ord : 'desc';
        $field      = !empty($field) ? $field : 'id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $con['_delete'] = 0;
        $con['prize_id'] = $id;
        $list = PrizeWinningLog::model()->get_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);
    }
    
     //统计分析导出
    public function actionStatistical_down(){
        $id = intval(Yii::app()->request->getParam( 'id' ));
        $con['_delete'] = 0;
        $con['prize_id'] = $id;
        $list = PrizeWinningLog::model()->get_list($con, 'desc', 'id', '50000', 0);
        $data = array();
        foreach($list['data'] as $v){
            $tmp['id'] = $v['id'];
            $tmp['province'] = $v['province'];
            $tmp['_create_time'] = $v['_create_time'];
            $tmp['member_nick_name'] = $v['member_nick_name'];
            $tmp['member_user_name'] = $v['member_user_name'];
            $tmp['mobile'] = $v['mobile'];
            $tmp['winning_num'] = $v['winning_num'];
            $tmp['prize_name'] = $v['prize_name'];
            $tmp['realname'] = $v['realname'];
            $tmp['address'] = $v['address'];
            $tmp['postcode'] = $v['postcode'];
            $data[] = $tmp;
        }
        $headerstr = '序号,地区,时间,昵称,帐号,手机号吗,中奖数量,奖品名称,收件人,邮寄地址,邮编';
        $header = explode(',',$headerstr);
        FwUtility::exportExcel($data, $header,'抽奖统计','抽奖统计_'.date('Y-m-d'));
        
    }
    

}




     
        