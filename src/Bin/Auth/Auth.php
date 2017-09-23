<?php namespace Wing\Bin\Auth;
use Wing\Bin\Constant\CapabilityFlag;
use Wing\Bin\Context;
use Wing\Bin\Net;
use Wing\Bin\Packet;

/**
 * ClientSocket.php
 * User: huangxiaoan
 * Created: 2017/9/11 18:26
 * Email: huangxiaoan@xunlei.com
 */
class Auth
{
	private static $socket;
//	private static $pdo;
//	private static $checksum;

	//Context &$context
	public static function execute($host, $user, $password, $db_name, $port)
	{
		if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
			throw new \Exception(sprintf("Unable to create a socket: %s", socket_strerror(socket_last_error())));
		}

		socket_set_block($socket);
		socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
		//socket_set_option($this->socket, SOL_SOCKET,SO_SNDTIMEO, ['sec' => 2, 'usec' => 5000]);
		//socket_set_option($this->socket, SOL_SOCKET,SO_RCVTIMEO, ['sec' => 2, 'usec' => 5000]);

		//连接到mysql
		if(!socket_connect($socket, $host, $port)) {
			throw new \Exception(
				sprintf(
					'error:%s, msg:%s',
					socket_last_error(),
					socket_strerror(socket_last_error())
				)
			);
		}

		self::$socket = Net::$socket = $socket;
		$serverinfo = self::auth($user, $password, $db_name);
		return [self::$socket, $serverinfo];
	}

	private static function auth($user, $password, $db)
	{
		//mysql认证流程
		// 1、socket连接服务器
		// 2、读取服务器返回的信息，关键是获取到加盐信息，用于后面生成加密的password
		// 3、生成auth协议包
		// 4、发送协议包，认证完成


		// 获取server信息 加密salt
		$pack   	 = Net::readPacket();
		$server_info = ServerInfo::parse($pack);
//var_dump("capability_flag", $server_info->capability_flag);

        //希望的服务器权能信息
        $flag = CapabilityFlag::DEFAULT_CAPABILITIES;//| CapabilityFlag::CLIENT_SECURE_CONNECTION ;//| $server_info->capability_flag;
        if ($db) {
            $flag |= CapabilityFlag::CLIENT_CONNECT_WITH_DB;
        }
        /**
		clientFlags := clientProtocol41 |
		clientSecureConn |
		clientLongPassword |
		clientTransactions |
		clientLocalFiles |
		clientPluginAuth |
		clientMultiResults |
		mc.flags&clientLongFlag
		 * if mc.cfg.ClientFoundRows {
		clientFlags |= clientFoundRows
		}

		// To enable TLS / SSL
		if mc.cfg.tls != nil {
		clientFlags |= clientSSL
		}

		if mc.cfg.MultiStatements {
		clientFlags |= clientMultiStatements
		}

		 */


		//认证
		$data = Packet::getAuth($flag, $user, $password, $server_info->salt,  $db);
		Net::send($data);

		$result = Net::readPacket();
		// 认证是否成功
		Packet::success($result);
		return $server_info;
	}

	public static function free()
	{
		socket_close(self::$socket);

	}
}