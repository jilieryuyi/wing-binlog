<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>wing-binlog</title>
    <link type="text/css" rel="stylesheet" href="css/index.css">
    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/index.js"></script>
</head>
<body>
<div style="border-bottom: #4cae4c solid 3px;height: 56px;">
    <h2 style="padding-right: 20px; float: left;">节点配置</h2>
    <div class="right-tool" style="">
        <span class="bth-refresh">下线</span>
    </div>
</div>
<div style="margin-top: 15px; font-size: 12px; ">
    <div>注意：</div>
    <div style="color: #f00;">节点下线之后将停止一切采集业务，也不会被分配为leader，可以随时回复上线</div>
    <div style="color: #f00;">持久化配置与运行时配置的区别在于，持久化配置在节点重启之后依然有效，而运行时配置则会失效，即仅在运行当时生效</div>
</div>
<div>
    <div>
        <div>事件通知</div>
        <div><span>通知方式</span><select>
                <option>redis队列</option>
                <option>http</option>
                <option>rabbitmq</option>
            </select>
        </div>
        <div><input type="button" value="更新运行时配置"/><input type="button" value="更新持久化配置"/></div>
    </div>
    <div>
        <div>节点本地redis配置</div>
        <div><span>ip</span><input type="text"/></div>
        <div><span>端口</span><input type="text"/></div>
        <div><span>密码</span><input type="text"/></div>
        <div><input type="button" value="更新运行时配置"/><input type="button" value="更新持久化配置"/></div>
    </div>

    <div>
        <div>事件队列redis配置</div>
        <div><span>ip</span><input type="text"/></div>
        <div><span>端口</span><input type="text"/></div>
        <div><span>密码</span><input type="text"/></div>
        <div><input type="button" value="更新运行时配置"/><input type="button" value="更新持久化配置"/></div>

    </div>

    <div>
        <div>http配置</div>
        <div><span>url</span><input type="text"/></div>
        <div><span>附加参数</span><input type="text"/></div>
        <div><input type="button" value="更新运行时配置"/><input type="button" value="更新持久化配置"/></div>

    </div>

    <div>
        <div>rabbitmq配置</div>
        <div><span>ip</span><input type="text"/></div>
        <div><span>端口</span><input type="text"/></div>
        <div><span>用户</span><input type="text"/></div>
        <div><span>密码</span><input type="text"/></div>
        <div><span>vhost</span><input type="text"/></div>
        <div><input type="button" value="更新运行时配置"/><input type="button" value="更新持久化配置"/></div>

    </div>
    <div>
        <div>群集配置</div>
        <div><span>启用</span><label>是</label><label>否</label></div>
        <div><span>组名称</span><input type="text"/></div>
        <div><span>ip</span><input type="text"/></div>
        <div><span>端口</span><input type="text"/></div>
        <div><span>密码</span><input type="text"/></div>
        <div><input type="button" value="更新运行时配置"/><input type="button" value="更新持久化配置"/></div>

    </div>

    <div>
        <div>数据库配置</div>
        <div><span>ip</span><input type="text"/></div>
        <div><span>端口</span><input type="text"/></div>
        <div><span>用户</span><input type="text"/></div>
        <div><span>密码</span><input type="text"/></div>
        <div><span>数据库</span><input type="text"/></div>
        <div><input type="button" value="更新运行时配置"/><input type="button" value="更新持久化配置"/></div>
    </div>
</div>
</body>
</html>