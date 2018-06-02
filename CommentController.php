<?php

/**
 * desc:用户反馈、 各栏目评论管理
 */
use application\models\Comment\Comment;
use application\models\ServiceRegion;
use application\models\Activity\ActivityComment;
use application\models\OnlineStudy\DocumentComment;
use application\models\OnlineStudy\VideoComment;
use application\models\Common\CommonMember;

class CommentController extends Controller {

    private $msg = array(
        'Y' => 'OK',
        1 => '数据库操作错误',
        2 => '请先登录',
        3 => '参数错误',
    );
    private $sourceInfo = array(
        'activity' => array('name' => '活动', 'delete_array' => array('_delete' => 1)),
        'training' => array('name' => '培训', 'delete_array' => array('_delete' => 1)),
        'product' => array('name' => '产品', 'delete_array' => array('_delete' => 1)),
        'information' => array('name' => '资讯', 'delete_array' => array('_delete' => 1)),
        'document' => array('name' => '图文', 'delete_array' => array('is_delete' => 1)),
        'video' => array('name' => '视频', 'delete_array' => array('is_delete' => 1)),
        'topic' => array('name' => '活动话题', 'delete_array' => array('_delete' => 1)),
    );

    private $client = array(0 => 'PC端', 1 => 'H5端', 2 => 'APP端');
    public $bigColumn = array(
        1 => '产品建议',
        2 => '服务投诉',
        3 => '销售投诉',
        4 => '产品投诉',
        5 => '网站建议意见',
        6 => '其他',
    );
    public $columnArr = array(
        1 => array(
            15 => '计价软件',
            16 => '擎州计价',
            17 => '钢筋算量及对量软件',
            18 => '图形算量及对量软件',
            19 => '安装算量',
            20 => '精装算量软件',
            21 => '市政算量软件',
            22 => '审核软件',
            23 => '结算软件',
            24 => '其他类'
        ),
        2 => array(
            25 => '服务人员态度',
            26 => '服务人员能力',
            27 => '服务过程处理不当',
            28 => '服务承诺未履行',
            29 => '后续服务跟不上',
            30 => '服务类其它',
        ),
        3 => array(
            31 => '销售过程处理不当',
            32 => '销售人员态度及不维护',
            33 => '销售承诺未履行',
            34 => '购买价格异议',
            35 => '销售类其他',
        ),
        4 => array(
            36 => '计价软件',
            37 => '擎州计价',
            38 => '钢筋算量及对量软件',
            39 => '图形算量及对量软件',
            40 => '安装算量',
            41 => '精装算量软件',
            42 => '市政算量软件',
            43 => '审核软件',
            44 => '结算软件',
            45 => '其他类'
        ),
        5 => array(
            47 => '答疑解惑',
            48 => '学习资料',
            49 => '同城活动',
            50 => '建筑课堂',
            1 => '广联达产品',
            2 => '风采展示',
            3 => '行业资讯',
            4 => '服务中心',
            5 => '广币商城',
            6 => '培训讲座',
            7 => '升级下载',
            8 => '服务app',
            9 => '预约服务',
            10 => '我来问',
            11 => '我来搜',
            12 => '我的服务',
            13 => '意见反馈',
            14 => 'APP意见反馈',
        ),
        6 => array(
            46 => '其他'
        )
    );
    public $userId;
    public $userName;
    public $branchId;

    public function init() {
        parent::init();
        $this->userId = Yii::app()->user->user_id;
        $this->userName = Yii::app()->user->user_name;
        $this->branchId = Yii::app()->user->branch_id;
    }

    public function actionIndex() {
        if (!isset($_GET['iDisplayLength'])) {
            $getBranchList = ServiceRegion::model()->getBranchList();
            $this->render('index', array('getBranchList' => $getBranchList));
            exit;
        }
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));

        $startTime = trim(Yii::app()->request->getParam('starttime'));
        $endTime = trim(Yii::app()->request->getParam('endtime'));
        $provinceCode = intval(Yii::app()->request->getParam('province_code')); //分支id前两位
        $bigColumn = intval(Yii::app()->request->getParam('bigcolumn'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'desc';
        $field = !empty($field) ? $field : 'id';

        $con = array();
        if ($startTime) {
            $con['_create_time>'] = $startTime;
            $con['_create_time<'] = $endTime ? $endTime . ' 23:59:59' : $startTime . ' 23:59:59';
        }
        if ($this->branchId == BRANCH_ID) {
            if ($provinceCode) {
                $con['branch_id'] = $provinceCode;
            }
        } else {
            $con['branch_id'] = $this->branchId;
        }
        if ($bigColumn) {
            $con['big_column'] = $bigColumn;
        }
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $result = Comment::model()->getList($con, $ord, $field, $limit, $page);
        echo CJSON::encode($result);
    }

    /**
     * desc:导出
     */
    public function actionExport_information() {
        $startTime = Yii::app()->request->getParam('starttime');
        $endTime = Yii::app()->request->getParam('endtime');
        $filiale_id = intval(Yii::app()->request->getParam('province_code'));
        $bigColumn = intval(Yii::app()->request->getParam('bigcolumn'));
        $con = array();
        if ($startTime) {
            $con['_create_time>'] = $startTime;
            $con['_create_time<'] = $endTime ? $endTime . ' 23:59:59' : $startTime . ' 23:59:59';
        } else {
            //不选时间默认查一个月
            $con['_create_time>'] = date('Y-m-d', strtotime('-1 month'));
        }
        if ($filiale_id) {
            $con['branch_id'] = $filiale_id;
        }
        if ($bigColumn) {
            $con['big_column'] = $bigColumn;
        }
        $list = Comment::model()->getlist($con, 'desc', '_create_time', 1000, 0);
        if (empty($list['data'])) {
            exit;
        }
        $data = array();
        foreach ($list['data'] as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['city_name'] = $v['city_name'];
            $data[$k]['big_column'] = empty($this->bigColumn[$v['big_column']]) ? '' : $this->bigColumn[$v['big_column']];
            $data[$k]['column'] = empty($this->columnArr[$v['big_column']][$v['column_id']]) ? '' : $this->columnArr[$v['big_column']][$v['column_id']];
            $data[$k]['user_name'] = $v['user_name'];
            $data[$k]['ip'] = $v['ip'];
            $data[$k]['mobile'] = $v['mobile'];
            $data[$k]['inputname'] = $v['inputname'];
            $data[$k]['email'] = $v['email'];
            $data[$k]['qq'] = $v['qq'];
            $data[$k]['_create_time'] = $v['_create_time'];
            $data[$k]['comment'] = $v['comment'];
        }
        $header = array('ID', '分支', '类型', '类别', '用户名', 'ip', '手机号', '姓名', '邮箱', 'qq', '反馈时间', '反馈内容');
        FwUtility::exportExcel($data, $header, '反馈明细', 'comment_' . date('Ymd'));
        print_r($list);
        die;
    }

    /*
     * 评论列表页
     */

    public function actionColumn_comment_list() {
        $column_id = Yii::app()->request->getParam('column_id');
        $column_type = trim(Yii::app()->request->getParam('column_type'));
        $column_type_Arr = ActivityComment::model()->column_type_Arr;
        $comment_id = Yii::app()->request->getParam('comment_id');
        $ancestor_comment_id = intval(Yii::app()->request->getParam('ancestor_comment_id'));
        if (!isset($_GET['iDisplayLength'])) {
            $this->render('column_comment_list', array(
                'column_type_Arr' => $column_type_Arr,
                'column_type' => $column_type,
                'column_id' => $column_id,
                'comment_id' => $comment_id,
                'ancestor_comment_id' => $ancestor_comment_id));
            exit;
        }
        $limit = trim(Yii::app()->request->getParam('iDisplayLength'));
        $page = trim(Yii::app()->request->getParam('iDisplayStart'));
        $index = trim(Yii::app()->request->getParam('iSortCol_0'));
        $ord = trim(Yii::app()->request->getParam('sSortDir_0'));
        $field = trim(Yii::app()->request->getParam('mDataProp_' . $index)); //排序字段
        $ord = !empty($ord) ? $ord : 'desc';
        $field = !empty($field) ? $field : 'id';
        $page = !empty($page) ? $page : 0;
        $limit = !empty($limit) ? $limit : 20;
        $limit = Yii::app()->request->getParam('down') ? 10000 : $limit;

        $starttime = trim(Yii::app()->request->getParam('starttime'));
        $endtime = trim(Yii::app()->request->getParam('endtime'));
        $search_content = trim(Yii::app()->request->getParam('search_content'));
        $delete_status = intval(Yii::app()->request->getParam('delete_status'));
        $delete_status = in_array($delete_status, [0, 1, 2]) ? $delete_status : 0;
        $comment_level = intval(Yii::app()->request->getParam('comment_level'));

        if ($search_content) {
            $con['comment'] = $search_content;
        }
        if (!empty($starttime) && !empty($endtime)) {
            $con['time'][0] = $starttime;
            $con['time'][1] = $endtime . ' 23:59:59';
        }
        $list = array('data' => [], 'iTotalDisplayRecords' => 0);
        if (in_array($column_type, ['document', 'video'])) {
            $con[$column_type . '_id'] = $column_id;
            $con['status'] = 1;
            $con['is_delete'] = $delete_status;
            if ($comment_level) {
                $con['parent_id'] = 0;
            } elseif ($comment_id) {
                unset($con[$column_type . '_id']);
                if ($ancestor_comment_id) {
                    //非一级评论只能看它自己的评论
                    $con['parent_id'] = $comment_id;
                } else {
                    //一级评论可以看下级评论和下下及评论
                    $con['ancestor_comment_id'] = $comment_id;
                }
            }
            //print_r($con);
            if ($column_type == 'document') {
                $_list = DocumentComment::model()->getlist($con, $ord, $field, $limit, $page);
            } elseif ($column_type == 'video') {
                $_list = VideoComment::model()->getlist($con, $ord, $field, $limit, $page);
            }

            if (isset($_list['data']) && !empty($_list['data'])) {
                $member = array();
                foreach ($_list['data'] as $sk => $sv) {
                    $_data['user_id'] = $sv['user_id'];
                    if (!isset($member[$_data['user_id']])) {
                        $CommonMember = CommonMember::model()->findMemberUserId($_data['user_id']);
                        $member[$_data['user_id']] = empty($CommonMember) ? array('global_id' => 0, 'member_user_name' => '', 'member_nick_name' => '') : $CommonMember->attributes;
                    }
                    $_data['global_id'] = $member[$_data['user_id']]['global_id'];
                    $_data['member_user_name'] = $member[$_data['user_id']]['member_user_name'];
                    $_data['member_nick_name'] = $member[$_data['user_id']]['member_nick_name'];

                    $_data['_create_time'] = $sv['create_time'];
                    $_data['id'] = $sv['id'];
                    $_data['ancestor_comment_id'] = $sv['ancestor_comment_id'];
                    $_data['zan_num'] = $sv['up_count'];
                    $_data['hf_num'] = $sv['reply_count'];
                    $_data['comment'] = $sv['comment'];
                    $_data['comment_short'] = cutstr($sv['comment'], 160);
                    $_data['column_type'] = $column_type;
                    $_data['client'] = $this->client[$sv['client']];
                    $list['data'][] = $_data;
                }
                $list['iTotalDisplayRecords'] = $_list['iTotalDisplayRecords'];
            }
        } else {
            $con['_delete'] = $delete_status;
            $con['status'] = isset($column_type_Arr[$column_type]['val']) ? $column_type_Arr[$column_type]['val'] : 0;
            $con['activity_id'] = $column_id;
            if ($comment_level) {
                $con['pid'] = 0;
            } elseif ($comment_id) {
                unset($con['activity_id'], $con['status']);
                if ($ancestor_comment_id) {
                    //非一级评论只能看它自己的评论
                    $con['pid'] = $comment_id;
                } else {
                    //一级评论可以看下级评论和下下及评论
                    $con['ancestor_comment_id'] = $comment_id;
                }
            }

            //print_r($con);
            $list = ActivityComment::model()->getlist($con, $ord, $field, $limit, $page);
        }
        if ($limit == 10000) {
            $this->_down_excel($list, $column_id, $column_type);
            die;
        }
        echo CJSON::encode($list);
    }

    /*
     * 导出评论
     */

    private function _down_excel($list, $column_id, $column_type) {
        $data = array();
        foreach ($list['data'] as $k => $v) {
            $tmp['id'] = $v['id'] . ' ';
            $tmp['comment'] = $v['comment'] . ' ';
            $tmp['user_id'] = $v['user_id'] . ' ';
            $tmp['global_id'] = $v['global_id'] . ' ';
            $tmp['member_user_name'] = $v['member_user_name'] . ' ';
            $tmp['member_nick_name'] = $v['member_nick_name'] . ' ';
            $tmp['_create_time'] = $v['_create_time'];
            $tmp['hf_num'] = $v['hf_num'];
            $tmp['client'] = $v['client'];
            $data[] = $tmp;
        }
        $header = array('ID', '内容', '用户ID', 'Global ID', '用户名', '用户昵称', '评论时间', '被评论数', '客户端来源');
        FwUtility::exportExcel($data, $header, '评论列表', $this->sourceInfo[$column_type]['name'] . '评论_' . $column_id . '_' . date('Y-m-d'));
    }

    /*
     * 删除
     */

    public function actionDel_comment() {
        $msgNo = 'Y';
        $column_type = Yii::app()->request->getParam('column_type');
        $ids = Yii::app()->request->getParam('ids');

        try {
            if (empty($ids)) {
                throw new \Exception(3);
            }
            $parent_id_key = 'parent_id';
            switch ($column_type) {
                case 'video':
                    $model = VideoComment::model();
                    break;
                case 'document':
                    $model = DocumentComment::model();
                    break;
                case 'activity':
                case 'training':
                case 'product':
                case 'information':
                case 'topic':
                    $parent_id_key = 'pid';
                    $model = ActivityComment::model();
                    break;
                default:
                    $model = '';
            }
            if (empty($model)) {
                throw new \Exception(3);
            }
            $ids = implode(',', $ids);

            $comment = $model->updateAll(
                    $this->sourceInfo[$column_type]['delete_array'], "id IN(" . $ids . ") OR ancestor_comment_id IN(" . $ids . ") OR " . $parent_id_key . " IN(" . $ids . ")"
            );
            if ($comment === false) {
                throw new \Exception(1);
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
    }

	/*
     * 恢复举报的评论
     */
	public function actionReport_examine(){
		$msgNo = 'Y';
        $column_type = Yii::app()->request->getParam('column_type');
        $ids = Yii::app()->request->getParam('ids');

        try {
            if (empty($ids)) {
                throw new \Exception(3);
            }
            $parent_id_key = 'parent_id';
            switch ($column_type) {
                case 'video':
                    $model = VideoComment::model();
                    break;
                case 'document':
                    $model = DocumentComment::model();
                    break;
                case 'activity':
                case 'training':
                case 'product':
                case 'information':
                case 'topic':
                    $parent_id_key = 'pid';
                    $model = ActivityComment::model();
                    break;
                default:
                    $model = '';
            }
            if (empty($model)) {
                throw new \Exception(3);
            }
            $ids = implode(',', $ids);
			foreach($this->sourceInfo[$column_type]['delete_array'] as $delete_key=>$delete_val){
				$this->sourceInfo[$column_type]['delete_array'][$delete_key] = 0;
			}
            $comment = $model->updateAll($this->sourceInfo[$column_type]['delete_array'], "id IN(" . $ids . ") AND ".$delete_key."=2 ");
            if ($comment === false) {
                throw new \Exception(1);
            }
        } catch (Exception $e) {
            $msgNo = $e->getMessage();
        }
        echo $this->encode($msgNo, $this->msg[$msgNo]);
	}

}
