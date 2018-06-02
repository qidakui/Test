<?php

/**
 * This is the model class for table "{{template}}".
 *
 * The followings are the available columns in table '{{template}}':
 * @property string $id
 * @property integer $column_type
 * @property integer $column_id
 * @property integer $parent_id
 * @property integer $show_type
 * @property integer $template_type
 * @property integer $animation_type
 * @property string $title
 * @property string $sub_title
 * @property string $desc
 * @property integer $font_color
 * @property string $background_pic
 * @property string $video_url
 * @property integer $sort
 * @property integer $status
 * @property integer $_delete
 * @property string $_create_time
 * @property string $_update_time
 */
namespace application\models\Template;
class Template extends \CActiveRecord
{

    public $columnType = array(
        1 => '向导页',
        2 => '线上培训'
    );

    public $templateType = array(
        1 => '图文',
        2 => '视频'
//			3 => 'banner'
    );

    public $showTypeKey = array(
        1 => '文字居上',
        2 => '文字居左',
        3 => '文字居右',
    );
    public $animationTypeKey = array(
        1 => '文字从下向上',
    );
    public $fontColorTypeKey = array(
        0 => '黑色',
        1 => '白色',
    );

    protected function beforeSave()
    {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->_delete = 0;
                $this->_update_time = date('y-m-d H:m:s');
                $this->_create_time = date('y-m-d H:m:s');
            } else {
                $this->_update_time = date('y-m-d H:m:s');
            }
            return true;
        } else {
            return false;
        }
    }

    public function defaultScope()
    {
        $alias = $this->getTableAlias(false, false);
        return array(
            'condition' => "{$alias}._delete=0",
            'order' => "{$alias}.sort asc",
        );
    }

    public function createRecord($info)
    {
        $model = new self();
        foreach ($info as $k => $v) {
            $model->$k = $v;
        }
        $model->save();
        $id = $model->primaryKey;
        return $id;
    }

    public function updateRecord($info)
    {
        foreach ($info as $k => $v) {
            $this->$k = $v;
        }
        $this->save();
        return $this;
    }

    public function saveRecord($info)
    {
        foreach ($info as $k => $v) {
            $this->$k = $v;
        }
        $this->save();
        return $this;
    }

    public function create_or_update_record($info)
    {
        if (isset($info['id'])) {
            $id = $info['id'];
            unset($info['id']);
        }
        if (!empty($id)) {
            $record = $this->findByPk($id);
            if (empty($record)) {
                return false;
            }
            $record->updateRecord($info);
            return $record->id;
        } else {
            $addid = $this->createRecord($info);
            return $addid;
        }
    }

    public function deleteRecordByPK00($id)
    {
        $record = $this->findByPk($id);
        if (!empty($record)) {
            $transaction = self::model()->dbConnection->beginTransaction();
            try {
                $record->_delete = true;
                $record->save();
                $son_template = self::model()->find_all_by_parent_id($id);
                foreach ($son_template as $v) {
                    self::model()->deleteRecordByPK($v->id);
                }
                $transaction->commit();
                return true;
            } catch (\Exception $e) {
                $transaction->rollBack();
                return false;
            }

            return $record;
        }
        return false;
    }


    public function deleteRecordByPK($id)
    {
        $record = $this->findByPk($id);
        if (!empty($record)) {
            $record->_delete = true;
            $record->save();
            return $record;
        }
        return false;
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return '{{template}}';
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
            'id' => 'ID',
            'column_type' => '源类型,1:向导页,2:线上培训',
            'column_id' => '源ID',
            'parent_id' => '父片段ID',
            'show_type' => '图文类型;1:文字居上;2:文字居左;3:文字居右',
            'template_type' => '模板类型;1:图文;2:视频;3:banner',
            'animation_type' => '动效类型;1:文字从下向上',
            'title' => '标题',
            'sub_title' => '子标题',
            'desc' => '描述',
            'font_color' => '字体颜色,0:黑色,1:白色',
            'background_pic' => '背景图',
            'video_url' => '视频地址',
            'sort' => '排序',
            'status' => '状态',
            '_delete' => '是否删除,0:未删除,1:已删除',
            '_create_time' => 'Create Time',
            '_update_time' => 'Update Time',
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
     * @return Template the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    //根据parent_id查询子模板,delete已过滤
    public function find_all_by_parent_id($parent_id)
    {
        $criteria = new \CDbCriteria;
        $criteria->compare('parent_id', $parent_id);
        return self::model()->findAll($criteria);
    }

    //根据源类型及源ID查询对应模板信息,delete已过滤
    public function find_template_by_column($column_type, $column_id, $index = false)
    {
        $criteria = new \CDbCriteria;
        $criteria->compare('column_type', $column_type);
        $criteria->compare('column_id', $column_id);
        $criteria->compare('template_type',array(1,2));
        if ($index)
            $criteria->index = $index;
        return self::model()->findAll($criteria);
    }

    public function find_banner_template($column_type, $column_id, $index = false)
    {
        $criteria = new \CDbCriteria;
        $criteria->compare('column_type', $column_type);
        $criteria->compare('column_id', $column_id);
        $criteria->compare('template_type', 3);
        if ($index)
            $criteria->index = $index;
        return self::model()->find($criteria);
    }
}
