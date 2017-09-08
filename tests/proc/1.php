<?php
$descriptorspec = array(
0 => array("pipe", "r"),
1 => array("pipe", "w"),
2 => array("pipe", "r")
);
$process = proc_open('php 2.php', $descriptorspec, $pipes, null, null); //run test_gen.php

function try_read($r){
    $read     = [$r];//$this->dispatch_pipes;
    $write    = null;
    $except   = null;
    $timeleft = 60;

    $ret = stream_select(
        $read,
        $write,// = null,
        $except,// = null,
        $timeleft
    );

    if ($ret === false || $ret === 0) {
        return;
    }

    foreach ($read as $sock) {
        $raw = stream_get_contents($sock);
        echo $raw,"\r\n";
        if (strpos($raw,"processexit") !== false) {
            echo "\r\nchild process exit2";
            exit;
        }
    }

}

if (is_resource($process))
{
    stream_set_blocking($pipes[1], 0);
	stream_set_blocking($pipes[0], 0);
    $i = 0;
    while(1) {
        fwrite($pipes[0], "hello_".$i."\r\n");
        $i++;
//        while($res = stream_get_contents($pipes[1]))
//        echo $res,"\r\n";
        //usleep(10000);
        try_read($pipes[1]);
        echo "send times : ", $i, "\r\n";
    }

fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);
}
