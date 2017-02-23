<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/19
 * Time: 09:06
 */
include __DIR__."/../vendor/autoload.php";

$sql = 'select * from x_fee where 1;show tables;';
$data = \Wing\Binlog\Library\Context::instance()->pdo->query( $sql );

var_dump($data);