<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/4/1
 * Time: 09:25
 */
define("__APP_DIR__", dirname(__DIR__));
include __DIR__."/../vendor/autoload.php";
//
//echo strlen("99143  Python       24.4 10:06.37 77    0    271    25M    0B     45M    99136 99139 sleeping *0[746]         0.00000 0.00000    0    7608198+   1064741+");
//
//$handle = popen("top | grep -e 'php|chrome'" ,"r");
//
//if (!$handle)
//    return null;
//
//while(1) {
//    $res = fgets($handle, 500);
//    $res = explode("\n", $res);
//    var_dump($res);
//
//}
//
//
//
//pclose($handle);

var_dump(\Seals\Library\System::getProcessInfo(97022));