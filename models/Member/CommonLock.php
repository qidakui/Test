<?php

/**
 * This is the model class for table "{{common_lock}}".
 *
 * The followings are the available columns in table '{{common_lock}}':
 * @property string $id
 * @property integer $member_id
 * @property string $operate_id
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Member;
class CommonLock extends \CActiveRecord
{
    private $msg = array(
            'Y' => '成功',
             1 => '操作数据库错误',
             2 => '数据已经存在',
             3 => '数据不存在',
             4 => '参数错误',
    );
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{common_lock}}';
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
	 * @return CommonLock the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 效验是否存在
         */
        public function findRecord($member_id){
            $result = self::model()->find('member_user_id=:member_user_id and _delete=:_delete',array(':member_user_id'=>$member_id,':_delete'=>0));
            return !empty($result) ? $result : array();
        }
        /**
         * 保存数据
         */
        public function SaveLock($data){
            try {
                $manager_id = \Yii::app()->user->user_id;
                if(empty($manager_id))
                     throw new \Exception(4);
                $member_id    = !empty($data['ids']) ? $data['ids'] : 0;
                $commonLockObj = self::model()->findRecord($member_id);
                if($data['status'] == 1){
                    if(empty($commonLockObj)){
                         $model = new self();
                         $model->member_user_id      = $data['ids'];
                         $model->manager_id          = $manager_id;
                         $model->content             = $data['content'];
                         $model->_create_time        = date('Y-m-d H:i:s');
                         $model->_update_time        = date('Y-m-d H:i:s');
                         $model->save();
                         $id  = $model->primaryKey;
                         if($id){
                              $msgNo = 'Y';
                         }else{
                             throw new \CException('1');
                         }
                    }else{
                        $msgNo = 'Y';
                    }
                }else{
                     if(!empty($commonLockObj)){
                         $commonLockObj->_delete      = 1;
                         $commonLockObj->_update_time        = date('Y-m-d H:i:s');
                         $commonLockObj->save();
                     }
                     $msgNo = 'Y';
                }
            } catch (\Exception $ex) {
                 $msgNo = $ex->getMessage();
            }
            return $msgNo;
        }
}
