/**
 * Created by yuyi on 17/1/13.
 */


function Warray(arr){
    if( typeof arr != "object"){
        console.assert("参数错误，参数必须是数组");
    }
    if( typeof arr.length != "number"){
        console.assert("参数错误，参数必须是数组");
    }

    var self      = this;
    this.len      = arr.length;
    this.raw_data = arr;

    this.has_value = false;
    //i是否劝分大小写 true区分（默认值）
    this.hasValue = function(a,value,i){
        if( typeof a != "object"){
            return;
        }
        if( typeof a.length != "number"){
            return;
        }

        if( typeof value == "undefined" )
            return false;
        if( typeof i == "undefined")
            i = false;
        var len = a.length;
        for( var k = 0; k < len; k++ ){
            var v = a[k];
            if( i && typeof v == "string" )
                v = v.toLowerCase();

            if( typeof v == "object" && typeof v.length == "number" ){
                self.hasValue(v,value,i);
                continue;
            }

            if( v == value )
                self.has_value = true;
        }
    };

    return {
        hasValue:function(value,i){
            self.hasValue( self.raw_data, value, i );
            return self.has_value;
        },
        map:function(callback){
            if(typeof callback != "function")
                return;
            for(var i =0;i<self.len;i++)
            {
                if( callback.length == 1) { //支持中断
                    if (callback(self.raw_data[i]) === false) break;
                }
                else{ //支持中断
                    if (callback(i,self.raw_data[i]) === false) break;
                }
            }
        }
    };
}

if( typeof Array.prototype.map == "undefined" ) {
    Array.prototype.map = function(callback){
        var arr = new Warray( this );
        arr.map(callback);
    };
}

if( typeof Array.prototype.hasValue == "undefined" ){
    Array.prototype.hasValue = function( str, i ){
        var arr = new Warray( this );
        return  arr.hasValue(str,i);
    };
}

if( typeof Array.prototype.foreach == "undefined" ){
    Array.prototype.foreach = function( callback ){
        var arr = new Warray( this );
        arr.map(callback);
    };
}


