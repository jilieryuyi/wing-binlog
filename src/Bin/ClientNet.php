<?php namespace Wing\Bin;
use Wing\Library\PDO;

/**
 * ClientSocket.php
 * User: huangxiaoan
 * Created: 2017/9/11 18:26
 * Email: huangxiaoan@xunlei.com
 * @property PDO $pdo
 */
class ClientNet
{
	private $socket;
	private $pdo;
	private $checksum;
	public function __construct($host, $port)
	{
		if (($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
			throw new \Exception(sprintf("Unable to create a socket: %s", socket_strerror(socket_last_error())));
		}

		socket_set_block($this->socket);
		socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
		//socket_set_option($this->socket, SOL_SOCKET,SO_SNDTIMEO, ['sec' => 2, 'usec' => 5000]);
		//socket_set_option($this->socket, SOL_SOCKET,SO_RCVTIMEO, ['sec' => 2, 'usec' => 5000]);

		//连接到mysql
		if(!socket_connect($this->socket, $host, $port)) {
			throw new \Exception(
				sprintf(
					'error:%s, msg:%s',
					socket_last_error(),
					socket_strerror(socket_last_error())
				)
			);
		}

		$this->pdo      = RowEvent::$pdo = new PDO();

		$res = $this->pdo->row("SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'");
		$this->checksum = !!$res['Value'];
	}

	public function auth($user, $password, $db)
	{
		$flag = CapabilityFlag::CAPABILITIES;
		if ($db) {
			$flag |= CapabilityFlag::CLIENT_CONNECT_WITH_DB;
		}
		// 获取server信息 加密salt
		$pack   	 = $this->readPacket();
		$server_info = new ServerInfo($pack);
		var_dump($server_info);
		$salt   	 = $server_info->getSalt();

		// 认证
		// pack拼接
		$data = PacketAuth::getAuthPack($flag, $user, $password, $salt,  $db);

		$this->send($data);
		//
		$result = $this->readPacket();

		// 认证是否成功
		PacketAuth::success($result);
	}

	public function send($data)
	{
		if(socket_write($this->socket, $data, strlen($data))=== false ) {
			throw new \Exception( sprintf( "Unable to write to socket: %s", socket_strerror( socket_last_error())));
		}
		return true;
	}
	private function _readBytes($data_len)
	{

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
	public function excute($sql) {
		$chunk_size = strlen($sql) + 1;
		$prelude    = pack('LC',$chunk_size, CommandType::COM_QUERY);
		$this->send($prelude . $sql);
		$res = $this->readPacket();
		file_put_contents(HOME."/logs/sql_debug.log", $res, FILE_APPEND);
	}
/*
func (mc *mysqlConn) getWarnings() (err error) {
rows, err := mc.Query("SHOW WARNINGS", nil)
if err != nil {
return
}

var warnings = MySQLWarnings{}
	var values = make([]driver.Value, 3)

	for {
        err = rows.Next(values)
		switch err {
            case nil:
                warning := MySQLWarning{}

			if raw, ok := values[0].([]byte); ok {
                warning.Level = string(raw)
			} else {
                warning.Level = fmt.Sprintf("%s", values[0])
			}
			if raw, ok := values[1].([]byte); ok {
                warning.Code = string(raw)
			} else {
                warning.Code = fmt.Sprintf("%s", values[1])
			}
			if raw, ok := values[2].([]byte); ok {
                warning.Message = string(raw)
			} else {
                warning.Message = fmt.Sprintf("%s", values[0])
			}

			warnings = append(warnings, warning)

		case io.EOF:
            return warnings

		default:
            rows.Close()
			return
		}
	}
}
	*/
    public function excute2($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_PREPARE);
        $this->send($prelude . $sql);
        $res = $this->readPacket();

        $this->_readBytes(4);

        var_dump(PacketAuth::success($res));

        //0000000: 0001 0000 0017 0000 0000 0000 0a
        //stmt.id
var_dump(unpack("L", $res[1].$res[2].$res[3].chr(0)));

//cloumns count
        var_dump(unpack("n", $res[4].$res[5].$res[6].$res[7]));


//        if !stmt.mc.strict {
//            return columnCount, nil
//		}
//
//        // Check for warnings count > 0, only available in MySQL > 4.1
//        if len(data) >= 12 && binary.LittleEndian.Uint16(data[10:12]) > 0 {
//            return columnCount, stmt.mc.getWarnings()
//		}

//        columnCount := binary.LittleEndian.Uint16(data[5:7])
//
//		// Param count [16 bit uint]
//		stmt.paramCount = int(binary.LittleEndian.Uint16(data[7:9]))
//
//		// Reserved [8 bit]
//
//		// Warning count [16 bit uint]
//		if !stmt.mc.strict {
//            return columnCount, nil
		//}

        file_put_contents(HOME."/logs/sql_debug.log", $res, FILE_APPEND);
    }

    public function excute3($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_EXECUTE);
        $this->send($prelude . $sql);
        $res = $this->readPacket();

        $this->_readBytes(4);

        var_dump(PacketAuth::success($res));

        //0000000: 0001 0000 0017 0000 0000 0000 0a
        //stmt.id
        var_dump(unpack("L", $res[1].$res[2].$res[3].chr(0)));

//cloumns count
        var_dump(unpack("n", $res[4].$res[5].$res[6].$res[7]));


//        if !stmt.mc.strict {
//            return columnCount, nil
//		}
//
//        // Check for warnings count > 0, only available in MySQL > 4.1
//        if len(data) >= 12 && binary.LittleEndian.Uint16(data[10:12]) > 0 {
//            return columnCount, stmt.mc.getWarnings()
//		}

//        columnCount := binary.LittleEndian.Uint16(data[5:7])
//
//		// Param count [16 bit uint]
//		stmt.paramCount = int(binary.LittleEndian.Uint16(data[7:9]))
//
//		// Reserved [8 bit]
//
//		// Warning count [16 bit uint]
//		if !stmt.mc.strict {
//            return columnCount, nil
        //}

        file_put_contents(HOME."/logs/sql_debug.log", $res, FILE_APPEND);
    }

	public function readPacket()
	{
		//消息头
		$header = $this->_readBytes(4);
		var_dump($header);
		if($header === false) return false;
		//消息体长度3bytes 小端序
		$unpack_data = unpack("L",$header[0].$header[1].$header[2].chr(0))[1];
		echo "length:";
		var_dump($unpack_data);

        var_dump(ord($header[0]) | ord($header[1])<<8 | ord($header[2])<<16);


		$result = $this->_readBytes($unpack_data);
		var_dump($result);
		return $result;
	}

	/**
	 * 注册成slave
	 * @param int $slave_server_id
	 */
	private function _registerAsSlave($slave_server_id)
	{
		$header   = pack('l', 18);

		// COM_BINLOG_DUMP
		$data  = $header . chr(ConstCommand::COM_REGISTER_SLAVE);
		$data .= pack('L', $slave_server_id);
		$data .= chr(0);
		$data .= chr(0);
		$data .= chr(0);

		$data .= pack('s', '');

		$data .= pack('L', 0);
		$data .= pack('L', 1);

		$this->send($data);

		$result = $this->readPacket();
		PacketAuth::success($result);
	}

	public function getCheckSum()
	{
		return $this->checksum;
	}
	protected function getPos() {
		$sql    = "SHOW MASTER STATUS";
		$result = $this->pdo->row($sql);
		return $result;
	}

	public function asSlave($slave_server_id, $last_binlog_file, $last_pos)
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

		$this->_registerAsSlave($slave_server_id);

		// 开始读取的二进制日志位置
		if(!$last_binlog_file) {
//            $sql  = 'show binary logs';
//            $res  = $this->pdo->query($sql);

			$logInfo = $this->getPos();
			//如果没有配置 则从第一个有效的binlog开始
			$last_binlog_file = $logInfo['File'];//$res[0]["Log_name"];
//            foreach ($res as $item) {
//                if ($item["File_size"] > 0) {
//                    $this->last_binlog_file = $item["Log_name"];
//                    break;
//                }
//            }
			if(!$last_pos) {
				//起始位置必须大于等于4
				$last_pos = $logInfo['Position'];
			}
		}


		// 初始化
		BinLogPack::setFilePos($last_binlog_file, $last_pos);

		$header = pack('l', 11 + strlen($last_binlog_file));

		// COM_BINLOG_DUMP
		$data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
		$data .= pack('L', $last_pos);
		$data .= pack('s', 0);
		$data .= pack('L', $slave_server_id);
		$data .= $last_binlog_file;

		$this->send($data);

		//认证
		$result = $this->readPacket();
		PacketAuth::success($result);
	}

	public function getEvent() {

		$pack   = $this->readPacket();

		// 校验数据包格式
		PacketAuth::success($pack);

		$binlog = BinLogPack::getInstance();
		$result = $binlog->init($pack, $this->checksum);


		return $result;
	}
}