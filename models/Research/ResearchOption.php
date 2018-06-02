<?php

/**
 * This is the model class for table "{{research_option}}".
 *
 * The followings are the available columns in table '{{research_option}}':
 * @property string $id
 * @property string $issue_id
 * @property string $answer_name
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Research;
class ResearchOption extends \CActiveRecord
{
        public $total;
        /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{research_option}}';
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
			array('_delete', 'numerical', 'integerOnly'=>true),
			array('issue_id', 'length', 'max'=>11),
			array('answer_name', 'length', 'max'=>120),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, issue_id, answer_name, _delete, _create_time, _update_time', 'safe', 'on'=>'search'),
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
			'issue_id' => '问题ID',
			'answer_name' => '选项名称',
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
		$criteria->compare('issue_id',$this->issue_id,true);
		$criteria->compare('answer_name',$this->answer_name,true);
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
	 * @return ResearchOption the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 新增问题选择项
         */
        public function addIssueOption($issue_id,$data = null){
                if(!empty($data)){
                    $return = array();
                    foreach ($data as $key=>$item){
                        $newarray = array();
                        $model = new ResearchOption(); 
                        $newarray['issue_id'] = $issue_id;
                        $newarray['answer_name'] = $item;
                        $newarray['_create_time'] = date('Y-m-d H:i:s');
                        $newarray['_update_time'] = date('Y-m-d H:i:s');
                        $model->attributes = $newarray;
                        $addoption = $model->save();
                        $return[$key] = $newarray;
                        $return[$key]['inserid'] =$model->primaryKey;
                    }
                    return $return;
                }
        }
        /**
         * 修改问题选择项
         */
        public function editIssueOption($issue_id,$data = null){
            if(!empty($data)){
                $delIssueOption = ResearchOption::model()->updateAll(array('_delete'=>1),'issue_id=:issue_id and _delete=:_delete',array('issue_id'=>$issue_id,'_delete'=>0));
                $editIssueOption = $this->addIssueOption($issue_id,$data);
                return $editIssueOption;
            }
        }
        /**
         * 删除问题选择项
         */
        public function deleteOption($del_id = null){
            if(!empty($del_id)){
                $criteria = new \CDbCriteria;
                $cartarray = array();
                $criteria->addInCondition('issue_id',$del_id);
                $editnot = ResearchOption::model()->updateAll(array('_delete'=>1),$criteria); 
                return $editnot;
            }
        }
        /**
         * 求出点击次数总数
         */
        public function opt_number_sum($con){
            $criteria = new \CDbCriteria();
            if(!empty($con)){
                foreach($con as $key => $val){
                    $criteria->compare($key, $val);
                }
            }
            $criteria->select = 'SUM(opt_number) total';
            $ret = self::model()->findAll($criteria);
            return $ret;
        }
        //查询单条
        public function getOne($con = array(),$nums = null) {
            $criteria = new \CDbCriteria;
            if (!empty($con)) {
                foreach ($con as $key => $val) {
                    $criteria->compare($key, $val);
                }
            }
            $ret = !empty($nums)?self::model()->findAll($criteria):self::model()->find($criteria);
            return $ret;
        }    
}
