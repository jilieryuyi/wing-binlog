<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 07:01
 */
class PacketAuth
{
    // 2^24 - 1 16m
    const PACK_MAX_LENGTH = 16777215;
    // https://dev.mysql.com/doc/dev/mysql-server/latest/page_protocol_basic_ok_packet.html
    // 00 FE
    const OK_PACK_HEAD = [0, 254];
    // FF
    const ERR_PACK_HEAD = [255];

}