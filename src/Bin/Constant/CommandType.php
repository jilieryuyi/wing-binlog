<?php namespace Wing\Bin\Constant;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/12
 * Time: 07:05
 * 用于标识当前请求消息的类型，例如切换数据库（0x02）、查询命令（0x03）等。
 * 命令值的取值范围及说明如下表
 * （参考MySQL源代码mysql-server/include/my_command.h头文件中的定义）
 * 类型值	命令	功能	关联函数
	0x00	COM_SLEEP	（内部线程状态）	（无）
	0x01	COM_QUIT	关闭连接	mysql_close
	0x02	COM_INIT_DB	切换数据库	mysql_select_db
	0x03	COM_QUERY	SQL查询请求	mysql_real_query
	0x04	COM_FIELD_LIST	获取数据表字段信息	mysql_list_fields
	0x05	COM_CREATE_DB	创建数据库	mysql_create_db
	0x06	COM_DROP_DB	删除数据库	mysql_drop_db
	0x07	COM_REFRESH	清除缓存	mysql_refresh
	0x08	COM_SHUTDOWN	停止服务器	mysql_shutdown
	0x09	COM_STATISTICS	获取服务器统计信息	mysql_stat
	0x0A	COM_PROCESS_INFO	获取当前连接的列表	mysql_list_processes
	0x0B	COM_CONNECT	（内部线程状态）	（无）
	0x0C	COM_PROCESS_KILL	中断某个连接	mysql_kill
	0x0D	COM_DEBUG	保存服务器调试信息	mysql_dump_debug_info
	0x0E	COM_PING	测试连通性	mysql_ping
	0x0F	COM_TIME	（内部线程状态）	（无）
	0x10	COM_DELAYED_INSERT	（内部线程状态）	（无）
	0x11	COM_CHANGE_USER	重新登陆（不断连接）	mysql_change_user
	0x12	COM_BINLOG_DUMP	获取二进制日志信息	（无）
	0x13	COM_TABLE_DUMP	获取数据表结构信息	（无）
	0x14	COM_CONNECT_OUT	（内部线程状态）	（无）
	0x15	COM_REGISTER_SLAVE	从服务器向主服务器进行注册	（无）
	0x16	COM_STMT_PREPARE	预处理SQL语句	mysql_stmt_prepare
	0x17	COM_STMT_EXECUTE	执行预处理语句	mysql_stmt_execute
	0x18	COM_STMT_SEND_LONG_DATA	发送BLOB类型的数据	mysql_stmt_send_long_data
	0x19	COM_STMT_CLOSE	销毁预处理语句	mysql_stmt_close
	0x1A	COM_STMT_RESET	清除预处理语句参数缓存	mysql_stmt_reset
	0x1B	COM_SET_OPTION	设置语句选项	mysql_set_server_option
	0x1C	COM_STMT_FETCH	获取预处理语句的执行结果	mysql_stmt_fetch
 */
class CommandType
{
    const COM_SLEEP                 = 0;
    const COM_QUIT                  = 1;
    const COM_INIT_DB               = 2;
    const COM_QUERY                 = 3;
    const COM_FIELD_LIST            = 4;
    const COM_CREATE_DB             = 5;
    const COM_DROP_DB               = 6;
    const COM_REFRESH               = 7;
    const COM_SHUTDOWN              = 8;
    const COM_STATISTICS            = 9;
    const COM_PROCESS_INFO          = 10;
    const COM_CONNECT               = 11;
    const COM_PROCESS_KILL          = 12;
    const COM_DEBUG                 = 13;
    const COM_PING                  = 14;
    const COM_TIME                  = 15;
    const COM_DELAYED_INSERT        = 16;
    const COM_CHANGE_USER           = 17;
    const COM_BINLOG_DUMP           = 18;
    const COM_TABLE_DUMP            = 19;
    const COM_CONNECT_OUT           = 20;
    const COM_REGISTER_SLAVE        = 21;
    const COM_STMT_PREPARE          = 22;
    const COM_STMT_EXECUTE          = 23;
    const COM_STMT_SEND_LONG_DATA   = 24;
    const COM_STMT_CLOSE            = 25;
    const COM_STMT_RESET            = 26;
    const COM_SET_OPTION            = 27;
    const COM_STMT_FETCH            = 28;
    const COM_DAEMON                = 29;
    const COM_BINLOG_DUMP_GTID      = 30;
    const COM_RESET_CONNECTION      = 31;
    const COM_END                   = 32;
}