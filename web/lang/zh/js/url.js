/**
 * Created by yuyi on 16/11/24.
 * @url解析
 */
function Url(url){
    //"http://www.baidu.com:9008/index.php?a=1#top"

    var http   = url.split("?");
    var temp   = url.split("://");
    var scheme = temp[0];
    var split  = temp[1].indexOf("/");
    var query  = temp[1].substr(split+1,temp[1].length-split);
    var host   = temp[1].substr(0,split);
    var port   = "";
    var pos    = host.indexOf(":");

    if( pos > 0 ){
        temp = host.split(":");
        host = temp[0];
        port = temp[1];
    }

    var path     = query;
    var fragment = "";
        pos      = query.indexOf("?");
    if( pos > 0 ) {
        temp  = query.split("?");
        path  = temp[0];
        query = temp[1];
        pos   = query.indexOf("#");
        if( pos > 0 ){
            temp     = query.split("#");
            query    = temp[0];
            fragment = temp[1];
        }
    }else{
        pos   = query.indexOf("#");
        if( pos > 0 ){
            temp     = query.split("#");
            path     = temp[0];
            fragment = temp[1];
        }
        query = "";
    }

    var get_params = {};
    if( query.length > 0 ){
        temp    = query.replace(/\&\&/g,"&");
        temp    = temp.split("&");
        var len = temp.length;
        for(var i = 0; i < len; i++ ){
            var t = temp[i].split("=");
            get_params[t[0]] = t[1];
        }
    }


    this.get = function( key ){
        if( typeof get_params[key] == "undefined" )
            return null;
        return get_params[key];
    };

    return {
        url      : http[0],  //http url
        scheme   : scheme,   //使用什么协议
        host     : host,     //主机名
        path     : path,     //路径
        query    : query,    //所传的参数
        fragment : fragment, //后面根的锚点
        port     : port,     //端口
        params   : get_params,
        get      : this.get  //http get 参数
    };
}
