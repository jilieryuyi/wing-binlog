<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/14
 * Time: 07:47
 */
ob_implicit_flush();
declare(ticks=1);
function  getCurrentProcessId()
{
    if (function_exists("getmypid"))
        return getmypid();

    if (function_exists("posix_getpid"))
        return posix_getpid();
    return 0;
}

pcntl_signal(SIGTERM,function(){
    echo "get singal 1\r\n";
    exit;
});
pcntl_signal(SIGHUP ,function(){
    echo "get singal 2\r\n";
    exit;
});
pcntl_signal(SIGUSR1,function(){
    echo "get singal 3\r\n";
    exit;
});

pcntl_signal(SIGQUIT,function(){
    echo "get singal 4\r\n";
    exit;
});

echo getCurrentProcessId(),"\r\n";
file_put_contents(__DIR__."/p.pid",getCurrentProcessId());
while(1){
    sleep(100000);
}