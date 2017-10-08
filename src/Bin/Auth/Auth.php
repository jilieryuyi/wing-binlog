<?php namespace Wing\Bin\Auth;
use Wing\Bin\Constant\CapabilityFlag;
use Wing\Bin\Net;
use Wing\Bin\Packet;

/**
 * Auth.php
 * User: huangxiaoan
 * Created: 2017/9/11 18:26
 * Email: 297341015@qq.com
 *
 * 连接mysql，完成认证
 */
class Auth
{
	/**
	 * @var Resource $socket socket资源句柄
	 */
	private static $socket;
	/**
	 * 连接mysql、认证连接，初始化Net::$socket
	 *
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 * @param string $db_name
	 * @param int $port
	 * @return array
	 */
	public static function execute($host, $user, $password, $db_name, $port)
	{
		if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
			$error_code = socket_last_error();
			throw new \Exception(sprintf("Unable to create a socket: %s", socket_strerror($error_code)), $error_code);
		}

		socket_set_block($socket);
		socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
		//socket_set_option($this->socket, SOL_SOCKET,SO_SNDTIMEO, ['sec' => 2, 'usec' => 5000]);
		//socket_set_option($this->socket, SOL_SOCKET,SO_RCVTIMEO, ['sec' => 2, 'usec' => 5000]);

		//连接到mysql
		if (!socket_connect($socket, $host, $port)) {
			$error_code = socket_last_error();
			$error_msg  = sprintf('error:%s, msg:%s', socket_last_error(), socket_strerror($error_code));
			throw new \Exception($error_msg, $error_code);
		}

		self::$socket = Net::$socket = $socket;
		$serverinfo   = self::auth($user, $password, $db_name);

		return [self::$socket, $serverinfo];
	}

	/**
	 * 认证
	 *
	 * @param string $user
	 * @param string $password
	 * @param string $db
	 * @throws \Exception
	 * @return ServerInfo|null
	 */
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

        //希望的服务器权能信息
        $flag = CapabilityFlag::DEFAULT_CAPABILITIES;
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
		if (!Net::send($data)) {
			return null;
		}

		$result = Net::readPacket();
		// 认证是否成功
		Packet::success($result);
		return $server_info;
	}

	/**
	 * 释放socket资源，关闭socket连接
	 */
	public static function free()
	{
		socket_close(self::$socket);
	}
}