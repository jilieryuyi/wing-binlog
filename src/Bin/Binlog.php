<?php namespace Wing\Bin;
/**
 * Binlog.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:13
 * Email: huangxiaoan@xunlei.com
 */
class Binlog
{
	private $binlog_file,
		$last_pos,
		$checksum,
		$slave_server_id;

	public function __construct(
		$binlog_file,
		$last_pos,
		$checksum,
		$slave_server_id
	)
	{

		$this->binlog_file = $binlog_file;
		$this->last_pos = $last_pos;
		$this->checksum = $checksum;
		$this->slave_server_id = $slave_server_id;
		// checksum
		if ($checksum){
			Mysql::query("set @master_binlog_checksum= @@global.binlog_checksum");
		}
		//heart_period
		$heart = 5;
		if ($heart) {
			Mysql::query("set @master_heartbeat_period=".($heart*1000000000));
		}

        $data = Packet::registerSlave($slave_server_id);
        Net::send($data);
        $result = Net::readPacket();
        Packet::success($result);

		// 开始读取的二进制日志位置
		if(!$binlog_file) {
//            $sql  = 'show binary logs';
//            $res  = $this->pdo->query($sql);

			$logInfo = Db::getPos();
			//如果没有配置 则从第一个有效的binlog开始
			$binlog_file = $logInfo['File'];//$res[0]["Log_name"];
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
		BinLogPack::setFilePos($binlog_file, $last_pos);

//		$header = pack('l', 11 + strlen($binlog_file));
//
//		// COM_BINLOG_DUMP
//		$data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
//		$data .= pack('L', $last_pos);
//		$data .= pack('s', 0);
//		$data .= pack('L', $slave_server_id);
//		$data .= $binlog_file;
        //封包
        $data = Packet::binlogDump($binlog_file, $last_pos, $slave_server_id);

		Net::send($data);

		//认证
		$result = Net::readPacket();
		Packet::success($result);
	}

	public function getBinlogEvents() {

		$pack   = Net::readPacket();

		// 校验数据包格式
		Packet::success($pack);

		$binlog = BinLogPack::getInstance();
		$result = $binlog->init($pack, $this->checksum);
		return $result;
	}
}