<?php namespace Wing\Bin\Auth;
use Symfony\Component\Console\Command\Command;
use Wing\Bin\Constant\CapabilityFlag;
use Wing\Bin\Constant\CommandType;
use Wing\Bin\Context;
use Wing\Bin\Net;
use Wing\Bin\PacketAuth;
use Wing\Bin\RowEvent;
use Wing\Library\PDO;

/**
 * ClientSocket.php
 * User: huangxiaoan
 * Created: 2017/9/11 18:26
 * Email: huangxiaoan@xunlei.com
 */
class Auth
{
//	private static $socket;
//	private static $pdo;
//	private static $checksum;

	public static function execute(Context &$context)
	{
		if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
			throw new \Exception(sprintf("Unable to create a socket: %s", socket_strerror(socket_last_error())));
		}

		socket_set_block($socket);
		socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
		//socket_set_option($this->socket, SOL_SOCKET,SO_SNDTIMEO, ['sec' => 2, 'usec' => 5000]);
		//socket_set_option($this->socket, SOL_SOCKET,SO_RCVTIMEO, ['sec' => 2, 'usec' => 5000]);

		//连接到mysql
		if(!socket_connect($socket, $context->host, $context->port)) {
			throw new \Exception(
				sprintf(
					'error:%s, msg:%s',
					socket_last_error(),
					socket_strerror(socket_last_error())
				)
			);
		}

		$context->socket = Net::$socket = $socket;
		self::auth($context->user, $context->password, $context->db_name);
	}

	private static function auth($user, $password, $db)
	{
		$flag = CapabilityFlag::DEFAULT_CAPABILITIES;
		if ($db) {
			$flag |= CapabilityFlag::CLIENT_CONNECT_WITH_DB;
		}
		// 获取server信息 加密salt
		$pack   	 = Net::readPacket();
		$server_info = ServerInfo::parse($pack);

		// 认证
		// pack拼接
		$data = PacketAuth::getAuthPack($flag, $user, $password, $server_info->salt,  $db);

		Net::send($data);
		//
		$result = Net::readPacket();

		// 认证是否成功
		PacketAuth::success($result);
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