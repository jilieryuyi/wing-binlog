/**
 * Created by yuyi on 17/3/16.
 */

/**
 * refresh node
 *
 * @param v
 * @param group_id
 * @param session_id
 * @return void
 */
function nodeRefresh(v, group_id, session_id)
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
            //console.log(msg);
            var data   = JSON.parse(msg);

            if (data.is_leader == 1) {
                $(v).find(".last-pos").html(data.last_binlog+" => "+data.last_pos);
                $(v).find(".is-leader").html("是");
            } else {
                $(v).find(".last-pos").html('');
                $(v).find(".is-leader").html("否");
            }

            $(v).find(".time-len").html(data.time_len);

            $(v).css("background","#fff");
            //mysql-bin.000031 => 154
            if (data.last_updated > 10 && data.last_updated < 20) {
                $(v).css("background","#f00");
            }

            if (data.last_updated >= 20 )
                $(v).addClass("hide");
            else
                $(v).removeClass("hide");

            var index = 1;
            $(".nodes-list .node").each(function(){
                if (!$(this).hasClass("hide")) {
                    $(this).find(".index").html(index);
                    index++;
                }
            });
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
            '<div>'+
        '<span class="node-id" title="'+session_id+'"><label class="index">'+index+'</label>、'+session_id+'</span>'+
        '<span class="is-leader">';
    if (parseInt(node.is_leader) == 1) {
        last_read = node.last_binlog+" => "+node.last_pos;
        html += "是";
    } else {
        html += "否";
    }
    html +=
        '</span>'+
        '<span class="last-pos">';
    if (parseInt(node.is_leader) == 1) {
        html += node.last_binlog+" => "+node.last_pos;
    } else {
        html += "";
    }
        html +=
            '</span>'+
            '<span class="version">'+node.version+'</span>' +

            '<span class="start-time">'+node.created+'</span>' +
            '<span class="time-len">'+node.time_len+'</span>' +
            '</div><div>'+
            '<span class="edit">'+
            '<a class="bg-normal" style="margin-left: 0;" '+
            'data-group-id="'+group_id+'" '+
            'data-session-id="'+session_id+'" '+
            'onclick="nodeConfig(this)" >配置</a>'+
            '<a class="bg-red" '+
            'data-group-id="'+group_id+'" '+
            'data-session-id="'+session_id+'" '+
            'onclick="nodeDown(this)" >下线</a>'+
            '<a class="bg-normal"  '+
            'data-group-id="'+group_id+'" '+
            'data-session-id="'+session_id+'" '+
            'onclick="" >报表</a>'+

            '<a class="bg-red"  '+
            'data-group-id="'+group_id+'" '+
            'data-session-id="'+session_id+'" '+
            'onclick="nodeRestart(this)" >重启</a>'+

            '<a class="bg-normal" title="composer update && git pull origin master&& php seals server:restart"  '+
            'data-group-id="'+group_id+'" '+
            'data-session-id="'+session_id+'" '+
            'onclick="nodeUpdate(this)" >更新</a>'+
            '<label class="error-info"></label>'+
            '</span></div>'+
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
    //console.log(nodes);
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
        html += '<li class="title" style="height: 25px;">' +
            '<span class="node-id">节点</span>' +
            '<span class="is-leader">leader</span>' +
            '<span class="last-pos">最后读取</span>' +
            '<span class="version">版本号</span>' +

            '<span class="start-time">启动时间</span>' +
            '<span class="time-len">运行时长</span>' +
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

/**
 * jump to node config page
 */
function nodeConfig(dom)
{
    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");

    window.location.href="node.config.php?group_id="+group_id+"&session_id="+session_id;
}

function nodeRestart(dom)
{
    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");
    $(dom).html("正在重启...");
    $.ajax({
        type: "POST",
        url : "/service/node/restart",
        data : {
            "group_id"  : group_id,
            "session_id": session_id
        },
        success:function(msg){
            //var data = JSON.parse(msg);
        }
    });

    var error = $(dom).parent().find(".error-info");
    window.setTimeout(function(){
        $(dom).html("重启");
        error.html("重启成功，右边的运行时长会发生明显变化").show();
        window.setTimeout(function(){
            error.hide("slow").html("");
        },5000);
    },2000);

}

function nodeUpdate(dom)
{
    if (!window.confirm("确定更新？更新时间可能比较长一些，还请耐心等待~"))
        return;
    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");
    $(dom).html("正在更新...");
    $.ajax({
        type: "POST",
        url : "/service/node/update",
        data : {
            "group_id"  : group_id,
            "session_id": session_id
        },
        success:function(msg){
            //var data = JSON.parse(msg);
        }
    });

    var error = $(dom).parent().find(".error-info");
    window.setTimeout(function(){
        $(dom).html("更新");
        error.html("更新成功后会重启，右边的运行时长会发生明显变化").show();
        window.setTimeout(function(){
            error.hide("slow").html("");
        },5000);
    },2000);

}

$(document).ready(function(){
    window.setInterval(function(){
        //refresh list
        getAllServices(function(msg){
            var data = JSON.parse(msg);
            //console.log(data);
            for (var group_id in data) {
                if (!data.hasOwnProperty(group_id))
                    continue;
                //console.log(group_id);
                if ($(".group-"+group_id).length <= 0) {
                    //如果群组不存在，新增的，追加到列表
                    appendGroup(group_id, data[group_id]);
                }
                //console.log(data[group_id]);
                for (var session_id in data[group_id]) {
                    if (!data[group_id].hasOwnProperty(session_id))
                        continue;
                    //console.log(session_id);
                    if ($(".node-"+session_id).length <= 0) {
                        appendNode(group_id, session_id, data[group_id][session_id]);
                    }
                }
            }
        });
        $(".nodes-list .node").each(function(i,v){
            var group_id   = $(v).attr("data-group-id");
            var session_id = $(v).attr("data-session-id");
            nodeRefresh(v, group_id, session_id);
        });
    },1000);
});