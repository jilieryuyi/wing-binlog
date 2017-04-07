/**
 * Created by yuyi on 17/1/13.
 */
if( typeof Number.prototype.inArray == "undefined" ){
    /**
     * @判断是否在某数组里面
     */
    Number.prototype.inArray = function(arr){

        if( typeof arr != "object" ){
            console.assert("参数错误，参数必须是非空数组");
            return false;
        }

        if( typeof arr.length != "number" ){
            console.assert("参数错误，参数必须是非空数组");
            return false;
        }

        if( typeof arr.length <= 0 ){
            console.assert("参数错误，参数必须是非空数组");
            return false;
        }

        // var len = arr.length;
        // var str = this;
        // for( var i = 0; i < len; i++ ){
        //     if( str == arr[i] ){
        //         return true;
        //     }
        // }
        // return false;

        var a = new Warray(arr);
        return a.hasValue(this);
    }
}


