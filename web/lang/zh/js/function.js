/**
 * Created by yuyi on 17/1/13.
 */

if(typeof Function.prototype.inArray == "undefined" ){
    /**
     * @判断是否在某数组里面
     */
    Function.prototype.inArray = function(arr){

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

        var len = arr.length;
        var f   = this;
        var str = f();
        for( var i = 0; i < len; i++ ){
            if( str == arr[i] ){
                return true;
            }
        }
        return false;
    }
}
