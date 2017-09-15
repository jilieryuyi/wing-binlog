<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 06:59
 */
class DBNamespace
{
    //数据库编码
    const ENCODING_GBK      = 0;
    const ENCODING_UTF8     = 1;
    const ENCODING_LATIN    = 2;
    const ENCODING_UTF8MB4  = 3;
    // 数据库句柄需要ping 重连
    const HANDLE_PING       = 100;
    // 数据库句柄不能 重连
    const NOT_HANDLE_PING   = 200;
}
