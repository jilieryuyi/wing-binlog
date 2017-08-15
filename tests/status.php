<<<<<<< HEAD
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
=======
<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/7
 * Time: 07:20
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

$client = new \Wing\Net\WsClient("127.0.0.1", 9998, "/");
$client->send('hello');
>>>>>>> 6ee3cbd6544d951ff92c5114316e3e698587ea1a
