<?php
$group_id   = $_GET["group_id"];
$session_id = $_GET["session_id"];

$node_info = \Seals\Web\Logic\Node::getInfo($group_id, $session_id);
var_dump($node_info);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>wing-binlog</title>
    <link type="text/css" rel="stylesheet" href="css/public.css">
    <link type="text/css" rel="stylesheet" href="css/button.css">
    <link type="text/css" rel="stylesheet" href="css/config.css">
    <link type="text/css" rel="stylesheet" href="css/all.css">
    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/index.js"></script>
</head>
<body>
<div class="title">
    <h2>节点配置<img title="<?php if($node_info["is_offline"])echo "已下线"; else echo "在线"; ?>" style="width: 12px;" src="img/<?php if($node_info["is_offline"])echo "offline.png"; else echo "online.png"; ?>"/></h2>
<!--    <div class="right-tool">-->
<!--        <span class="button button-royal button-primary">下线</span>-->
<!--    </div>-->
</div>
<div class="warn-info">
    <div>注意：</div>
    <div class="c-red">已去除所有的敏感密码信息，密码字段均未返回和填充，如需更新，请正确填写，否则忽略密码字段</div>
    <div class="c-red">节点下线之后将停止一切采集业务，也不会被分配为leader，可以随时恢复上线</div>
    <div class="c-red">持久化配置与运行时配置的区别在于，持久化配置在节点重启之后依然有效，而运行时配置则会失效，即仅在运行当时生效</div>
</div>
<div>
    <div class="c-item">
        <div class="t">工作进程</div>
        <div>进程数量<input type="text" value="<?php echo $node_info["workers"]; ?>"/></div>
        <div><label>开启debug<input type="checkbox" <?php if($node_info["debug"]) echo "checked";?>/></label></div>
        <div><span class="button button-small button-local">更新配置</span></div>
    </div>
    <div class="c-item">
        <div class="t">事件通知</div>
        <div style="font-size: 12px;">关于参数<br/><span class="c-red">如果是redis，只需要填写第一个，第一个为事件队列名称<br/>
            如果是http，第一个参数为url，第二个参数为自定义数据<br/>
                如果是rabbitmq，第一个参数为交换机名称，第二个参数为队列名称
            </span></div>
        <div>
            <span>通知方式</span>
            <select>
                <option value="Seals\\Notify\\Redis" <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Redis") echo "selected"; ?>>redis队列</option>
                <option value="Seals\\Notify\\Http" <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Http") echo "selected"; ?>>http</option>
                <option value="Seals\\Notify\\Rabbitmq" <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Rabbitmq") echo "selected"; ?>>rabbitmq</option>
            </select>
        </div>
        <div>
            <?php foreach ($node_info["notify"]["params"] as $index => $param) {
                ?>
                <div>参数<?php echo ($index+1); ?><input type="text" value="<?php echo $param; ?>"/></div>
            <?php
            } ?>
        </div>
        <div><label>持久化<input type="checkbox"/></label></div>
        <div><span class="button button-small button-local">更新配置</span></div>
    </div>
    <div class="c-item">
        <div>节点本地redis配置</div>
        <div><span>ip</span><input type="text" value="<?php echo $node_info["redis_local"]["host"]; ?>" /></div>
        <div><span>端口</span><input type="text" value="<?php echo $node_info["redis_local"]["port"]; ?>"/></div>
        <div><span>密码</span><input type="text" value=""/>
            <span class="c-red" style="font-size: 12px;">如果为空，则忽略密码字段，即不会更新密码字段</span>
        </div>
        <div><label>持久化<input type="checkbox"/></label></div>
        <div><span class="button button-small button-local">更新配置</span></div>
    </div>

    <div class="c-item">
        <div>事件队列redis配置</div>
        <div><span>ip</span><input type="text" value="<?php echo $node_info["redis_config"]["host"]; ?>"/></div>
        <div><span>端口</span><input type="text" value="<?php echo $node_info["redis_config"]["port"]; ?>"/></div>
        <div><span>密码</span><input type="text" value=""/>
            <span class="c-red" style="font-size: 12px;">如果为空，则忽略密码字段，即不会更新密码字段</span>
        </div>
        <div><label>持久化<input type="checkbox"/></label></div>
        <div><span class="button button-small button-local">更新配置</span></div>
    </div>

    <div class="c-item">
        <div>群集配置</div>
        <div><span>组id</span><input type="text" value="<?php echo $node_info["zookeeper"]["group_id"]; ?>"/></div>
        <div><span>ip</span><input type="text" value="<?php echo $node_info["zookeeper"]["host"]; ?>"/></div>
        <div><span>端口</span><input type="text" value="<?php echo $node_info["zookeeper"]["port"]; ?>"/></div>
        <div><span>密码</span><input type="text" value=""/>
            <span class="c-red" style="font-size: 12px;">如果为空，则忽略密码字段，即不会更新密码字段</span>
        </div>
        <div><label>持久化<input type="checkbox"/></label></div>
        <div><span class="button button-small button-local">更新配置</span></div>
    </div>

    <div class="c-item">
        <div>数据库配置</div>
        <div><span>ip</span><input type="text" value="<?php echo $node_info["db_config"]["host"]; ?>"/></div>
        <div><span>端口</span><input type="text" value="<?php echo $node_info["db_config"]["port"]; ?>"/></div>
        <div><span>用户</span><input type="text" value="<?php echo $node_info["db_config"]["user"]; ?>"/></div>
        <div><span>密码</span><input type="text" value=""/></div>
        <div><span>数据库</span><input type="text"  value="<?php echo $node_info["db_config"]["db_name"]; ?>"/></div>
        <div><label>持久化<input type="checkbox"/></label></div>
        <div><span class="button button-small button-local">更新配置</span></div>
    </div>
</div>
</body>
</html>