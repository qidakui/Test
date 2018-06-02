<?php
/**
 * 首页导航管理
 * @author hd
 */
use application\models\Menu\Menu;
use application\models\Menu\MenuFiliale;
use application\models\ServiceRegion;
class MenuController extends Controller{
    
    private $msg = array(
        'Y' => '操作成功',
         1 => '数据库操作错误',
         2 => '导航名称不能为空',
         3 => '导航链接不能为空',
         4 => '该名称已存在',
         5 => '导航数量已上限，请查看绑定数量.',
         6 => '开通地区不能为空',
    ); 
    public $status_is = array(
        0 => '显示',
        1 => '隐藏'
    );
    public $Menu_target = array(
        0 => '否',
        1 => '是'
    );
    public function actionindex(){   
        $this->render('index');
    }
    public function actionmenu_list(){
        $limit   	= trim(Yii::app()->request->getParam( 'iDisplayLength' ));
        $page   	= trim(Yii::app()->request->getParam( 'iDisplayStart' ));
        $index   	= trim(Yii::app()->request->getParam( 'iSortCol_0' ));
        $ord            = trim(Yii::app()->request->getParam( 'sSortDir_0' ));
        $field          = trim(Yii::app()->request->getParam( 'mDataProp_'. $index )); //排序字段
        $ord            = !empty($ord) ? $ord : 'asc';
        $field          = !empty($field) ? $field : 'sort_order ,id';
        $page		= !empty($page) ? $page : 0;
        $limit		= !empty($limit) ? $limit : 20;
        $con['_delete'] = 0;
        $list = Menu::model()->ger_Menu_list($con, $ord, $field, $limit, $page);
        echo CJSON::encode($list);        
    }
    public function actionadd(){
        if($_SERVER['REQUEST_METHOD']=='POST'){
            try {
                $menu_name = trim(Yii::app()->request->getParam( 'menu_name' ));
                $menu_link = trim(Yii::app()->request->getParam( 'menu_link' ));
                $status_is = trim(Yii::app()->request->getParam( 'status_is' ));
                $sort_order = trim(Yii::app()->request->getParam( 'sort_order' ));
                $status_is = trim(Yii::app()->request->getParam( 'status_is' ));
                $unique = trim(Yii::app()->request->getParam( 'unique' ));
                $memu_id = trim(Yii::app()->request->getParam( 'memu_id' ));
                $open_area = Yii::app()->request->getParam('open_area');
                //非空检查
                if(empty($menu_name))
                   throw new Exception('2');
                if(empty($menu_link))
                   throw new Exception('3');
                if(empty($open_area))
                    throw new Exception('6');
                $_POST['_create_time'] = date('Y-m-d H:i:s');
                $_POST['_update_time'] = date('Y-m-d H:i:s');
                if(!empty($memu_id)){
                    $loldata = Menu::model()->findByPk($memu_id);
                    $editmenu = $loldata->editsave($_POST); 
                    if($editmenu){
                        $delMenuFiliale = MenuFiliale::model()->deleteAll('menu_id=:menu_id',array(':menu_id'=>$memu_id));
                        $savefiliale = MenuFiliale::model()->saveFiliale($memu_id,$open_area);
                        OperationLog::addLog(OperationLog::$operationIndexMenu , 'edit', '导航管理', $editmenu, $loldata->attributes, $_POST); 
                        $this->refreshRedis();
                        $msgNo = 'Y';
                    }    
                }else{
                    $countMun = Menu::model()->getMenuCeiling($open_area);
                    if($countMun===false)
                        throw new Exception('5');               
                    $checkres = Menu::model()->find('menu_name=:menu_name and _delete=:_delete',array(':menu_name'=>$menu_name,':_delete'=>0));
                    if(!empty($checkres))
                         throw new Exception('4'); 
                    $savemenu = Menu::model()->menuSave($_POST);                   
                    if($savemenu){
                        $savefiliale = MenuFiliale::model()->saveFiliale($savemenu,$open_area);
                        OperationLog::addLog(OperationLog::$operationIndexMenu , 'add', '导航管理', $savemenu, array(), $_POST);
                        $this->refreshRedis();
                        $msgNo = 'Y';
                    }                   
                }
            } catch (Exception $ex) {
                $msgNo = $ex->getMessage();
            }
             $msg = $this->msg[$msgNo];
              echo $this->encode($msgNo, $msg);
        }else{ 
            $open_area = array();
            $memu_id= trim(Yii::app()->request->getParam('memu_id'));
            $menufiliale = MenuFiliale::model()->findAll('menu_id=:menu_id',array('menu_id'=>$memu_id));
            if(!empty($menufiliale)){
                foreach ($menufiliale as $key=>$row){
                     $open_area []= $row->filiale_id;
                }
            }
            $province_list = ServiceRegion::model()->getProvinceList();
            $editinfo = Menu::model()->findByPk($memu_id);
            $this->render('add',array('data'=>$editinfo,'memu_id'=>$memu_id,'province_list' => $province_list,'open_area'=>$open_area));  
        }
    }
    public function actiondel_menu(){
        try {
          $memu_id = trim(Yii::app()->request->getParam( 'menu_id' ));
          if(empty($memu_id))
            throw new Exception('1');
            $model = Menu::model()->findbypk($memu_id);
            $model->_delete = 1;
            $flag = $model->save();
            if ($flag) {
                $delMenuFiliale = MenuFiliale::model()->deleteAll('menu_id=:menu_id',array(':menu_id'=>$memu_id));
                $msgNo = 'Y';
                $this->refreshRedis();
                OperationLog::addLog(OperationLog::$operationIndexMenu, 'del', '导航管理', $memu_id, array(), array());
            } else {
                throw new Exception('1');
            }            
        } catch (Exception $ex) {
            $msgNo = $ex->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    public function refreshRedis(){
        $newarray = array();
        $openArea = ServiceRegion::model()->getProvinceList();
        foreach ($openArea as $key=>$item){
            $newarray[] = $item->filiale_id!=QG_BRANCH_ID?substr($item->filiale_id, 0, 2):$item->filiale_id;
        }
        foreach ($newarray as $key=>$row){         
            Yii::app()->redis->getClient()->delete('fwxgx_'.'global_menus_'.$row.  md5('global_menus_'.$row));
        }
        return true;
    }
}
