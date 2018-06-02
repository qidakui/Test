<?php

/**
 * This is the model class for table "{{export}}".
 *
 * The followings are the available columns in table '{{export}}':
 * @property string $id
 * @property string $title
 * @property string $download_url
 * @property string $table_name
 * @property string $export_data
 * @property string $excel_header
 * @property string $file_name
 * @property integer $is_execute
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\ExportTask;
class ExportTask extends \CActiveRecord
{
    private $msg = array(
       'Y' => '成功',
        1  => '来源错误',
        2  => '参数错误',
        3  => '表头错误',
    );
    private $typeKey = array(
        0 => '导出结果生成中',
        1 => '已生成',
        2 => '导出结果执行中',
    );
        /**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{export_task}}';
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
	 * @return Export the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
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
                $data[$k]['execute_name']   = isset($this->typeKey[$data[$k]['is_execute']]) ? $this->typeKey[$data[$k]['is_execute']] : '';
            }
            $data = !empty($data) ? $data : array();
            return array('data' => $data, 'iTotalRecords' => $count, 'iTotalDisplayRecords' => $count,);
        }
        public function creditTask($action, $param, $header, $title = 'fwxgx', $filename = 'data'){  
            try {
                if(empty($action)){
                    throw new \CException('1');
                }
                if(empty($param)){
                   throw new \CException('2'); 
                }
                if(empty($header)){
                   throw new \CException('3'); 
                }
                $model = new self();
                $model->title               = !empty($title) ? $title : '';
                $model->source              = !empty($action) ? $action : '';
                $model->export_param        = !empty($param) ? serialize($param) : '';
                $model->excel_header        = !empty($header) ? serialize($header) : '';
                $model->file_name           = !empty($filename) ? $filename : date('Y-m-d');
                $model->_create_time        = date('Y-m-d H:i:s');
                $model->_update_time        = date('Y-m-d H:i:s');
                $model->save();
                $msgId =$model->primaryKey;
                if($msgId){
                    $msgNo = 'Y';
                }
            } catch (Exception $ex) {
                 $msgNo = $ex->getMessage();
            }
            if($msgNo == 'Y'){
                return true;
            } else{
                return $this->msg[$msgNo];
            }        
        }
        /**
         * 获取导出任务
         */
        public function  exportinfo($limit){
            $result = $this->find(array(
                    'limit'  => $limit,
                    'order' => '_create_time DESC',
                    'condition' => 'is_execute=:is_execute ', //占位符，
                    'params' => array(':is_execute'=>'0'),                    
            ));
            return $result;
        }
}
