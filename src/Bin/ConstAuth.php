<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 07:01
 */
class ConstAuth {


    // 2^24 - 1 16m
    public static $PACK_MAX_LENGTH = 16777215;

    // http://dev.mysql.com/doc/internals/en/auth-phase-fast-path.html
    // 00 FE
    public static $OK_PACK_HEAD = [0, 254];
    // FF
    public static $ERR_PACK_HEAD = [255];

}