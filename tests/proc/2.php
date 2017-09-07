<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/7
 * Time: 21:32
 */
$i = 0;
while(true)
{
    $val_in=fread(STDIN,4096);
    file_put_contents(__DIR__."/log.log", $val_in."\r\n", FILE_APPEND);

    echo $i;
    $i++;
    //usleep(10000);
}