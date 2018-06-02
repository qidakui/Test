<?php

use application\models\BranchContacts;

class ContactController extends Controller {

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2 => '标题不能为空',
        3 => '英文标题不能为空',
        4 => '地址不能为空',
        5 => '英文地址不能为空',
        6 => '邮编不能为空',
        7 => '电话不能为空',
        8 => '邮箱不能为空',
        9 => '坐标不能为空',
    );

    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex() {
        $branch_id = Yii::app()->user->branch_id;
        $contact = BranchContacts::model()->find_by_branch_id($branch_id);
        if(empty($contact))
            $contact = new BranchContacts();
        $this->render('contact_form', array('contact' => $contact));
    }

    public function actionSave_contact(){
        $msgNo = 'Y';
        try{
            $contact_info = array();
            $branch_id = Yii::app()->user->branch_id;
            $contact = BranchContacts::model()->find_by_branch_id($branch_id);
            if(!empty($contact)){
                $contact_info['id'] = $contact->id;
            }
            $contact_info['branch_id'] = $branch_id;
            $contact_info['title'] = $this->getParam('title',2);
            $contact_info['e_title'] = $this->getParam('e_title',3);
            $contact_info['address'] = $this->getParam('address',4);
            $contact_info['e_address'] = $this->getParam('e_address',5);
            $contact_info['zip_code'] = $this->getParam('zip_code',6);
            $contact_info['telephone'] = $this->getParam('telephone',7);
            $contact_info['facsimile'] = $this->getParam('facsimile');
            $contact_info['email'] = $this->getParam('email',8);
            $contact_info['map_point'] = $this->getParam('map_point',9);
            $contact_info['branch_service_phone'] = $this->getParam('branch_service_phone');
            $contact_info['status'] = 0;
            BranchContacts::model()->create_or_update_record($contact_info);
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    private function getParam($name,$msgNo=''){
        $res = trim(Yii::app()->request->getParam($name));
        if(!empty($msgNo) && empty($res)){
            throw new Exception($msgNo);
        }
        return $res;
    }

}
