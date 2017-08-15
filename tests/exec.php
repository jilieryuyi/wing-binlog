<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/11
 * Time: 22:06
 */
$command = 'mysqlbinlog --base64-output=DECODE-ROWS -v --start-position=4 --stop-position=500  "/usr/local/var/mysql/mysql-bin.000058"';
// exec($command, $out, $res);
////
//var_dump(implode("\n", $out));


$descriptorspec = array(
    0 => array("pipe", "r"),
    1 => array("pipe", "w"),
    2 => array("pipe", "w")
);
//$cmd = "php " . HOME . "/services/parse_worker --file=".$cache_file;
//echo "开启新的解析进程,", $cmd,"\r\n";
 proc_open($command, $descriptorspec, $pipes);
//不阻塞
stream_set_blocking($pipes[1], 0);
fclose($pipes[0]);
fclose($pipes[2]); //标准错误直接关闭 不需要


$read = [$pipes[1]];//$this->parse_pipes;
$write = null;
$except = null;
$timeleft = 60;

$ret = stream_select(
    $read,
    $write,
    $except,
    $timeleft
);

if ($ret === false || $ret === 0) {
    //foreach ($this->parse_pipes as $id => $sock) {
        fclose($pipes[1]);
//        unset($this->parse_pipes[$id]);
//        proc_close($this->parse_processes[$id]);
//        unset($this->parse_processes[$id]);
//    }
//    return;
}

foreach ($read as $sock) {

    $events = stream_get_contents($sock);
    //$events = json_decode($events, true);
    var_dump($events);

   // self::$event_times += count($events);
    //echo "总事件次数：", self::$event_times, "\r\n";
    fclose($sock);
//    $id = array_search($sock, $this->parse_pipes);
//    unset($this->parse_pipes[$id]);
//    proc_close($this->parse_processes[$id]);
//    unset($this->parse_processes[$id]);
//    $all_count--;
}

//if ($all_count <= 0) {
//    break;
//}
//}



//$res = system($command);
//var_dump($res);

//
//$handle = popen($command,"r");
//
//if (!$handle) {
//    while(!feof($handle))
//    {
//        // send the current file part to the browser
//        print fread($handle, 1024);
//        // flush the content to the browser
//       // flush();
//    }
//    //echo stream_get_contents($handle);
//    pclose($handle);
//}
