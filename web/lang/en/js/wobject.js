/**
 * Created by yuyi on 17/1/14.
 */
function Wobject(obj){

    var self = this;

    self.raw_data  = obj;
    self.has_key   = false;
    self.has_value = false;

    self.is_array = typeof self.raw_data.length == "number";

    this.hasKey = function( obj , key, i ){

        //兼容数组
        if( self.is_array ){

            if( typeof key != "number" )
            {
                self.has_value = false;
                return;
            }

            if( parseInt(key) < self.raw_data.length ){
                self.has_value = true;
                return;
            }
        }

        if( typeof i == "undefined" )
            i = false;

        if( i && typeof key == "string" )
            key = key.toLowerCase();

        for( var k in obj ){
            if( !obj.hasOwnProperty(k) )
                continue;

            if( i && typeof k == "string" )
            {
                k = k.toLowerCase();
            }

            if (k == key)
                self.has_key = true;
            if( typeof obj[k] == "object" ){
                self.hasKey(obj[k],key);
            }
        }
    };

    this.hasValue = function( obj , value, i ){

        if( typeof i == "undefined" )
            i = false;

        if( i && typeof value == "string" )
            value = value.toLowerCase();


        for( var k in obj ){
            if( !obj.hasOwnProperty(k) )
                continue;

            if( typeof obj[k] == "object" ){
                self.hasValue(obj[k],value,i);
                continue;
            }

            var v = obj[k];
            if( i && typeof v == "string" )
            {
                v = v.toLowerCase();
            }

            if (v == value)
                self.has_value = true;

        }
    };

    this.map = function(callback){
        for( var key in self.raw_data ){
            if( self.raw_data.hasOwnProperty(key) ) {
                if ( callback.length == 1) {
                    if (callback(self.raw_data[key]) === false) break;
                } else {
                    if (callback(key, self.raw_data[key]) === false) break;
                }
            }
        }
    };


    return {
        hasKey : function(key,i){
            if( typeof i == "undefined" )
                i = false;
            self.hasKey( self.raw_data, key, i );
            return self.has_key;
        },
        hasValue:function(value,i){
            if( typeof i == "undefined" )
                i = false;
            self.hasValue(self.raw_data,value,i);
            return self.has_value;
        },
        map:self.map,
        foreach:self.map
    };
}
