/**
 * Created by yuyi on 17/1/8.
 */
function Upload(url,file,options){
    var self       = this;
    this.onsuccess = function(data){};
    this.onerror   = function(XMLHttpRequest, textStatus, errorThrown, data){};
    if( typeof options == "object" ) {
        for (var key in options) {
            if(options.hasOwnProperty(key))
            {
                this[key] = options[key];
            }
        }
    }
    var formData = new FormData();
    formData.append('file',file);    //将文件转成二进制形式
    this.submit = function() {
        $.ajax({
            type: "post",
            url: url,
            async: false,
            contentType: false,    //这个一定要写
            processData: false, //这个也一定要写，不然会报错
            data: formData,
            dataType: 'text',    //返回类型，有json，text，HTML。这里并没有jsonp格式，所以别妄想能用jsonp做跨域了。
            headers:{
                "token":Cookies.get("token")
            },
            success: function (data) {
                data = JSON.parse( data );

                if( parseInt(data.error_code) == 3001 )
                {
                    alert("请重新登录");
                    window.location.href="/user/login.html";
                }
                else {
                    if (typeof data == "object") {
                        if (typeof data.token == "string" && data.token.length > 0) {
                            Cookies.set("token", data.token);
                        }
                    }

                    console.log(data);
                    self.onsuccess(data);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown, data) {
                self.onerror(XMLHttpRequest, textStatus, errorThrown, data);
            }
        });
    };

    return {
        submit: self.submit
    };
}
