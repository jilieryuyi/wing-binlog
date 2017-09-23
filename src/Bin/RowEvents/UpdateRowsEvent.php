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

	private function columnFormat($cols_bitmap)
	{
		$values = [];
		$len    = (int)((self::bitCount($cols_bitmap) + 7) / 8);

		//null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
		//See http://dev.mysql.com/doc/internals/en/rows-event.html

		$null_bitmap 	   = $this->packet->read($len);
		$null_bitmap_index = 0;
		$fields            = self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'];

		foreach ($fields as $i => $value) {
			$column   = $value;
			$name     = $value['name'];
			$unsigned = $value['unsigned'];


			if (self::BitGet($cols_bitmap, $i) == 0) {
				$values[$name] = null;
				continue;
			}

			if (self::_is_null($null_bitmap, $null_bitmap_index)) {
				$values[$name] = null;
			} elseif ($column['type'] == FieldType::TINY) {
				if ($unsigned)
					$values[$name] = unpack("C", self::$PACK->read(1))[1];
				else
					$values[$name] = unpack("c", self::$PACK->read(1))[1];
			} elseif ($column['type'] == FieldType::SHORT) {
				if ($unsigned)
					$values[$name] = unpack("S", self::$PACK->read(2))[1];
				else
					$values[$name] = unpack("s", self::$PACK->read(2))[1];
			} elseif ($column['type'] == FieldType::LONG) {

				if ($unsigned) {
					$values[$name] = unpack("I", self::$PACK->read(4))[1];
				} else {
					$values[$name] = unpack("i", self::$PACK->read(4))[1];
				}
			} elseif ($column['type'] == FieldType::INT24) {
				if ($unsigned)
					$values[$name] = self::$PACK->readUint24();
				else
					$values[$name] = self::$PACK->read_int24();
			} elseif ($column['type'] == FieldType::FLOAT)
				$values[$name] = unpack("f", self::$PACK->read(4))[1];
			elseif ($column['type'] == FieldType::DOUBLE)
				$values[$name] = unpack("d", self::$PACK->read(8))[1];
			elseif ($column['type'] == FieldType::VARCHAR ||
				$column['type'] == FieldType::STRING
			) {
				if ($column['max_length'] > 255)
					$values[$name] = self::_read_string(2, $column);
				else
					$values[$name] = self::_read_string(1, $column);
			} elseif ($column['type'] == FieldType::NEWDECIMAL) {

				//$precision = unpack('C', self::$PACK->read(1))[1];
				//$decimals  = unpack('C', self::$PACK->read(1))[1];

//var_dump($precision,$decimals);exit;
//precision = metadata[:precision]
//        scale = metadata[:decimals]
				$values[$name] = self::read_new_decimal($column);
			} elseif ($column['type'] == FieldType::BLOB) {
				//ok
				$values[$name] = self::_read_string($column['length_size'], $column);

			}
			elseif ($column['type'] == FieldType::DATETIME) {

				$values[$name] = self::_read_datetime();
			} elseif ($column['type'] == FieldType::DATETIME2) {
				//ok
				$values[$name] = self::_read_datetime2($column);
			}elseif ($column['type'] == FieldType::TIME2) {

				$values[$name] = self::_read_time2($column);
			}
			elseif ($column['type'] == FieldType::TIMESTAMP2){
				//ok
				$time = date('Y-m-d H:i:m',self::$PACK->read_int_be_by_size(4));
				// 微妙
				$time .= '.' . self::_add_fsp_to_time($column);
				$values[$name] = $time;
			}
			elseif ($column['type'] == FieldType::DATE)
				$values[$name] = self::_read_date();
			/*
		elseif ($column['type'] == FieldType::TIME:
			$values[$name] = self.__read_time()
		elseif ($column['type'] == FieldType::DATE:
			$values[$name] = self.__read_date()
			*/
			elseif ($column['type'] == FieldType::TIMESTAMP) {
				$values[$name] = date('Y-m-d H:i:s', self::$PACK->readUint32());
			}

			# For new date format:
			/*
						elseif ($column['type'] == FieldType::TIME2:
							$values[$name] = self.__read_time2(column)
						elseif ($column['type'] == FieldType::TIMESTAMP2:
							$values[$name] = self.__add_fsp_to_time(
									datetime.datetime.fromtimestamp(
										self::$PACK->read_int_be_by_size(4)), column)
						*/
			elseif ($column['type'] == FieldType::LONGLONG) {
				if ($unsigned) {
					$values[$name] = self::$PACK->readUint64();
				} else {
					$values[$name] = self::$PACK->readInt64();
				}

			} elseif($column['type'] == FieldType::ENUM) {
				$values[$name] = $column['enum_values'][self::$PACK->read_uint_by_size($column['size']) - 1];
			} else {
			}
			/*
			elseif ($column['type'] == FieldType::YEAR:
				$values[$name] = self::$PACK->read_uint8() + 1900
			elseif ($column['type'] == FieldType::SET:
				# We read set columns as a bitmap telling us which options
				# are enabled
				bit_mask = self::$PACK->read_uint_by_size(column.size)
				$values[$name] = set(
					val for idx, val in enumerate(column.set_values)
				if bit_mask & 2 ** idx
				) or None

			elseif ($column['type'] == FieldType::BIT:
				$values[$name] = self.__read_bit(column)
			elseif ($column['type'] == FieldType::GEOMETRY:
				$values[$name] = self::$PACK->read_length_coded_pascal_string(
						column.length_size)
			else:
				raise NotImplementedError("Unknown MySQL column type: %d" %
					(column.type))
			*/
			$null_bitmap_index += 1;
		}
		//$values['table_name'] = self::$TABLE_NAME;
		return $values;
	}


}