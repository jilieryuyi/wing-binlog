<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 21:26
 */
include __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

$pdo = new \Wing\Library\PDO();
$sql1 = 'update new_yonglibao_c.bl_city set provinces_id=(provinces_id+1) where id=5753598';
$sql2 = 'update new_yonglibao_c.bl_city set provinces_id=(provinces_id-1) where id=5753598';
while (1) {
    $pdo->query($sql1);
    $pdo->query($sql2);
    usleep(1000);
}

