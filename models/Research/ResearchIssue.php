<?php

/**
 * This is the model class for table "{{research_issue}}".
 *
 * The followings are the available columns in table '{{research_issue}}':
 * @property string $id
 * @property string $research_id
 * @property string $template_id
 * @property string $issue_name
 * @property integer $issue_type
 * @property integer $claim_type
 * @property integer $is_default
 * @property integer $is_template
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Research;
use application\models\Research\ResearchOption;
use application\models\ServiceRegion;
class ResearchIssue extends \CActiveRecord
{       
        private $issue_type_name = array(
            1 => '多选',
            2 => '单选',
            3 => '输入型'
        );    
        private $claim_type_name = array(
            1 => '必答',
            2 => '非必答'
        );
        private $msg = array(
                'Y' => '成功',
                 1 => '参数错误',
                 2 => '数据已经存在',
                 3=> '数据异常'
        );         
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{research_issue}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('_create_time, _update_time', 'safe'),
			array('issue_type, claim_type, is_default, is_template,_delete', 'numerical', 'integerOnly'=>true),
			array('research_id', 'length', 'max'=>11),
			array('template_id', 'length', 'max'=>11),
			array('issue_name', 'length', 'max'=>120),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, research_id, template_id,issue_name, issue_type, claim_type, is_default,is_template, _delete, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'research_id' => '调研ID',
			'template_id' => '模板ID',
			'issue_name' => '问题标题',
			'issue_type' => '问题类型: 1:多选,2:单选,3:输入型',
			'claim_type' => '答题要求: 1:必答,2:非必答',
                        'is_default' => '是否默认问题: 0:否,1:是',
                        'is_template'   => '是否模板问题: 0:否,1:是',
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
		$criteria->compare('research_id',$this->research_id,true);
		$criteria->compare('template_id',$this->research_id,true);
		$criteria->compare('issue_name',$this->issue_name,true);
		$criteria->compare('issue_type',$this->issue_type);
		$criteria->compare('claim_type',$this->claim_type);
		$criteria->compare('is_default',$this->is_default);
		$criteria->compare('is_template'   ,$this->is_template);
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
	 * @return ResearchIssue the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        
        /**
         * 获取数据列表
         */
        public function ger_Issue_list($con, $orderBy, $order, $limit, $offset){
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
            foreach($ret as $k => $v){
                $data[$k] = $v->attributes;
                $data[$k]['issue_type_name'] =!empty($this->issue_type_name[$data[$k]['issue_type']]) ? $this->issue_type_name[$data[$k]['issue_type']] : '';
                $data[$k]['claim_type_name'] =!empty($this->claim_type_name[$data[$k]['claim_type']]) ? $this->claim_type_name[$data[$k]['claim_type']] : '';
                $data[$k]['issue_option_list'] = $this->getIssueList($data[$k]['id']);
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);   
        }
        /**
         * 根据问题ID获取问题以及答案
         */
        public function getIssueInfo($issue_id){
            if(!empty($issue_id)){
                $newarray = array();
                $issueinfo = ResearchIssue::model()->findByPk($issue_id);
                if($issueinfo){
                    $newarray['issueinfo'] = $issueinfo;
                    $newarray['issueoption']=ResearchOption::model()->findAll("_delete=:_delete and issue_id=:issue_id",array('_delete'=>0,'issue_id'=>$issue_id));
                    return $newarray;
                }
            }
        }
        /**
         * 获取问题选择项
         */
        public function getIssueList($issue_id){
            if($issue_id){
                $optionName = '';
                /*修复漏洞数据start*/
                $patchdata = ResearchIssue::model()->findByPk($issue_id);
                if(!empty($patchdata)){
                    if($patchdata->issue_type == 3){
                        $findInfo = ResearchOption::model()->find("issue_id=:issue_id",array('issue_id'=>$issue_id));
                        if(!empty($findInfo))
                         ResearchOption::model()->updateAll(array('_delete'=>1), "issue_id=:issue_id",array(':issue_id'=>$patchdata->id));
                    }
                }
                /*修复漏洞数据end*/
                $OptionList = ResearchOption::model()->findAll("_delete=:_delete and issue_id=:issue_id",array('_delete'=>0,'issue_id'=>$issue_id));
                if($OptionList){
                    foreach ($OptionList as $key=>$item){
                        $optionName .= $item->answer_name."<br>";
                    }
                }
                return $optionName;
            }
        }
        /**
         * 添加问题
         */
        public function issueSave($research_id,$data = null,$copy = null){
            if(!empty($data)){
                $newarray = array();
                $model = new ResearchIssue();
                $newarray['research_id'] = $research_id;
                $newarray['issue_name'] = $data['issue_name'];
                $newarray['issue_type'] = $data['issue_type'];
                $newarray['claim_type'] = $data['claim_type'];
                $newarray['is_default'] = !empty($data['is_default'])?$data['is_default']:'0';
                $newarray['template_id'] = !empty($data['template_id'])?$data['template_id']:'0';
                $newarray['is_template']     = !empty($data['template_id'])?1:2;
                $newarray['_create_time'] = date('Y-m-d H:i:s');
                $newarray['_update_time'] = date('Y-m-d H:i:s');
                if($copy == 'copy'){
                    $model->attributes=$newarray;
                    if($model->save())
                        $id[] = $model->primaryKey;
                    return $id;
                }else{
                    $model->attributes=$newarray;
                    if($model->save())
                        $id  = $model->primaryKey;
                        return $id;
                }
            }
        }
        /**
         * 修改问题
         */
        public function enditIssue($issue_id,$data = null){
            if(!empty($data)){
                $editarray = array();
                $editarray['issue_name'] = $data['issue_name'];
                $editarray['issue_type'] = $data['issue_type'];
                $editarray['claim_type'] = $data['claim_type'];
                $editarray['_update_time'] = date('Y-m-d H:i:s');                
                $enditIssue = ResearchIssue::model()->updateByPk($issue_id,$editarray);
                return $enditIssue;
            }
        }
        /**
         * 效验问题是否有
         */
        public function findIssue($research_id,$is_default){
            $result = $this->findAll('research_id=:research_id and is_default=:is_default and _delete=:_delete',array(':research_id'=>$research_id,':is_default'=>$is_default,':_delete'=>0));
            return $result;
        }
        //查询记录
        public function getdata($con = array(),$nums = null) {
            $criteria = new \CDbCriteria;
            if (!empty($con)) {
                foreach ($con as $key => $val) {
                    $criteria->compare($key, $val);
                }
            }
            $ret = !empty($nums)?self::model()->findAll($criteria):self::model()->find($criteria);
            return $ret;
        }         
        /**
         * 统计某个培训，满意度、符合度
         */
        public function statistical($column_id = null,$column_type = null,$trigger_config = null){
            $newarray = array();
            try {
                if(empty($column_id))
                    throw new \CException('1');
                if(empty($column_type))
                    throw new \CException('1');
                if(empty($trigger_config))
                     throw new \CException('1');
                $criteria = new \CDbCriteria;
                $criteria->compare('column_id',$column_id);
                $criteria->compare('column_type',$column_type);
                $criteria->compare('trigger_config',$trigger_config);
                $criteria->compare('_delete',0);
                $researchInfo = Research::model()->findAll($criteria);
                if(empty($researchInfo))
                    throw new \CException('3');
                foreach ($researchInfo as $key=>$item){
                    $userResearch = UserResearch::model()->findAll("research_id=:research_id and column_id=:column_id",array(':research_id'=>$item->id,':column_id'=>$column_id));
                    if(!empty($userResearch)){
                        $researray[$key]['research_id'] = $item->id;
			if(isset($researray[$key]['research_id'])){
                                $researchArray[] = $researray[$key]['research_id'];
			}
                    }
                }
                if(empty($researchArray))
                    throw new \CException('3'); 
                $con['_delete'] = 0;
                $con['research_id'] = $researchArray;
                $con['is_default']  = 1;
                $list = ResearchIssue::model()->ger_Issue_list($con, 'desc', 'id', '10', 0);
                foreach ($list['data'] as $key=>$item){
                     $getOptionList = ResearchOption::model()->findAll("issue_id=:issue_id and _delete=:_delete", array('issue_id' => $item['id'],':_delete'=>0));
                     if (!empty($getOptionList)) {
                         foreach ($getOptionList as $key=>$row){
                              if ($row->issue_id == $item['id']) {
                                  $opt_number_sum = ResearchOption::model()->opt_number_sum(array('issue_id' => $item['id']));
                                  $percent = !empty($row->opt_number) ? number_format($row->opt_number / $opt_number_sum[0]['total'], 2) * 100 : '0';
                                  $array[$column_id][] = $percent;
                              }
                         }
                     }
                }
                if(!empty($array)){
                    $msgNo = 'Y';
                    foreach ($array as $key=>$row){
                        $newarray[$column_id]['satisfied'] = $row[0];
                        $newarray[$column_id]['conform'] = $row[3];
                    }
                }else{
                    throw new \CException('1');
                }
            } catch (\Exception $ex) {
                 $msgNo = $ex->getMessage();
            }
            $msg = isset($this->msg[$msgNo]) ? $this->msg[$msgNo] : $this->msg[1];
            $return = array(
                'code' => $msgNo,
                'msg' => $msg,
                'data' => $newarray
            );
			//print_r($return);exit;
            return $return;
        }
}
