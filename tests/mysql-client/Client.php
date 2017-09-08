<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/7
 * Time: 22:54
 */
define("DEBUG", true);



class Log {



    public static function out($message, $category = 'out') {
        $file = __DIR__."/debug.log";
        return self::_write($message, $category, $file);
    }
    public static function error($message, $category, $file) {
        return self::_write($message, $category, $file);
    }

    public static function warn($message, $category, $file ) {
        return self::_write($message, $category, $file);
    }

    public static function notice($message, $category, $file ) {
        return self::_write($message, $category, $file);
    }


    private static function _write($message, $category, $file) {
        return	file_put_contents(
            $file,
            $category . '|' . date('Y-m-d H:i:s') . '|'. $message . "\n",
            FILE_APPEND
        );

    }
}









class DBMysql {

    /**
     * 已打开的db handle
     */
    private static $_HANDLE_ARRAY   = array();
    private static $_HANDLE_CONFIG  = array();


    private static function _getHandleKey($params) {
        ksort($params);
        return md5(implode('_' , $params));
    }


    /// 根据数据库表述的参数获取数据库操作句柄
    /// @param[in] array $db_config_array, 是一个array类型的数据结构，必须有host, username, password 三个熟悉, port为可选属性， 缺省值分别为3306
    /// @param[in] string $db_name, 数据库名称
    /// @param[in] enum $encoding, 从$DBConstNamespace中数据库编码相关的常量定义获取, 有缺省值 $DBConstNamespace::ENCODING_UTF8
    /// @return 非FALSE表示成功获取hadnle， 否则返回FALSE
    public static function createDBHandle($encoding = DBConstNamespace::ENCODING_UTF8) {
        $db_config_array['db_name']     = Client::$db;
        $db_config_array['encoding']    = $encoding;
        $db_config_array['host']        = Client::$host;
        $db_config_array['username']    = Client::$user;
        $db_config_array['password']    = Client::$password;
        $db_config_array['port']        = Client::$port;


        self::$_HANDLE_CONFIG = $db_config_array;

        $handle_key = self::_getHandleKey($db_config_array);

        $port = 3306;
        do {
            if (!is_array($db_config_array))
                break;
            if (!is_string(Client::$db))
                break;
            if (strlen(Client::$db) == 0)
                break;
            if (!array_key_exists('host', $db_config_array))
                break;
            if (!array_key_exists('username', $db_config_array))
                break;
            if (!array_key_exists('password', $db_config_array))
                break;
            if (array_key_exists('port', $db_config_array)) {
                $port = (int)($db_config_array['port']);
                if (($port < 1024) || ($port > 65535))
                    break;
            }
            $host = $db_config_array['host'];
            if (strlen($host) == 0)
                break;
            $username = $db_config_array['username'];
            if (strlen($username) == 0)
                break;
            $password = $db_config_array['password'];
            if (strlen($password) == 0)
                break;

            $handle = @mysqli_connect($host, $username, $password, Client::$db, $port);
            // 如果连接失败，再重试2次
            for ($i = 1; ($i < 3) && (FALSE === $handle); $i++) {
                // 重试前需要sleep 50毫秒
                usleep(50000);
                $handle = @mysqli_connect($host, $username, $password, Client::$db, $port);
            }
            if (FALSE === $handle)
                break;

            if (FALSE === mysqli_set_charset($handle, "utf8")) {
                self::logError( sprintf("Connect Set Charset Failed2:%s", mysqli_error($handle)), 'mysqlns.connect');
                mysqli_close($handle);
                break;
            }


            self::$_HANDLE_ARRAY[$handle_key]    = $handle;

            return $handle;
        } while (FALSE);

        // to_do, 连接失败
        self::logError( sprintf("Connect failed:time=%s", date('Y-m-d H:i:s',time())), 'mysqlns.connect');
        return FALSE;
    }

    /// 释放通过getDBHandle或者getDBHandleByName 返回的句柄资源
    /// @param[in] handle $handle, 你懂的
    /// @return void
    public static function releaseDBHandle($handle) {
        if (!self::_checkHandle($handle))
            return;
        foreach (self::$_HANDLE_ARRAY as $handle_key => $handleObj) {
            if ($handleObj->thread_id == $handle->thread_id) {
                unset(self::$_HANDLE_ARRAY[$handle_key]);
            }
        }
        mysqli_close($handle);
    }

    /// 将所有结果存入数组返回
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @param[in] string $sql, 具体执行的sql语句
    /// @return FALSE表示执行失败， 否则返回执行的结果, 结果格式为一个数组，数组中每个元素都是mysqli_fetch_assoc的一条结果
    public static function query($handle, $sql) {
        do {
            if (($result = self::mysqliQueryApi($handle, $sql)) === FALSE){
                break;
            }
            if ($result === true) {
                self::logWarn("err.func.query,SQL=$sql", 'mysqlns.query' );
                return array();
            }
            $res = array();
            while($row = mysqli_fetch_assoc($result)) {
                $res[] = $row;
            }
            mysqli_free_result($result);
            return $res;
        } while (FALSE);
        // to_do, execute sql语句失败， 需要记log
        self::logError( "SQL Error: $sql, errno=" . self::getLastError($handle), 'mysqlns.sql');

        return FALSE;
    }

    /// 将查询的第一条结果返回
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @param[in] string $sql, 具体执行的sql语句
    /// @return FALSE表示执行失败， 否则返回执行的结果, 执行结果就是mysqli_fetch_assoc的结果
    public static function queryFirst($handle, $sql) {
        if (!self::_checkHandle($handle))
            return FALSE;
        do {
            if (($result = self::mysqliQueryApi($handle, $sql)) === FALSE)
                break;
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return $row;
        } while (FALSE);
        // to_do, execute sql语句失败， 需要记log
        self::logError( "SQL Error: $sql," . self::getLastError($handle), 'mysqlns.sql');
        return FALSE;
    }

    /**
     * 将所有结果存入数组返回
     * @param Mysqli $handle 句柄
     * @param string $sql 查询语句
     * @return FALSE表示执行失败， 否则返回执行的结果, 结果格式为一个数组，数组中每个元素都是mysqli_fetch_assoc的一条结果
     */
    public static function getAll($handle , $sql) {
        return self::query($handle, $sql);
    }

    /**
     * 将查询的第一条结果返回
     * @param[in] Mysqli $handle, 操作数据库的句柄
     * @param[in] string $sql, 具体执行的sql语句
     * @return FALSE表示执行失败， 否则返回执行的结果, 执行结果就是mysqli_fetch_assoc的结果
     */
    public static function getRow($handle , $sql) {
        return self::queryFirst($handle, $sql);
    }

    /**
     * 查询第一条结果的第一列
     * @param Mysqli $handle, 操作数据库的句柄
     * @param string $sql, 具体执行的sql语句
     */
    public static function getOne($handle , $sql) {
        $row    = self::getRow($handle, $sql);
        if (is_array($row))
            return current($row);
        return $row;
    }

    /// 得到最近一次操作影响的行数
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @return FALSE表示执行失败， 否则返回影响的行数
    public static function lastAffected($handle) {
        if (!is_object($handle))
            return FALSE;
        $affected_rows = mysqli_affected_rows($handle);
        if ($affected_rows < 0)
            return FALSE;
        return $affected_rows;
    }

    /*
     *  返回最后一次查询自动生成并使用的id
     *  @param[in] handle $handle, 操作数据库的句柄
     *  @return FALSE表示执行失败， 否则id
     */
    public static function getLastInsertId($handle) {
        if (!is_object($handle)) {
            return false ;
        }
        if (($lastInsertId = mysqli_insert_id($handle)) <= 0) {
            return false ;
        }
        return $lastInsertId;
    }

    /// 得到最近一次操作错误的信息
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @return FALSE表示执行失败， 否则返回 'errorno: errormessage'
    public static function getLastError($handle) {
        if(($handle)) {
            return mysqli_errno($handle).': '.mysqli_error($handle);
        }
        return FALSE;
    }

    /**
     * @brief 检查handle
     * @param[in] handle $handle, 操作数据库的句柄
     * @return boolean true|成功, false|失败
     */
    private static function _checkHandle($handle, $log_category = 'mysqlns.handle') {
        if (!is_object($handle) || $handle->thread_id < 1) {
            if ($log_category) {
                self::logError(sprintf("handle Error: handle='%s'",var_export($handle, true)), $log_category);
            }
            return false;
        }
        return true;
    }


    public static function mysqliQueryApi($handle, $sql) {
        do {
            $result = mysqli_query($handle, $sql);

            return $result;
        } while (0);
        return false;
    }

    /**
     * @breif 记录统一错误日志
     */
    protected static function logError($message, $category) {
        Log::error( $message, $category , __DIR__."/binlog-error.log");
    }

    /**
     * @breif 记录统一警告日志
     */
    protected static function logWarn($message, $category) {

        Log::warn( $message, $category , __DIR__."/binlog-warn.log");

    }
}

class DBHelper {

    /**
     * @brief 获取字段相关信息
     * @param $schema
     * @param $table
     * @return array|bool
     */
    public static function getFields($schema, $table) {

        $db  = DBMysql::createDBHandle();
        $sql = "SELECT
                COLUMN_NAME,COLLATION_NAME,CHARACTER_SET_NAME,COLUMN_COMMENT,COLUMN_TYPE,COLUMN_KEY
                FROM
                information_schema.columns
                WHERE
                table_schema = '{$schema}' AND table_name = '{$table}'";
        $result = DBMysql::query($db,$sql);
        DBMysql::releaseDBHandle($db);
        return $result;
    }

    /**
     * @brief 是否使用checksum
     * @return array|bool
     */
    public static function isCheckSum() {
        $db  = DBMysql::createDBHandle();
        $sql = "SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'";
        $res = DBMysql::getRow($db,$sql);
        DBMysql::releaseDBHandle($db);
        if($res['Value']) return true;
        return false;
    }

    /**
     * @breif 获取主库状态pos，file
     * @return FALSE表示执行失败
     */
    public static function getPos() {
        $db     = DBMysql::createDBHandle();
        $sql    = "SHOW MASTER STATUS";
        $result = DBMysql::getRow($db,$sql);
        DBMysql::releaseDBHandle($db);
        return $result;
    }
}

class Slave
{
    public static $host = '127.0.0.1';
    public static $port = 3306;
    public static $password = '123456';
    public static $user = 'root';
    public static $db = 'xsl';

    private $socket;
    private $checksum = false;
    private $slave_server_id = 100;
    private $file;
    private $pos;

    public function __construct()
    {

        if (($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
            throw new \Exception( sprintf( "Unable to create a socket: %s", socket_strerror( socket_last_error())));
        }
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
//         socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_SNDTIMEO,['sec' => 2, 'usec' => 5000]);
//         socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_RCVTIMEO,['sec' => 2, 'usec' => 5000]);

        $flag = ConstCapability::$CAPABILITIES;//BinHelper::capabilities(self::$db) ;//| S::$MULTI_STATEMENTS;
        if (self::$db) {
            $flag |= ConstCapability::$CONNECT_WITH_DB;
        }

        //self::$_FLAG |= S::$MULTI_RESULTS;

        // 连接到mysql
        // create socket
        if(!socket_connect($this->socket, self::$host, self::$port)) {
            throw new \Exception(
                sprintf(
                    'error:%s, msg:%s',
                    socket_last_error(),
                    socket_strerror(socket_last_error())
                )
            );
        }

        // 获取server信息
        $pack   = self::_readPacket();
        ServerInfo::run($pack);
        // 加密salt
        $salt = ServerInfo::getSalt();

        // 认证
        // pack拼接
        $data = PackAuth::initPack($flag, self::$user, self::$password, $salt,  self::$db);

        $this->_write($data);
        //
        $result = $this->_readPacket();

        // 认证是否成功
        PackAuth::success($result);

        //
        self::getBinlogStream();
    }


    private function _write($data) {
        if(socket_write($this->socket, $data, strlen($data))=== false )
        {
            throw new \Exception( sprintf( "Unable to write to socket: %s", socket_strerror( socket_last_error())));
        }
        return true;
    }
    private function _readBytes($data_len) {

        // server gone away
        if ($data_len == 5) {
            throw new \Exception('read 5 bytes from mysql server has gone away');
        }

        try{
            $bytes_read = 0;
            $body       = '';
            while ($bytes_read < $data_len) {
                $resp = socket_read($this->socket, $data_len - $bytes_read);

                //
                if($resp === false) {
                    throw new \Exception(
                        sprintf(
                            'remote host has closed. error:%s, msg:%s',
                            socket_last_error(),
                            socket_strerror(socket_last_error())
                        ));
                }

                // server kill connection or server gone away
                if(strlen($resp) === 0){
                    throw new \Exception("read less " . ($data_len - strlen($body)));
                }
                $body .= $resp;
                $bytes_read += strlen($resp);
            }
            if (strlen($body) < $data_len){
                throw new \Exception("read less " . ($data_len - strlen($body)));
            }
            return $body;
        } catch (Exception $e) {
            throw new \Exception(var_export($e, true));
        }

    }
    private function _readPacket() {
        //消息头
        $header = $this->_readBytes(4);
        if($header === false) return false;
        //消息体长度3bytes 小端序
        $unpack_data = unpack("L",$header[0].$header[1].$header[2].chr(0))[1];
        $result = $this->_readBytes($unpack_data);
        return $result;
    }
    public function excute($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',$chunk_size, 0x03);
        $this->_write($prelude . $sql);
    }

    /**
     * @breif 注册成slave
     * @return void
     */
    private function _writeRegisterSlaveCommand() {
        $header   = pack('l', 18);

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_REGISTER_SLAVE);
        $data .= pack('L', $this->slave_server_id);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);

        $data .= pack('s', '');

        $data .= pack('L', 0);
        $data .= pack('L', 1);

        $this->_write($data);

        $result = $this->_readPacket();
        PackAuth::success($result);
    }

    public function getBinlogStream() {

        // checksum
        $this->checksum = DBHelper::isCheckSum();
        if($this->checksum){
            $this->excute("set @master_binlog_checksum= @@global.binlog_checksum");
        }
        //heart_period
        $heart = 5;
        if($heart) {
            $this->excute("set @master_heartbeat_period=".($heart*1000000000));
        }

        $this->_writeRegisterSlaveCommand();

        // 开始读取的二进制日志位置
        if(!$this->file) {
            $logInfo = DBHelper::getPos();
            $this->file = $logInfo['File'];
            if(!$this->pos) {
                $this->pos = $logInfo['Position'];
            }
        }

        // 初始化
        BinLogPack::setFilePos($this->file, $this->pos);

        $header   = pack('l', 11 + strlen($this->file));

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', $this->pos);
        $data .= pack('s', 0);
        $data .= pack('L', $this->slave_server_id);
        $data .= $this->file;

        self::_write($data);

        //认证
        $result = self::_readPacket();
        PackAuth::success($result);
    }

    public function analysisBinLog($flag = false) {

        $pack   = $this->_readPacket();

        // 校验数据包格式
        PackAuth::success($pack);

        //todo eof pack 0xfe

        $binlog = BinLogPack::getInstance();
        $result = $binlog->init($pack, $this->checksum);

        // debug
            echo round(memory_get_usage()/1024/1024, 2).'MB',"\r\n";

        //持久化当前读到的file pos

            if($result) var_dump($result);

    }

}

$client = new Slave();
while (1)$client->analysisBinLog();