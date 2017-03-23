<?php
$history_read_max  = \Seals\Web\Logic\Node::getHistoryReadMax($_GET["session_id"]);
$history_write_max = \Seals\Web\Logic\Node::getHistoryWriteMax($_GET["session_id"]);
$today_read_max    = \Seals\Web\Logic\Node::getDayReadMax($_GET["session_id"], date("Ymd"));
$today_write_max   = \Seals\Web\Logic\Node::getDayWriteMax($_GET["session_id"], date("Ymd"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>wing-binlog</title>
    <link type="text/css" rel="stylesheet" href="css/public.css">
    <link type="text/css" rel="stylesheet" href="css/index.css">
    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/index.js"></script>
</head>
<body>
<div style="border-bottom: #4cae4c solid 3px;height: 56px;">
    <h2 style="padding-right: 20px; float: left;">数据统计</h2>
</div>
<div style="margin-top: 16px;">历史读秒并发最高值：<?php echo $history_read_max; ?>/秒</div>
<div>历史写秒并发最高值：<?php echo $history_write_max; ?>/秒</div>
<div>今天读秒并发最高值：<?php echo $today_read_max; ?>/秒</div>
<div>今天写秒并发最高值：<?php echo $today_write_max; ?>/秒</div>
</body>
</html>