<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>wing-binlog</title>
</head>
<body>
<?php $services = \Seals\Library\Zookeeper::getServices();//\Seals\Library\Context::instance()->get("zookeeper")->getServices();
var_dump($services); ?>
<h2>服务列表 群集>群集节点</h2>
<ul>
<?php
foreach ($services as $group_id => $groups) {
?>
    <li><?php echo $group_id; ?></li>
    <ul>
        <?php
        $leader_id = \Seals\Library\Zookeeper::getLeader($group_id);

        foreach ($groups as $session_id => $last_updated) {
            $is_leader = $leader_id == $session_id;
            $time_span = time() - $last_updated;
                ?>
                <li style="<?php
                if ($time_span >= 10 && $time_span <= 20) echo 'background: #f00;'; ?>"><?php
                    if ($is_leader)
                        echo "leader:";
                    echo $session_id;
                    if ($is_leader) {
                        $res = \Seals\Library\Zookeeper::getGroupLastPost($group_id);
                        echo "=>", $res[0],":",$res[1];
                    }
                    ?></li>
                <?php
            }
        ?>
    </ul>
<?php
}
?>
</ul>
</body>
</html>