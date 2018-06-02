<?php

/**
 * This is the model class for table "{{research}}".
 *
 * The followings are the available columns in table '{{research}}':
 * @property string $id
 * @property string $column_id
 * @property string $research_title
 * @property string $research_url
 * @property integer $column_type
 * @property string $filiale_id
 * @property string $area_name
 * @property integer $user_id
 * @property integer $trigger_config
 * @property integer $status
 * @property integer $terminal_type
 * @property integer $login_type
 * @property string $start_time
 * @property string $end_time
 * @property string $explain
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Research;
use application\models\Research\ResearchIssue;
use application\models\Research\ResearchOption;
use application\models\Research\UserResearch;
use application\models\Member\CommonMember;
use application\models\User\UserBrief;
use application\models\ServiceRegion;
class Research extends \CActiveRecord
{
        private $msg = array(
            'Y' => '处理成功',
            1 => '栏目ID，不合法',
            2 => '栏目类型，不合法',
            3 => '触发设置，不合法',
            4 => '用户信息不合法',
            5 => '信息发送失败',
            6 => '调研信息已下线',
            7 => '参数错误',
            8 => '秘钥检查失败',
        );
        private $column_type_name = array(
                    '1'=>'同城活动',
                    '2'=>'培训报名'
        ); 
        private $status_name = array(
                    '0'=>'屏蔽',
                    '1'=>'上线',
                    '2'=>'已下线'
        ); 
        /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{research}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('start_time, end_time, _create_time, _update_time', 'safe'),
			array('column_type, user_id, trigger_config, status, terminal_type, login_type, _delete', 'numerical', 'integerOnly'=>true),
			array('column_id', 'length', 'max'=>11),
			array('research_title', 'length', 'max'=>120),
			array('research_url, area_name', 'length', 'max'=>150),
			array('filiale_id', 'length', 'max'=>10),
			array('explain', 'length', 'max'=>255),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, column_id, research_title, research_url, column_type, filiale_id, area_name, user_id, trigger_config, status, terminal_type, login_type, start_time, end_time, explain, _delete, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'id' => 'ID',
			'column_id' => '栏目ID',
			'research_title' => '调研标题',
			'research_url' => '调研url',
			'column_type' => '栏目类型: 1:同城活动,2:培训报名',
			'filiale_id' => '分之id',
			'area_name' => '调研地区',
			'user_id' => '用户标识id',
			'trigger_config' => '触发设置: 1:查看内容时,2:签到完成后,3:报名完成后',
			'status' => '发布状态:0屏蔽 1正常 2待发布',
			'terminal_type' => '终端类型:1:PC端 2手机端 3两端共有',
      'login_type' => '登录类型:1:广联达登录 2手机号登录 3无登录',
			'start_time' => '开启时间',
			'end_time' => '结束时间',
			'explain' => '调研说明',
			'_delete' => '是否已经删除，0 ，否； 1 ，已删除',
			'_create_time' => '添加时间',
			'_update_time' => '更新时间',
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

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('column_id',$this->column_id,true);
		$criteria->compare('research_title',$this->research_title,true);
		$criteria->compare('research_url',$this->research_url,true);
		$criteria->compare('column_type',$this->column_type);
		$criteria->compare('filiale_id',$this->filiale_id,true);
		$criteria->compare('area_name',$this->area_name,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('trigger_config',$this->trigger_config);
		$criteria->compare('status',$this->status);
                $criteria->compare('terminal_type',$this->terminal_type);
		$criteria->compare('start_time',$this->start_time,true);
		$criteria->compare('end_time',$this->end_time,true);
		$criteria->compare('explain',$this->explain,true);
		$criteria->compare('_delete',$this->_delete);
		$criteria->compare('_create_time',$this->_create_time,true);
		$criteria->compare('_update_time',$this->_update_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Research the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 获取调研列表
         */
        public function get_research_list($con, $orderBy, $order, $limit, $offset){
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
            foreach ($ret as $k=>$v){
                $data[$k] = $v->attributes;
                if($data[$k]['end_time']<date('Y-m-d H:i:s',time())){
                    $editRes = $this->updateByPk($data[$k]['id'],array('status'=>2,'research_url'=>'','qrcodeurl'=>''));
                    if($editRes){
                        $data[$k]['research_url'] = '';
                        $data[$k]['qrcodeurl'] = '';
                        $data[$k]['status'] = 2;
                    }
                }
                $areaName = ServiceRegion::model()->getBranchToCity($data[$k]['filiale_id']);
                $data[$k]['column_name'] =!empty($this->column_type_name [$data[$k]['column_type']])&& $data[$k]['column_type']!=3? $this->column_type_name [$data[$k]['column_type']].'--'.$data[$k]['column_id'] : '无关联栏目';
                $data[$k]['status_name'] =!empty($this->status_name [$data[$k]['status']]) ? $this->status_name [$data[$k]['status']] : '';
                $data[$k]['area_name']   = !empty($areaName[0]['region_name'])?$areaName[0]['region_name']:'全国';
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);   
        }
        /**
         * 添加调研
         */
        public function researchSave($data = null){
            if(!empty($data)){
                $model = new Research();
                $data['filiale_id'] = \Yii::app()->user->branch_id;
                $data['user_id'] = \Yii::app()->user->user_id;
                $data['_create_time'] = date('Y-m-d H:i:s');
                $data['_update_time'] = date('Y-m-d H:i:s');
                $model->attributes=$data;
                if($model->save())
                  $id  = $model->primaryKey;
                return $id;
            }
        }
        /**
         * 修改问题
         */
        public function enditResearch($research_id,$data = null){
            if(!empty($data)){
                $editarray = array();
                $data['_update_time'] = date('Y-m-d H:i:s');
                $editarray =ResearchIssue::model()->attributes=$data;
                $enditIssue = $this->updateByPk($research_id,$editarray);
                return $enditIssue;
            }
        }
        /**
         * 效验同一个时间线上只能存在一个上线调研
         */
        public function checkResearch($con){
            $criteria = new \CDbCriteria;
            if(!empty($con)){
                 foreach($con as $key => $val){
                      $criteria->compare($key, $val);
                 }
            }
            $ret = self::model()->findAll($criteria);
            return $ret;
        }
        /**
         * 获取导出记录
         */
        public function GetExcelInfo($research_id){
            $fixationHeader = array('地区','用户名','电话','调研标题','提交时间','提交IP');
            $tmp = array();
            $data  =array();
            $getResearchRes = $this->findByPk($research_id);
            $getIssueInfo = ResearchIssue::model()->findAll("research_id=:research_id and _delete=:_delete",array('research_id'=>$research_id,'_delete'=>0));
            foreach ($getIssueInfo as $key=>$item){
                $dynamicHeader[] = $item->issue_name;
            }
            $heder = array_merge ($fixationHeader,$dynamicHeader);
            $getMemberResearch = UserResearch::model()->findAll(array(
                    'group' => 'member_id', 
                    'condition' => 'research_id=:research_id', //占位符，安全考虑，避免sql注入
                    'params' => array(':research_id'=>$research_id)
            ));
            if(!empty($getMemberResearch)){
                foreach ($getMemberResearch as $key=>$row){
                    $tmp['member_area'] = $row->member_area;
                    $getMenberName = CommonMember::model()->find("member_user_id=:member_user_id",array('member_user_id'=>$row->member_id));
                    $tmp['memberName'] = isset($getMenberName->member_user_name)?$getMenberName->member_user_name:'';
                    $getphone = UserBrief::model()->find("UIN=:UIN",array('UIN'=>$row->member_id));
                    $tmp['phone'] = !empty($getphone)?$getphone->sMobile:$row->mobile_phone;
                    $tmp['research_title'] = $getResearchRes->research_title;
                    $tmp['_create_time'] = $row->_create_time;
                    $tmp['member_ip'] = $row->member_ip;
                    foreach ($getIssueInfo as $key=>$item){
                        $answerContent = UserResearch::model()->find("issue_id=:issue_id and research_id=:research_id and member_id=:member_id",array('issue_id'=>$item->id,'research_id'=>$getResearchRes->id,'member_id'=>$row->member_id));
                        if(empty($answerContent)){
                            $answerContent = UserResearch::model()->find("issue_id=:issue_id and research_id=:research_id and mobile_phone=:mobile_phone",array('issue_id'=>$item->id,'research_id'=>$getResearchRes->id,'mobile_phone'=>$row->mobile_phone));
                        }
                        $tmp['answer_content'.$item->id] = $this->disposeContent($answerContent['answer_content'],$item->issue_type);
                    }
                    $data[] = $tmp;
                }
            }
            return array('data'=>$data,'header'=>$heder);
        }
        /**
         * 处理问题
         */
        public function disposeContent($answer_content,$issue_type){
            $optionName = '';
            if(!empty($answer_content)){
                $unserialize= explode(',', iunserializer($answer_content));
                if(is_array($unserialize)){
                    foreach ($unserialize as $key=>$item){
                        if(in_array($issue_type, array(1,2))){
                            $OptionList = ResearchOption::model()->findByPk($item);
                            if(!empty($OptionList)){
                                $optionName.= $OptionList->answer_name."|";
                            }
                        }else{
                            $optionName.= $item;
                        } 
                    }                   
                }else{
                    $optionName = iunserializer($answer_content);
                }
            }
            return $optionName;
        }
}
