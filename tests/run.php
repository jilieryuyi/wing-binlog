<?php
/**
 * run.php
 * User: huangxiaoan
 * Created: 2017/8/11 10:01
 * Email: huangxiaoan@xunlei.com
 */
$str = fread(STDIN, 1024);
$s = rand(1,10);
sleep($s);

fwrite(STDOUT, $str."====".$s);