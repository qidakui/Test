<?php

/**
 * This is the model class for table "{{information_cat}}".
 *
 * The followings are the available columns in table '{{information_cat}}':
 * @property string $id
 * @property integer $parent_id
 * @property string $cat_name
 * @property integer $cat_type
 * @property integer $level
 * @property integer $sort_order
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Home;
class InformationCat extends \CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{information_cat}}';
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
	 * @return InformationCat the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
        protected function beforeSave()
	{
		if(parent::beforeSave()){
			if($this->isNewRecord){
				$this->_delete = 0;
				$this->_update_time = date('y-m-d H:m:s');
				$this->_create_time = date('y-m-d H:m:s');
			}else{
				$this->_update_time = date('y-m-d H:m:s');
			}
			return true;
		}else{
			return false;
		}
	}        
	public function get_list(){
            $filiale_id = \Yii::app()->user->branch_id;
            $result = array();
            $criteria = new \CDbCriteria;
            $criteria->compare('_delete', 0);
            $criteria->order = 'sort asc, _create_time desc';
            $categorys = self::model()->findAll($criteria);
            foreach($categorys as $c){
                $c = $c->attributes;
                $c['category_name'] = $c['name'];
                if($c['parent_id']==0){
                    $ToChinaseNum = $c['sort']==0 ? '零' : $this->ToChinaseNum($c['sort']);
                    $c['name'] = '<'.$ToChinaseNum.'>'.$c['name'];
                }else{
                    $c['name'] = $c['sort'].' '.$c['name'];
                }
                    $result[]= $c;
             }
                   return $result;
	}
        public function getlist($con, $orderBy, $order, $limit, $offset){
           $criteria = new \CDbCriteria;
           if(!empty($con)){
               foreach($con as $key => $val){
                   $criteria->compare($key, $val);
               }
           }
           $criteria->order = 'sort asc, _create_time desc';
           $criteria->limit = $limit; //取1条数据，如果小于0，则不作处理
           $criteria->offset = $offset; //两条合并起来，则表示 limit 10 offset 1,或者代表了。limit 1,10
           $ret = self::model()->findAll($criteria);
           foreach($ret as $k => $v){
               $data[$k] = $v->attributes;
           }
           $data = !empty($data) ? $data : array();
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
        //查询排序最大值
        public function get_big_sort($parent_id){
            $criteria = new \CDbCriteria;
            $criteria->select = 'sort';
            $criteria->compare('filiale_id', \Yii::app()->user->branch_id);
            $criteria->compare('parent_id', $parent_id);
            $criteria->compare('_delete', 0);
            $criteria->order = 'sort desc';
            $category = self::model()->find($criteria);
            return empty($category) ? 1 : intval($category->sort) + 1;
        }  
	public function createCategory($category){
		$model = new self();
		foreach($category as $k=>$v){
			$model->$k = $k=='name' ? \CHtml::encode($v) : $v;
		}
		return $model->save();
	}

	public function updateCategory($info){
        //print_r($this);die;
		//$this->old_date = $this->attributes;
		foreach($info as $k=>$v){
			$this->$k = $k=='name' ? \CHtml::encode($v) : $v;
		}
		return $this->save();
	}        
        //数字转中文
        protected function ToChinaseNum($num){
            $char = array("零","一","二","三","四","五","六","七","八","九");
            $dw = array("","十","百","千","万","亿","兆");
            $retval = "";
            $proZero = false;
            for($i = 0;$i < strlen($num);$i++)
            {
                if($i > 0)    $temp = (int)(($num % pow (10,$i+1)) / pow (10,$i));
                else $temp = (int)($num % pow (10,1));

                if($proZero == true && $temp == 0) continue;

                if($temp == 0) $proZero = true;
                else $proZero = false;

                if($proZero)
                {
                    if($retval == "") continue;
                    $retval = $char[$temp].$retval;
                }
                else $retval = $char[$temp].$dw[$i].$retval;
            }
            if($retval == "一十") $retval = "十";
            return $retval;
    }        
}
