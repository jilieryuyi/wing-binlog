var im={
    my:null,
    socket:null,
    online:false,
    msg_count:0,
    showStatus:function(str){
        $(".client-status").html(str);
    },
    send:function(){

    },
    sendMessage:function(sendto,msg){
        this.log("\nsend msg==>sendto:"+sendto+"\nmsg:"+msg);
        var msg_id = sendto+"-"+(new Date().getTime());
        var _msg='{"service":"sendMessage","to":"'+sendto+'","msg":"'+encodeURIComponent(msg)+'","msg_id":"'+msg_id+'"}';
        console.log("send msg:"+_msg);
        $(".msg-win").append(
            '<div msg-id="'+msg_id+'" class="msg-list" style="bottom:'+(this.msg_count*30)+'px;text-align: right;">'+
            '<div class="msg-time">'+window.date.getTime()+'</div>'+
            '<div class="msg-from">my</div>'+
            '<div class="msg-content">'+
            this.msg_count+"=>"+ msg+
            '</div>'+
            '</div>');
        this.msg_count++;
        $(".msg-win").scrollTop(999999);
        return this.socket.send(_msg);
    },
    login:function(username,password){
        var _msg='{"service":"login","username":"'+username+'","password":"'+password+'"}';
        return this.socket.send(_msg);
    },
    onDisConnect:function(){
        this.log("onDisConnect");
        this.online=false;
        this.showStatus("离线");
    },
    onConnect:function(){
        this.log("onConnect");
        this.online=true;
        this.showStatus("在线");
    },
    onLogin:function(status,err_msg,user_id){
        this.log("\nlogin==>status:"+status+"\nerr_msg:"+err_msg+"\nuser_id:"+user_id);
        if(status==1000)
        {
            //登陆成功
            this.my=user_id;
            //this.onMessage("system","system","login success");
            im.onConnect();
            im.online=true;
        }
        else{
            //登陆失败 用户名或者密码错误
            alert(err_msg);
        }
    },
    onMessage:function(msg){

        this.log(msg);
        $(".msg-win").append(
            '<div class="msg-list" style="bottom:'+(this.msg_count*30)+'px;">'+
            '<div class="msg-time">'+window.date.getTime()+'</div>'+
            '<div class="msg-from">sys</div>'+
            '<div class="msg-content">'+
            this.msg_count+"=>"+ decodeURIComponent(msg)+
            '</div>'+
            '</div>');
        this.msg_count++;
        $(".msg-win").scrollTop(999999);
    },
    log:function(content){

    },
    onSendError:function(msg){
       /* console.log("send error",msg);
        $(".msg-list").each(function(i,v){
            if($(v).attr("msg-id")==msg.msg_id)
            $(v).css("background","#f00");
        });*/
    }
};


$(document).ready(function(){
    $(".send-bth").click(function(){
        im.sendMessage(1,$(".send-msg").html());
        $(".send-msg").html("");
    });
});



function start_service(){
    var ws = new WebSocket("ws://127.0.0.1:9998");
    im.socket = ws;

    ws.onopen = function() {
        im.online = 1;
        var _msg='{"service":"login","username":"root2","password":"123456"}';
        ws.send(_msg);
    };

    var message_temp = [];
    ws.onmessage = function(e) {
        im.onMessage(e.data);
    };
    ws.onclose=function(){
        im.onDisConnect();
    }
}


start_service();

window.setInterval(function(){
    if(!im.online){
        start_service();
    }
},5000);
