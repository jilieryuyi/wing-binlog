<?php namespace Wing\Library;
use Wing\Bin\RowEvent;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 22:57
 */
class Slave
{
    private $host;// = '127.0.0.1';
    private $port;// = 3306;
    private $password;// = '123456';
    private $user;// = 'root';
    private $db;// = 'xsl';

    private $socket;
    private $checksum        = false;
    private $slave_server_id = 99999;
    private $file;
    private $pos;
    private $pdo;

    public function __construct()
    {
        $config = load_config("app");
        /**
        "mysql" => [
            "db_name"  => "wordpress",
            "host"     => "127.0.0.1",
            "user"     => "root",
            "password" => "123456",
         */
        $this->host = $config["mysql"]["host"];
        $this->port = $config["mysql"]["port"];
        $this->password = $config["mysql"]["password"];
        $this->user = $config["mysql"]["user"];
        $this->db = $config["mysql"]["db_name"];
        $this->pdo = RowEvent::$pdo = new PDO();

        \Wing\Bin\ConstCapability::init();

        if (($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
            throw new \Exception( sprintf( "Unable to create a socket: %s", socket_strerror( socket_last_error())));
        }

        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
//         socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_SNDTIMEO,['sec' => 2, 'usec' => 5000]);
//         socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_RCVTIMEO,['sec' => 2, 'usec' => 5000]);

        $flag = \Wing\Bin\ConstCapability::$CAPABILITIES;//BinHelper::capabilities(self::$db) ;//| S::$MULTI_STATEMENTS;
        if ($this->db) {
            $flag |= \Wing\Bin\ConstCapability::$CONNECT_WITH_DB;
        }

        //self::$_FLAG |= S::$MULTI_RESULTS;

        // 连接到mysql
        // create socket
        if(!socket_connect($this->socket, $this->host, $this->port)) {
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
        \Wing\Bin\ServerInfo::run($pack);
        // 加密salt
        $salt = \Wing\Bin\ServerInfo::getSalt();

        // 认证
        // pack拼接
        $data = \Wing\Bin\PackAuth::initPack($flag, $this->user, $this->password, $salt,  $this->db);

        $this->_write($data);
        //
        $result = $this->_readPacket();

        // 认证是否成功
        \Wing\Bin\PackAuth::success($result);

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
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            //throw new \Exception(var_export($e, true));
        }
        return null;
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
        $data  = $header . chr(\Wing\Bin\ConstCommand::COM_REGISTER_SLAVE);
        $data .= pack('L', $this->slave_server_id);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);

        $data .= pack('s', '');

        $data .= pack('L', 0);
        $data .= pack('L', 1);

        $this->_write($data);

        $result = $this->_readPacket();
        \Wing\Bin\PackAuth::success($result);
    }

    protected function isCheckSum()
    {
        $res = $this->pdo->row("SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'");
        return $res['Value'];
    }
    protected function getPos() {
        $sql    = "SHOW MASTER STATUS";
        $result = $this->pdo->row($sql);
        return $result;
    }
    public function getBinlogStream() {

        // checksum
        $this->checksum = $this->isCheckSum();
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
            $logInfo = $this->getPos();
            $this->file = $logInfo['File'];
            if(!$this->pos) {
                $this->pos = $logInfo['Position'];
            }
        }

        // 初始化
        \Wing\Bin\BinLogPack::setFilePos($this->file, $this->pos);

        $header   = pack('l', 11 + strlen($this->file));

        // COM_BINLOG_DUMP
        $data  = $header . chr(\Wing\Bin\ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', $this->pos);
        $data .= pack('s', 0);
        $data .= pack('L', $this->slave_server_id);
        $data .= $this->file;

        self::_write($data);

        //认证
        $result = self::_readPacket();
        \Wing\Bin\PackAuth::success($result);
    }

    public function analysisBinLog() {

        $pack   = $this->_readPacket();
        wing_log("wing_debug", $pack);

        // 校验数据包格式
        \Wing\Bin\PackAuth::success($pack);

        //todo eof pack 0xfe

        $binlog = \Wing\Bin\BinLogPack::getInstance();
        $result = $binlog->init($pack, $this->checksum);

        // debug
       // echo round(memory_get_usage()/1024/1024, 2).'MB',"\r\n";

        //持久化当前读到的file pos

        if($result) var_dump($result);

    }

}