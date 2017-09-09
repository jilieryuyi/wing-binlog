<?php namespace Wing\Library;
use Wing\Bin\RowEvent;
use Wing\FileSystem\WDir;

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
    /**
     * @var bool
     */
    private $checksum        = false;
    private $slave_server_id = 99999;
    private $pdo;

    private $last_binlog_file;
    private $last_pos;

    public function __construct()
    {
        $config         = load_config("app");
        $this->host     = $config["mysql"]["host"];
        $this->port     = $config["mysql"]["port"];
        $this->password = $config["mysql"]["password"];
        $this->user     = $config["mysql"]["user"];
        $this->db       = $config["mysql"]["db_name"];
        $this->pdo      = RowEvent::$pdo = new PDO();
        $this->checksum = !!$this->getCheckSum();

        $this->last_binlog_file = null;
        $file = HOME."/cache/slave/last_binlog_file";
        $dir  = new WDir(HOME."/cache/slave");
        $dir->mkdir();
        unset($dir);
        if (!file_exists($file))
        touch($file);
        $this->last_binlog_file = file_get_contents($file);



        $this->last_pos = 0;
        $file = HOME."/cache/slave/last_pos_file";
        if (!file_exists($file))
        touch($file);
        $this->last_pos = file_get_contents($file);

        \Wing\Bin\ConstCapability::init();

        if (($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
            throw new \Exception( sprintf( "Unable to create a socket: %s", socket_strerror( socket_last_error())));
        }

        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
        //socket_set_option($this->socket, SOL_SOCKET,SO_SNDTIMEO, ['sec' => 2, 'usec' => 5000]);
        //socket_set_option($this->socket, SOL_SOCKET,SO_RCVTIMEO, ['sec' => 2, 'usec' => 5000]);

        $flag = \Wing\Bin\ConstCapability::$CAPABILITIES;
        if ($this->db) {
            $flag |= \Wing\Bin\ConstCapability::$CONNECT_WITH_DB;
        }


        //连接到mysql
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
        $pack   = $this->_readPacket();
        \Wing\Bin\ServerInfo::run($pack);
        // 加密salt
        $salt   = \Wing\Bin\ServerInfo::getSalt();

        // 认证
        // pack拼接
        $data = \Wing\Bin\PackAuth::initPack($flag, $this->user, $this->password, $salt,  $this->db);

        $this->sendData($data);
        //
        $result = $this->_readPacket();

        // 认证是否成功
        \Wing\Bin\PackAuth::success($result);

        //
        self::getBinlogStream();
    }


    private function sendData($data) {
        if(socket_write($this->socket, $data, strlen($data))=== false ) {
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

    protected function excute($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude    = pack('LC',$chunk_size, 0x03);
        $this->sendData($prelude . $sql);
    }

    /**
     * 注册成slave
     */
    private function registerAsSlave()
    {
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

        $this->sendData($data);

        $result = $this->_readPacket();
        \Wing\Bin\PackAuth::success($result);
    }

    protected function getCheckSum()
    {
        $res = $this->pdo->row("SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'");
        return $res['Value'];
    }
    protected function getPos() {
        $sql    = "SHOW MASTER STATUS";
        $result = $this->pdo->row($sql);
        return $result;
    }
    public function getBinlogStream()
    {

        // checksum
        if($this->checksum){
            $this->excute("set @master_binlog_checksum= @@global.binlog_checksum");
        }
        //heart_period
        $heart = 5;
        if ($heart) {
            $this->excute("set @master_heartbeat_period=".($heart*1000000000));
        }

        $this->registerAsSlave();

        // 开始读取的二进制日志位置
        if(!$this->last_binlog_file) {
//            $sql  = 'show binary logs';
//            $res  = $this->pdo->query($sql);

            $logInfo = $this->getPos();
            //如果没有配置 则从第一个有效的binlog开始
            $this->last_binlog_file = $logInfo['File'];//$res[0]["Log_name"];
//            foreach ($res as $item) {
//                if ($item["File_size"] > 0) {
//                    $this->last_binlog_file = $item["Log_name"];
//                    break;
//                }
//            }
            if(!$this->last_pos) {
                //起始位置必须大于等于4
                $this->last_pos = $logInfo['Position'];
            }
        }
        var_dump($this->last_binlog_file, $this->last_pos);

        // 初始化
        \Wing\Bin\BinLogPack::setFilePos($this->last_binlog_file, $this->last_pos);

        $header = pack('l', 11 + strlen($this->last_binlog_file));

        // COM_BINLOG_DUMP
        $data  = $header . chr(\Wing\Bin\ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', $this->last_pos);
        $data .= pack('s', 0);
        $data .= pack('L', $this->slave_server_id);
        $data .= $this->last_binlog_file;

        $this->sendData($data);

        //认证
        $result = $this->_readPacket();
        \Wing\Bin\PackAuth::success($result);
    }

    public function getEvent() {

        $pack   = $this->_readPacket();

        // 校验数据包格式
        \Wing\Bin\PackAuth::success($pack);

        $binlog = \Wing\Bin\BinLogPack::getInstance();
        $result = $binlog->init($pack, $this->checksum);

        $file = HOME."/cache/slave/last_binlog_file";
        $bin_file = $binlog->getLastBinLogFile();
        if (0 >= file_put_contents($file, $bin_file))
            file_put_contents($file, $bin_file);

        $file = HOME."/cache/slave/last_pos_file";
        $pos = $binlog->getLastPos();
        if (0 >= file_put_contents($file, $pos))
            file_put_contents($file, $pos);

        return $result;
    }

}