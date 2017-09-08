<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 06:59
 */
class DBConstNamespace {
    // 数据库编码相关
    const ENCODING_GBK      = 0; ///< GBK 编码定义
    const ENCODING_UTF8     = 1; ///< UTF8 编码定义
    const ENCODING_LATIN    = 2; ///< LATIN1 编码定义
    const ENCODING_UTF8MB4  = 3; ///< UTF8MB4 编码定义, 4字节emoji表情要用,http://punchdrunker.github.io/iOSEmoji/table_html/flower.html
    // 数据库句柄需要ping 重连
    const HANDLE_PING       = 100;

    // 数据库句柄不能 重连
    const NOT_HANDLE_PING   = 200;

}
