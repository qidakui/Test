<?php
use application\models\Template\Template;

class TemplateController extends Controller
{

    private $msg = array(
        'Y' => '成功',
        1 => '操作数据库错误',
        2001 => '模板不能为空',
        2002 => '模板源类型及源ID不能为空',
        2003 => '背景图不能为空',
        2004 => '至少需要一张banner图'
    );

    public function actionTest()
    {
        $column_type = Yii::app()->request->getParam('column_type');
        $column_id = Yii::app()->request->getParam('column_id');
        $callback = Yii::app()->request->getParam('callback');
        //调用详情模板
        $this->render('test', array('column_type' => $column_type, 'column_id' => $column_id, 'callback' => $callback));exit;

    }

    public function actionTest_banner(){
        $column_type = Yii::app()->request->getParam('column_type');
        $column_id = Yii::app()->request->getParam('column_id');
        $callback = Yii::app()->request->getParam('callback');
         $this->render('banner_test', array('column_type' => $column_type, 'column_id' => $column_id, 'callback' => $callback));
    }

    public function actionSave_template()
    {
        try {
            $msgNo = 'Y';
            $ids = Yii::app()->request->getParam('id');
            $column_type = Yii::app()->request->getParam('column_type');
            $column_id = Yii::app()->request->getParam('column_id');
            $show_type = Yii::app()->request->getParam('show_type');
            $titles = Yii::app()->request->getParam('title');
            $animation_types = Yii::app()->request->getParam('animation_type');
            $font_colors = Yii::app()->request->getParam('font_color');
            $sub_titles = Yii::app()->request->getParam('sub_title');
            $descs = Yii::app()->request->getParam('desc');
            $background_pics = Yii::app()->request->getParam('background_pic');
            $background_urls = Yii::app()->request->getParam('background_url');
            $video_info = Yii::app()->request->getParam('video_info');
            $is_son = Yii::app()->request->getParam('is_son');
            if (empty($ids)) {
                throw new Exception(2001);
            }
            if (empty($column_type) or empty($column_id))
                throw new Exception(2002);

            //删除失效模板片段
            $sections = Template::model()->find_template_by_column($column_type, $column_id, 'id');
            $delete_ids = array_diff(array_keys($sections), $ids);
            foreach ($delete_ids as $delete_id) {
                if (!empty($delete_id))
                    Template::model()->deleteRecordByPK($delete_id);
            }
            //新增及修改模板片段
            $parent_id = 0;
            foreach ($ids as $index => $id) {
                //判断是否为父片段,如果是父片段则parent_id为0,否则parent_id不变
                $parent_id = empty($is_son[$index]) ? 0 : $parent_id;
                $info = array('id' => $ids[$index], 'column_type' => $column_type, 'column_id' => $column_id,
                    'template_type' => 1, 'animation_type' => $animation_types[$index],
                    'show_type' => $show_type[$index], 'title' => $titles[$index], 'sub_title' => $sub_titles[$index],
                    'desc' => $descs[$index], 'font_color' => $font_colors[$index], 'parent_id' => $parent_id,
                    'background_pic' => $background_pics[$index], 'background_url' => $background_urls[$index],
                    'sort' => $index, 'video_info' => $video_info[$index]);
                $result = Template::model()->create_or_update_record($info);
                if (!$result) {
                    throw new Exception(1);
                } else {
                    //如果当前片段为父片段,则标记parent_id为当前片段ID,下一个片段如果为子片段则parent_id使用当前片段ID,
                    //在下一次循环开始处判断如果为子片段则parent_id使用当前片段ID,否则parent_id只为0
                    if (empty($is_son[$index]))
                        $parent_id = $result;
                }
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);

    }

    public function actionSave_banner_template()
    {
        try {
            $msgNo = 'Y';
            $id = Yii::app()->request->getParam('id');
            $column_type = Yii::app()->request->getParam('column_type');
            $column_id = Yii::app()->request->getParam('column_id');
            $background_pics = Yii::app()->request->getParam('background_pic');
            $background_urls = Yii::app()->request->getParam('background_url');
            if(empty(array_filter($background_pics)))
                throw new Exception(2004);
            if (empty($column_type) or empty($column_id))
                throw new Exception(2002);
            if (empty($background_pics))
                throw new Exception(2003);
            $info = array('id' => $id, 'column_type' => $column_type, 'column_id' => $column_id,
                'template_type' => 3, 'parent_id' => 0, 'background_pic' => serialize($background_pics),
                'background_url' => serialize($background_urls), 'sort' => 0);
            $result = Template::model()->create_or_update_record($info);
            if (!$result) {
                throw new Exception(1);
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

    //异步上传图片
    public function actionSave_pic()
    {
        if (isset($_FILES['pic'])) {
            $upload = new Upload();
            $flag = $upload->uploadFile('pic');
            if (empty($upload->getErrorMsg())) {
                echo CJSON::encode(array('base_url' => UPLOADURL, 'pic_url' => $flag, 'status' => 'Y'));
                exit;
            } else {
                echo $this->encode('N', $upload->getErrorMsg());
                exit;
            }
        }
    }

}




     
        