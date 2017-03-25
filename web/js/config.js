/**
 * Created by yuyi on 17/3/20.
 */

/**
 * set process num and enable or disable debug mode
 *
 * @param dom
 */
function setRuntimeConfig(dom)
{
    //try lock , if it still running will return fail
    if (!Wing.lock())
        return;

    var c_item  = $(dom).parents(".c-item");
    var workers = c_item.find(".workers").val();
    var debug   = c_item.find(".debug").prop("checked")?1:0;

    var old_html = $(dom).html();
    $(dom).addClass("disable").html("Doing...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html(old_html);
        //unlock after 3 seconds timeout
        Wing.unlock();
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/runtime/config/save",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "workers"   : workers,
            "debug"     : debug
        },
        success:function(msg){
        }
    });
}

var set_notify_config_doing = false;
function setNotifyConfig(dom)
{
    if (set_notify_config_doing)
        return;

    set_notify_config_doing = true;
    var c_item  = $(dom).parents(".c-item");
    var _class  = c_item.find(".notify-class").val();
    var param1  = c_item.find(".param1").val();
    var param2  = c_item.find(".param2").val();

    $(dom).addClass("disable").html("正在更新...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("更新配置");
        set_notify_config_doing = false;
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/notify/config/save",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "class"     : _class,
            "param1"    : param1,
            "param2"    : param2
        },
        success:function(msg){
            // node_offline_doing = false;
            // $(dom).removeClass("disable");
        }
    });
}

function onNotifySelect(dom)
{
    var s = $(dom).find(":selected");
    var param1 = s.attr("data-param-1");
    var param2 = s.attr("data-param-2");

    var c = $(dom).parents(".c-item");
    c.find(".param1").val(param1);
    c.find(".param2").val(param2);
}

var set_local_redis_config_doing = false;
function setLocalRedisConfig(dom)
{
    if (set_local_redis_config_doing)
        return;

    set_local_redis_config_doing = true;
    var c_item    = $(dom).parents(".c-item");
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

    $(dom).addClass("disable").html("正在更新...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("更新配置");
        set_local_redis_config_doing = false;
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/local_redis/config/save",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "host"      : host,
            "port"      : port,
            "password"  : password
        },
        success:function(msg){
            // node_offline_doing = false;
            // $(dom).removeClass("disable");
        }
    });
}

var set_rabbitmq_config_doing = false;
function setRabbitmqConfig(dom)
{
    if (set_rabbitmq_config_doing)
        return;

    set_rabbitmq_config_doing = true;
    var c_item    = $(dom).parents(".c-item");
    var host      = c_item.find(".host").val();
    var user      = c_item.find(".user").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();
    var vhost     = c_item.find(".vhost").val();


    $(dom).addClass("disable").html("正在更新...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("更新配置");
        set_rabbitmq_config_doing = false;
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/rabbitmq/config/save",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "host"      : host,
            "port"      : port,
            "user"      : encodeURIComponent(user),
            "password"  : encodeURIComponent(password),
            "vhost"     : encodeURIComponent(vhost)
        },
        success:function(msg){
            // node_offline_doing = false;
            // $(dom).removeClass("disable");
        }
    });
}

var set_redis_config_doing = false;
function setRedisConfig(dom)
{
    if (set_redis_config_doing)
        return;

    set_redis_config_doing = true;
    var c_item    = $(dom).parents(".c-item");
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

    $(dom).addClass("disable").html("正在更新...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("更新配置");
        set_redis_config_doing = false;
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/redis/config/save",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "host"      : host,
            "port"      : port,
            "password"  : password
        },
        success:function(msg){
            // node_offline_doing = false;
            // $(dom).removeClass("disable");
        }
    });
}

var set_zookeeper_config_doing = false;
function setZookeeperConfig(dom)
{
    if (set_zookeeper_config_doing)
        return;

    set_zookeeper_config_doing = true;
    var c_item    = $(dom).parents(".c-item");
    var group_id  = c_item.find(".group_id").val();
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

    $(dom).addClass("disable").html("正在更新...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("更新配置");
        set_zookeeper_config_doing = false;
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/zookeeper/config/save",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "host"      : host,
            "port"      : port,
            "password"  : password
        },
        success:function(msg){
            // node_offline_doing = false;
            // $(dom).removeClass("disable");
        }
    });
}

var set_db_config_doing = false;
function setDbConfig(dom)
{
    if (set_db_config_doing)
        return;

    set_db_config_doing = true;
    var c_item    = $(dom).parents(".c-item");
    var db_name   = c_item.find(".db_name").val();
    var user      = c_item.find(".user").val();
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

    $(dom).addClass("disable").html("正在更新...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("更新配置");
        set_db_config_doing = false;
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/db/config/save",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "db_name"   : db_name,
            "user"      : user,
            "host"      : host,
            "port"      : port,
            "password"  : password
        },
        success:function(msg){
            // node_offline_doing = false;
            // $(dom).removeClass("disable");
        }
    });
}

