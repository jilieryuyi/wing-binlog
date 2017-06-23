<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/22
 * Time: 10:24
 */
define('FCGI_HOST', '127.0.0.1');
define('FCGI_PORT', 9000);
define('FCGI_SCRIPT_FILENAME', '/home/goal/fcgiclient/www/test.php');
define('FCGI_REQUEST_METHOD', 'POST');
define('FCGI_REQUEST_ID', 1);

define('FCGI_VERSION_1', 1);
define('FCGI_BEGIN_REQUEST', 1);
define('FCGI_RESPONDER', 1);
define('FCGI_END_REQUEST', 3);
define('FCGI_PARAMS', 4);
define('FCGI_STDIN', 5);
define('FCGI_STDOUT', 6);
define('FCGI_STDERR', 7);

echo strlen(pack("nC6", FCGI_RESPONDER, 0, 0, 0, 0, 0, 0)),"\r\n";

$data = file_get_contents("D:/123.log");
$headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';

$arr = unpack($headerFormat, substr($data,0,8));

var_dump($arr);

$arr = unpack("nC6", substr($data,9,8));
var_dump($arr);

$arr = unpack($headerFormat, substr($data,17,8));
var_dump($arr);

$arr = unpack("C4", substr($data,25));
var_dump($arr);
var_dump($arr[1]>>24);
var_dump(($arr[2]>>16)<<8);
var_dump(($arr[3]>>8)<<16);
var_dump($arr[4]>>24);
