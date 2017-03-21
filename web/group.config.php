<?php
if (!isset($_GET["group_id"])) {
    echo "params error";
    return;
}
$group_id   = $_GET["group_id"];

$node_info = [];//\Seals\Web\Logic\Node::getInfo($group_id, $session_id);
//var_dump($node_info);

$databases = [];//\Seals\Web\Logic\Node::getDatabases($session_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>wing-binlog</title>
    <link type="text/css" rel="stylesheet" href="css/public.css">
<!--    <link type="text/css" rel="stylesheet" href="css/buttons.css">-->
    <link type="text/css" rel="stylesheet" href="css/config.css">
    <link type="text/css" rel="stylesheet" href="css/all.css">
    <script src="js/jquery-3.1.1.min.js"></script>
    <script>
        var group_id = "<?php echo $_GET["group_id"]; ?>";
    </script>
    <script src="js/config.js"></script>
</head>
<body>
<div class="title">
    <h2>群组配置<img title="<?php if($node_info["is_offline"])echo "已下线"; else echo "在线"; ?>" style="width: 12px;" src="images/<?php if($node_info["is_offline"])echo "offline.png"; else echo "online.png"; ?>"/></h2>
<!--    <div class="right-tool">-->
<!--        <span class="button button-royal button-primary">下线</span>-->
<!--    </div>-->
</div>
<div class="warn-info">
    <div>注意：</div>
    <div class="c-red">修改群组配置将下发到该群组下的所有节点，修改时请注意</div>
    <div class="c-red">节点下线之后将停止一切采集业务，也不会被分配为leader，可以随时恢复上线</div>
    <div class="c-red">密码字段，null请使用 :null 代替</div>
</div>
<div>
    <div class="c-item">
        <div class="t">工作进程</div>
        <div>进程数量<input class="workers" type="text" value="<?php echo $node_info["workers"]; ?>"/></div>
        <div><label>开启debug<input class="debug" type="checkbox" <?php if($node_info["debug"]) echo "checked";?>/></label></div>
        <div><span class="button button-small button-local" onclick="setRuntimeConfig(this)">更新配置</span></div>
    </div>
    <div class="c-item">
        <div class="t">事件通知</div>
        <div style="font-size: 12px;">关于参数<br/><span class="c-red">如果是redis，只需要填写第一个，第一个为事件队列名称<br/>
            如果是http，第一个参数为url，第二个参数为自定义数据<br/>
                如果是rabbitmq，第一个参数为交换机名称，第二个参数为队列名称
            </span></div>
        <div>
            <span>通知方式</span>
            <select class="notify-class" onchange="onNotifySelect(this)">
                <option data-param-1="<?php
                if ($node_info["notify"]["handler"] == "Seals\\Notify\\Redis")
                    echo $node_info["notify"]["params"][0];
                else
                    echo "seals:event:list";
                ?>"
                        data-param-2=""
                        value="Seals\\Notify\\Redis"
                    <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Redis") echo "selected"; ?>
                >redis队列</option>
                <option
                    data-param-1="<?php
                    if ($node_info["notify"]["handler"] == "Seals\\Notify\\Http")
                        echo $node_info["notify"]["params"][0];
                    else
                        echo "http://127.0.0.1:9998/";
                    ?>"
                    data-param-2="<?php
                    if ($node_info["notify"]["handler"] == "Seals\\Notify\\Http" &&
                        isset($node_info["notify"]["params"][1]))
                        echo $node_info["notify"]["params"][1];
                    else
                        echo "http://127.0.0.1:9998/";
                    ?>"
                    value="Seals\\Notify\\Http"
                    <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Http") echo "selected"; ?>
                >http</option>
                <option
                    data-param-1="<?php
                    if ($node_info["notify"]["handler"] == "Seals\\Notify\\Rabbitmq")
                        echo $node_info["notify"]["params"][0];
                    else
                        echo "wing-binlog-exchange";
                    ?>"
                    data-param-2="<?php
                    if ($node_info["notify"]["handler"] == "Seals\\Notify\\Rabbitmq" &&
                        isset($node_info["notify"]["params"][1]))
                        echo $node_info["notify"]["params"][1];
                    else
                        echo "wing-binlog-queue";
                    ?>"
                    value="Seals\\Notify\\Rabbitmq"
                    <?php if ($node_info["notify"]["handler"] == "Seals\\Notify\\Rabbitmq") echo "selected"; ?>
                >rabbitmq</option>
            </select>
        </div>
        <div>
            <div>参数1<input class="param1" type="text" value="<?php echo $node_info["notify"]["params"][0]; ?>"/></div>
            <div>参数2<input class="param2" type="text" value="<?php if (isset($node_info["notify"]["params"][1]))
                echo $node_info["notify"]["params"][1]; ?>"/></div>
        </div>
        <div><span onclick="setNotifyConfig(this)" class="button button-small button-local">更新配置</span></div>
    </div>

    <div class="c-item">
        <div>事件队列rabbitmq配置</div>
        <div><span>ip</span><input class="host" type="text" value="<?php echo $node_info["rabbitmq"]["host"]; ?>" /></div>
        <div><span>端口</span><input class="port" type="text" value="<?php echo $node_info["rabbitmq"]["port"]; ?>"/></div>
        <div><span>用户</span><input class="user" type="text" value="<?php echo $node_info["rabbitmq"]["user"]; ?>"/></div>
        <div><span>密码</span><input class="password" type="text" value=""/></div>
        <div><span>vhost</span><input class="vhost" type="text" value="<?php echo $node_info["rabbitmq"]["vhost"]; ?>"/></div>
        <div><span onclick="setRabbitmqConfig(this)" class="button button-small button-local">更新配置</span></div>
    </div>

    <div class="c-item">
        <div>事件队列redis配置</div>
        <div><span>ip</span><input class="host" type="text" value="<?php echo $node_info["redis_config"]["host"]; ?>"/></div>
        <div><span>端口</span><input class="port" type="text" value="<?php echo $node_info["redis_config"]["port"]; ?>"/></div>
        <div><span>密码</span><input class="password" type="text" value=""/></div>
        <div><span onclick="setRedisConfig(this)" class="button button-small button-local">更新配置</span></div>
    </div>

    <div class="c-item">
        <div>群集配置</div>
        <div><span>组id</span><input class="group_id" type="text" value="<?php echo $node_info["zookeeper"]["group_id"]; ?>"/></div>
        <div><span>ip</span><input class="host" type="text" value="<?php echo $node_info["zookeeper"]["host"]; ?>"/></div>
        <div><span>端口</span><input class="port" type="text" value="<?php echo $node_info["zookeeper"]["port"]; ?>"/></div>
        <div><span>密码</span><input class="password" type="text" value=""/></div>
        <div><span onclick="setZookeeperConfig(this)" class="button button-small button-local">更新配置</span></div>
    </div>

    <div class="c-item">
        <div>数据库配置</div>
        <div><span>ip</span><input class="host" type="text" value="<?php echo $node_info["db_config"]["host"]; ?>"/></div>
        <div><span>端口</span><input class="port" type="text" value="<?php echo $node_info["db_config"]["port"]; ?>"/></div>
        <div><span>用户</span><input class="user" type="text" value="<?php echo $node_info["db_config"]["user"]; ?>"/></div>
        <div><span>密码</span><input class="password" type="text" value=""/></div>
        <div><span>数据库</span>
            <select class="db_name">
                <?php foreach ($databases as $database){
                    $selected = $node_info["db_config"]["db_name"] == $database ? "selected" : "";
                    ?>
                <option <?php echo $selected; ?> value="<?php echo $database; ?>"><?php echo $database; ?></option>
                <?php } ?>
            </select>
<!--            <input class="db_name" type="text"  value="--><?php //echo $node_info["db_config"]["db_name"]; ?><!--"/>-->
        </div>
        <div><span onclick="setDbConfig(this)" class="button button-small button-local">更新配置</span></div>
    </div>
</div>
</body>
</html>