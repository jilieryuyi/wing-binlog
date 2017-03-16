/**
 * Created by yuyi on 17/3/16.
 */

/**
 * refresh node
 *
 * @param group_id
 * @param session_id
 * @return void
 */
function nodeRefresh(group_id, session_id)
{
    $.ajax({
        type : "POST",
        url  : "/service/node/refresh",
        data : {
            "group_id"  : group_id,
            "session_id": session_id
        },
        success:function(msg){
            if (msg == "")
                return;
            console.log(msg);
            var data   = JSON.parse(msg);

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
                $(v).show();
        }
    });
}

/**
 * just down node
 *
 * @param dom
 * @return void
 */
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

/**
 * page refresh
 */
function refresh()
{
    window.location.reload();
}


/**
 * get all services
 *
 * @param callback
 */
function getAllServices(callback) {
    $.ajax({
        type :"POST",
        url  : "/service/all",
        success:function(msg){
            callback(msg);
        }
    });
}

/**
 * get object length
 *
 * @param obj
 * @return int
 */
function count(obj)
{
    var c = 0;
    for (var k in obj)
        c++;
    return c;
}

/**
 * append node to list
 *
 * @param group_id
 * @param session_id
 * @param node
 */
function appendNode(group_id, session_id, node)
{
    var index = $(".group-"+group_id+" li").length;
    var html =
        '<li class="node node-'+session_id+'" '+
        'data-group-id="'+group_id+'" '+
        'data-session-id="'+session_id+'" '+
        '>'+
        '<span class="node-id" title="'+session_id+'">'+index+'、'+session_id+'</span>'+
        '<span class="is-enable">';

    if (parseInt(node.enable) == 1) {
        html += "启用";
    } else {
        html += "禁用";
    }
    html +=
        '</span>'+
        '<span class="is-leader">';
    if (parseInt(node.is_leader) == 1) {
        html += "是";
    } else {
        html += "否";
    }
    html +=
        '</span>'+
        '<span class="last-pos">'+node.last_binlog+" => "+node.last_pos+'</span>'+
        '<span class="edit">'+
        '<a '+
        'data-group-id="'+group_id+'" '+
        'data-session-id="'+session_id+'" '+
        'onclick="nodeDown(this)" >下线</a>'+
        '</span>'+
        '</li>';

    $(".group-"+group_id+ " ul").append(html);
}

/**
 * append group
 *
 * @param group_id
 * @param nodes
 */
function appendGroup(group_id, nodes)
{
    console.log(nodes);
    var length = count(nodes);



    var html   =
        '<li class="group-'+group_id+'">'+
            '<div class="item">'+
                '<span class="group-id">'+group_id+'</span>'+
                '<span class="node-count">'+length+'</span>'+
                '<span class="group-edit edit"><a>配置</a></span>'+
            '</div>'+
            '<ul class="nodes-list">';


    if (length > 0) {
        html += '<li class="title">' +
            '<span class="node-id">节点</span>' +
            '<span class="is-enable">启用群组</span>' +
            '<span class="is-leader">leader</span>' +
            '<span class="last-pos">最后读取</span>' +
            '<span class="group-edit edit">操作</span>' +
            '</li>';
    }

    html += '</ul>';
    $(".groups").append(html);

    for (var session_id in nodes) {
        if (!nodes.hasOwnProperty(session_id))
            continue;
        var node = nodes[session_id];
        appendNode(group_id, session_id, node);
    }

}


$(document).ready(function(){
    window.setInterval(function(){
        //refresh list
        getAllServices(function(msg){
            var data = JSON.parse(msg);
            console.log(data);
            for (var group_id in data) {
                if (!data.hasOwnProperty(group_id))
                    continue;
                console.log(group_id);
                if ($(".group-"+group_id).length <= 0) {
                    //如果群组不存在，新增的，追加到列表
                    appendGroup(group_id, data[group_id]);
                }
                console.log(data[group_id]);
                for (var session_id in data[group_id]) {
                    if (!data[group_id].hasOwnProperty(session_id))
                        continue;
                    console.log(session_id);
                    if ($(".node-"+session_id).length <= 0) {
                        appendNode(group_id, data[group_id][session_id]);
                    }
                }
            }
        });
        $(".nodes-list .node").each(function(i,v){
            var group_id   = $(v).attr("data-group-id");
            var session_id = $(v).attr("data-session-id");
            nodeRefresh(group_id, session_id);
        });
    },1000);
});