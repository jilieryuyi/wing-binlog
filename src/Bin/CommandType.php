<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/12
 * Time: 07:05
 */
class CommandType
{
    const COM_SLEEP = 0;
    const COM_QUIT = 1;
    const COM_INIT_DB = 2;
    const COM_QUERY = 3;
    const COM_FIELD_LIST = 4;
    const COM_CREATE_DB = 5;
    const COM_DROP_DB = 6;
    const COM_REFRESH = 7;
    const COM_SHUTDOWN = 8;
    const COM_STATISTICS = 9;
    const COM_PROCESS_INFO = 10;
    const COM_CONNECT = 11;
    const COM_PROCESS_KILL = 12;
    const COM_DEBUG = 13;
    const COM_PING = 14;
    const COM_TIME = 15;
    const COM_DELAYED_INSERT = 16;
    const COM_CHANGE_USER = 17;
    const COM_BINLOG_DUMP = 18;
    const COM_TABLE_DUMP = 19;
    const COM_CONNECT_OUT = 20;
    const COM_REGISTER_SLAVE = 21;
    const COM_STMT_PREPARE = 22;
    const COM_STMT_EXECUTE = 23;
    const COM_STMT_SEND_LONG_DATA = 24;
    const COM_STMT_CLOSE = 25;
    const COM_STMT_RESET = 26;
    const COM_SET_OPTION = 27;
    const COM_STMT_FETCH = 28;
    const COM_DAEMON = 29;
    const COM_BINLOG_DUMP_GTID = 30;
    const COM_RESET_CONNECTION = 31;
    const COM_END = 32;
}