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
                window.location.href="/login.php";
                $(".login-timeout").show();
                return;
            } else {
                $(".login-timeout").hide();
            }
            if (typeof data.error_code != "undefined") {
                return;
            }

            if (data.is_leader == 1) {
                $(v).find(".last-pos").html(data.last_binlog+" => "+data.last_pos);
                $(v).find(".is-leader").html("Yes");
            } else {
                $(v).find(".last-pos").html('');
                $(v).find(".is-leader").html("No");
            }

            $(v).find(".time-len").html(data.time_len);
            $(v).next("tr").find(".set-offline").attr("data-is_offline", data.is_offline);
            $(v).find(".start-time").html(data.created);

            if (data.is_offline) {
                $(v).find(".online-status").children("img").attr("src", "images/offline.png").attr("title", "Offline");
                $(v).next("tr").find(".set-offline").html("Online")
                    .removeClass("bg-red").addClass("btn-success");
            } else {
                $(v).find(".online-status").children("img").attr("src", "images/online.png").attr("title", "Online");
                $(v).next("tr").find(".set-offline").html("Offline")
                    .removeClass("btn-success").addClass("bg-red");
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
function nodeOffline(dom)
{

    if (!Wing.lock()) {
        return;
    }

    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");
    var is_offline = $(dom).attr("data-is_offline") == "1" ? 0 : 1;

    $(dom).html("Offline...").addClass("disable");
    window.setTimeout(function(){
        $(dom).html("Offline");
        $(dom).removeClass("disable");
        Wing.unlock();
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

// function openGenerallog(dom)
// {
//
//     if (!Wing.lock()) {
//         return;
//     }
//
//     var group_id   = $(dom).attr("data-group-id");
//     var session_id = $(dom).attr("data-session-id");
//     var open       = $(dom).attr("data-open") == "1" ? 0 : 1;
//
//     $(dom).html("Doing...").addClass("disable");
//     window.setTimeout(function(){
//         if (open == 0)
//             $(dom).html("Enable General Log");
//         else
//             $(dom).html("Disable General Log");
//         $(dom).removeClass("disable");
//         Wing.unlock();
//     },3000);
//
//     $.ajax({
//         type :"POST",
//         url  : "/service/node/generallog/open",
//         data : {
//             "group_id"  : group_id,
//             "session_id": session_id,
//             "open"      : open
//         },
//         success:function(msg){
//         }
//     });
// }

// function openGroupGenerallog(dom, open)
// {
//
//         if (!Wing.lock()) {
//             return;
//         }
//
//         var group_id   = $(dom).attr("data-group-id");
//         var old_html   = $(dom).html();
//
//         $(dom).html("Doing...").addClass("disable");
//         window.setTimeout(function(){
//             $(dom).html(old_html);
//             $(dom).removeClass("disable");
//             Wing.unlock();
//         },3000);
//
//         $.ajax({
//             type :"POST",
//             url  : "/service/group/generallog/open",
//             data : {
//                 "group_id"  : group_id,
//                 "open"      : open
//             },
//             success:function(msg){
//             }
//         });
// }

function groupOffline(dom, is_offline)
{

    if (!Wing.lock()) {
        return;
    }

    var group_id   = $(dom).attr("data-group-id");

    $(dom).html("Doing...").addClass("disable");
    window.setTimeout(function(){
        if (is_offline == 1)
            $(dom).html("Offline");
        else
            $(dom).html("Online");
        $(dom).removeClass("disable");
        Wing.unlock();
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
            var data=JSON.parse(msg);
            if (typeof data.error_code != "undefined" && data.error_code == 4000) {
                window.location.href="/login.php";
                $(".login-timeout").show();
                return;
            }
            if (typeof data.error_code != "undefined") {
                return;
            }
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
    var index = $(".group-"+group_id+" li").length+1;

    var html =
        '<tr ' +
            'class="node node-'+session_id+'" '+
            'data-group-id="'+group_id+'" '+
            'data-session-id="'+session_id+'" '+
            '>'+
                '<td class="node-id" title="'+session_id+'">' +
                    '<label class="index">'+index+'</label>、<a href="node.info.php?session_id='+session_id+'">'+session_id+'</a><br/>'+
                    node.ip+
                '</td>'+
                '<td class="online-status">';
                    if (parseInt(node.is_offline) == 1) {
                        html += '<img title="online" src="images/online.png"/>';
                    } else {
                        html += '<img title="offline" src="images/offline.png"/>';
                    }
                    html +=
                '</td>'+
                '<td class="is-leader">';
                    if (parseInt(node.is_leader) == 1) {
                        last_read = node.last_binlog+" => "+node.last_pos;
                        html += "Yes";
                    } else {
                        html += "No";
                    }
                    html +=
                '</td>'+
            //     '<td class="generallog">';
            // if (parseInt(node.generallog) == 1) {
            //     html += "Enable";
            // } else {
            //     html += "Disable";
            // }
            // html +=
            //     '</td>'+
                '<td class="last-pos">';
                    if (parseInt(node.is_leader) == 1) {
                        html += node.last_binlog+" => "+node.last_pos;
                    } else {
                        html += "";
                    }
                    html +=
                '</td>'+
                '<td class="version">'+node.version+'</td>' +
                '<td class="start-time">'+node.created+'</td>' +
                '<td class="time-len">'+node.time_len+'</td>' +
            '</tr>' +
            '<tr>'+
                '<td class="edit" colspan="8">'+
                    '<a ' +
                        'class="btn btn-success" ' +
                        'style="margin-left: 0;" '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'href="node.config.php?group_id='+group_id+'&session_id='+session_id+'" >Configure</a>'+
                    '<a ' +
                        'class="btn bg-red set-offline" ' +
                        'title="Offline the current node, only use for runtime" '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'data-is_offline="'+node.is_offline+'" '+
                        'onclick="nodeOffline(this)" >';
                        if (parseInt(node.is_offline) == 1) {
                            html += 'Online';
                        } else {
                            html += 'Offline';
                        }
                        html +='</a>'+
                    '<a ' +
                        'class="btn btn-success"  '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'href="report.php?group_id='+group_id+'&session_id='+session_id+'" >Report</a>'+

                            '<a ' +
                            'class="btn btn-success"  '+
                            'data-group-id="'+group_id+'" '+
                            'data-session-id="'+session_id+'" '+
                            'href="node.info.php?session_id='+session_id+'" >Server Detail</a>'+
                            '<a ' +
                            'class="btn btn-success"  '+
                            'data-group-id="'+group_id+'" '+
                            'data-session-id="'+session_id+'" '+
                            'href="logs.php?session_id='+session_id+'" >Logs</a>'+


                            '<a class="btn bg-red"  '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'onclick="nodeRestart(this)" >Restart</a>'+

                    '<a class="btn btn-success update-btn" ' +
                        'title="git pull origin master&& ' +
                            'php seals server:restart"  '+
                        'data-group-id="'+group_id+'" '+
                        'data-session-id="'+session_id+'" '+
                        'onclick="nodeUpdate(this)" >Update';
                    if (node.update == 1)
                     html +='<label>1</label>';

                    html+='</a>';

                    // html += '<a class="btn btn-success open-generallog" ' +
                    //     'data-group-id="' + group_id + '" ' +
                    //     'data-session-id="' + session_id + '" ' +
                    //     'data-open="' + node.generallog + '" ' +
                    //
                    //     'onclick="openGenerallog(this)" >';
                    //  if (parseInt(node.generallog) != 1)
                    //      html+= 'Enable General Log';
                    //  else
                    //      html+= 'Disable General Log';
                    // html+='</a>';
                    html+='<label class="error-info"></label>'+
                '</span>' +
            '</tr>';

    $(".group-"+group_id+ " table").append(html);
}

/**
 * append group
 *
 * @param group_id
 * @param nodes
 */
function appendGroup(group_id, nodes)
{
    var length = count(nodes);

    var html   =
        '<li class="group group-'+group_id+'">'+
            '<div class="item">'+
                '<span class="group-id col-md-2">'+group_id+'</span>'+
                '<span class="node-count col-md-2">'+length+'</span>'+
                '<span class="group-edit edit col-md-8">' +
                    '<a class="btn btn-success" href="group.config.php?group_id='+group_id+'" style="margin-left: 0;">Configure</a>' +
                    '<a ' +
                    'class="btn bg-red set-offline" ' +
                    'title="Offline all the nodes in the group, only use for runtime" '+
                    'data-group-id="'+group_id+'" ' +
                    'onclick="groupOffline(this,1)">Offline</a>'+

                    '<a ' +
                    'class="btn btn-success set-offline" ' +
                    'title="Online all the nodes in the group" '+
                    'data-group-id="'+group_id+'" ' +
                    'onclick="groupOffline(this,0)">Online</a>'+

                    '<a title="Restart all the nodes in the group" ' +
                    'class="btn bg-red"  '+
                    'data-group-id="'+group_id+'" ' +
                    'onclick="groupRestart(this)">Restart</a>'+

                    '<a title="Update all the nodes in the group" class="btn btn-success" ' +
                    'title="composer update && ' +
                    'git pull origin master&& ' +
                    'php seals server:restart"  '+
                    'data-group-id="'+group_id+'" onclick="groupRestart(this)" >Update</a>'+

         // '<a class="btn btn-success" title="Enable general log all the nodes in the group" ' +
         //    'data-group-id="' + group_id + '" ' +
         //    'onclick="openGroupGenerallog(this,1)" >Enable General Log</a>'+

        // '<a class="btn bg-red" ' +
        // 'data-group-id="' + group_id + '" title="Disable general log all the nodes in the group" ' +
        // 'onclick="openGroupGenerallog(this,0)" >Disable General Log</a>'+

                    '<label class="error-info"></label>'+
                '</span>'+
            '</div>'+
            '<table class="nodes-list table table-striped"><thead><tr>';
                if (length > 0) {
                html +=
                    '<th class="node-id">Node</th>' +
                    '<th class="online-status">Status</th>'+
                    '<th class="is-leader">Leader</th>' +
                    // '<th class="generallog">General Log</th>' +
                    '<th class="last-pos">Last Read</th>' +
                    '<th class="version">Version</th>' +
                    '<th class="start-time">Start Time</th>' +
                    '<th class="time-len">Running Time</th>';// +
                }

            html +=
            '</tr></thead></table>';

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
 * restart node
 */
function nodeRestart(dom)
{
    if (!Wing.lock())
        return;

    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");

    $(dom).html("Restart...").addClass("disable");

    window.setTimeout(function(){
        $(dom).removeClass("disable");
        var error = $(dom).parent().find(".error-info");
        $(dom).html("Restart");
        error.html("Restart successfully, the right running time will change significantly").show();
        Wing.unlock();
        window.setTimeout(function(){
            error.hide("slow").html("");
        },5000);
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
}

function nodeUpdate(dom)
{
    if (!Wing.lock()) {
        return;
    }
    if (!window.confirm("Sure update？Update maybe take a long time, be patience~"))
        return;

    var group_id   = $(dom).attr("data-group-id");
    var session_id = $(dom).attr("data-session-id");

    $(dom).html("Update...").addClass("disable");

    window.setTimeout(function(){
        $(dom).html("Update").removeClass("disable");
        var error = $(dom).parent().find(".error-info");
        error.html("Update successfully, the right running time will change significantly").show();
        Wing.unlock();
        window.setTimeout(function(){
            error.hide("slow").html("");
        },5000);
    },3000);

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
}

function restartMaster(dom) {
    if (!Wing.lock()) {
        return;
    }

    $(dom).addClass("disable").html("Restart...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("Restart Master Process");
        Wing.unlock();
    },3000);

    $.ajax({
        type:"POST",
        url:"/service/master/restart",
        success:function(msg){}
    });
}

function groupUpdate(dom)
{
    if (!Wing.lock()) {
        return;
    }

    var old_html = $(dom).html();
    $(dom).addClass("disable").html("Update...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html(old_html);
        Wing.unlock();
    },3000);

    var group_id = $(dom).attr("data-group-id");

    $.ajax({
        type:"POST",
        data:"group_id="+group_id,
        url:"/service/group/update",
        success:function(msg){}
    });
}

function groupRestart(dom)
{
    if (!Wing.lock()) {
        return;
    }

    var old_html = $(dom).html();
    $(dom).addClass("disable").html("Restart...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html(old_html);
        Wing.unlock();
    },3000);

    var group_id = $(dom).attr("data-group-id");

    $.ajax({
        type:"POST",
        data:"group_id="+group_id,
        url:"/service/group/restart",
        success:function(msg){}
    });
}

function updateMaster(dom) {
    if (!Wing.lock()) {
        return;
    }

    $(dom).addClass("disable").html("Update...");
    window.setTimeout(function(){
        $(dom).removeClass("disable").html("Update Master");
        Wing.unlock();
        $(dom).children("label").remove();
    },3000);

    $.ajax({
        type:"POST",
        url:"/service/master/update",
        success:function(msg){}
    })
}

function loadTotalServers()
{
    $.ajax({
        type : "POST",
        url : "/services/servers/num",
        success : function(num) {
            if (num && num.isNumber())
                $(".total-servers").html(num);
        }
    });
}

function loadTotalEvents()
{
    $.ajax({
        type : "POST",
        url : "/services/server/events/total",
        success : function(num) {
            if (num && num.isNumber())
                $(".total-events").html(num);
        }
    });
}

$(document).ready(function(){
    window.setInterval(function(){
        getAllServices(function(msg){
            var data = JSON.parse(msg);

            if (typeof data.error_code != "undefined" && data.error_code == 4000) {
                return;
            }

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
        // loadTotalServers();
        // loadTotalEvents();

    },1000);
});