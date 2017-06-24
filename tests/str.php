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


function getBeginRequestBody()
{
	return pack("nC6", FCGI_RESPONDER, 0, 0, 0, 0, 0, 0);
}

function getHeader($type, $requestId, $contentLength, $paddingLength, $reserved=0)
{
	return pack("C2n2C2", FCGI_VERSION_1, $type, $requestId, $contentLength, $paddingLength, $reserved);
}

function getPaddingLength($body)
{
	$left = strlen($body) % 8;
	if ($left == 0)
	{
		return 0;
	}

	return (8 - $left);
}

function getPaddingData($paddingLength=0)
{
	if ($paddingLength <= 0)
	{
		return '';
	}
	$paddingArray = array_fill(0, $paddingLength, 0);
	return call_user_func_array("pack", array_merge(array("C{$paddingLength}"), $paddingArray));
}

function getNameValue($name, $value)
{
	$nameLen  = strlen($name);
	$valueLen = strlen($value);
	$bin      = '';

	// 如果大于127，则需要4个字节来存储，下面的$valueLen也需要如此计算
	if ($nameLen > 0x7f)
	{
		// 将$nameLen变成4个无符号字节
		$b0 = $nameLen << 24;
		$b1 = ($nameLen << 16) >> 8;
		$b2 = ($nameLen << 8) >> 16;
		$b3 = $nameLen >> 24;
		// 将最高位置1，表示采用4个无符号字节表示
		$b3 = $b3 | 0x80;
		$bin = pack("C4", $b3, $b2, $b1, $b0);
	}
	else
	{
		$bin = pack("C", $nameLen);
	}

	if ($valueLen > 0x7f)
	{
		// 将$nameLen变成4个无符号字节
		$b0 = $valueLen << 24;
		$b1 = ($valueLen << 16) >> 8;
		$b2 = ($valueLen << 8) >> 16;
		$b3 = $valueLen >> 24;
		// 将最高位置1，表示采用4个无符号字节表示
		$b3 = $b3 | 0x80;
		$bin .= pack("C4", $b3, $b2, $b1, $b0);
	}
	else
	{
		$bin .= pack("C", $valueLen);
	}

	$bin .= pack("a{$nameLen}a{$valueLen}", $name, $value);

	return $bin;
}

//$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//socket_connect($sock, FCGI_HOST, FCGI_PORT);

$body   = getBeginRequestBody();
$paddingLength = getPaddingLength($body);
$header = getHeader(FCGI_BEGIN_REQUEST, FCGI_REQUEST_ID, strlen($body), $paddingLength, 0);
$record = $header . $body . getPaddingData($paddingLength);

//echo "header length : ".strlen($record),"\r\n";


//echo strlen(pack("nC6", FCGI_RESPONDER, 0, 0, 0, 0, 0, 0)),"\r\n";

$data = file_get_contents("/Users/yuyi/Downloads/123.log");
$headerFormat = 'Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength/x';



//第一个包
$arr = unpack($headerFormat, substr($data,16,8));
var_dump($arr);
//exit;
/*$arr1 = unpack("nC6", substr($data,9,$arr["contentLength"]));
var_dump($arr1);

$arr2 = unpack($headerFormat, substr($data,9+$arr["contentLength"],8));
var_dump($arr2);*/


//echo "name===>";
//$start = 18;
//$f = substr($data,$start , 1);
//var_dump("namelen flag ====>",$f);
//
//
//for($i=0;$i<30;$i++) {
//	$arr = unpack("C", substr($data, $i));
//	echo $i,"=>length ===> " . $arr[1], "\r\n";
//}

while (1) {
    $arr = unpack($headerFormat, substr($data, 16, 8));
    var_dump($arr);

    $content_len = $arr["contentLength"];
    var_dump($content_len);

    $start       = 24;
    $length      = strlen($data);


    while ($start < $content_len) {
        $f = substr($data, $start, 1);

        $flag = substr(sprintf("%08b", (ord($f))), 0, 1);
        if ($flag == "0") {
            //echo "=================>";
            $temp = unpack("C", substr($data, $start, 1));
            //var_dump($temp);
            $name_len = unpack("C", substr($data, $start, 1))[1];
            $start += 1;
        } else {
            $temp = unpack("C4", substr($data, $start, 4));
            $B3 = $temp[1];
            $B2 = $temp[2];
            $B1 = $temp[3];
            $B0 = $temp[4];
            $name_len = (($B3 & 0x7f) << 24) + ($B2 << 16) + ($B1 << 8) + $B0;
            $start += 4;
        }

        echo $name_len, "--->";

        $key = substr($data, $start, $name_len + 1);

        // echo $key,"\r\n";
        $f = substr($data, $start, 1);
        $flag = substr(sprintf("%08b", (ord($f))), 0, 1);
        if ($flag == "0") {
            //if (!$f) {
            $value_len = unpack("C", substr($data, $start, 1))[1];
            $start += 1;
        } else {
            $temp = unpack("C4", substr($data, $start, 4));
            $B3 = $temp[1];
            $B2 = $temp[2];
            $B1 = $temp[3];
            $B0 = $temp[4];
            $value_len = (($B3 & 0x7f) << 24) + ($B2 << 16) + ($B1 << 8) + $B0;
            $start += 4;
        }
        echo $value_len, "\r\n";

        $start += $name_len;
        $value = substr($data, $start, $value_len + 1);
        $start += $value_len;

        echo $key, "===>", $value, "\r\n";
        //exit;
    }

    $data = substr($data, $start+16);

    var_dump($data);
    if (!$data)
        break;
}



/*$f = substr($data,9+$arr["contentLength"]+9 , 1);
var_dump("content flag ====>",$f);

if ($f == 0) {
	$arr = unpack("C", substr($data,9+$arr["contentLength"]+9));
	var_dump($arr);
}*/



//((B3 & 0x7f) << 24) + (B2 << 16) + (B1 << 8) + B0
//var_dump($arr[1]>>24);
//var_dump(($arr[2]>>16)<<8);
//var_dump(($arr[3]>>8)<<16);
//var_dump($arr[4]>>24);
