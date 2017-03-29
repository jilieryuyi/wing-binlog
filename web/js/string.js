/**
 * Created by yuyi on 17/1/13.
 */
if( typeof String.prototype.inArray == "undefined" ){
    /**
     * @判断是否在某数组里面
     */
    String.prototype.inArray = function(arr){

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

if( typeof String.prototype.trim == "undefined") {
    /**
     * @去掉两边的空格
     */
    String.prototype.trim = function () {
        return this.replace(/(^\s)|(\s$)/g, "");
    }
}

if( typeof String.prototype.ltrim == "undefined") {
    /**
     * @去掉左边的空格
     */
    String.prototype.ltrim = function () {
        return this.replace(/(^\s)/g, "");
    }
}


if( typeof String.prototype.rtrim == "undefined") {
    /**
     * @去掉右边的空格
     */
    String.prototype.rtrim = function () {
        return this.replace(/(\s$)/g, "");
    }
}

if( typeof String.prototype.contains == "undefined") {
    /**
     * @判断字符串是否包含
     */
    String.prototype.contains = function (subStr) {
        return this.indexOf(subStr) != -1;
    }
}
if( typeof String.prototype.isNumber == "undefined"){
    /**
     * @判断字符串是否是数字
     */
    String.prototype.isNumber = function(){
        return this.match(/^[\+\-]?(\d+)(\.\d+)?$/);
    }
}

if( typeof String.prototype.matchCallback == "undefined" ) {
    /**
     * @正则匹配遍历回调
     */
    String.prototype.matchCallback = function (p, callback) {
        var data = this.match(p);
        if (typeof data != "object" || data == null)
            return;

        console.log(typeof data);
        if (typeof data.length == "undefined")
            return;
        var len = data.length;
        for (var i = 0; i < len; i++) {
            callback(data[i]);
        }
    };
}

if( typeof String.prototype.replaceCallback == "undefined" ) {
    /**
     * @正则替换迭代器
     */
    String.prototype.replaceCallback = function (p, callback) {
        var data = this.match(p);
        if (typeof data != "object" || data == null)
            return;

        console.log(typeof data);
        if (typeof data.length == "undefined")
            return;
        var len = data.length;

        var str = this;

        for (var i = 0; i < len; i++) {
            var ns = callback(data[i]);
            str = str.replace(data[i], ns);
        }
        return str;
    };
}