/**
 *@author yuyi
 *@created 2016/10/4 21:23
 *@email 297341015@qq.com
 *@停止输入事件，只要300毫秒没有继续输入然后就会触发
 */
var input={
    inputStop:function(dom,callback){
        var ele   = $(dom);
        var start = false;
        ele.focusin(function(){
            start = true;
        });
        var old_value = ele.val();
        window.setInterval(function(){
            if( old_value != ele.val() && ele.val() != "" ){
                old_value = ele.val();
                start     = true;
            }else if( ele.val() != ""){
                if(start){
                    callback(ele);
                    start = false;
                }
            }else if(ele.val() == ""){
                $(".selectpicker").hide();
            }
        },300);
    }
};