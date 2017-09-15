<?php namespace Wing\Bin;
/**
 * Binlog.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:13
 * Email: huangxiaoan@xunlei.com
 */
class Binlog
{
	/**
	 * @var Context
	 */
	public static $context;

	public static function registerSlave()
	{

		$last_binlog_file = self::$context->last_binlog_file;
		$last_pos = self::$context->last_pos;
		// checksum
		if (self::$context->checksum){
			Mysql::query("set @master_binlog_checksum= @@global.binlog_checksum");
		}
		//heart_period
		$heart = 5;
		if ($heart) {
			Mysql::query("set @master_heartbeat_period=".($heart*1000000000));
		}

        $data = Packet::registerSlave(self::$context->slave_server_id);
        Net::send($data);
        $result = Net::readPacket();
        Packet::success($result);

		// 开始读取的二进制日志位置
		if(!$last_binlog_file) {
//            $sql  = 'show binary logs';
//            $res  = $this->pdo->query($sql);

			$logInfo = Db::getPos();
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

//		$header = pack('l', 11 + strlen($last_binlog_file));
//
//		// COM_BINLOG_DUMP
//		$data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
//		$data .= pack('L', $last_pos);
//		$data .= pack('s', 0);
//		$data .= pack('L', self::$context->slave_server_id);
//		$data .= $last_binlog_file;
        //封包
        $data = Packet::binlogDump($last_binlog_file, $last_pos, self::$context->slave_server_id);

		Net::send($data);

		//认证
		$result = Net::readPacket();
		Packet::success($result);
	}

	public static function getEvent() {

		$pack   = Net::readPacket();

		// 校验数据包格式
		Packet::success($pack);

		$binlog = BinLogPack::getInstance();
		$result = $binlog->init($pack, self::$context->checksum);
		return $result;
	}
}