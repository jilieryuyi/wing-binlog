/**
 * Created by yuyi on 17/3/26.
 */

String.prototype.replaceCallback = function(p,callback){
    var data = this.match(p);
    if( typeof data != "object" || data == null)
        return;

    console.log(typeof data);
    if( typeof data.length == "undefined")
        return;
    var len = data.length;

    var str = this;

    for( var i=0; i<len; i++ ){
        var ns = callback(data[i]);
        str = str.replace(data[i],ns);
    }
    return str;
};

function strtotime(daytime){
    daytime = daytime.replace(/\-/g,"/");
    var timestamp = Date.parse(new Date(daytime));
    return timestamp/1000;
}
function time(){
    return parseInt((new Date().getTime())/1000);
}

var WingDate = function(format,time) {

    if( typeof format == "undefined" ){
        format = "U";
    }

    if( typeof time == "undefined"){
        time =  new Date().getTime();
    }else{
        time = time * 1000;
    }


    var self = this;
    this.date = new Date();
    this.date.setTime( time );

    this.U = function(){
        return parseInt((new Date().getTime())/1000);
    };
    //d格式支持 返回 01-31
    this.d = function(){
        var day = self.date.getDate();
        if( day < 10 )
            return "0"+day;
        return day;
    };
    //返回星期几的缩写字母 三位
    this.D = function(){
        var day = self.date.getDay();
        var res = "";
        switch(day){
            case 0:
                res = "Sun";
                break;
            case 1:
                res = "Mon";
                break;
            case 2:
                res = "Tue";
                break;
            case 3:
                res = "Wed";
                break;
            case 4:
                res = "Thu";
                break;
            case 5:
                res = "Fri";
                break;
            case 6:
                res = "Sat";
                break;
        }
        return res;
    };
    this.j = function(){
        var day = self.date.getDate();
        return day;
    };
    this.l = function(){
        var day = self.date.getDay();
        var res = "";
        switch(day){
            case 0:
                res = "Sunday";
                break;
            case 1:
                res = "Monday";
                break;
            case 2:
                res = "Tuesday";
                break;
            case 3:
                res = "Wednesday";
                break;
            case 4:
                res = "Thursday";
                break;
            case 5:
                res = "Friday";
                break;
            case 6:
                res = "Saturday";
                break;
        }
        return res;
    };
    this.N = function(){
        return self.date.getDay()+1;
    };
    this.S = function(){
        var month = self.date.getMonth()+1;
        switch(month){
            case 1:

        }
    };

    this.Y = function(){
        return self.date.getFullYear();
    };
    this.m = function(){
        var month = self.date.getMonth()+1;
        if( month < 10 )
            return "0"+month;
        return month;
    };
    this.H = function(){
        var hour = self.date.getHours();
        if( hour < 10 )
            return "0"+hour;
        return hour;
    };
    this.i = function(){
        var minutes = self.date.getMinutes();
        if( minutes <  10 )
            return "0"+minutes;
        return minutes;
    };
    this.s = function(){
        var seconds = self.date.getSeconds();
        if( seconds < 10 )
            return "0"+seconds;
        return seconds;
    };

    this.result = format.replaceCallback(/[a-zA-Z]/g,function(item){
        var func = self[item];
        return func();
    });

    this.toString = function(){
        return self.result;
    };

    return {
        toString:self.toString
    };
};


