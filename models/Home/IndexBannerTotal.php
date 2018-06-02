<?php

/**
 * This is the model class for table "{{index_banner_total}}".
 *
 * The followings are the available columns in table '{{index_banner_total}}':
 * @property string $id
 * @property string $banner_id
 * @property string $branch_id
 * @property string $_create_time
 * @property string $_update_time
 */

namespace application\models\Home;

class IndexBannerTotal extends \CActiveRecord {

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{index_banner_total}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
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
    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return IndexBannerTotal the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    protected function beforeSave() {
        if (parent::beforeSave()) {
            if ($this->isNewRecord) {
                $this->_delete = 0;
                $this->_update_time = date('Y-m-d H:i:s');
                $this->_create_time = date('Y-m-d H:i:s');
            } else {
                $this->_update_time = date('Y-m-d H:i:s');
            }
            return true;
        } else {
            return false;
        }
    }

    public function createRecord($info) {
        $model = new self();
        foreach ($info as $k => $v) {
            $model->$k = $v;
        }
        $model->save();
        return $model;
    }

    public function updateRecord($info) {
        foreach ($info as $k => $v) {
            $this->$k = $v;
        }
        $this->save();
        return $this;
    }

    //查询单条
    public function getOne($con = array(), $nums = null) {
        $criteria = new \CDbCriteria;
        if (!empty($con)) {
            foreach ($con as $key => $val) {
                $criteria->compare($key, $val);
            }
        }
        $ret = !empty($nums) ? self::model()->findAll($criteria) : self::model()->find($criteria);
        return $ret;
    }

    /**
     * 添加数据
     */
    public function addBanner($banner_id, $branch_id) {
        $branch_array = iunserializer($branch_id);
        if (!empty($branch_array)) {
            foreach ($branch_array as $key => $item) {
                $addinfo['banner_id'] = $banner_id;
                $addinfo['branch_id'] = $item;
                $this->createRecord($addinfo);
            }
        }
        return true;
    }

    public function verifyInfo($branchList, $diff = null, $banner_id = null) {
        $temp = false;
        $branch_array = iunserializer($branchList);
        $check = $this->getOne(array('branch_id' => $diff, '_delete' => 0, 'banner_id' => $banner_id), 'all');
        if (!empty($check)) {
            $temp = false;
        } else {
            if (!empty($branch_array)) {
                foreach ($branch_array as $key => $item) {
                    $countTotal = $this->count('branch_id=:branch_id and _delete=:_delete', array('branch_id' => $item, '_delete' => 0));
                    if ($countTotal >= 3) {
                        $temp = true;
                        break;
                    }
                }
            }
        }
        return $temp;
    }

    /**
     * 处理地区结果集
     */
    public function disposeInfo($data) {
        if (!empty($data)) {
            foreach ($data as $key => $item) {
                $filiale_id = $item['filiale_id'] != QG_BRANCH_ID ? substr($item['filiale_id'], 0, 2) : $item['filiale_id'];
                $countTotal = $this->neatenData($this->getOne(array('branch_id' => $filiale_id, '_delete' => 0), 'all'));
                $data[$key]['total'] = !empty($countTotal) ? $countTotal['total'] : 0;
                $data[$key]['banner_id'] = !empty($countTotal) ? $countTotal['pitch'] : array();
            }
        }
        return !empty($data) ? $data : array();
    }

    /**
     * 整理结果集
     */
    public function neatenData($data) {
        $newarray = array();
        foreach ($data as $key => $item) {
            $bannerId[] = $item['banner_id'];
            $newarray['pitch'] = $bannerId;
            $newarray['total'] = count($bannerId);
        }
        return $newarray;
    }

    /**
     * 修改结果集
     */
    public function editBanner($banner_id, $branch_id) {
        $temp = false;
        $branch_array = iunserializer($branch_id);
        if (!empty($branch_array)) {
            $editRs = $this->deleteAll("banner_id=:banner_id", array(":banner_id" => $banner_id));
            if($editRs){
                foreach ($branch_array as $key => $item) {
                   $addinfo['banner_id'] = $banner_id;
                   $addinfo['branch_id'] = $item;
                   $this->createRecord($addinfo);
               }               
            }
            $temp = true;
        }
        return $temp === true ? true : false;
    }

    /**
     * 下架修改信息
     */
    public function updateByInfo($banner_id, $editarray) {
        $Res = false;
        $findInfo = $this->getOne(array('banner_id' => $banner_id, '_delete' => 1), 'all');
        if (!empty($findInfo)) {
            if ($editarray['status'] == 1) {
                foreach ($findInfo as $key => $item) {
                    $newarray[] = $item['branch_id'];
                }
                $delInfo = $this->deleteAll("banner_id=:banner_id", array(":banner_id" => $banner_id));
                if ($delInfo) {
                    $this->a_array_unique($newarray);
                    foreach ($newarray as $key => $item) {
                        $addinfo['banner_id'] = $banner_id;
                        $addinfo['branch_id'] = $item;
                        $flag[] = $this->createRecord($addinfo);
                    }
                }
            }
        }
        if ($editarray['status'] == 2) {
            $Res = $this->updateAll(array('_delete' => 1), 'banner_id=:banner_id', array(':banner_id' => $banner_id));
        }
        return !empty($flag) ? true : $Res;
    }

    /**
     * 获取上架总数
     */
    public function getInfo($banner_id) {
        $newarray = array();
        $res = $this->getOne(array('banner_id' => $banner_id), 'all');
        if (!empty($res)) {
            foreach ($res as $key => $item) {
                $newarray[] = $item['branch_id'];
            }
        }
        return $newarray;
    }

    /**
     * 获取数量
     */
    public function getConunt($branch_array) {
        $temp = false;
        if (!empty($branch_array)) {
            foreach ($branch_array as $key => $item) {
                $countTotal = $this->count('branch_id=:branch_id and _delete=:_delete', array('branch_id' => $item, '_delete' => 0));
                if ($countTotal >= 3) {
                    $temp = true;
                    break;
                }
            }
        }
        return $temp;
    }

    /**
     * 去除数组重复项(hd)
     */
    function a_array_unique($array) {//写的比较好  如果保留原来的键循环加key 不保留就不用
        $out = array();
        foreach ($array as $key => $value) {
            if (!in_array($value, $out)) {
                $out[] = $value;
            }
        }
        return $out;
    }

}
