<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/22
 * Time: 10:24
 */
function buildPacket($type, $content, $requestId = 1)
{
$offset = 0;
$totLen = strlen($content);
$buf    = '';
do {
	// Packets can be a maximum of 65535 bytes
	$part = substr($content, $offset, 0xffff - 8);
	$segLen = strlen($part);
	$buf .= chr(1)        /* version */
		. chr($type)                    /* type */
		. chr(($requestId >> 8) & 0xFF) /* requestIdB1 */
		. chr($requestId & 0xFF)        /* requestIdB0 */
		. chr(($segLen >> 8) & 0xFF)    /* contentLengthB1 */
		. chr($segLen & 0xFF)           /* contentLengthB0 */
		. chr(0)                        /* paddingLength */
		. chr(0)                        /* reserved */
		. $part;                        /* content */
	$offset += $segLen;
} while ($offset < $totLen);
return $buf;
}


$p =  buildPacket(1, chr(0) . chr(1) . chr(0) . str_repeat(chr(0), 5),
	2);

function decodePacketHeader($data)
{
	$ret = array();
	$ret['version']       = ord($data{0});
	$ret['type']          = ord($data{1});
	$ret['requestId']     = (ord($data{2}) << 8) + ord($data{3});
	$ret['contentLength'] = (ord($data{4}) << 8) + ord($data{5});
	$ret['paddingLength'] = ord($data{6});
	$ret['reserved']      = ord($data{7});
	return $ret;
}

echo $p,"\r\n";
var_dump(decodePacketHeader($p));


function buildNvpair($name, $value)
{
	$nlen = strlen($name);
	$vlen = strlen($value);
	if ($nlen < 128) {
		/* nameLengthB0 */
		$nvpair = chr($nlen);
	} else {
		/* nameLengthB3 & nameLengthB2 & nameLengthB1 & nameLengthB0 */
		$nvpair = chr(($nlen >> 24) | 0x80) . chr(($nlen >> 16) & 0xFF) . chr(($nlen >> 8) & 0xFF) . chr($nlen & 0xFF);
	}
	if ($vlen < 128) {
		/* valueLengthB0 */
		$nvpair .= chr($vlen);
	} else {
		/* valueLengthB3 & valueLengthB2 & valueLengthB1 & valueLengthB0 */
		$nvpair .= chr(($vlen >> 24) | 0x80) . chr(($vlen >> 16) & 0xFF) . chr(($vlen >> 8) & 0xFF) . chr($vlen & 0xFF);
	}
	/* nameData & valueData */
	return $nvpair . $name . $value;
}



echo "===>".strlen($p)."<===";

$nv = $p.buildNvpair("http", urlencode("yuyi你好")).buildNvpair("version", urlencode("v1.0.1"));
echo "===>".$nv;
var_dump(decodePacketHeader($nv));
//exit;

function readNvpair($data, $length = null)
{
	if ($length === null) {
		$length = strlen($data);
	}

	$array = array();
	$p = 0;
	while ($p != $length) {
		$nlen = ord($data{$p++});
		if ($nlen >= 128) {
			$nlen = ($nlen & 0x7F << 24);
			$nlen |= (ord($data{$p++}) << 16);
			$nlen |= (ord($data{$p++}) << 8);
			$nlen |= (ord($data{$p++}));
		}
		$vlen = ord($data{$p++});
		if ($vlen >= 128) {
			$vlen = ($nlen & 0x7F << 24);
			$vlen |= (ord($data{$p++}) << 16);
			$vlen |= (ord($data{$p++}) << 8);
			$vlen |= (ord($data{$p++}));
		}
		$array[substr($data, $p, $nlen)] = substr($data, $p+$nlen, $vlen);
		$p += ($nlen + $vlen);
	}

	return $array;
}

$header = substr($nv,0,8);
var_dump(decodePacketHeader($header));

$s = substr($nv,9,strlen($nv)-8);
var_dump(readNvpair($s));