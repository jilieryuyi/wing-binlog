<?php namespace Wing\Bin\Auth;
use Wing\Bin\Constant\CapabilityFlag;
use Wing\Bin\Context;
use Wing\Bin\Net;
use Wing\Bin\Packet;
use Wing\Bin\PacketAuth;

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
		//mysql认证流程
		// 1、socket连接服务器
		// 2、读取服务器返回的信息，关键是获取到加盐信息，用于后面生成加密的password
		// 3、生成auth协议包
		// 4、发送协议包，认证完成


		// 获取server信息 加密salt
		$pack   	 = Net::readPacket();
		$server_info = ServerInfo::parse($pack);
var_dump("capability_flag", $server_info->capability_flag);

        //希望的服务器权能信息
        $flag = CapabilityFlag::DEFAULT_CAPABILITIES | $server_info->capability_flag;
        if ($db) {
            $flag |= CapabilityFlag::CLIENT_CONNECT_WITH_DB;
        }

		//认证
		$data = Packet::getAuth($flag, $user, $password, $server_info->salt,  $db);
		Net::send($data);

		$result = Net::readPacket();
		// 认证是否成功
		PacketAuth::success($result);
	}
}