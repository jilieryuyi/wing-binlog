<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>wing-binlog</title>
</head>
<body>
<ul>
<?php
$services = \Seals\Library\Zookeeper::getServices();//\Seals\Library\Context::instance()->get("zookeeper")->getServices();
var_dump($services);
foreach ($services as $group_id => $groups) {
?>
    <li><?php echo $group_id; ?></li>
    <ul>
        <?php
        foreach ($groups as $session_id => $last_updated) {?>
        <li style="<?php
        $time_span = time()-$last_updated;
        if($time_span>=10 && $time_span<=20) echo 'background: #f00;';?>"><?php echo $session_id;  ?></li>
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