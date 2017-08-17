<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/16
 * Time: 21:47
 */
$rex = "/\\[.*\\]/";

$str = '"  [哭啼]“ ';

preg_match($rex, $str, $match);
var_dump($match);