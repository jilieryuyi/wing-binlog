<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/16
 * Time: 21:47
 */

/**
 * https://dev.mysql.com/doc/internals/en/myisam-column-attributes.html
 * https://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html

DATETIME
Storage: eight bytes.
Part 1 is a 32-bit integer containing year*10000 + month*100 + day.
Part 2 is a 32-bit integer containing hour*10000 + minute*100 + second.
Example: a DATETIME column for '0001-01-01 01:01:01' looks like: hexadecimal B5 2E 11 5A 02 00 00 00
 */

$data1 = 1*10000+1*100+1;
$data2 = 1*10000+1*100+1;

//小端序32位pack
$str1 = pack("V", $data1);
for ($i = 0; $i < strlen($str1); $i++) {
    echo dechex(ord($str1[$i]))." ";
}
//小端序32位pack
$str2 = pack("V", $data2);
for ($i = 0; $i < strlen($str2); $i++) {
    echo dechex(ord($str2[$i]))." ";
}

//输出 75 27 0 0 75 27 0 0
//B5 2E 11 5A 02 00 00 00 官方的输出是怎么的一个pack的过程？

echo "\r\n";

$str2 = pack("P", $data2+$data1);
for ($i = 0; $i < strlen($str2); $i++) {
    echo dechex(ord($str2[$i]))." ";
}

//2015-01-15 19:40:02
$data = 2015*13+1;
echo "\r\n";
$str2 = pack("N", $data);
for ($i = 0; $i < strlen($str2); $i++) {
    echo (ord($str2[$i]))." ";
}
