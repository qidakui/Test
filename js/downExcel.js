/*
*  定时保存内容到redis
*	type: 类型如 product_down_experience 保存后的redis key为 product_down_experience_draft_bak_2
*   getEditorId:页面编辑器id 用户获取编辑器内容（仅限百uedit编辑器）
*	columnId：栏目id  如活动id
*	time：定时秒数 10000十秒
*/
 
function gcmc_down_excel(column){
    var starttime = typeof($('#starttime').val())=='undefined' ? '' : $('#starttime').val();
    var endtime = typeof($('#endtime').val())=='undefined' ? '' : $('#endtime').val();
    var filiale_id = typeof($('#filiale_id').val())=='undefined' ? '' : $('#filiale_id').val();
    var table = typeof($('#table').val())=='undefined' ? '' : $('#table').val();
    var province_code = typeof($('#province_code').val())=='undefined' ? '' : $('#province_code').val();
    var search_content = typeof($('#search_content').val())=='undefined' ? '' : $('#search_content').val();
    var url = "&column="+column+"&starttime="+starttime;
        url += "&endtime="+endtime;
        url +="&filiale_id="+filiale_id;
        url +="&table="+table;
        url +="&province_code="+province_code;
        url +="&search_content="+search_content;
    location.href = "index.php?r=gcmc/downexcel"+url;
}
 