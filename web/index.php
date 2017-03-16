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
<?php $services = \Seals\Library\Zookeeper::getServices();//\Seals\Library\Context::instance()->get("zookeeper")->getServices();
 ?>
<div style="border-bottom: #4cae4c solid 3px;height: 56px;">
<h2 style="padding-right: 20px; float: left;">服务列表 群集>群集节点</h2>
<div class="right-tool" style="">
    <span onclick="refresh()" class="bth-refresh">刷新</span>
</div>
</div>
<ul>
    <li class="title">
        <span class="group-id">群组</span>
        <span class="node-count">节点数</span>
        <span class="group-edit edit">操作</span>
    </li>
<?php
foreach ($services as $group_id => $groups) {
?>
    <li>
        <div class="item">
            <span class="group-id"><?php echo $group_id; ?></span>
            <span class="node-count"><?php echo count($groups); ?></span>
            <span class="group-edit edit"><a>配置</a></span>
        </div>
        <ul class="nodes-list">
        <?php
        if (count($groups) > 0) {
            ?>
            <li class="title">
                <span class="node-id">节点</span>
                <span class="is-enable">启用群组</span>
                <span class="is-leader">leader</span>
                <span class="last-pos">最后读取</span>
                <span class="group-edit edit">操作</span>
            </li>
            <?php
        }
        ?>
        <?php
        $leader_id = \Seals\Library\Zookeeper::getLeader($group_id);
        $index = 1;
        foreach ($groups as $session_id => $last_updated) {
            $is_leader = $leader_id == $session_id;
            $time_span = time() - $last_updated;
                ?>
                <li class="node"
                    data-group-id="<?php echo $group_id; ?>"
                    data-session-id="<?php echo $session_id; ?>"
                    style="<?php
                if ($time_span >= 10 && $time_span <= 20) echo 'background: #f00;'; ?>">
                    <span class="node-id" title="<?php echo $session_id; ?>"><?php echo ($index++),"、",$session_id; ?></span>
                    <span class="is-enable"><?php
                        if (\Seals\Library\Zookeeper::isEnable($group_id,$session_id))
                            echo "启用";
                        else
                            echo "禁用";
                        ?></span>
                    <span class="is-leader"><?php if ($is_leader) echo "是";else echo "否";?></span>
                    <span class="last-pos">
                    <?php
//                    if ($is_leader)
//                        echo "leader:";
                    //echo $session_id;
//                    if ($is_leader) {
                    echo \Seals\Library\Zookeeper::getGroupLastBinlog($group_id);
                        $res = \Seals\Library\Zookeeper::getGroupLastPost($group_id);
                        echo " => ",$res[1];
//                    }
                    ?>
                </span>
                    <span class="edit">
                        <a
                            data-group-id="<?php echo $group_id; ?>"
                            data-session-id="<?php echo $session_id; ?>"
                            onclick="nodeDown(this)"
                        >下线</a>
                    </span>
                </li>
                <?php
            }
        ?>
    </ul>
    </li>
<?php
}
?>
</ul>
</body>
</html>