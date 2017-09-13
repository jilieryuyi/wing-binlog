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
	 * 注册成slave
	 * @param int $slave_server_id
	 */
	private static function _sendRegisterSlavePacket($slave_server_id)
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

		Net::send($data);

		$result = Net::readPacket();
		PacketAuth::success($result);
	}

	public function registerSlave($slave_server_id)
	{

		$last_binlog_file = null; $last_pos = 4;
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