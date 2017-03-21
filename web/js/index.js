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
            if (typeof data.error_code != "undefined" && data.error_code == 4000) {
                $(".login-timeout").show();
                return;
            }

            if (data.is_leader == 1) {
                $(v).find(".last-pos").html(data.last_binlog+" => "+data.last_pos);
                $(v).find(".is-leader").html("是");
            } else {
                $(v).find(".last-pos").html('');
                $(v).find(".is-leader").html("否");
            }

            $(v).find(".time-len").html(data.time_len);
            $(v).find(".set-offline").attr("data-is_offline", data.is_offline);
            $(v).find(".start-time").html(data.created);

            if (data.is_offline) {
                $(v).find(".online-status").children("img").attr("src", "images/offline.png").attr("title", "已下线");
                $(v).find(".set-offline").html("上线");
            } else {
                $(v).find(".online-status").children("img").attr("src", "images/online.png").attr("title", "在线");
                $(v).find(".set-offline").html("下线");
            }

            $(v).find(".version").html(data.version);
            $(v).css("background","#fff");
            //mysql-bin.000031 => 154
            if (data.last_updated > 10 && data.last_updated < 20) {
                $(v).css("background","#f00");
            }

            if (data.last_updated >= 20 )
                $(v).addClass("hide");
            else
                $(v).removeClass("hide");

            $(".open-generallog").attr("data-open",data.generallog);
            if (parseInt(data.generallog) == 1) {
                $(".open-generallog").html("关闭generallog");
            } else {
                $(".open-generallog").html("开启generallog");
            }
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
 * offline a node
 *
 * @param dom
 * @return void
 */
var node_offline_doing = false;
function nodeOffline(dom)
{

    if (node_offline_doing) {
        return;
    }

    node_offline_doing = true;

    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");
    var is_offline = parseInt($(dom).attr("data-is_offline")) == 1 ? 0 : 1;

    $(dom).html("正在操作...").addClass("disable");
    window.setTimeout(function(){
        $(dom).html("下线");
        node_offline_doing = false;
        $(dom).removeClass("disable")
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/node/offline",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "is_offline": is_offline
        },
        success:function(msg){
        }
    });
}

var open_generallog_doing = false;
function openGenerallog(dom)
{

    if (open_generallog_doing) {
        return;
    }

    open_generallog_doing = true;

    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");
    var open       = $(dom).attr("data-open") == "1" ? 0 : 1;

    $(dom).html("正在操作...").addClass("disable");
    window.setTimeout(function(){
        if (open == 0)
            $(dom).html("开启generallog");
        else
            $(dom).html("关闭generallog");
        open_generallog_doing = false;
        $(dom).removeClass("disable")
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/generallog/open",
        data : {
            "group_id"  : group_id,
            "session_id": session_id,
            "open"      : open
        },
        success:function(msg){
        }
    });
}

var open_group_generallog_doing = false;
function openGroupGenerallog(dom, open)
{

        if (open_group_generallog_doing) {
            return;
        }

        open_group_generallog_doing = true;

        var group_id   = $(dom).attr("data-group-id");

        var old_html = $(dom).html();
        $(dom).html("正在操作...").addClass("disable");
        window.setTimeout(function(){
                $(dom).html(old_html);
            open_group_generallog_doing = false;
            $(dom).removeClass("disable")
        },3000);

        $.ajax({
            type :"POST",
            url  : "/service/group/generallog/open",
            data : {
                "group_id"  : group_id,
                "open"      : open
            },
            success:function(msg){
            }
        });
}



var group_offline_doing = false;
function groupOffline(dom, is_offline)
{

    if (group_offline_doing) {
        return;
    }

    group_offline_doing = true;

    var group_id   = $(dom).attr("data-group-id");

    $(dom).html("正在操作...").addClass("disable");
    window.setTimeout(function(){
        if (is_offline == 1)
            $(dom).html("下线");
        else
            $(dom).html("上线");
        group_offline_doing = false;
        $(dom).removeClass("disable")
    },3000);

    $.ajax({
        type :"POST",
        url  : "/service/group/offline",
        data : {
            "group_id"   : group_id,
            "is_offline" : is_offline
        },
        success:function(msg){
        }
    });
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
        '<li ' +
            'class="node node-'+session_id+'" '+
            'data-group-id="'+group_id+'" '+
            'data-session-id="'+session_id+'" '+
            '>'+
            '<div>'+
                '<span class="node-id" title="'+session_id+'">' +
                    '<label class="index">'+index+'</label>、'+session_id+
                '</span>'+
                '<span class="online-status">';
                    if (parseInt(node.is_offline) == 1) {
                        html += '<img title="在线" src="images/online.png"/>';
                    } else {
                        html += '<img title="已下线" src="images/offline.png"/>';
                    }
                    html +=
                '</span>'+
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
            '</div>' +
            '<div>'+
                '<span class="edit">'+
                    '<a ' +
                        'class="bg-normal" ' +
                        'style="margin-left: 0;" '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'onclick="nodeConfig(this)" >配置</a>'+
                    '<a ' +
                        'class="bg-red set-offline" ' +
                        'title="仅运行时有效，重启后失效。' +
                                '节点下线之后将停止一切采集业务，' +
                                '也不会被分配为leader，可以随时恢复上线" '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'data-is_offline="'+node.is_offline+'" '+
                        'onclick="nodeOffline(this)" >';
                        if (parseInt(node.is_offline) == 1) {
                            html += '上线';
                        } else {
                            html += '下线';
                        }
                        html +='</a>'+
                    '<a ' +
                        'class="bg-normal"  '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'onclick="" >报表</a>'+

                    '<a class="bg-red"  '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'onclick="nodeRestart(this)" >重启</a>'+

                    '<a class="bg-normal" ' +
                        'title="composer update && ' +
                            'git pull origin master&& ' +
                            'php seals server:restart"  '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'onclick="nodeUpdate(this)" >更新</a>';

     {
        html += '<a class="bg-normal open-generallog" ' +
            'data-group-id="' + group_id + '" ' +
            'data-session-id="' + session_id + '" ' +
            'data-open="' + node.generallog + '" ' +

            'onclick="openGenerallog(this)" >';
         if (parseInt(node.generallog) != 1)
             html+= '开启generallog';
         else
             html+= '关闭generallog';
         html+='</a>';
    }
                    html+='<label class="error-info"></label>'+
                '</span>' +
            '</div>'+
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
        '<li class="group group-'+group_id+'">'+
            '<div class="item">'+
                '<span class="group-id">'+group_id+'</span>'+
                '<span class="node-count">'+length+'</span>'+
                '<span class="group-edit edit">' +
                    '<a class="bg-normal" href="group.config.php?group_id='+group_id+'" style="margin-left: 0;">配置</a>' +
                    '<a ' +
                    'class="bg-red set-offline" ' +
                    'title="下线整个群组！仅运行时有效，重启后失效。' +
                    '节点下线之后将停止一切采集业务，' +
                    '也不会被分配为leader，可以随时恢复上线" '+
                    'data-group-id="'+group_id+'" ' +
                    'onclick="groupOffline(this,1)">下线</a>'+

                    '<a ' +
                    'class="bg-normal set-offline" ' +
                    'title="上线整个群组" '+
                    'data-group-id="'+group_id+'" ' +
                    'onclick="groupOffline(this,0)">上线</a>'+
                    '<a ' +
                    'class="bg-normal"  '+
                    'data-group-id="'+group_id+'">报表</a>'+

                    '<a title="重启整个群组" class="bg-red"  '+
                    'data-group-id="'+group_id+'">重启</a>'+

                    '<a title="更新整个群组" class="bg-normal" ' +
                    'title="composer update && ' +
                    'git pull origin master&& ' +
                    'php seals server:restart"  '+
                    'data-group-id="'+group_id+'" >更新</a>'+

         '<a class="bg-normal" ' +
            'data-group-id="' + group_id + '" ' +
            'onclick="openGroupGenerallog(this,1)" >开启generallog</a>'+

        '<a class="bg-red" ' +
        'data-group-id="' + group_id + '" ' +
        'onclick="openGroupGenerallog(this,0)" >关闭generallog</a>'+

                    '<label class="error-info"></label>'+
                '</span>'+
            '</div>'+
            '<ul class="nodes-list">';
                if (length > 0) {
                html +=
                '<li class="title" style="height: 25px;">' +
                    '<span class="node-id">节点</span>' +
                    '<span class="online-status">状态</span>'+
                    '<span class="is-leader">leader</span>' +
                    '<span class="last-pos">最后读取</span>' +
                    '<span class="version">版本号</span>' +
                    '<span class="start-time">启动时间</span>' +
                    '<span class="time-len">运行时长</span>' +
                '</li>';
                }

            html +=
            '</ul>';

    $(".groups").append(html);

    for (var session_id in nodes) {
        if (!nodes.hasOwnProperty(session_id)) {
            continue;
        }
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

/**
 * restart node
 */
var node_restart_doing = false;
function nodeRestart(dom)
{
    if (node_restart_doing)
        return;

    node_restart_doing = true;

    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");

    $(dom).html("正在重启...").addClass("disable");

    window.setTimeout(function(){
        node_restart_doing = false;
        $(dom).removeClass("disable");
    },3000);

    $.ajax({
        type: "POST",
        url : "/service/node/restart",
        data : {
            "group_id"  : group_id,
            "session_id": session_id
        },
        success:function(msg){
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
        getAllServices(function(msg){
            var data = JSON.parse(msg);
            for (var group_id in data) {
                if (!data.hasOwnProperty(group_id)) {
                    continue;
                }
                if ($(".group-"+group_id).length <= 0) {
                    //如果群组不存在，新增的，追加到列表
                    appendGroup(group_id, data[group_id]);
                }
                for (var session_id in data[group_id]) {
                    if (!data[group_id].hasOwnProperty(session_id)) {
                        continue;
                    }
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