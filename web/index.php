<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>wing-binlog</title>
    <style>
        .title {
            font-weight: bold;
        }
        div.item {
            height: 25px;
        }
        li {
            list-style: none;
            border-bottom: #ccc solid 1px;
            height: 25px;
        }
        li span {
            display: inline-block;
            height: 25px;
            word-wrap: break-word;
            word-break: break-all;
            float:left;
        }
        span.group-id {
            width: 150px;
        }
        span.node-count {
            width:80px;
        }
        span a:hover {
            text-decoration: underline;
            font-weight: bold;
        }
        span a {
            cursor: pointer;
            color: #0000ff;
            text-decoration:none;
        }
        span.node-id {
            width: 180px;
            padding-right: 6px;
            overflow: hidden;
        }
        span.is-enable {
            width: 80px;
        }
        span.last-pos {
            width: 220px;
        }
        span.edit {
            width: 80px;
        }
        .nodes-list {
            padding-top: 15px;
        }
        span.is-leader {
            width:80px;
        }
    </style>
</head>
<body>
<?php $services = \Seals\Library\Zookeeper::getServices();//\Seals\Library\Context::instance()->get("zookeeper")->getServices();
var_dump($services); ?>
<h2 style="border-bottom: #4cae4c solid 3px;">服务列表 群集>群集节点</h2>
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
                <li style="<?php
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
                        <a>下线</a>
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