<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/16
 * Time: 21:47
 */
$pass = "123456";
$salt = "456789";
var_dump(sha1($pass, true) ^ sha1($salt . sha1(sha1($pass, true), true),true));