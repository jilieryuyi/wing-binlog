<?php namespace Wing\Bin\RowEvents;
use Wing\Bin\Constant\EventType;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/23
 * Time: 18:31
 */
class UpdateRowsEvent extends BinlogEvent
{

	public function parse()
	{

		$table_id = $this->readTableId();

		if ($this->event_type == EventType::UPDATE_ROWS_EVENT_V2) {
			$flags 			   = unpack('S', $this->packet->read(2))[1];
			$extra_data_length = unpack('S', $this->packet->read(2))[1];
			$extra_data  	   = $this->packet->read($extra_data_length / 8);
		} else {
			$flags = unpack('S', $this->packet->read(2))[1];
		}

		$columns_num = $this->readCodedBinary();

		$len     = intval(($columns_num + 7) / 8);
		$bitmap1 = $this->packet->read($len);
		$bitmap2 = $this->packet->read($len);


		//nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
		//$value['table'] = "";
		//$value['update'] = self::_getUpdateRows($result, $len);


		$value = [
			"database" => self::$SCHEMA_NAME,
			"table"    => self::$TABLE_NAME,
			"event"    =>  [
				"event_type" => "update_rows",
				"data"       => $this->getUpdateRows($bitmap1, $bitmap2)
			]
		];

		return $value;
	}

	private function getUpdateRows($bitmap1, $bitmap2) {
		$rows = [];
		while(!$this->packet->isComplete($this->packet_size)) {
			$rows[] = [
				"old_data" => $this->columnFormat($bitmap1),
				"new_data" => $this->columnFormat($bitmap2)
			];
		}
		return $rows;
	}
}