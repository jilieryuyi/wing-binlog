<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/17
 * Time: 22:51
 */
//$str = chr(123).chr(123>>8).chr(123>>16);
//
//$data = unpack("C3", $str);//[1];
//$len  = $data[1] + ($data[2] << 8) + ($data[3] << 16);
//var_dump($len);
var_dump(-1<<32, (2<<62));
