/**
*desc:上传前检测图片大小 使用案列：<input type="file" onchange="fileChange(this,2*1024*1024);">
*author:besttaowenjing@163.com
*date:2016-07-01
*/
var isIE = /msie/i.test(navigator.userAgent) && !window.opera;
var sizeLabel = ["B", "KB", "MB", "GB"];
function fileChange(target,daxiao) {
	var fileSize = 0;
	if (isIE && !target.files) {
		var filePath = target.value;
		var fileSystem = new ActiveXObject("Scripting.FileSystemObject"); 
		var file = fileSystem.GetFile (filePath);
		fileSize = file.Size;
	} else {
		fileSize = target.files[0].size;
	}
	if(fileSize > daxiao){
		target.value = '';
                return false;
	}
        return true;
}

function calFileSize(size) {
	for (var index = 0; index < sizeLabel.length; index++) {
		if (size < 1024) {
			return round(size, 2) + sizeLabel[index];
		}
		size = size / 1024;
	}
	return round(size, 2) + sizeLabel[index];
}

function round(number, count) {
	return Math.round(number * Math.pow(10, count)) / Math.pow(10, count);
}