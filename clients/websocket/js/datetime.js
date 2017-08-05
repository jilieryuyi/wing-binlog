/**
 * Created by Administrator on 2016/1/25.
 */
if(typeof window.date=='undefined')
    window.date={};
window.date.getTime=function(Milliseconds){
    //获取时间
    var myDate = new Date();
    var month=myDate.getMonth()+1;
    month=month<10?"0"+month:month;
    var day=myDate.getDate();
    day=day<10?"0"+day:day;
    var hour=myDate.getHours();
    hour=hour<10?"0"+hour:hour;
    var minute=myDate.getMinutes();
    minute=minute<10?"0"+minute:minute;
    var seconds=myDate.getSeconds();
    seconds=seconds<10?"0"+seconds:seconds;
    var time=myDate.getFullYear()+'-'+month + '-' + day + ' '+hour+':'+minute+":"+seconds;
    return Milliseconds==true?(time+":"+myDate.getMilliseconds()):time;
}
window.date.getDay=function(){
    var myDate = new Date();
    var month=myDate.getMonth()+1;
    month=month<10?"0"+month:month;
    var day=myDate.getDate();
    day=day<10?"0"+day:day;
    var hour=myDate.getHours();
    hour=hour<10?"0"+hour:hour;
    var minute=myDate.getMinutes();
    minute=minute<10?"0"+minute:minute;
    var seconds=myDate.getSeconds();
    seconds=seconds<10?"0"+seconds:seconds;
    var time=myDate.getFullYear()+'-'+month + '-' + day;
    return time;

}
window.date.getDayEx=function(){
    var myDate = new Date();
    var month=myDate.getMonth()+1;
    month=month<10?"0"+month:month;
    var day=myDate.getDate();
    day=day<10?"0"+day:day;
    var hour=myDate.getHours();
    hour=hour<10?"0"+hour:hour;
    var minute=myDate.getMinutes();
    minute=minute<10?"0"+minute:minute;
    var seconds=myDate.getSeconds();
    seconds=seconds<10?"0"+seconds:seconds;
    var time=myDate.getFullYear()+''+month + '' + day;
    return time;

}

window.date.getimestamp=function(){
    var datetime=window.date.getTime(true);
    datetime=datetime.replace(/-/g,'/');
    var time=new Date(datetime);
    return time.getTime();
}
window.date.timestamp=function(datetime){
    datetime=datetime.replace(/-/g,'/');
    var time=new Date(datetime);
    return time.getTime();
}