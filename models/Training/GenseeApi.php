<?php
/**
 * Created by PhpStorm.
 * User: qik
 * Date: 2016/5/13
 * Time: 14:32
 */
/**
 * This is the model class for table "{{training}}".
 *
 * The followings are the available columns in table '{{admin}}':
 * @property string $id
 * @property string $user_name
 * @property string $password
 * @property string $phone
 * @property string $email
 * @property string $random
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 * 
    0	成功
-1	失败
101	参数错误
102	参数转换错误
200	认证失败
201	口令过期
300	系统错误
500	业务错误
501	业务错误 – 数据不存在
502	业务错误 – 重复数据
600	接口被禁用，请联系管理员

 */
namespace application\models\Training;
use application\models\ServiceRegion;
class GenseeApi extends \CActiveRecord
{
    public $parame = array(
        'loginName' => 'hhy@126.com',
        'password' => '123456',
        'sec' => '123456',
    );
    
    public $apierror = array(
        '0' => '成功',
        '-1' => '接口请求失败',
        '101' => '参数错误',
        '102' => '参数转换错误',
        '200' => '认证失败',
        '201' => '口令过期',
        '300' => '系统错误',
        '500' => '业务错误',
        '501' => '业务错误 – 数据不存在',
        '502' => '业务错误 – 重复数据',
        '600' => '接口被禁用，请联系管理员'
    );

    private $_msg = array(

    );
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{training_gensee}}';
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
     * @return Admin the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
     //保存/修改
    public function saveData( $data=array() ){
        $model = self::model()->findByPk($data['training_id']);
        if($model){
            $model->_update_time = date('Y-m-d H:i:s');
        }else{
            $model = new self();
            $model->_create_time = date('Y-m-d H:i:s');
        }
        foreach($data as $k=>$v){
            $model->$k = \CHtml::encode($v);
			//$model->$k = $v;
        }
		$model->save();
        $id  = intval($model->primaryKey);
        return $id;
    }
    
    /*
     * 创建大课堂
     */
    public function createGensee($training_id){
        $Training = Training::model()->findByPk($training_id);
        
        if($Training['status']==1 && $Training['is_create_gensee']==0 && $Training['way']==2 ){
            $Extension = TrainingExtension::model()->findByPk($training_id, array('select'=>array('video_form')));
            if( $Extension['video_form']==3 ){
                //设置时间段计划表
                TrainingGenseePlan::model()->setPlan($Training);
                $filiale_name = '全国';
                if($Training['filiale_id']!=BRANCH_ID){
                    $city = ServiceRegion::model()->getBranchToCity($Training['filiale_id']);
                    $filiale_name = isset($city[0]['region_name'])?$city[0]['region_name']:'';
                }

                $this->parame['startDate'] = substr($Training['starttime'], 0,10).' 00:00:00';
                $this->parame['invalidDate'] = substr($Training['endtime'], 0,10).' 23:59:59';
                $this->parame['subject'] = $filiale_name.$Training['title'].date('YmdHi',strtotime($Training['starttime']));
                $res = \Yii::app()->curl->post('http://fwxgx.gensee.com/integration/site/training/room/created', $this->parame);
                $res = \CJSON::decode($res, true);
                $res['training_id'] = $training_id;
                $res['subject'] = trim($this->parame['subject']);
                if($res['code']==0){
                    if($this->saveData($res)){
                        $Training->is_create_gensee = 1;
                        $Training->save();
                    }
                }
                return $res['code'];
            } 
        }
    }
    
    /*
     * 编辑大课堂
     */
    public function updateGensee($training_id){
        $code = 0;
        $Gensee = self::model()->findByPk($training_id);
        if($Gensee){
            $id = $Gensee['id'];
            $subject = $Gensee['subject'];
            $startDate = date('Y-m-d', $Gensee['startDate'] / 1000);
            $invalidDate = date('Y-m-d', $Gensee['invalidDate'] / 1000);
            
            $Training = Training::model()->findByPk($training_id);
            if( !empty($Training) && $Training['way']==2 ){
                //TrainingGenseePlan::model()->setPlan($Training);
                $filiale_name = '全国';
                if($Training['filiale_id']!=BRANCH_ID){
                    $city = ServiceRegion::model()->getBranchToCity($Training['filiale_id']);
                    $filiale_name = isset($city[0]['region_name'])?$city[0]['region_name']:'';
                }
                $starttime = substr($Training['starttime'],0, 10);
                $endtime = substr($Training['endtime'],0, 10);
                $title = $filiale_name.$Training['title'].date('YmdHi',strtotime($Training['starttime']));
                
                if( $starttime!=$startDate || $endtime!=$invalidDate || $title!=$subject ){
                    $this->parame['startDate'] = strtotime($starttime) * 1000;
                    $this->parame['invalidDate'] = strtotime($endtime.' 23:59:59') * 1000;
                    $this->parame['subject'] = $title;
                    $this->parame['id'] = $id;
                    
                    $res = \Yii::app()->curl->post('http://fwxgx.gensee.com/integration/site/training/room/modify', $this->parame);
                    $res = \CJSON::decode($res, true);
                    if(isset($res['code']) && $res['code']==0){
                        $data['training_id'] = $training_id;
                        $data['startDate'] = $this->parame['startDate'];
                        $data['invalidDate'] = $this->parame['invalidDate'];
                        $data['subject'] = trim($this->parame['subject']);
                        $this->saveData($data);
                    }else{
                        $code = isset($res['code']) ? $res['code'] : 500;
                    }
                }
            }
        }
        return $code;
    }
    
    /*
     * 将录制或上传的课程地址更新到coursewareUrl
     */
    public function upCoursewareUrl($training_id, $courseware_type='recording', $coursewareId, $coursewareUrl, $updateCoursewareId, $updateCoursewareUrl){
        $res = 'Y';
        if(is_numeric($training_id)){
            $Gensee = self::model()->findByPk($training_id);
            if($Gensee){
               if(empty($coursewareId) || empty($coursewareUrl)){
					$courseware = $this->getCoursewareUrl($Gensee->id);
					$coursewareId = isset($courseware['id']) ? $courseware['id'] : '';
					$coursewareUrl = isset($courseware['url']) ? $courseware['url'] : '';
			   }
               $Gensee->coursewareId = $coursewareId;
			   $Gensee->coursewareUrl = $coursewareUrl;
			   if(!empty($updateCoursewareId)){
					if(empty($updateCoursewareUrl)){
						$updateCoursewareUrl = $this->getUploadCoursewareUrl($updateCoursewareId);
						$updateCoursewareUrl = strstr($updateCoursewareUrl,'http://') ? $updateCoursewareUrl : '';
					}
					$Gensee->sdkid = $updateCoursewareId;
					$Gensee->updateCoursewareUrl = $updateCoursewareUrl;
			   }
			   $Gensee->courseware_type = $courseware_type;
               $Gensee->_update_time = date('Y-m-d H:i:s');
               $Gensee->save();
               $courseware_url = $courseware_type=='upload' ? $updateCoursewareUrl : $coursewareUrl;
               Training::model()->updateByPk($training_id, array('courseware_url'=>$courseware_url) );
            }else{
               $res = NULL; 
            }
        }
        return $res;
    }
    
    /*
     *  获取课堂录制地址（多个取最新一条）
     */
    public function getCoursewareUrl($roomId){
        $data = [];
        $this->parame['roomId'] = $roomId;
        $res = \Yii::app()->curl->post('http://fwxgx.gensee.com/integration/site/training/courseware/list', $this->parame);
        $res = \CJSON::decode($res, true);
        if(isset($res['code']) && $res['code']==0 ){
            if( isset($res['coursewares']) && is_array($res['coursewares']) && !empty($res['coursewares']) ){
                $coursewares = $res['coursewares'];
                if(count($coursewares)==1){
                    $data['url'] = $coursewares[0]['url'];
					$data['id'] = $coursewares[0]['id'];
                }else{
                    foreach($coursewares as $v){
                        $createdTime = $v['createdTime'] / 1000;
                        $timeToUrl[$createdTime]['url'] = $v['url'];
						$timeToUrl[$createdTime]['id'] = $v['id'];
                    }
                    ksort($timeToUrl);
                    $data = end($timeToUrl);
                }
            }else{
                $data = '不存在回放课件';
            }
        }else{
            $data = isset($this->apierror[$res['code']]) ? $this->apierror[$res['code']] : '未知错误';
        }
        return $data;
    }
    
    /*
     * 获取上传课件地址
     */
    public function getUploadCoursewareUrl($sdkid){
        $data = '';
        $this->parame['coursewareId'] = $sdkid;
        $res = \Yii::app()->curl->post('http://fwxgx.gensee.com/integration/site/training/courseware/info', $this->parame);
        $res = \CJSON::decode($res, true);
        if(isset($res['code']) && $res['code']==0 ){
            if( isset($res['url']) && !empty($res['url']) ){
                $data = $res['url'];
            }else{
                $data = '不存在此课件';
            }
        }else{
            $data = isset($this->apierror[$res['code']]) ? $this->apierror[$res['code']] : '未知错误';
        }
        return $data;
    }
    
    /*
     * 
     */
    public function getHistory(){
        $this->parame['roomId'] = '96AuuEzSmV';
        $res = \Yii::app()->curl->post('http://fwxgx.gensee.com/integration/site/training/export/history', $this->parame);
        $res = \CJSON::decode($res, true);
        print_r($res);
    }
   
}