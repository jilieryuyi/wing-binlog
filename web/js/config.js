/**
 * Created by yuyi on 17/3/20.
 */
function setRuntimeConfig(dom)
{
    var c_item  = $(dom).parents(".c-item");
    var workers = c_item.find(".workers").val();
    var debug   = c_item.find(".debug").prop("checked")?1:0;
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