/**
 * Created by yuyi on 17/3/16.
 */
function nodeRefresh(group_id, session_id, callback)
{
    $.ajax({
        type : "POST",
        url  : "/service/node/refresh",
        data : {
            "group_id"  : group_id,
            "session_id": session_id
        },
        success:function(msg){
            callback(msg);
        }
    });
}
function nodeDown(dom)
{
    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");

    $.ajax({
        type :"POST",
        url  : "/service/node/down",
        data : {
            "group_id"  : group_id,
            "session_id": session_id
        },
        success:function(msg){

        }
    });
}

function refresh()
{
    window.location.reload();
}
$(document).ready(function(){
    window.setInterval(function(){
        $(".nodes-list .node").each(function(i,v){
            var group_id   = $(v).attr("data-group-id");
            var session_id = $(v).attr("data-session-id");
            nodeRefresh(group_id, session_id, function(msg){
                //{"enable":"1","is_leader":1,"last_updated":"1489651673","last_binlog":"mysql-bin.000031","last_pos":"154","is_down":0}
                var data   = eval("("+msg+")");

                if (data.enable == 1) {
                    $(v).find(".is-enable").html("启用");
                } else {
                    $(v).find(".is-enable").html("禁用");
                }

                if (data.is_leader == 1) {
                    $(v).find(".is-leader").html("是");
                } else {
                    $(v).find(".is-leader").html("否");
                }

                //mysql-bin.000031 => 154
                $(v).find(".last-pos").html(data.last_binlog+" => "+data.last_pos);
                if (data.last_updated > 10 && data.last_updated < 20) {
                    $(v).css("background","#f00");
                }
                if (data.last_updated >= 20 && data.last_updated < 86400)
                    $(v).hide();
                else
                    $(v).show()

            });
        });
    },1000);
});