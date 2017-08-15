<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/7
 * Time: 07:20
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

$command = "php ".HOME."/tcp status";
$output = $return = null;
exec($command,$output,$return);

var_dump($output, $return);

$start = false;
$processes = [];
foreach ($output as $row) {
    if (substr($row, 0, 3) == "pid") {
        $start = true;
        continue;
    }
    if ($start) {
        list($process_id,) = preg_split("/\D/", $row, 2);
        $processes[]= $process_id;//,"\r\n";
    }
}
var_dump($processes);