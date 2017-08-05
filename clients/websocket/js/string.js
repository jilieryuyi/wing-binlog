String.prototype.trim=function(){
	return this.replace(/(^\s+)|(\s+$)/g,"");  
	}
String.prototype.ltrim=function(){
	return this.replace(/(^\s+)/g,"");  
	}
String.prototype.rtrim=function(){
	return this.replace(/(\s+$)/g,"");  
	}
String.prototype.delSpaces=function(){
	return this.replace(/\s/g,'');
	}
String.prototype.lengthX=function(){
	//每个中文按三个字符返回
	if(arguments.length>0)l=arguments[0];
	else l=3;
	//if(typeof l=='undefined')l=3;
	var len=this.length;
	var lenX=0;
	for(i=0;i<len;i++){
		if(this.charCodeAt(i)>256){lenX +=l;}
		else{lenX++;}
	}
	return lenX;
}
String.prototype.lengthFliter=function(minLen,maxLen){
	var len=this.length;
	if(len<minLen||len>maxLen)return false;
	return true;
	}
String.prototype.isPhone=function(){
	var reg =/^13[0-9]{1}[0-9]{8}$|14[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/;
	if(reg.test(this))return true;
	return false;
	}
String.prototype.isEmail=function(){
	var reg = /^([\w-\.]+)@\w+([-.]\w+)*\.\w+([-.]\w+)*$/;
	if(reg.test(this))return true;
	return false;
	}
String.prototype.startWith=function(start){
	eval("var re = /^"+start+"/gi");
	return re.test(this);
}

String.prototype.isEmailX=function(){
	var  sReg =/[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/; 
	if(sReg.test(this))return true;
	return false;
	}
String.prototype.isEmpty=function(){
	var str=this.replace(/\s/g,'');
	if(str=="")return true;
	return false;
	}
String.prototype.ajaxFilter=function(){
	 var str=this.replace(/\&/g, "%26");
	 	 str=str.replace(/\+/g, "%2B");
     return encodeURI(str) ;
	}
String.prototype.subString=function(start,end){
	//start 大于0 正向提取 小于0 逆向提取
	return this.slice(start,end);
	}
String.prototype.isUrl=function(){
	var strRegex = "^((https|http|ftp|rtsp|mms)?://)"  
  + "?(([0-9a-z_!~*'().&=+$%-]+: )?[0-9a-z_!~*'().&=+$%-]+@)?" //ftp的user@  
        + "(([0-9]{1,3}\.){3}[0-9]{1,3}" // IP形式的URL- 199.194.52.184  
        + "|" // 允许IP和DOMAIN（域名） 
        + "([0-9a-z_!~*'()-]+\.)*" // 域名- www.  
        + "([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\." // 二级域名  
        + "[a-z]{2,6})" // first level domain- .com or .museum  
        + "(:[0-9]{1,4})?" // 端口- :80  
        + "((/?)|" // a slash isn't required if there is no file name  
        + "(/[0-9a-z_!~*'().;?:@&=+$,%#-]+)+/?)$"; 
	var re=new RegExp(strRegex);  
	if(re.test(this))return true;
	return false; 
}
String.prototype.delSpecialChars=function(){
	return this.replace(/(\~|\`|\·|\！|\@|\#|\$|\%|\^|\&|\*|\(|\)|\+|\-|\/|\\|\[|\]|\{|\}|\||\>|\<|\,|\.|\'|\"|。|\:|\;|\=|\：|\；|\￥|\！|\（|\）|\【|\】)|\?/g,'');
	}	
String.prototype.hasSpecialChars=function(){
	var re=/(\~|\`|\·|\！|\@|\#|\$|\%|\^|\&|\*|\(|\)|\+|\-|\/|\\|\[|\]|\{|\}|\||\>|\<|\,|\.|\'|\"|。|\:|\;|\=|\：|\；|\￥|\！|\（|\）|\【|\】)|\?/g;
	if(re.test(this))return true;
	return false;
	}
String.prototype.isNumber=function(){
	var re = /^-?[0-9]+(\.\d+)?$|^-?0(\.\d+)?$|^-?[1-9]+[0-9]*(\.\d+)?$/; 
    var reg = new RegExp(re);
	if(reg.test(this))return true;
	return false;
	}
String.prototype.jsonFilter=function(){
	/*
	换行符影响php json的解析
	双引号影响php json的解析
	\ 影响PHP json的解析
	*/
	return this.replace(/(\n)|(\r\n)/g,"").replace(/\"/g,"0x22").replace(/\\/g,"/");
	}
String.prototype.hasString=function(str){
    //是否包含字符串 区分大小写
	var index=this.indexOf(str);
	if(index>=0)return true;
	return false;
	}
String.prototype.hasStringX=function(str){
    //是否包含字符串 不区分大小写
	var index=this.toLowerCase().indexOf(str.toLowerCase());
	if(index>=0)return true;
	return false;
	}