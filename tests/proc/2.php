<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/7
 * Time: 21:32
 */
$i = 0;
register_shutdown_function(function(){
   echo "processexit";
});
$re_times = 0;
while(true)
{
    $val_in=fread(STDIN,4096);
    file_put_contents(__DIR__."/log.log", $val_in."\r\n", FILE_APPEND);

    echo $i;
    $i++;
    //usleep(10000);
   // exit;
	//sleep(1);
	$tt = explode("\r\n", $val_in);
	foreach ($tt as $ii) {if ($ii)$re_times++; }
	//$re_times+= count(explode("\r\n", $val_in));
	echo "\r\n收到次数：", $re_times,"\r\n";
	//exit;
}
