<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/16
 * Time: 21:47
 */

var_dump(is_numeric("1.01"));
if (intval("1.01") == "10.1") {
    var_dump("int");
} else {
    var_dump("float");
}