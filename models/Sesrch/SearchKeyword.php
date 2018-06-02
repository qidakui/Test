<?php

/**
 * This is the model class for table "{{search_keyword}}".
 *
 * The followings are the available columns in table '{{search_keyword}}':
 * @property string $id
 * @property string $keyword
 * @property string $search_total
 */
namespace application\models\Sesrch;
class SearchKeyword extends \CActiveRecord
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
		return '{{search_keyword}}';
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
	 * @return SearchKeyword the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        /**
         * 搜索词列表
         * @param type $con
         * @param type $orderBy
         * @param type $order
         * @param type $limit
         * @param type $offset
         * @return type
         */
        public function getlist($con, $orderBy, $order, $limit, $offset){
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
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        }        
}
