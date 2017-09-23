<?php namespace Wing\Bin\RowEvents;
use Wing\Bin\BinLogColumns;
use Wing\Bin\BinLogPack;
use Wing\Bin\Constant\EventType;
use Wing\Bin\Db;
use Wing\Bin\Packet;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/23
 * Time: 17:42
 */
class TableMapEvent extends BinlogEvent
{
	
	public function Parse()
	{
		//$event    = new self($packet, EventType::TABLE_MAP_EVENT);
		$table_id = $this->readTableId();
		$flags 	  = unpack('S', $this->packet->read(2))[1];

		$schema_length = unpack("C", $this->packet->read(1))[1];
		$schema_name   = $this->packet->read($schema_length);

		// 00
		$this->packet->read(1);

		$table_length = unpack("C", $this->packet->read(1))[1];
		$table_name   = $this->packet->read($table_length);

		// 00
		$this->packet->read(1);

		$columns_num     = $this->readCodedBinary();
		$column_type_def = $this->packet->read($columns_num);


		$data = [
			'schema_name'=> $schema_name,
			'table_name' => $table_name,
			'table_id'   => $table_id
		];
		
		//避免重复读取表信息
		if (isset(self::$TABLE_MAP[$schema_name][$table_name]['table_id'])
			&& self::$TABLE_MAP[$schema_name][$table_name]['table_id']== $table_id) {
			return $data;//self::$TABLE_MAP[$schema_name][$table_name];
		}

		self::$TABLE_MAP[$schema_name][$table_id] = 

		$this->readCodedBinary();

		// fields 相应属性
		$colums = Db::getFields($schema_name, $table_name);

		self::$TABLE_MAP[$schema_name][$table_name]['fields'] = [];

		for ($i = 0; $i < strlen($column_type_def); $i++) {
			$type = ord($column_type_def[$i]);
			if (!isset($colums[$i])) {
				//抛出一个警告
				//wing_log("slave_warn", var_export($colums, true).var_export($data, true));
			}
			self::$TABLE_MAP[$schema_name][$table_name]['fields'][$i] = BinLogColumns::parse($type, $colums[$i], self::$PACK);
		}

		return $data;//self::$TABLE_MAP[$schema_name][$table_id];
	}
}