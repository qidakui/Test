<?php
/**
 * Created by PhpStorm.
 * User: wenlh
 * Date: 2016/8/15
 */

use application\models\Shop\Category;
use application\models\ServiceRegion;
use application\models\Shop\Goods;
use application\models\Shop\GoodsDescribe;
use application\models\Shop\GoodsInfo;
use application\models\Shop\GoodsPic;
use application\models\Shop\GoodsSaleArea;
use application\models\Shop\GoodsAttr;
use application\models\Shop\GoodsSpec;
use application\models\Shop\GoodsRecommend;
use application\models\Shop\OrderInfo;
use application\models\Shop\OrderGoods;
class ShopController extends Controller
{

    private $msg = array(
        'Y' => '操作成功',
        1 => '数据库操作错误',
        2 => '分类名称不能为空',
        3 => '分类ID不能为空',
        4 => '分类不存在',
        5 => '该分类下存在商品,不能删除分类',
        6 => '商品名称不能为空',
        7 => '商品描述不能为空',
        8 => '商品分类不能为空',
        9 => '商品可售卖地区不能为空',
        10 => '商品不存在',
        11 => '商品属性不能为空',
        12 => '商品规格不能为空',
        13 => '数据错误',
        14 => '状态不能为空',
        15 => '图片处理错误',
        16 => '商品属性类型不能为空',
        17 => '属性图片为空',
        18 => '推荐位置不能为空',
        19 => '推荐不存在',
        20 => '推荐名称不能为空',
        21 => '推荐链接不能为空',
        22 => '推荐背景色不能为空',
        23 => '推荐图片不能为空',
        24 => '商品不存在或已下架',
        25 => '订单ID不能为空',
        26 => '订单不存在',
        27 => '快递单号不能为空',
        28 => '订单未支付',
        29 => '收件人所在省不能为空',
        30 => '收件人所在城市不能为空',
        31 => '收件人详细地址不能为空',
        32 => '收件人姓名不能为空',
        33 => '收件人手机号不能为空',
        34 => '商品列表页图片不能为空',
        35 => '快递公司不能为空',
        36 => '初始化浏览量不能为空',
        37 => '该分类下存在子分类,不能删除',
    );

    //分类展示列表
    public function actionCategory(){
        $category_info = Category::model()->getAllCategory();
        $this->render('category',array('category_info' => $category_info));
    }

    //新建一级分类、新建子分类、修改分类，id为空时新增,否则为修改
    public function actionCreate_or_edit_category(){
        $msgNo = 'Y';
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            $parent_id = trim(Yii::app()->request->getParam('parent_id'));
            $category_name = trim(Yii::app()->request->getParam('category_name'));
            if(empty($category_name))
                throw new Exception('2');
            if(empty($id)){
                Category::model()->createCategory(array('parent_id' => $parent_id, 'category_name' => $category_name));
            }else{
                $category = Category::model()->findByPk($id);
                if(empty($category)){
                    throw new Exception('4');
                }else{
                    $category->updateCategory(array('parent_id' => $parent_id, 'category_name' => $category_name));
                }
            }
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionDel_category(){
        $msgNo = 'Y';
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            if(empty($id))
                throw new Exception('3');
            $category = Category::model()->with('goods_num', 'child_category_count')->findByPk($id);
            if(empty($category))
                throw new Exception('4');
            if(!empty($category->goods_num))
                throw new Exception('5');
            if(!empty($category->child_category_count))
                throw new Exception('37');
            $category->logical_delete();
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }
    /**
     * 商品基本信息维护页面
     * by:wenlh
     */
    public function actionGoods_basic_info(){
        $id = trim(Yii::app()->request->getParam('goods_id'));
        $sale_area = array();
        $first_category = 0;
        $goods = new Goods();
        if(!empty($id)){
            $goods = Goods::model()->findByPk($id);
            if(empty($goods)){
                echo $this->encode(10, $this->msg[10]);exit;
            }
            foreach($goods->sale_area as $area){
                $sale_area []= $area->area_id;
            }
            $first_category = Category::model()->getParentCategory(intval($goods->category_id))->id;
        }
        $categorys = Category::model()->getAllCategory();
        $province_list = ServiceRegion::model()->getCityList();
        $this->render('goods_basic_info', array('categorys' => $categorys, 'province_list' => $province_list, 'goods' => $goods,
            'sale_area' => $sale_area, 'first_category' => $first_category));
    }

    /**
     * 新增商品或编辑商品基本信息提交的方法
     * by:wenlh
     */
    public function actionCreate_or_update_basic_info(){
        $msgNo = 'Y';
        try{
            $id = trim(Yii::app()->request->getParam('id'));
            $goods_name = trim(Yii::app()->request->getParam('goods_name'));
            $goods_brief = trim(Yii::app()->request->getParam('goods_brief'));
            $category_id = trim(Yii::app()->request->getParam('category_id'));
            $sale_type = trim(Yii::app()->request->getParam('goods_sale_type'));
            $sale_area_type = trim(Yii::app()->request->getParam('sale_area_type'));
            $sale_area = Yii::app()->request->getParam('sale_area');
            $old_goods_pic = Yii::app()->request->getParam('old_goods_pic');
            $init_click_count = Yii::app()->request->getParam('init_click_count');
            $goods_pic_url = '';
            //非空检查
            if(empty($goods_name))
                throw new Exception('6');
            if(empty($goods_brief))
                throw new Exception('7');
            if(empty($category_id))
                throw new Exception('8');
            if(empty($init_click_count) && $init_click_count != 0)
                throw new Exception('36');
            if($sale_area_type == 'select_area' && empty($sale_area))
                throw new Exception('9');
            //兼容IE增加判断!empty($_FILES['goods_pic']) && !empty($_FILES['goods_pic']['name'])
            if(isset($_FILES['goods_pic']) && !empty($_FILES['goods_pic']) && !empty($_FILES['goods_pic']['name'])) {
                $upload = new Upload();
                $flag = $upload->uploadFile('goods_pic');
                if (empty($upload->getErrorMsg())) {
                    $goods_pic_url = $flag;
                } else {
                    echo $this->encode('N', $upload->getErrorMsg());exit;
                }
            }else{
                if(!empty($old_goods_pic)){
                    $goods_pic_url = $old_goods_pic;
                }else{
                    throw new Exception('34');
                }
            }
            //$id为空则创建,否则为更新
            if(empty($id)){
                $goods = Goods::model()->createGoods(array('goods_name' => $goods_name, 'goods_brief' => $goods_brief,
                    'category_id' => $category_id, 'sale_type' => $sale_type, 'goods_pic' => $goods_pic_url, 'init_click_count' => $init_click_count));
            }else{
                $goods = Goods::model()->findByPk($id);
                if(empty($goods))
                    throw new Exception('10');
                $goods->updateGoods(array('goods_name' => $goods_name, 'goods_brief' => $goods_brief,
                    'category_id' => $category_id, 'sale_type' => $sale_type, 'goods_pic' => $goods_pic_url, 'init_click_count' => $init_click_count));
            }

            //如果不是手动选择地区,则数据库中存储QG_BRANCH_ID
            $sale_area = ($sale_area_type == 'select_area') ? $sale_area : array(QG_BRANCH_ID);

            //获取所有售卖地区的旧数据
            $old_sale_area = array();
            foreach($goods->sale_area as $area){
                $old_sale_area []= $area->area_id;
            }
            $del_area = array_diff($old_sale_area,$sale_area);
            $new_area = array_diff($sale_area,$old_sale_area);

            //新数据进行存储,选择地区不涉及更新
            foreach($new_area as $area){
                GoodsSaleArea::model()->createGoodsSaleArea(array('goods_id' => $goods->id,'area_id' => $area));
            }

            //删除取消售卖的地区
            foreach($del_area as $area){
                $area = GoodsSaleArea::model()->getSaleArea(array('goods_id' => $goods->id,'area_id' => $area));
                $area->deleteGoodsSaleArea();
            }

            //返回goods_id,后续操作更新到对应商品上
            echo CJSON::encode(array('status' => $msgNo, 'goods_id' => $goods->id));exit;
        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);

    }

    public function actionGoods_spec_attr_info(){
        $goods_id = Yii::app()->request->getParam('goods_id');
        //非空检查
        if(empty($goods_id))
            throw new Exception('10');
        $goods = Goods::model()->with('goods_attr_and_pic')->findByPk($goods_id);
        if(empty($goods))
            throw new Exception('10');
        $this->render('goods_spec_attr_info', array('goods' => $goods));
    }

    public function actionCreate_or_update_spec_attr_info(){
        $msgNo = 'Y';
        try{
            $sepcs = Yii::app()->request->getParam('spec');
            $attrs = Yii::app()->request->getParam('attr');
            $goods_id = Yii::app()->request->getParam('goods_id');
            $goods_attr_type = Yii::app()->request->getParam('goods_attr_type');

            //非空判断
            if(empty($goods_id))
                throw new Exception('10');
            $goods = Goods::model()->with('goods_attr_and_pic')->findByPk($goods_id);
            if(empty($goods))
                throw new Exception('10');
            if(empty($sepcs))
                throw new Exception('12');
            if(empty($attrs))
                throw new Exception('11');
            if(empty($goods_attr_type))
                throw new Exception('16');

            //检查商品属性对应图片是否为空
            foreach($attrs as $attr){
                if($attr['image'] == array('','','','')){
                    echo $this->encode(17, $attr['name'] . $this->msg[17]);exit;
                }
            }
            //更新商品属性类型
            $goods->updateGoods(array('goods_attr_type' => $goods_attr_type));

            //获取旧的规格型号列表,后续判断是否有需要进行删除操作时使用
            $old_specs = array();
            foreach($goods->goods_spec as $spec){
                $old_specs[$spec->id] = $spec;
            }
            //创建或更新规格型号
            foreach($sepcs as $spec){
                if(empty($spec['id'])){
                    GoodsSpec::model()->createGoodsSpec(array('goods_id' => $goods_id, 'goods_spec_name' => $spec['name'],'goods_price' => $spec['price']*100));
                }else{
                    $old_specs[$spec['id']]->updateGoodsSpec(array('goods_id' => $goods_id, 'goods_spec_name' => $spec['name'],'goods_price' => $spec['price']*100));
                    unset($old_specs[$spec['id']]);
                }
            }
            //删除规格型号
            foreach($old_specs as $old_spec){
                $old_spec->deleteGoodsSpec();
                //删除规格型号同时删除对应库存表
                foreach($old_spec->goods_info as $info){
                    $info->deleteGoodsInfo();
                }
            }

            //获取旧的属性列表,后续判断是否有需要进行删除操作时使用
            $old_attrs = array();
            foreach($goods->goods_attr as $attr){
                $old_attrs[$attr->id] = $attr;
            }

            //创建或更新属性
            foreach($attrs as $attr){
                if(empty($attr['id'])){
                    $attr_record = GoodsAttr::model()->createGoodsAttr(array('goods_id' => $goods_id, 'goods_attr_name' => $attr['name']));
                }else{
                    $attr_record = $old_attrs[$attr['id']]->updateGoodsAttr(array('goods_id' => $goods_id, 'goods_attr_name' => $attr['name']));
                    unset($old_attrs[$attr['id']]);
                }
                //获取属性对应旧图片
                $old_attr_pics = array();
                foreach($attr_record->goods_pic as $pic){
                    $old_attr_pics[$pic->id] = $pic;
                }

                //更新图片的goods_attr_id属性
                foreach($attr['image'] as $img){
                    //如果图片ID在旧图片中不存在,更新图片goods_attr_id属性,否则在$old_attr_pics中删除对应图片
                    if(!empty($img)){
                        if(!isset($old_attr_pics[$img])){
                            GoodsPic::model()->findByPk($img)->updateGoodsPic(array('goods_attr_id' => $attr_record->id));
                        }else{
                            unset($old_attr_pics[$img]);
                        }
                    }
                }

                //删除无效旧图片
                foreach($old_attr_pics as $old_pic){
                    $old_pic->deleteGoodsPic();
                }
            }

            //删除属性
            foreach($old_attrs as $old_attr){
                $old_attr->deleteGoodsAttr();
                //删除属性同时删除对应图片
                foreach($old_attr->goods_pic as $del_pic){
                    $del_pic->deleteGoodsPic();
                }
                //删除属性同时删除对应库存表
                foreach($old_attr->goods_info as $info){
                    $info->deleteGoodsInfo();
                }
            }

            //获取新创建的全部规格ID和属性ID,并组合成数组
            $spec_attr_info = array();
            $new_spec_ids = array_keys(GoodsSpec::model()->getSpecByGoodsId($goods_id));
            $new_attr_ids = array_keys(GoodsAttr::model()->getAttrByGoodsId($goods_id));
            foreach($new_spec_ids as $spec){
                foreach($new_attr_ids as $attr) {
                    $spec_attr_info[$spec . '_' . $attr] = array('spec_id' => $spec, 'attr_id' => $attr);
                }
            }

            //获取goods_id为指定值的goods_infos,遍历goods_infos从$spec_attr_info中删除对应的数据,此处不涉及更新库存,只有新增操作
            $goods_infos = GoodsInfo::model()->getInfoByGoodsId($goods_id);
            foreach($goods_infos as $info){
                unset($spec_attr_info[$info->goods_spec_id . '_' . $info->goods_attr_id]);
            }
            foreach($spec_attr_info as $info){
                GoodsInfo::model()->createGoodsInfo(array('goods_id' => $goods_id,'goods_spec_id' => $info['spec_id'],
                    'goods_attr_id' => $info['attr_id'], 'goods_num' => 0));
            }

        } catch(Exception $e){
            $msgNo = $e->getMessage();
        }

        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    //保存属性图片,未做大中小图处理
    public function actionSave_attr_pic(){
        if (isset($_FILES['arrt_pic'])) {
            $upload = new Upload();
            $flag = $upload->uploadFile('arrt_pic');
            if (empty($upload->getErrorMsg())) {
                //保存数据库及返回前台结果
                $base_path = $upload->getBase();
                $path_info = pathinfo($flag);
                $medium_img_path = $path_info['dirname'] . '/' . $path_info['filename'] . '_m.' .$path_info['extension'];
                $samll_img_path = $path_info['dirname'] . '/' . $path_info['filename'] . '_s.' .$path_info['extension'];
                $medium_img_flag = $this->_create_more_img($base_path . $flag, $base_path . $medium_img_path, 380, 380);
                $small_img_flag = $this->_create_more_img($base_path . $flag, $base_path . $samll_img_path, 81, 81);
                if($medium_img_flag['flag'] == 1 && $small_img_flag['flag']  == 1){
                    $pic = GoodsPic::model()->createGoodsPic(array('big_pic_url' => $flag, 'medium_pic_url' => $medium_img_path, 'small_pic_url' => $samll_img_path));
                    echo CJSON::encode(array('pic_id' => $pic->id, 'pic_url' => UPLOADURL . $pic->small_pic_url, 'status' => 'Y'));exit;
                }else{
                    echo $this->encode(15, $this->msg[15]);
                }
            } else {
                echo $this->encode('N', $upload->getErrorMsg());exit;
            }
        }
    }

    public function actionGoods_info(){
        $msgNo = 'Y';
        try{
            $goods_id = Yii::app()->request->getParam('goods_id');
            //非空检查
            if(empty($goods_id))
                throw new Exception('10');
            $goods = Goods::model()->with('goods_info_with_name')->findByPk($goods_id);
            if(empty($goods))
                throw new Exception('10');
            $this->render('goods_info',array('goods' => $goods));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionEdit_goods_info(){
        $msgNo = 'Y';
        try{
            $goods_id = Yii::app()->request->getParam('goods_id');
            $infos = Yii::app()->request->getParam('info');
            if(empty($goods_id))
                throw new Exception('10');
            if(empty($infos)){
                throw new Exception('13');
            }
            $old_infos = GoodsInfo::model()->getInfoByGoodsId($goods_id);
            foreach($infos as $info){
                if(isset($old_infos[$info['id']]) && $old_infos[$info['id']]->goods_num != $info['goods_num']){
                    $old_infos[$info['id']]->updateGoodsInfo(array('goods_num' => $info['goods_num']));
                }
            }
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionGoods_params(){
        $msgNo = 'Y';
        try{
            $goods_id = Yii::app()->request->getParam('goods_id');
            //非空检查
            if(empty($goods_id))
                throw new Exception('10');
            $goods = Goods::model()->with('goods_describe')->findByPk($goods_id);
            if(empty($goods))
                throw new Exception('10');
            $this->render('goods_params', array('goods' =>$goods));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionCreate_or_edit_goods_params(){
        $msgNo = 'Y';
        try{
            $goods_id = Yii::app()->request->getParam('goods_id');
            $goods_params = Yii::app()->request->getParam('info');
            //非空检查
            if(empty($goods_id))
                throw new Exception('10');
            $goods = Goods::model()->with('goods_describe')->findByPk($goods_id);
            if(empty($goods))
                throw new Exception('10');
            $goods_describe = $goods->goods_describe;
            if(empty($goods_describe)){
                GoodsDescribe::model()->createGoodsDescribe(array('goods_id' => $goods_id, 'goods_params' => serialize($goods_params)));
            }else{
                $goods_describe->updateGoodsDescribe(array('goods_params' => serialize($goods_params)));
            }
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }


    public function actionGoods_describe(){
        $msgNo = 'Y';
        try{
            $goods_id = Yii::app()->request->getParam('goods_id');
            //非空检查
            if(empty($goods_id))
                throw new Exception('10');
            $goods = Goods::model()->with('goods_describe')->findByPk($goods_id);
            if(empty($goods))
                throw new Exception('10');
            $this->render('goods_describe', array('goods' =>$goods));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionCreate_or_edit_goods_describe(){
        $msgNo = 'Y';
        try{
            $goods_id = Yii::app()->request->getParam('goods_id');
            $describe = Yii::app()->request->getParam('goods_describe');
            $status = Yii::app()->request->getParam('status');
            //非空检查
            if(empty($status))
                throw new Exception('14');
            if(empty($goods_id))
                throw new Exception('10');
            $goods = Goods::model()->with('goods_describe')->findByPk($goods_id);
            if(empty($goods))
                throw new Exception('10');
            $goods_describe = $goods->goods_describe;
            $data = array();
            if(intval($status) == 3){
                $data['goods_preview_desc'] = \CHtml::encode($describe);
            }else{
                $data['goods_desc'] = \CHtml::encode($describe);
                $data['goods_preview_desc'] = \CHtml::encode($describe);
            }

            if(empty($goods_describe)){
                GoodsDescribe::model()->createGoodsDescribe(array_merge(array('goods_id' => $goods_id)),$data);
            }else{
                $goods_describe->updateGoodsDescribe($data);
            }
            if(intval($status) == 1){
                $goods->updateGoods(array('status' => $status,'begin_sale_time' => date('y-m-d H:m:s')));
            }else{
                $goods->updateGoods(array('status' => 2));
            }

        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionGoods_index(){
        $categorys = Category::model()->getAllCategory();
        $this->render('goods_index', array('categorys' => $categorys));
    }
    public function actionGet_goods_list(){
        $offset = Yii::app()->request->getParam('start');
        $limit = Yii::app()->request->getParam('length');
        $con = array();
        $first_category = Yii::app()->request->getParam('first_category');
        $second_category = Yii::app()->request->getParam('second_category');
        $goods_name = Yii::app()->request->getParam('goods_name');
        $export = Yii::app()->request->getParam('export');
        if(!empty($goods_name)){
            $con['goods_name'] = array('search_like', $goods_name);
        }
        if(!empty($first_category)){
            if(empty($second_category)){
                $child_category = array_keys(Category::model()->getChildCategory($first_category));
                $con['category_id'] = $child_category;
            }else{
                $con['category_id'] = $second_category;
            }
        }
        if($export == 'true'){
            $goods_list = Goods::model()->getList($con,'id desc');
            $header = array('商品ID','一级分类','二级分类','商品名称','上架时间','可售数量','价格','销售地区','销售方式','状态');
            $data = array();
            foreach ($goods_list['data'] as $goods) {
                $data[]= array($goods['id'], $goods['first_category_name'], $goods['second_category_name'],
                    $goods['goods_name'], $goods['begin_sale_time'], $goods['goods_num'], $goods['goods_price'],
                    implode('、',$goods['sale_area']), $goods['sale_type_name'], $goods['status_name']);
            }
            FwUtility::exportExcel($data, $header, '商品列表','商品列表'.date('Ymd'));
        }else{
            $goods_list = Goods::model()->getList($con,'id desc',$limit,$offset);
            echo CJSON::encode($goods_list);exit;
        }
    }

    public function actionUp_down_goods(){
        $msgNo = 'Y';
        try{
            //非空检查
            $goods_id = Yii::app()->request->getParam('goods_id');
            $status = Yii::app()->request->getParam('status');
            if(empty($goods_id))
                throw new Exception('10');
            if(empty($status))
                throw new Exception('14');
            $goods = Goods::model()->with('goods_describe')->findByPk($goods_id);
            if(empty($goods))
                throw new Exception('10');
            $goods_recommend = $goods->goods_recommend;
            if($status == 2 && !empty($goods_recommend)){
                $info = array();
                foreach ($goods_recommend as $v) {
                    $info[]= $v->positionKey[$v->position];
                }
                echo $this->encode(-1, '该商品已推荐到' . implode('、',array_unique($info)) . ',不能下架');exit;
            }
            $goods->updateGoods(array('status' => $status));
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionGoods_recommend_index(){
        $position = Yii::app()->request->getParam('position') ?: 1;
        $position_key = GoodsRecommend::model()->positionKey;
        $this->render('goods_recommend_index',array('position' => $position, 'position_key' => $position_key));
    }

    public function actionGet_recommend_list(){
        $msgNo = 'Y';
        try{
            $position = Yii::app()->request->getParam('position');
            $offset = Yii::app()->request->getParam('start');
            $limit = Yii::app()->request->getParam('length');
            if(empty($position))
                throw new Exception('18');
            $recommend_list = GoodsRecommend::model()->getList(array('position' => $position),'sort asc',$limit,$offset);
            if($position != 1 && $position != 2){
                $goods_ids = array();
                foreach($recommend_list['data'] as $v){
                    $goods_ids []= $v['goods_id'];
                }
                $goods_list = Goods::model()->getList(array('id' => $goods_ids),'id', -1, 0, 'id')['data'];
                $data = $recommend_list['data'];
                foreach($data as $k => $v){
                    $data[$k] = $goods_list[$v['goods_id']];
                    $data[$k]['recommend_id'] = $v['id'];
                    $data[$k]['name'] = $v['name'];
                    $data[$k]['recommend_status'] = $v['status'];
                }
                $recommend_list['data'] = $data;
            }
            echo CJSON::encode($recommend_list);exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionUp_down_recommend(){
        $msgNo = 'Y';
        try{
            $recommend_id = Yii::app()->request->getParam('recommend_id');
            $status = Yii::app()->request->getParam('status');
            if(empty($recommend_id))
                throw new Exception('19');
            $recommend = GoodsRecommend::model()->findByPk($recommend_id);
            if(empty($recommend))
                throw new Exception('19');
            if(empty($status))
                throw new Exception('14');
            $recommend->updateGoodsRecommend(array('status' => $status));
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionChange_sort_recommend(){
        $msgNo = 'Y';
        try{
            $operate_id = Yii::app()->request->getParam('operate_id');
            $replace_id = Yii::app()->request->getParam('replace_id');
            if(empty($operate_id) || empty($replace_id))
                throw new Exception('13');
            $operate = GoodsRecommend::model()->findByPk($operate_id);
            $replace = GoodsRecommend::model()->findByPk($replace_id);
            if(empty($operate) || empty($replace))
                throw new Exception('19');
            $sorts = array($operate->sort,$replace->sort);
            $operate->updateGoodsRecommend(array('sort' => $sorts[1]));
            $replace->updateGoodsRecommend(array('sort' => $sorts[0]));
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actiondelete_recommend(){
        $msgNo = 'Y';
        try{
            $recommend_id = Yii::app()->request->getParam('recommend_id');
            if(empty($recommend_id))
                throw new Exception('19');
            $recommend = GoodsRecommend::model()->findByPk($recommend_id);
            if(empty($recommend))
                throw new Exception('19');
            $recommend->deleteGoodsRecommend();
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionRecommend_form(){
        $msgNo = 'Y';
        try{
            $recommend_id = Yii::app()->request->getParam('recommend_id');
            $recommend = new GoodsRecommend();
            if(empty($recommend_id)){
                $position = Yii::app()->request->getParam('position');
                if(empty($position))
                    throw new Exception('18');
                $recommend->position = $position;
            }else{
                $recommend = GoodsRecommend::model()->findByPk($recommend_id);
                if(empty($recommend))
                    throw new Exception('19');
            }
            $this->render('recommend_form', array('recommend' => $recommend));exit;
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);

    }

    public function actionCreate_or_update_recommend(){
        $msgNo = 'Y';
        try{
            $name = Yii::app()->request->getParam('name');
            $position = Yii::app()->request->getParam('position');
            $link = Yii::app()->request->getParam('link');
            $colour_number = Yii::app()->request->getParam('colour_number');
            $goods_id = Yii::app()->request->getParam('goods_id');
            $recommend_id = Yii::app()->request->getParam('recommend_id');
            $pic_path = Yii::app()->request->getParam('pic_path');
            $info = array('name' => $name, 'link' => $link, 'position' => $position,
                'goods_id' => $goods_id, 'color_num' => $colour_number);
            //根据不同类型判断非空,名字和位置是所有类型通用的
            if(empty($name))
                throw new Exception('20');
            if(empty($position))
                throw new Exception('18');

            //如果是banner或特惠推荐需要检查链接和上传的图片
            if($position == 1 || $position == 2){
                if(empty($link))
                    throw new Exception('21');
                //如果是banner需要检查背景色
                if($position == 1){
                    if(empty($colour_number))
                        throw new Exception('22');
                }

                //如果没有上传图片,且没有历史图片则报错,存在历史图片则不修改,如果上传了图片则修改历史图片
                if (!isset($_FILES['recommend_pic'])){
                    if(empty($pic_path))
                        throw new Exception('23');
                }else{
                    $upload = new Upload();
                    $flag = $upload->uploadFile('recommend_pic');
                    if (empty($upload->getErrorMsg())) {
                        $info['pic_path'] = $flag;
                    }
                }
            }else{
                //如果不是banner或特惠推荐,需要填入商品ID,并检查对应商品是否存在
                if(empty($goods_id)){
                    throw new Exception('10');
                }
                $goods = Goods::model()->findByPk($goods_id);
                if(empty($goods) || $goods->status == 2)
                    throw new Exception('24');
            }
            if(empty($recommend_id)){
                GoodsRecommend::model()->createGoodsRecommend($info);
            }else{
                $recommend = GoodsRecommend::model()->findByPk($recommend_id);
                if(empty($recommend))
                    throw new Exception('19');
                $recommend->updateGoodsRecommend($info);
            }

        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionOrder_index(){
        $status_key = OrderInfo::model()->orderStatusKey;
        $invoice_type_key = OrderInfo::model()->invoiceTypeKey;
        $this->render('goods_order_index',array('status_key' => $status_key, 'invoice_type_key' =>$invoice_type_key));
    }

    public function actionGet_order_list(){
        $start_date = Yii::app()->request->getParam('start_date');
        $end_date = Yii::app()->request->getParam('end_date');
        $status = Yii::app()->request->getParam('status');
        $search_content = Yii::app()->request->getParam('search_content');
        $limit = trim(Yii::app()->request->getParam('length'));
        $offset = trim(Yii::app()->request->getParam('start'));
        $export = trim(Yii::app()->request->getParam('export'));
        $con = array();
        if (!empty($start_date) && !empty($end_date)) {
            $end_date = date("Y-m-d", strtotime("$end_date   +1   day"));
            $con['_create_time'] = array('between', $start_date, $end_date);
        }
        if($status == 0 || !empty($status))
            $con['order_status'] = $status;
        if(!empty($search_content)){
            $order_ids = OrderInfo::model()->searchOrder($search_content);
            if(empty($order_ids)){
                echo CJSON::encode(array('data' => array(), 'iTotalRecords' => 0, 'iTotalDisplayRecords' => 0,));exit;
            }else{
                $con['id'] = $order_ids;
            }
        }
        $order_list = OrderInfo::model()->getList($con, 'id desc', $limit, $offset);
        if(empty($export)){
            echo CJSON::encode($order_list);exit;
        }else{
            $header = array('订单编号','商品名称','型号','数量','下单时间','订单价格','广币支付','现金支付','支付状态','支付类型','收货地址',
                '收货人姓名','收货人手机', '用户留言','用户名','订单进度');
            $data = array();
            foreach ($order_list['data'] as $value) {
                $data[]= array($value['order_sn'], str_replace('</br>', "\r\n", $value['goods_name']), str_replace('</br>', "\r\n", $value['goods_attr']),
                    str_replace('</br>', "\r\n", $value['goods_number']), $value['_create_time'], $value['goods_amount'],$value['gb_money'],$value['real_money'],
                    $value['pay_status'], $value['pay_name'], $value['province_name'] . $value['city_name'] . $value['address'],
                    $value['consignee'], $value['mobile'], $value['postscript'], $value['member_user_name'], $value['status_name'] );
            }
            FwUtility::exportExcel($data, $header, '订单列表','订单列表'.date('Ymd'));exit;
        }

    }

    public function actionUpdate_express_num(){
        $msgNo = 'Y';
        try{
            $order_id = Yii::app()->request->getParam('id');
            $invoice_no = Yii::app()->request->getParam('invoice_no');
            $invoice_type = Yii::app()->request->getParam('invoice_type');
            if(empty($order_id))
                throw new Exception('25');
            if(empty($invoice_no))
                throw new Exception('27');
            if(empty($invoice_type))
                throw new Exception('35');
            $order = OrderInfo::model()->findByPk($order_id);
            if(empty($order))
                throw new Exception('26');
            if($order->order_status != 12 && $order->order_status < 20)
                throw new Exception('28');
            $order->updateOrderInfo(array('invoice_no' => $invoice_no, 'order_status' => 21,
                'shipping_time' => date('y-m-d H:m:s'), 'invoice_type' => $invoice_type));
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }

    public function actionGoods_order_info(){
//        $a = Express::getLastTrace('ZTO','410381951875');
        $order_id = Yii::app()->request->getParam('id');
        $order = OrderInfo::model()->getList(array('id' => $order_id),'id desc');
        $province = ServiceRegion::model()->getCityList();
        $invoice_type_key = OrderInfo::model()->invoiceTypeKey;
        $this->render('goods_order_info',array('order' => $order['data'][0], 'province' => $province, 'invoice_type_key' =>$invoice_type_key));
    }

    public function actionGet_city_list(){
        $province_id = trim(Yii::app()->request->getParam('province_id'));
        $city_list = ServiceRegion::model()->getCityByProvince($province_id);
        echo CJSON::encode($city_list);exit;
    }

    public function actionUpdate_shipping_address(){
        $msgNo = 'Y';
        try{
            $data = array();
            $order_id = Yii::app()->request->getParam('id');
            $data['province'] = Yii::app()->request->getParam('province');
            $data['city'] = Yii::app()->request->getParam('city');
            $data['address'] = Yii::app()->request->getParam('address');
            $data['consignee'] = Yii::app()->request->getParam('consignee');
            $data['mobile'] = Yii::app()->request->getParam('mobile');
            if(empty($order_id))
                throw new Exception('25');
            if(empty($data['province']))
                throw new Exception('29');
            if(empty($data['city']))
                throw new Exception('30');
            if(empty($data['address']))
                throw new Exception('31');
            if(empty($data['consignee']))
                throw new Exception('32');
            if(empty($data['mobile']))
                throw new Exception('33');

            $order = OrderInfo::model()->findByPk($order_id);
            if(empty($order))
                throw new Exception('26');

            $order->updateOrderInfo($data);
        }catch(Exception $e){
            $msgNo = $e->getMessage();
        }
        $msg = $this->msg[$msgNo];
        echo $this->encode($msgNo, $msg);
    }


    private function _create_more_img($img_path,$new_file,$width,$height){
        $img = new Img();
        return $img->resize_image($img_path, $new_file,array('width' => $width,'height' => $height));
    }
}