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

    showDoing(dom);

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

function setNotifyConfig(dom)
{
    if (!Wing.lock())
        return;

    var c_item  = $(dom).parents(".c-item");
    var _class  = c_item.find(".notify-class").val();
    var param1  = c_item.find(".param1").val();
    var param2  = c_item.find(".param2").val();

    showDoing(dom);
    console.log(_class, param1, param2);
    //return;

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

    $(".data-target-config").hide();
    var _class = s.attr("data-config-class");
    $("."+_class).show();
}

function setLocalRedisConfig(dom)
{
    if (!Wing.lock())
        return;

    var c_item    = $(dom).parents(".c-item");
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

    showDoing(dom);

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

function setRabbitmqConfig(dom)
{
    if (Wing.lock())
        return;

    var c_item    = $(dom).parents(".c-item");
    var host      = c_item.find(".host").val();
    var user      = c_item.find(".user").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();
    var vhost     = c_item.find(".vhost").val();


    showDoing(dom);

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

function setRedisConfig(dom)
{
    if (!Wing.lock())
        return;

    var c_item    = $(dom).parents(".c-item");
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

    showDoing(dom);

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
        }
    });
}

function setZookeeperConfig(dom)
{
    if (!Wing.lock())
        return;

    var c_item    = $(dom).parents(".c-item");
    var group_id  = c_item.find(".group_id").val();
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

   showDoing(dom);

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

function setDbConfig(dom)
{
    if (!Wing.lock())
        return;

    var c_item    = $(dom).parents(".c-item");
    var db_name   = c_item.find(".db_name").val();
    var user      = c_item.find(".user").val();
    var host      = c_item.find(".host").val();
    var port      = c_item.find(".port").val();
    var password  = c_item.find(".password").val();

    showDoing(dom);

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
        }
    });
}

