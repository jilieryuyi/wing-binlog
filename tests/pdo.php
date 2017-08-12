<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 21:26
 */
include __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));
define("WING_DEBUG",  true);

$pdo  = new \Wing\Library\PDO();
$sql1 = 'update new_yonglibao_c.bl_city set provinces_id=(provinces_id+1) where id=5753598';
$sql2 = 'update new_yonglibao_c.bl_city set provinces_id=(provinces_id-1) where id=5753598';

$sql1 = 'update xsl.x_messages set phone=(phone+1) where id=3';
$sql2 = 'update xsl.x_messages set phone=(phone-1) where id=3';
$count = 0;
while ($count<1000000)
{//$count<10000
    $count +=2;
    ($pdo->query($sql1));
    ($pdo->query($sql2));
    echo "事件次数：",$count,"\r\n";
    //usleep(1000);
}

