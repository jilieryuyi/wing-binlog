/**
 * Created by yuyi on 17/3/20.
 */
var set_runtime_config_doing = false;
function setRuntimeConfig(dom)
{
    if (set_runtime_config_doing)
        return;

    set_runtime_config_doing = true;
    var c_item  = $(dom).parents(".c-item");
    var workers = c_item.find(".workers").val();
    var debug   = c_item.find(".debug").prop("checked")?1:0;

    $(dom).addClass("disable").html("正在更新...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("更新配置");
        set_runtime_config_doing = false;
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
            // node_offline_doing = false;
            // $(dom).removeClass("disable");
        }
    });
}