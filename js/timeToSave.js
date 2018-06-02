/*
*  定时保存内容到redis
*	type: 类型如 product_down_experience 保存后的redis key为 product_down_experience_draft_bak_2
*   getEditorId:页面编辑器id 用户获取编辑器内容（仅限百uedit编辑器）
*	columnId：栏目id  如活动id
*	time：定时秒数 10000十秒
*/

var get_current_url = window.location.href;
var get_current_url = get_current_url.substr(0,get_current_url.indexOf('?'));
var setInterval_index = 0;

function timeToSave(type, getEditorId, columnId, time){
	setInterval_index = setInterval(function(){
		timeToSaveData(type, getEditorId, columnId);
	},time);
}
function timeToSaveData(type, getEditorId, columnId){
    var content = UE.getEditor(getEditorId).getContent();
    if(content){
        $.ajax({
            type: "POST",
            async: true,
            url: get_current_url+'?r=ajax/savebakredis',
            data: {id:columnId,content:content,type:type},
            dataType: 'json',
            timeout: 10000,
            success: function(data) {
                layer.msg('内容已暂存!',{icon: 1,time:1000});
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if(textStatus=='error' || textStatus=='timeout'){

                }
            }
        });
    }
}