/**
 * Created by yuyi on 16/11/24.
 */


function Http(url, data, options) {

    var self        = this;
    this.url        = url;
    this.http_data  = "";
    this.timeout    = 8000;

    if( typeof data != "undefined" )
        this.http_data = data;

    if( typeof options == "object" ) {
        for (var key in options){
            if( options.hasOwnProperty(key) ){
                this[key] = options[key];
            }
        }
    }


    this.callbacks  = {
        //data 请求url的返回值
        //headers 格式为object 请求url响应的header
        onsuccess  :function(data,headers,status,xhr){},
        onerror    :function(error_code,error_msg,xhr){},
        onstart    :function(){},
        onend      :function(){}
    };

    this.getData    = function(){
        return this.http_data;
    };

    this.getTimeout = function(){
        if( this.timeout < 1000 )
            return 1000;
        return this.timeout;
    };

    this.cacheKey = function(){

        var cache_key = this.url;
        var data      = this.getData();

        if( typeof data == "object" ) {
            for (var key in data )
            {
                if(data.hasOwnProperty(key))
                {
                    cache_key+=key+data[key];
                }
            }
        }else{
            cache_key+=data;
        }

        return encodeURIComponent( cache_key ).replace("%","");
    };

    this.getHeaders = function(xhr){
        var str = xhr.getAllResponseHeaders();
        var arr = str.split("\r\n");
        var headers = {};
        //坑爹的ie8 没有map api
        arr.map(function(header_str){

            if( header_str.indexOf(":") < 0 )
                return;

            var temp = header_str.split(":");
            temp[0]  = $.trim(temp[0]);
            temp[1]  = $.trim(temp[1]);

            if( temp[0].length <= 0 )
                return;

            headers[temp[0]] = temp[1];
        });

        return headers;
    };


    this.post = function(){
        self.callbacks.onstart();
        $.ajax({
            "type"       : "POST",
            "data"       : self.getData(),
            headers:{
                "token":Cookies.get("token")
            },
            crossDomain  : true,
            "url"        : self.url,
            "timeout"    : self.getTimeout(),
            "success"    : function(data,status,xhr){

                self.callbacks.onend();

                if(typeof data=="object"){
                    if(typeof data.token == "string"&&data.token.length > 0 ){
                        Cookies.set("token",data.token);
                    }
                }

                if( parseInt( data.error_code ) == 3001 )
                {
                    window.location.href="/user/login.html";
                    return;
                }

                self.callbacks.onsuccess(data,self.getHeaders(xhr),status,xhr);
            },
            "error"      : function(jqXHR, textStatus, errorThrown){
                self.callbacks.onend();
                self.callbacks.onerror(textStatus,errorThrown,jqXHR);
            }
        });
    };

    this.get = function(){
        $.get( self.url,self.getData(), function(data,status,xhr){
            self.callbacks.onsuccess(data,self.getHeaders(xhr),status,xhr);
        });
    };

    //开放的接口
    return {
        events:{
            onSuccess:"onsuccess",
            onsuccess:"onsuccess",
            success:"onsuccess",
            onError:"onerror",
            onerror:"onerror",
            error:"onerror",
            onStart:"onstart",
            onstart:"onstart",
            start:"onstart",
            onEnd:"onend",
            onend:"onend",
            end:"onend"
        },
        post:self.post,
        get :self.get,
        setData:function(http_data){
            self.http_data = http_data;
            return this;
        },
        //可选_event onsuccess onerror
        setCallback:function(_event,callback){
            self.callbacks[_event.toLowerCase()] = callback;
            return this;
        },
        on:function(_event,callback){
            self.callbacks[_event.toLowerCase()] = callback;
            return this;
        },
        setTimeout:function(timeout){
            self.timeout = timeout;
            return this;
        }
    };
}