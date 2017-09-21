<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/17
 * Time: 22:51
 */
include __DIR__."/../vendor/autoload.php";
//$str = chr(123).chr(123>>8).chr(123>>16);
//
//$data = unpack("C3", $str);//[1];
//$len  = $data[1] + ($data[2] << 8) + ($data[3] << 16);
//var_dump($len);
//var_dump(-1<<7, (2<<62));
//
//$a = 1.1;
//$str = pack("V", 1.1);
//var_dump($str);
//var_dump(10<<2);
//
//$str = chr(63).chr(116).chr(52).chr(51);
//var_dump(unpack("N", $str));
//
//var_dump(ord(63).ord(116).ord(52).ord(51));

var_dump(\Wing\Bin\Constant\CapabilityFlag::CLIENT_ALL_FLAGS);
var_dump(\Wing\Bin\Constant\CapabilityFlag::CLIENT_BASIC_FLAGS);

$str = '5.7.18-log';
list($main_version, $minor_version, $sub_version) = explode(".",$str);
$sub_version = preg_replace("/\D/","", $sub_version);

var_dump($sub_version);
$server_version = $main_version*10000 + $minor_version *100 + $sub_version;
var_dump($server_version);