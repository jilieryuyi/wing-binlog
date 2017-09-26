<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/26
 * Time: 17:31
 */

$str = 'qwefqwe<coordinates>1.2,3.4,5 6</coordinates>';
preg_match("/\<coordinates\>[\s\S]{1,}\<\/coordinates>/",
$str, $mac);
var_dump($mac);
preg_match_all("/[\d]+(\.[\d]+)?/", $mac[0], $matches);
var_dump($matches);