<?php
/**
 * Created by PhpStorm.
 * User: wangh-ah
 * Date: 2016/5/16
 * Time: 10:12
 */
Yii::import('application.models.Admin.*');
class FileController extends Controller
{
    public function actionIndex(){
        echo phpinfo();exit;
        $this->render('index');
    }
    public function actionFile_add_op(){
        //$upload = new Upload();
        //$flag = $upload->uploadFile('filename');
        $objPHPExcel = FwUtility::readerExcel(UPLOAD_PATH.'/uploads/2016/08/20160815152423_499.xlsx');
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumn = $sheet->getHighestColumn(); // 取得总列数
        $str = '';
        $arr = array();
        //循环读取excel文件,读取一条,插入一条
        for($j=2;$j<=$highestRow;$j++)
        {
            for($k='A';$k<=$highestColumn;$k++)
            {
                $str .= $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue();//读取单元格
            }
        }
        print_r($str);exit;
    }
}