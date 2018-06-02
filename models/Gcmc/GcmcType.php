<?php

/**
 * This is the model class for table "{{category}}".
 *
 * The followings are the available columns in table '{{category}}':
 * @property string $id
 * @property string $category_name
 * @property string $parent_id
 * @property integer $sort_order
 * @property string $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Gcmc;
class GcmcType extends \CActiveRecord
{

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{gcmc_type}}';
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
			'id' => 'id',
            'filiale_id' => '分支id',
			'name' => '分类名称',
			'parent_id' => '父ID',
			'level' => '分类级别',
			'sort' => '排序',
			'_delete' => '是否删除',
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

	protected function afterSave(){
		$column_name = '产品分类';
		if($this->isNewRecord){
			$column_name = '新增产品分类';
			$operate = 'add';
		}else{
			if($this->_delete == 1){
				$column_name = '删除产品分类';
				$operate = 'del';
			}else{
				$column_name = '编辑产品分类';
				$operate = 'edit';
			}
		}
		//OperationLog::addLog(OperationLog::$operationCategory, $operate, $column_name, $this->id, $this->old_date, $this->attributes);
		//$this->old_date = '';
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

	public function logical_delete(){
		$this->_delete=1;
		return $this->save();
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
    

        
    public function get_name_list($con){
        $bangding = false;
        if(isset($con['bangding'])){
            $bangding = true;
            $CourseIntroduceId = $con['CourseIntroduceId'];
            unset($con['bangding'],$con['CourseIntroduceId']);
        }
        
        $criteria = new \CDbCriteria;
        if(!empty($con)){
            foreach($con as $key => $val){
                $criteria->compare($key, $val);
            }
        }
        $ret = self::model()->findAll($criteria);
        if($bangding){
            foreach($ret as $k => $v){
                $CourseIntroduceCount = GcmcCourseIntroduce::model()->getCount(array('type_id'=>$v['id'],'id!'=>$CourseIntroduceId,'status'=>1));
                if( $CourseIntroduceCount ){
                    continue;
                }
                $data[$v['id']] = $v['name'];
            }  
        }else{
            foreach($ret as $k => $v){
                $data[$v['id']] = $v['name'];
            }
        }
            
        $data = !empty($data) ? $data : array();
		return $data;
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
    

    
	public function getChildCategory($parent_id){
		$criteria = new \CDbCriteria;
        $criteria->compare('_delete', 0);
		$criteria->compare('parent_id', $parent_id);
		$criteria->order = 'sort asc';
		return self::findAll($criteria);
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
