<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/7
 * Time: 22:54
 */
define("DEBUG", true);
class ConstFieldType {

    const DECIMAL = 0;
    const TINY = 1;
    const SHORT = 2;
    const LONG = 3;
    const FLOAT = 4;
    const DOUBLE = 5;
    const NULL = 6;
    const TIMESTAMP = 7;
    const LONGLONG = 8;
    const INT24 = 9;
    const DATE = 10;
    const TIME = 11;
    const DATETIME = 12;
    const YEAR = 13;
    const NEWDATE = 14;
    const VARCHAR = 15;
    const BIT = 16;
    const TIMESTAMP2 = 17;
    const DATETIME2 = 18;
    const TIME2 = 19;
    const NEWDECIMAL = 246;
    const ENUM = 247;
    const SET = 248;
    const TINY_BLOB = 249;
    const MEDIUM_BLOB = 250;
    const LONG_BLOB = 251;
    const BLOB = 252;
    const VAR_STRING = 253;
    const STRING = 254;
    const GEOMETRY = 255;

    const CHAR = self::TINY;
    const INTERVAL = self::ENUM;

}
class BinLogColumns {

    private static $field;

    public static function parse($column_type, $column_schema, $packet) {

        self::$field = [];

        self::$field['type'] = $column_type;
        self::$field['name'] = $column_schema["COLUMN_NAME"];
        self::$field['collation_name'] = $column_schema["COLLATION_NAME"];
        self::$field['character_set_name'] = $column_schema["CHARACTER_SET_NAME"];
        self::$field['comment'] = $column_schema["COLUMN_COMMENT"];
        self::$field['unsigned'] = stripos($column_schema["COLUMN_TYPE"], 'unsigned') === false ? false : true;
        self::$field['type_is_bool'] = false;
        self::$field['is_primary'] = $column_schema["COLUMN_KEY"] == "PRI";

        if (self::$field['type'] == ConstFieldType::VARCHAR) {
            self::$field['max_length'] = unpack('s', $packet->read(2))[1];
        }elseif (self::$field['type'] == ConstFieldType::DOUBLE){
            self::$field['size'] = $packet->readUint8();
        }elseif (self::$field['type'] == ConstFieldType::FLOAT){
            self::$field['size'] = $packet->readUint8();
        }elseif (self::$field['type'] == ConstFieldType::TIMESTAMP2){
            self::$field['fsp'] = $packet->readUint8();
        }elseif (self::$field['type'] == ConstFieldType::DATETIME2){
            self::$field['fsp']= $packet->readUint8();
        }elseif (self::$field['type'] == ConstFieldType::TIME2) {
            self::$field['fsp'] = $packet->readUint8();
        }elseif (self::$field['type'] == ConstFieldType::TINY && $column_schema["COLUMN_TYPE"] == "tinyint(1)") {
            self::$field['type_is_bool'] = True;
        }elseif (self::$field['type'] == ConstFieldType::VAR_STRING || self::$field['type'] == ConstFieldType::STRING){
            self::_read_string_metadata($packet, $column_schema);
        }elseif( self::$field['type'] == ConstFieldType::BLOB){
            self::$field['length_size'] = $packet->readUint8();
        }elseif (self::$field['type'] == ConstFieldType::GEOMETRY){
            self::$field['length_size'] = $packet->readUint8();
        }elseif( self::$field['type'] == ConstFieldType::NEWDECIMAL){
            self::$field['precision'] = $packet->readUint8();
            self::$field['decimals'] = $packet->readUint8();
        }elseif (self::$field['type'] == ConstFieldType::BIT) {
            $bits = $packet->readUint8();
            $bytes = $packet->readUint8();
            self::$field['bits'] = ($bytes * 8) + $bits;
            self::$field['bytes'] = int((self::$field['bits'] + 7) / 8);
        }
        return self::$field;
    }

    private static function _read_string_metadata($packet, $column_schema){

        $metadata = ($packet->readUint8() << 8) + $packet->readUint8();
        $real_type = $metadata >> 8;
        if($real_type == ConstFieldType::SET || $real_type == ConstFieldType::ENUM) {
            self::$field['type'] = $real_type;
            self::$field['size'] = $metadata & 0x00ff;
            self::_read_enum_metadata($column_schema);
        } else {
            self::$field['max_length'] = ((($metadata >> 4) & 0x300) ^ 0x300) + ($metadata & 0x00ff);
        }
    }
    private static function _read_enum_metadata($column_schema) {
        $enums = $column_schema["COLUMN_TYPE"];
        if (self::$field['type'] == ConstFieldType::ENUM) {
            $enums = str_replace('enum(', '', $enums);
            $enums = str_replace(')', '', $enums);
            $enums = str_replace('\'', '', $enums);
            self::$field['enum_values'] = explode(',', $enums);
        } else {
            $enums = str_replace('set(', '', $enums);
            $enums = str_replace(')', '', $enums);
            $enums = str_replace('\'', '', $enums);
            self::$field['set_values'] = explode(',', $enums);
        }
    }

}
class ConstEventType {

    const UNKNOWN_EVENT    = 0,
        START_EVENT_V3         = 1,
        QUERY_EVENT= 2,
        STOP_EVENT= 3,
        ROTATE_EVENT= 4,
        INTVAR_EVENT= 5,
        LOAD_EVENT= 6,
        SLAVE_EVENT= 7,
        CREATE_FILE_EVENT= 8,
        APPEND_BLOCK_EVENT= 9,
        EXEC_LOAD_EVENT= 10,
        DELETE_FILE_EVENT= 11,
        NEW_LOAD_EVENT= 12,
        RAND_EVENT= 13,
        USER_VAR_EVENT= 14,
        FORMAT_DESCRIPTION_EVENT= 15;

    //Transaction ID for 2PC, written whenever a COMMIT is expected.
    const XID_EVENT= 16,
        BEGIN_LOAD_QUERY_EVENT= 17,
        EXECUTE_LOAD_QUERY_EVENT= 18;

    const GTID_LOG_EVENT= 33;
    const ANONYMOUS_GTID_LOG_EVENT= 34;
    const PREVIOUS_GTIDS_LOG_EVENT= 35;

    const INCIDENT_EVENT       = 26;
    const HEARTBEAT_LOG_EVENT  = 27;
    const IGNORABLE_LOG_EVENT  = 28;
    const ROWS_QUERY_LOG_EVENT = 29;

    // Row-Based Binary Logging
    // TABLE_MAP_EVENT,WRITE_ROWS_EVENT
    // UPDATE_ROWS_EVENT,DELETE_ROWS_EVENT
    const TABLE_MAP_EVENT          = 19;

    // MySQL 5.1.5 to 5.1.17,
    const PRE_GA_WRITE_ROWS_EVENT  = 20;
    const PRE_GA_UPDATE_ROWS_EVENT = 21;
    const PRE_GA_DELETE_ROWS_EVENT = 22;

    // MySQL 5.1.15 to 5.6.x
    const WRITE_ROWS_EVENT_V1  = 23;
    const UPDATE_ROWS_EVENT_V1 = 24;
    const DELETE_ROWS_EVENT_V1 = 25;

    // MySQL 5.6.x
    const WRITE_ROWS_EVENT_V2  = 30;
    const UPDATE_ROWS_EVENT_V2 = 31;
    const DELETE_ROWS_EVENT_V2 = 32;


    public static $EVENT = [

    ];

}
class Log {



    public static function out($message, $category = 'out') {
        $file = __DIR__."/debug.log";
        return self::_write($message, $category, $file);
    }
    public static function error($message, $category, $file) {
        return self::_write($message, $category, $file);
    }

    public static function warn($message, $category, $file ) {
        return self::_write($message, $category, $file);
    }

    public static function notice($message, $category, $file ) {
        return self::_write($message, $category, $file);
    }


    private static function _write($message, $category, $file) {
        return	file_put_contents(
            $file,
            $category . '|' . date('Y-m-d H:i:s') . '|'. $message . "\n",
            FILE_APPEND
        );

    }
}
class ConstMy {
    # Constants from PyMYSQL source code
    const NULL_COLUMN = 251;
    const UNSIGNED_CHAR_COLUMN  = 251;
    const UNSIGNED_SHORT_COLUMN = 252;
    const UNSIGNED_INT24_COLUMN = 253;
    const UNSIGNED_INT64_COLUMN = 254;
    const UNSIGNED_CHAR_LENGTH  = 1;
    const UNSIGNED_SHORT_LENGTH = 2;
    const UNSIGNED_INT24_LENGTH = 3;
    const UNSIGNED_INT64_LENGTH = 8;
}
class BinLogEvent {


    public static $EVENT_TYPE;

    public static $TABLE_ID;
    public static $TABLE_NAME;

    public static $SCHEMA_NAME;

    public static $TABLE_MAP;
    public static $PACK;
    public static $PACK_SIZE;
    public static $FLAGS;
    public static $EXTRA_DATA_LENGTH;
    public static $EXTRA_DATA;
    public static $SCHEMA_LENGTH;
    public static $COLUMNS_NUM;

    public static $bitCountInByte = [
        0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        4, 5, 5, 6, 5, 6, 6, 7, 5, 6, 6, 7, 6, 7, 7, 8,
    ];

    public static function _init(BinLogPack $pack,$event_type, $size = 0) {

        self::$PACK       = $pack;
        self::$EVENT_TYPE = $event_type;
        self::$PACK_SIZE  = $size;
    }

    public static function readTableId()
    {
        $a = (int)(ord(self::$PACK->read(1)) & 0xFF);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 8);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 16);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 24);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 32);
        $a += (int)((ord(self::$PACK->read(1)) & 0xFF) << 40);
        return $a;
    }

    public static function bitCount($bitmap) {
        $n = 0;
        for($i=0;$i<strlen($bitmap);$i++) {
            $bit = $bitmap[$i];
            if(is_string($bit)) {
                $bit = ord($bit);
            }
            $n += self::$bitCountInByte[$bit];
        }
        return $n;
    }


}
class RowEvent extends BinLogEvent
{


    public static function rowInit(BinLogPack $pack, $event_type, $size)
    {
        parent::_init($pack, $event_type, $size);
        self::$TABLE_ID = self::readTableId();

        if (in_array(self::$EVENT_TYPE, [ConstEventType::DELETE_ROWS_EVENT_V2, ConstEventType::WRITE_ROWS_EVENT_V2, ConstEventType::UPDATE_ROWS_EVENT_V2])) {
            self::$FLAGS = unpack('S', self::$PACK->read(2))[1];

            self::$EXTRA_DATA_LENGTH = unpack('S', self::$PACK->read(2))[1];

            self::$EXTRA_DATA = self::$PACK->read(self::$EXTRA_DATA_LENGTH / 8);

        } else {
            self::$FLAGS = unpack('S', self::$PACK->read(2))[1];
        }

        // Body
        self::$COLUMNS_NUM = self::$PACK->readCodedBinary();
    }


    public static function tableMap(BinLogPack $pack, $event_type)
    {
        parent::_init($pack, $event_type);

        self::$TABLE_ID = self::readTableId();

        self::$FLAGS = unpack('S', self::$PACK->read(2))[1];

        $data = [];
        $data['schema_length'] = unpack("C", $pack->read(1))[1];

        $data['schema_name'] = self::$SCHEMA_NAME = $pack->read($data['schema_length']);

        // 00
        self::$PACK->advance(1);

        $data['table_length'] = unpack("C", self::$PACK->read(1))[1];
        $data['table_name'] = self::$TABLE_NAME = $pack->read($data['table_length']);

        // 00
        self::$PACK->advance(1);

        self::$COLUMNS_NUM = self::$PACK->readCodedBinary();

        //
        $column_type_def = self::$PACK->read(self::$COLUMNS_NUM);


        // 避免重复读取 表信息
        if (isset(self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['table_id'])
            && self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['table_id']== self::$TABLE_ID) {
            return $data;
        }


        self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME] = array(
            'schema_name'=> $data['schema_name'],
            'table_name' => $data['table_name'],
            'table_id'   => self::$TABLE_ID
        );


        self::$PACK->readCodedBinary();


        // fields 相应属性
        $colums = DBHelper::getFields($data['schema_name'], $data['table_name']);

        self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'] = [];

        for ($i = 0; $i < strlen($column_type_def); $i++) {
            $type = ord($column_type_def[$i]);
            if(!isset($colums[$i])){
                Log::warn(var_export($colums, true).var_export($data, true), 'tableMap', __DIR__."/binlog-error.log");
            }
            self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'][$i] = BinLogColumns::parse($type, $colums[$i], self::$PACK);

        }

        return $data;


    }

    public static function addRow(BinLogPack $pack, $event_type, $size)
    {
        self::rowInit($pack, $event_type, $size);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap'] = self::$PACK->read($len);

        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['add'] = self::_getAddRows($result, $len);
        return $value;
    }

    public static function delRow(BinLogPack $pack, $event_type, $size)
    {
        self::rowInit($pack, $event_type, $size);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap'] = self::$PACK->read($len);


        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['del'] = self::_getDelRows($result, $len);

        return $value;
    }

    public static function updateRow(BinLogPack $pack, $event_type, $size)
    {

        self::rowInit($pack, $event_type, $size);

        $result = [];
        // ？？？？
        //$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", self::$PACK->read(1))[1];
        //$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
        $len = (int)((self::$COLUMNS_NUM + 7) / 8);


        $result['bitmap1'] = self::$PACK->read($len);

        $result['bitmap2'] = self::$PACK->read($len);


        //nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
        $value['update'] = self::_getUpdateRows($result, $len);

        return $value;
    }

    public static function BitGet($bitmap, $position)
    {
        $bit = $bitmap[intval($position / 8)];

        if (is_string($bit)) {

            $bit = ord($bit);
        }

        return $bit & (1 << ($position & 7));
    }

    public static function _is_null($null_bitmap, $position)
    {
        $bit = $null_bitmap[intval($position / 8)];
        if (is_string($bit)) {
            $bit = ord($bit);
        }


        return $bit & (1 << ($position % 8));
    }

    private static function _read_string($size, $column)
    {
        $string = self::$PACK->read_length_coded_pascal_string($size);
        if ($column['character_set_name']) {
            //string = string . decode(column . character_set_name)
        }
        return $string;
    }

    private static function _read_column_data($cols_bitmap, $len)
    {
        $values = [];

        //$l = (int)(($len * 8 + 7) / 8);
        $l = (int)((self::bitCount($cols_bitmap) + 7) / 8);

        # null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
        # See http://dev.mysql.com/doc/internals/en/rows-event.html


        $null_bitmap = self::$PACK->read($l);

        $nullBitmapIndex = 0;
        foreach (self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'] as $i => $value) {
            $column = $value;
            $name = $value['name'];
            $unsigned = $value['unsigned'];


            if (self::BitGet($cols_bitmap, $i) == 0) {
                $values[$name] = null;
                continue;
            }

            if (self::_is_null($null_bitmap, $nullBitmapIndex)) {
                $values[$name] = null;
            } elseif ($column['type'] == ConstFieldType::TINY) {
                if ($unsigned)
                    $values[$name] = unpack("C", self::$PACK->read(1))[1];
                else
                    $values[$name] = unpack("c", self::$PACK->read(1))[1];
            } elseif ($column['type'] == ConstFieldType::SHORT) {
                if ($unsigned)
                    $values[$name] = unpack("S", self::$PACK->read(2))[1];
                else
                    $values[$name] = unpack("s", self::$PACK->read(2))[1];
            } elseif ($column['type'] == ConstFieldType::LONG) {

                if ($unsigned) {
                    $values[$name] = unpack("I", self::$PACK->read(4))[1];
                } else {
                    $values[$name] = unpack("i", self::$PACK->read(4))[1];

                }
            } elseif ($column['type'] == ConstFieldType::INT24) {
                if ($unsigned)
                    $values[$name] = self::$PACK->read_uint24();
                else
                    $values[$name] = self::$PACK->read_int24();
            } elseif ($column['type'] == ConstFieldType::FLOAT)
                $values[$name] = unpack("f", self::$PACK->read(4))[1];
            elseif ($column['type'] == ConstFieldType::DOUBLE)
                $values[$name] = unpack("d", self::$PACK->read(8))[1];
            elseif ($column['type'] == ConstFieldType::VARCHAR ||
                $column['type'] == ConstFieldType::STRING
            ) {
                if ($column['max_length'] > 255)
                    $values[$name] = self::_read_string(2, $column);
                else
                    $values[$name] = self::_read_string(1, $column);
            } elseif ($column['type'] == ConstFieldType::NEWDECIMAL) {
                //$values[$name] = self.__read_new_decimal(column)
            } elseif ($column['type'] == ConstFieldType::BLOB) {
                //ok
                $values[$name] = self::_read_string($column['length_size'], $column);

            }
            elseif ($column['type'] == ConstFieldType::DATETIME) {

                $values[$name] = self::_read_datetime();
            } elseif ($column['type'] == ConstFieldType::DATETIME2) {
                //ok
                $values[$name] = self::_read_datetime2($column);
            }elseif ($column['type'] == ConstFieldType::TIME2) {

                $values[$name] = self::_read_time2($column);
            }
            elseif ($column['type'] == ConstFieldType::TIMESTAMP2){
                //ok
                $time = date('Y-m-d H:i:m',self::$PACK->read_int_be_by_size(4));
                // 微妙
                $time .= '.' . self::_add_fsp_to_time($column);
                $values[$name] = $time;
            }
            elseif ($column['type'] == ConstFieldType::DATE)
                $values[$name] = self::_read_date();
            /*
        elseif ($column['type'] == ConstFieldType::TIME:
            $values[$name] = self.__read_time()
        elseif ($column['type'] == ConstFieldType::DATE:
            $values[$name] = self.__read_date()
            */
            elseif ($column['type'] == ConstFieldType::TIMESTAMP) {
                $values[$name] = date('Y-m-d H:i:s', self::$PACK->readUint32());
            }

            # For new date format:
            /*
                        elseif ($column['type'] == ConstFieldType::TIME2:
                            $values[$name] = self.__read_time2(column)
                        elseif ($column['type'] == ConstFieldType::TIMESTAMP2:
                            $values[$name] = self.__add_fsp_to_time(
                                    datetime.datetime.fromtimestamp(
                                        self::$PACK->read_int_be_by_size(4)), column)
                        */
            elseif ($column['type'] == ConstFieldType::LONGLONG) {
                if ($unsigned) {
                    $values[$name] = self::$PACK->readUint64();
                } else {
                    $values[$name] = self::$PACK->readInt64();
                }

            } elseif($column['type'] == ConstFieldType::ENUM) {
                $values[$name] = $column['enum_values'][self::$PACK->read_uint_by_size($column['size']) - 1];
            } else {
            }
            /*
            elseif ($column['type'] == ConstFieldType::YEAR:
                $values[$name] = self::$PACK->read_uint8() + 1900
            elseif ($column['type'] == ConstFieldType::SET:
                # We read set columns as a bitmap telling us which options
                # are enabled
                bit_mask = self::$PACK->read_uint_by_size(column.size)
                $values[$name] = set(
                    val for idx, val in enumerate(column.set_values)
                if bit_mask & 2 ** idx
                ) or None

            elseif ($column['type'] == ConstFieldType::BIT:
                $values[$name] = self.__read_bit(column)
            elseif ($column['type'] == ConstFieldType::GEOMETRY:
                $values[$name] = self::$PACK->read_length_coded_pascal_string(
                        column.length_size)
            else:
                raise NotImplementedError("Unknown MySQL column type: %d" %
                    (column.type))
            */
            $nullBitmapIndex += 1;
        }
        $values['table_name'] = self::$TABLE_NAME;
        return $values;
    }


    private static function _read_datetime()
    {
        $value = self::$PACK->readUint64();
        if ($value == 0)  # nasty mysql 0000-00-00 dates
            return null;

        $date = $value / 1000000;
        $time = (int)($value % 1000000);

        $year = (int)($date / 10000);
        $month = (int)(($date % 10000) / 100);
        $day = (int)($date % 100);
        if ($year == 0 or $month == 0 or $day == 0)
            return null;

        return $year.'-'.$month.'-'.$day .' '.intval($time / 10000).':'.intval(($time % 10000) / 100).':'.intval($time % 100);

    }

    private static function _read_date() {
        $time = self::$PACK->readUint24();

        if ($time == 0)  # nasty mysql 0000-00-00 dates
            return null;

        $year = ($time & ((1 << 15) - 1) << 9) >> 9;
        $month = ($time & ((1 << 4) - 1) << 5) >> 5;
        $day = ($time & ((1 << 5) - 1));
        if ($year == 0 || $month == 0 || $day == 0)
            return null;

        return $year.'-'.$month.'-'.$day;
    }

    private static function  _read_datetime2($column) {
        /*DATETIME

        1 bit  sign           (1= non-negative, 0= negative)
        17 bits year*13+month  (year 0-9999, month 0-12)
         5 bits day            (0-31)
         5 bits hour           (0-23)
         6 bits minute         (0-59)
         6 bits second         (0-59)
        ---------------------------
        40 bits = 5 bytes
        */
        $data = self::$PACK->read_int_be_by_size(5);

        $year_month = self::_read_binary_slice($data, 1, 17, 40);


        $year=(int)($year_month / 13);
        $month=$year_month % 13;
        $day=self::_read_binary_slice($data, 18, 5, 40);
        $hour=self::_read_binary_slice($data, 23, 5, 40);
        $minute=self::_read_binary_slice($data, 28, 6, 40);
        $second=self::_read_binary_slice($data, 34, 6, 40);
        if($hour < 10) {
            $hour ='0'.$hour;
        }
        if($minute < 10) {
            $minute = '0'.$minute;
        }
        if($second < 10) {
            $second = '0'.$second;
        }
        $time = $year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second;
        $microsecond = self::_add_fsp_to_time($column);
        if($microsecond) {
            $time .='.'.$microsecond;
        }
        return $time;
    }

    private static function _read_binary_slice($binary, $start, $size, $data_length) {
        /*
        Read a part of binary data and extract a number
        binary: the data
        start: From which bit (1 to X)
        size: How many bits should be read
        data_length: data size
        */
        $binary = $binary >> $data_length - ($start + $size);
        $mask = ((1 << $size) - 1);
        return $binary & $mask;
    }

    private static function _add_fsp_to_time($column)
    {
        /*Read and add the fractional part of time
            For more details about new date format:
            http://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
            */


        $read = 0;
        $time = '';
        if( $column['fsp'] == 1 or $column['fsp'] == 2)
            $read = 1;
        elseif($column['fsp'] == 3 or $column['fsp'] == 4)
            $read = 2;
        elseif ($column ['fsp'] == 5 or $column['fsp'] == 6)
            $read = 3;
        if ($read > 0) {
            $microsecond = self::$PACK->read_int_be_by_size($read);
            if ($column['fsp'] % 2)
                $time = (int)($microsecond / 10);
            else
                $time = $microsecond;
        }
        return $time;
    }




    private static function _getUpdateRows($result, $len) {
        $rows = [];
        while(!self::$PACK->isComplete(self::$PACK_SIZE)) {

            $value['beform'] = self::_read_column_data($result['bitmap1'], $len);
            $value['after'] = self::_read_column_data($result['bitmap2'], $len);
            $rows[] = $value['after'];
        }
        return $rows;
    }

    private static function _getDelRows($result, $len) {
        $rows = [];
        while(!self::$PACK->isComplete(self::$PACK_SIZE)) {
            $rows[] = self::_read_column_data($result['bitmap'], $len);
        }
        return $rows;
    }

    private static function  _getAddRows($result, $len) {
        $rows = [];

        while(!self::$PACK->isComplete(self::$PACK_SIZE)) {
            $rows[] = self::_read_column_data($result['bitmap'], $len);
        }
        return $rows;
    }
}
class BinLogPack {


    public static $EVENT_INFO;
    public static $EVENT_TYPE;

    private static $_PACK_KEY = 0;
    private static $_PACK;

    private static $_instance = null;

    // 持久化记录 file pos  不能在next event 为dml操作记录
    // 获取不到table map
    private static $_FILE_NAME;
    private static $_POS;


    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function init($pack, $checkSum = true) {

        if(!self::$_instance) {
            self::$_instance = new self();
        }

        //
        self::$_PACK       = $pack;
        self::$_PACK_KEY   = 0;
        self::$EVENT_INFO  = [];
        $this->advance(1);
        self::$EVENT_INFO['time'] = $timestamp  = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['type'] = self::$EVENT_TYPE = unpack('C', $this->read(1))[1];
        self::$EVENT_INFO['id']   = $server_id  = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['size'] = $event_size = unpack('L', $this->read(4))[1];

        //position of the next event
        self::$EVENT_INFO['pos']  = $log_pos    = unpack('L', $this->read(4))[1];//
        self::$EVENT_INFO['flag'] = $flags      = unpack('S', $this->read(2))[1];
        $event_size_without_header = $checkSum === true ? ($event_size -23) : $event_size - 19;
        $data = [];

        // 映射fileds相关信息
        if (self::$EVENT_TYPE == ConstEventType::TABLE_MAP_EVENT) {
            RowEvent::tableMap(self::getInstance(), self::$EVENT_TYPE);
        } elseif(in_array(self::$EVENT_TYPE,[ConstEventType::UPDATE_ROWS_EVENT_V2,ConstEventType::UPDATE_ROWS_EVENT_V1])) {
            $data =  RowEvent::updateRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
            self::$_POS = self::$EVENT_INFO['pos'];
        }elseif(in_array(self::$EVENT_TYPE,[ConstEventType::WRITE_ROWS_EVENT_V1, ConstEventType::WRITE_ROWS_EVENT_V2])) {
            $data = RowEvent::addRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
            self::$_POS = self::$EVENT_INFO['pos'];
        }elseif(in_array(self::$EVENT_TYPE,[ConstEventType::DELETE_ROWS_EVENT_V1, ConstEventType::DELETE_ROWS_EVENT_V2])) {
            $data = RowEvent::delRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
            self::$_POS = self::$EVENT_INFO['pos'];
        }elseif(self::$EVENT_TYPE == 16) {
            //var_dump(bin2hex($pack),$this->readUint64());
            //return RowEvent::delRow(self::getInstance(), self::$EVENT_TYPE);
        }elseif(self::$EVENT_TYPE == ConstEventType::ROTATE_EVENT) {
            $log_pos = $this->readUint64();
            self::$_FILE_NAME = $this->read($event_size_without_header-8);
        }elseif(self::$EVENT_TYPE == ConstEventType::GTID_LOG_EVENT) {
            //gtid event

        }elseif(self::$EVENT_TYPE == 15) {
            //$pack = self::getInstance();
            //$pack->read(4);
        } elseif(self::$EVENT_TYPE == ConstEventType::QUERY_EVENT) {

        } elseif(self::$EVENT_TYPE == ConstEventType::HEARTBEAT_LOG_EVENT) {
            //心跳检测机制
            $binlog_name = $this->read($event_size_without_header);
            echo 'heart beat '.$binlog_name."\n";
        }

        if(DEBUG) {
            $msg  = self::$_FILE_NAME;
            $msg .= '-- next pos -> '.$log_pos;
            $msg .= ' --  typeEvent -> '.self::$EVENT_TYPE;
            Log::out($msg);
        }
        return $data;
    }

    public function read($length) {
        $length = (int)$length;
        $n='';
        for($i = self::$_PACK_KEY; $i < self::$_PACK_KEY + $length; $i++) {
            $n .= self::$_PACK[$i];
        }

        self::$_PACK_KEY += $length;

        return $n;

    }

    /**
     * @brief 前进步长
     * @param $length
     */
    public  function advance($length) {
        $this->read($length);
    }

    /**
     * @brief read a 'Length Coded Binary' number from the data buffer.
     ** Length coded numbers can be anywhere from 1 to 9 bytes depending
     ** on the value of the first byte.
     ** From PyMYSQL source code
     * @return int|string
     */

    public function readCodedBinary(){
        $c = ord($this->read(1));
        if($c == ConstMy::NULL_COLUMN) {
            return '';
        }
        if($c < ConstMy::UNSIGNED_CHAR_COLUMN) {
            return $c;
        } elseif($c == ConstMy::UNSIGNED_SHORT_COLUMN) {
            return $this->unpackUint16($this->read(ConstMy::UNSIGNED_SHORT_LENGTH));

        }elseif($c == ConstMy::UNSIGNED_INT24_COLUMN) {
            return $this->unpackInt24($this->read(ConstMy::UNSIGNED_INT24_LENGTH));
        }
        elseif($c == ConstMy::UNSIGNED_INT64_COLUMN) {
            return $this->unpackInt64($this->read(ConstMy::UNSIGNED_INT64_LENGTH));
        }
    }

    public function unpackUint16($data) {
        return unpack("S",$data[0] . $data[1])[1];
    }

    public function unpackInt24($data) {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        return $a;
    }

    //ok
    public function unpackInt64($data) {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        $a += (int)((ord($data[3]) & 0xFF) << 24);
        $a += (int)((ord($data[4]) & 0xFF) << 32);
        $a += (int)((ord($data[5]) & 0xFF) << 40);
        $a += (int)((ord($data[6]) & 0xFF) << 48);
        $a += (int)((ord($data[7]) & 0xFF) << 56);
        return $a;
    }

    public function read_int24()
    {
        $data = unpack("CCC", $this->read(3));

        $res = $data[1] | ($data[2] << 8) | ($data[3] << 16);
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    public function read_int24_be()
    {
        $data = unpack('C3', $this->read(3));
        $res = ($data[1] << 16) | ($data[2] << 8) | $data[3];
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    //
    public function readUint8()
    {
        return unpack('C', $this->read(1))[1];
    }

    //
    public function readUint16()
    {
        return unpack('S', $this->read(2))[1];
    }

    public function readUint24()
    {
        $data = unpack("C3", $this->read(3));
        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
    }

    //
    public function readUint32()
    {
        return unpack('I', $this->read(4))[1];
    }

    public function readUint40()
    {
        $data = unpack("CI", $this->read(5));
        return $data[1] + ($data[2] << 8);
    }

    public function read_int40_be()
    {
        $data1= unpack("N", $this->read(4))[1];
        $data2 = unpack("C", $this->read(1))[1];
        return $data2 + ($data1 << 8);
    }

    //
    public function readUint48()
    {
        $data = unpack("vvv", $this->read(6));
        return $data[1] + ($data[2] << 16) + ($data[3] << 32);
    }

    //
    public function readUint56()
    {
        $data = unpack("CSI", $this->read(7));
        return $data[1] + ($data[2] << 8) + ($data[3] << 24);
    }

    /*
     * 不支持unsigned long long，溢出
     */
    public function readUint64() {
        $d = $this->read(8);
        $data = unpack('V*', $d);
        $bigInt = bcadd($data[1], bcmul($data[2], bcpow(2, 32)));
        return $bigInt;

//        $unpackArr = unpack('I2', $d);
        //$data = unpack("C*", $d);
        //$r = $data[1] + ($data[2] << 8) + ($data[3] << 16) + ($data[4] << 24);//+
        //$r2= ($data[5]) + ($data[6] << 8) + ($data[7] << 16) + ($data[8] << 24);

//        return $unpackArr[1] + ($unpackArr[2] << 32);
    }

    public function readInt64()
    {
        return $this->readUint64();
    }

    public function read_uint_by_size($size)
    {

        if($size == 1)
            return $this->readUint8();
        elseif($size == 2)
            return $this->readUint16();
        elseif($size == 3)
            return $this->readUint24();
        elseif($size == 4)
            return $this->readUint32();
        elseif($size == 5)
            return $this->readUint40();
        elseif($size == 6)
            return $this->readUint48();
        elseif($size == 7)
            return $this->readUint56();
        elseif($size == 8)
            return $this->readUint64();
    }
    public function read_length_coded_pascal_string($size)
    {
        $length = $this->read_uint_by_size($size);
        return $this->read($length);
    }

    public function read_int_be_by_size($size) {
        //Read a big endian integer values based on byte number
        if ($size == 1)
            return unpack('c', $this->read($size))[1];
        elseif( $size == 2)
            return unpack('n', $this->read($size))[1];
        elseif( $size == 3)
            return $this->read_int24_be();
        elseif( $size == 4)
            return unpack('N', $this->read($size))[1];
        elseif( $size == 5)
            return $this->read_int40_be();
        //TODO
        elseif( $size == 8)
            return unpack('N', $this->read($size))[1];
    }

    /**
     * @return bool
     */
    public function isComplete($size) {
        // 20解析server_id ...
        if(self::$_PACK_KEY + 1 - 20 < $size) {
            return false;
        }
        return true;
    }

    /**
     * @biref  初始化设置 file，pos，解决持久化file为空问题
     * @param $file
     * @param $pos
     */
    public static function setFilePos($file, $pos) {
        self::$_FILE_NAME = $file;
        self::$_POS       = $pos;
    }

    /**
     * @brief 获取binlog file，pos持久化
     * @return array
     */
    public static function getFilePos() {
        return array(self::$_FILE_NAME, self::$_POS);
    }

}
class ConstCommand {
    const COM_BINLOG_DUMP    = 0x12;
    const COM_REGISTER_SLAVE = 0x15;
}

class DBConstNamespace {
    // 数据库编码相关
    const ENCODING_GBK      = 0; ///< GBK 编码定义
    const ENCODING_UTF8     = 1; ///< UTF8 编码定义
    const ENCODING_LATIN    = 2; ///< LATIN1 编码定义
    const ENCODING_UTF8MB4  = 3; ///< UTF8MB4 编码定义, 4字节emoji表情要用,http://punchdrunker.github.io/iOSEmoji/table_html/flower.html
    // 数据库句柄需要ping 重连
    const HANDLE_PING       = 100;

    // 数据库句柄不能 重连
    const NOT_HANDLE_PING   = 200;

}



class DBMysql {

    /**
     * 已打开的db handle
     */
    private static $_HANDLE_ARRAY   = array();
    private static $_HANDLE_CONFIG  = array();


    private static function _getHandleKey($params) {
        ksort($params);
        return md5(implode('_' , $params));
    }


    /// 根据数据库表述的参数获取数据库操作句柄
    /// @param[in] array $db_config_array, 是一个array类型的数据结构，必须有host, username, password 三个熟悉, port为可选属性， 缺省值分别为3306
    /// @param[in] string $db_name, 数据库名称
    /// @param[in] enum $encoding, 从$DBConstNamespace中数据库编码相关的常量定义获取, 有缺省值 $DBConstNamespace::ENCODING_UTF8
    /// @return 非FALSE表示成功获取hadnle， 否则返回FALSE
    public static function createDBHandle($encoding = DBConstNamespace::ENCODING_UTF8) {
        $db_config_array['db_name']     = Client::$db;
        $db_config_array['encoding']    = $encoding;
        $db_config_array['host']        = Client::$host;
        $db_config_array['username']    = Client::$user;
        $db_config_array['password']    = Client::$password;
        $db_config_array['port']        = Client::$port;


        self::$_HANDLE_CONFIG = $db_config_array;

        $handle_key = self::_getHandleKey($db_config_array);

        $port = 3306;
        do {
            if (!is_array($db_config_array))
                break;
            if (!is_string(Client::$db))
                break;
            if (strlen(Client::$db) == 0)
                break;
            if (!array_key_exists('host', $db_config_array))
                break;
            if (!array_key_exists('username', $db_config_array))
                break;
            if (!array_key_exists('password', $db_config_array))
                break;
            if (array_key_exists('port', $db_config_array)) {
                $port = (int)($db_config_array['port']);
                if (($port < 1024) || ($port > 65535))
                    break;
            }
            $host = $db_config_array['host'];
            if (strlen($host) == 0)
                break;
            $username = $db_config_array['username'];
            if (strlen($username) == 0)
                break;
            $password = $db_config_array['password'];
            if (strlen($password) == 0)
                break;

            $handle = @mysqli_connect($host, $username, $password, Client::$db, $port);
            // 如果连接失败，再重试2次
            for ($i = 1; ($i < 3) && (FALSE === $handle); $i++) {
                // 重试前需要sleep 50毫秒
                usleep(50000);
                $handle = @mysqli_connect($host, $username, $password, Client::$db, $port);
            }
            if (FALSE === $handle)
                break;

            if (FALSE === mysqli_set_charset($handle, "utf8")) {
                self::logError( sprintf("Connect Set Charset Failed2:%s", mysqli_error($handle)), 'mysqlns.connect');
                mysqli_close($handle);
                break;
            }


            self::$_HANDLE_ARRAY[$handle_key]    = $handle;

            return $handle;
        } while (FALSE);

        // to_do, 连接失败
        self::logError( sprintf("Connect failed:time=%s", date('Y-m-d H:i:s',time())), 'mysqlns.connect');
        return FALSE;
    }

    /// 释放通过getDBHandle或者getDBHandleByName 返回的句柄资源
    /// @param[in] handle $handle, 你懂的
    /// @return void
    public static function releaseDBHandle($handle) {
        if (!self::_checkHandle($handle))
            return;
        foreach (self::$_HANDLE_ARRAY as $handle_key => $handleObj) {
            if ($handleObj->thread_id == $handle->thread_id) {
                unset(self::$_HANDLE_ARRAY[$handle_key]);
            }
        }
        mysqli_close($handle);
    }

    /// 将所有结果存入数组返回
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @param[in] string $sql, 具体执行的sql语句
    /// @return FALSE表示执行失败， 否则返回执行的结果, 结果格式为一个数组，数组中每个元素都是mysqli_fetch_assoc的一条结果
    public static function query($handle, $sql) {
        do {
            if (($result = self::mysqliQueryApi($handle, $sql)) === FALSE){
                break;
            }
            if ($result === true) {
                self::logWarn("err.func.query,SQL=$sql", 'mysqlns.query' );
                return array();
            }
            $res = array();
            while($row = mysqli_fetch_assoc($result)) {
                $res[] = $row;
            }
            mysqli_free_result($result);
            return $res;
        } while (FALSE);
        // to_do, execute sql语句失败， 需要记log
        self::logError( "SQL Error: $sql, errno=" . self::getLastError($handle), 'mysqlns.sql');

        return FALSE;
    }

    /// 将查询的第一条结果返回
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @param[in] string $sql, 具体执行的sql语句
    /// @return FALSE表示执行失败， 否则返回执行的结果, 执行结果就是mysqli_fetch_assoc的结果
    public static function queryFirst($handle, $sql) {
        if (!self::_checkHandle($handle))
            return FALSE;
        do {
            if (($result = self::mysqliQueryApi($handle, $sql)) === FALSE)
                break;
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return $row;
        } while (FALSE);
        // to_do, execute sql语句失败， 需要记log
        self::logError( "SQL Error: $sql," . self::getLastError($handle), 'mysqlns.sql');
        return FALSE;
    }

    /**
     * 将所有结果存入数组返回
     * @param Mysqli $handle 句柄
     * @param string $sql 查询语句
     * @return FALSE表示执行失败， 否则返回执行的结果, 结果格式为一个数组，数组中每个元素都是mysqli_fetch_assoc的一条结果
     */
    public static function getAll($handle , $sql) {
        return self::query($handle, $sql);
    }

    /**
     * 将查询的第一条结果返回
     * @param[in] Mysqli $handle, 操作数据库的句柄
     * @param[in] string $sql, 具体执行的sql语句
     * @return FALSE表示执行失败， 否则返回执行的结果, 执行结果就是mysqli_fetch_assoc的结果
     */
    public static function getRow($handle , $sql) {
        return self::queryFirst($handle, $sql);
    }

    /**
     * 查询第一条结果的第一列
     * @param Mysqli $handle, 操作数据库的句柄
     * @param string $sql, 具体执行的sql语句
     */
    public static function getOne($handle , $sql) {
        $row    = self::getRow($handle, $sql);
        if (is_array($row))
            return current($row);
        return $row;
    }

    /// 得到最近一次操作影响的行数
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @return FALSE表示执行失败， 否则返回影响的行数
    public static function lastAffected($handle) {
        if (!is_object($handle))
            return FALSE;
        $affected_rows = mysqli_affected_rows($handle);
        if ($affected_rows < 0)
            return FALSE;
        return $affected_rows;
    }

    /*
     *  返回最后一次查询自动生成并使用的id
     *  @param[in] handle $handle, 操作数据库的句柄
     *  @return FALSE表示执行失败， 否则id
     */
    public static function getLastInsertId($handle) {
        if (!is_object($handle)) {
            return false ;
        }
        if (($lastInsertId = mysqli_insert_id($handle)) <= 0) {
            return false ;
        }
        return $lastInsertId;
    }

    /// 得到最近一次操作错误的信息
    /// @param[in] handle $handle, 操作数据库的句柄
    /// @return FALSE表示执行失败， 否则返回 'errorno: errormessage'
    public static function getLastError($handle) {
        if(($handle)) {
            return mysqli_errno($handle).': '.mysqli_error($handle);
        }
        return FALSE;
    }

    /**
     * @brief 检查handle
     * @param[in] handle $handle, 操作数据库的句柄
     * @return boolean true|成功, false|失败
     */
    private static function _checkHandle($handle, $log_category = 'mysqlns.handle') {
        if (!is_object($handle) || $handle->thread_id < 1) {
            if ($log_category) {
                self::logError(sprintf("handle Error: handle='%s'",var_export($handle, true)), $log_category);
            }
            return false;
        }
        return true;
    }


    public static function mysqliQueryApi($handle, $sql) {
        do {
            $result = mysqli_query($handle, $sql);

            return $result;
        } while (0);
        return false;
    }

    /**
     * @breif 记录统一错误日志
     */
    protected static function logError($message, $category) {
        Log::error( $message, $category , __DIR__."/binlog-error.log");
    }

    /**
     * @breif 记录统一警告日志
     */
    protected static function logWarn($message, $category) {

        Log::warn( $message, $category , __DIR__."/binlog-warn.log");

    }
}

class DBHelper {

    /**
     * @brief 获取字段相关信息
     * @param $schema
     * @param $table
     * @return array|bool
     */
    public static function getFields($schema, $table) {

        $db  = DBMysql::createDBHandle();
        $sql = "SELECT
                COLUMN_NAME,COLLATION_NAME,CHARACTER_SET_NAME,COLUMN_COMMENT,COLUMN_TYPE,COLUMN_KEY
                FROM
                information_schema.columns
                WHERE
                table_schema = '{$schema}' AND table_name = '{$table}'";
        $result = DBMysql::query($db,$sql);
        DBMysql::releaseDBHandle($db);
        return $result;
    }

    /**
     * @brief 是否使用checksum
     * @return array|bool
     */
    public static function isCheckSum() {
        $db  = DBMysql::createDBHandle();
        $sql = "SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'";
        $res = DBMysql::getRow($db,$sql);
        DBMysql::releaseDBHandle($db);
        if($res['Value']) return true;
        return false;
    }

    /**
     * @breif 获取主库状态pos，file
     * @return FALSE表示执行失败
     */
    public static function getPos() {
        $db     = DBMysql::createDBHandle();
        $sql    = "SHOW MASTER STATUS";
        $result = DBMysql::getRow($db,$sql);
        DBMysql::releaseDBHandle($db);
        return $result;
    }
}
class ConstCapability {

    public static $LONG_PASSWORD;
    public static $FOUND_ROWS;
    public static $LONG_FLAG;
    public static $CONNECT_WITH_DB;
    public static $NO_SCHEMA;
    public static $COMPRESS;
    public static $ODBC;
    public static $LOCAL_FILES;
    public static $IGNORE_SPACE;
    public static $PROTOCOL_41;
    public static $INTERACTIVE;
    public static $SSL;
    public static $IGNORE_SIGPIPE;
    public static $TRANSACTIONS;
    public static $SECURE_CONNECTION;
    public static $MULTI_STATEMENTS;
    public static $MULTI_RESULTS;
    public static $CAPABILITIES;

    public static function init() {
        self::$LONG_PASSWORD = 1;
        self::$FOUND_ROWS = 1 << 1;
        self::$LONG_FLAG = 1 << 2;
        self::$CONNECT_WITH_DB = 1 << 3;
        self::$NO_SCHEMA = 1 << 4;
        self::$COMPRESS = 1 << 5;
        self::$ODBC = 1 << 6;
        self::$LOCAL_FILES = 1 << 7;
        self::$IGNORE_SPACE = 1 << 8;
        self::$PROTOCOL_41 = 1 << 9;
        self::$INTERACTIVE = 1 << 10;
        self::$SSL = 1 << 11;
        self::$IGNORE_SIGPIPE = 1 << 12;
        self::$TRANSACTIONS = 1 << 13;
        self::$SECURE_CONNECTION = 1 << 15;
        self::$MULTI_STATEMENTS = 1 << 16;
        self::$MULTI_RESULTS = 1 << 17;
        self::$CAPABILITIES = (self::$LONG_PASSWORD | self::$LONG_FLAG | self::$TRANSACTIONS |
            self::$PROTOCOL_41 | self::$SECURE_CONNECTION);
    }
}
ConstCapability::init();

class ServerInfo {


    public static $INFO = [];
    public static $PACK;

    public static function run($pack) {

        $i = 0;
        $length = strlen($pack);
        self::$INFO['protocol_version'] = ord($pack[$i]);
        $i++;

        //version
        self::$INFO['server_version'] = '';
        $start = $i;
        for($i = $start; $i < $length; $i++) {
            if($pack[$i] === chr(0)) {
                $i++;
                break;
            } else{
                self::$INFO['server_version'] .= $pack[$i];
            }
        }

        //connection_id 4 bytes
        self::$INFO['connection_id'] = $pack[$i]. $pack[++$i] . $pack[++$i] . $pack[++$i];
        $i++;

        //auth_plugin_data_part_1
        //[len=8] first 8 bytes of the auth-plugin data
        self::$INFO['salt'] = '';
        for($j = $i;$j<$i+8;$j++) {
            self::$INFO['salt'] .= $pack[$j];
        }
        $i = $i + 8;


        //filler_1 (1) -- 0x00
        $i++;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        $i = $i + 2;



        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
        self::$INFO['character_set'] = $pack[$i];

        $i++;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        $i = $i + 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        $i = $i + 2;


        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        $salt_len = ord($pack[$i]);
        $i++;

        $salt_len = max(12, $salt_len - 9);

        //填充值
        $i = $i + 10;

        //next salt
        if ($length >= $i + $salt_len)
        {
            for($j = $i ;$j < $i + $salt_len;$j++)
            {
                self::$INFO['salt'] .= $pack[$j];
            }

        }

        self::$INFO['auth_plugin_name'] = '';
        $i = $i + $salt_len + 1;
        for($j = $i;$j<$length-1;$j++) {
            self::$INFO['auth_plugin_name'] .=$pack[$j];
        }
    }

    /**
     * @brief 获取salt auth
     * @return mixed
     */
    public static function getSalt() {
        return self::$INFO['salt'];
    }

    /**
     * @brief 获取编码
     * @return mixed
     */
    public static function getCharSet() {
        return self::$INFO['character_set'];
    }

    public static function getVersion() {
        return self::$INFO['server_version'];
    }

    public static function getInfo() {
        return self::$INFO;
    }
}
class ConstAuth {


    // 2^24 - 1 16m
    public static $PACK_MAX_LENGTH = 16777215;

    // http://dev.mysql.com/doc/internals/en/auth-phase-fast-path.html
    // 00 FE
    public static $OK_PACK_HEAD = [0, 254];
    // FF
    public static $ERR_PACK_HEAD = [255];

}
/**
 * https://dev.mysql.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake
 * Created by PhpStorm.
 * User: baidu
 * Date: 15/11/19
 * Time: 下午2:51
 */
class PackAuth {

    /**
     * @param $flag
     * @param $user
     * @param $pass
     * @param $salt
     * @param string $db
     * @return string
     */
    public static function  initPack($flag, $user, $pass, $salt, $db = '') {

        $data = pack('L',$flag);

        // max-length 4bytes，最大16M 占3bytes
        $data .= pack('L', ConstAuth::$PACK_MAX_LENGTH);


        // Charset  1byte utf8=>33
        $data .= chr(33);


        // 空 bytes23
        for($i=0;$i<23;$i++){
            $data .=chr(0);
        }

        // http://dev.mysql.com/doc/internals/en/secure-password-authentication.html#packet-Authentication::Native41
        $result = sha1($pass, true) ^ sha1($salt . sha1(sha1($pass, true), true),true);

        //转码 8是 latin1
        //$user = iconv('utf8', 'latin1', $user);

        //
        $data = $data . $user . chr(0) . chr(strlen($result)) . $result;
        if($db) {
            $data .= $db . chr(0);
        }

        // V L 小端，little endian
        $str = pack("L", strlen($data));
        $s =$str[0].$str[1].$str[2];

        $data = $s . chr(1) . $data;

        return $data;
    }

    /**
     * @breif 校验数据包格式是否正确，验证是否成功
     * @param $pack
     * @return array
     */
    public static function success($pack) {
        $head = ord($pack[0]);
        if(in_array($head, ConstAuth::$OK_PACK_HEAD)) {
            return ['status' => true, 'code' => 0, 'msg' => ''];
        } else{
            $error_code = unpack("v", $pack[1] . $pack[2])[1];
            $error_msg  = '';
            for($i = 9; $i < strlen($pack); $i ++) {
                $error_msg .= $pack[$i];
            }
            var_dump(['code' => $error_code, 'msg' => $error_msg]);
            exit;
        }

    }
}

class Client
{
    public static $host = '127.0.0.1';
    public static $port = 3306;
    public static $password = '123456';
    public static $user = 'root';
    public static $db = 'xsl';

    private $socket;
    private $checksum = false;
    private $slave_server_id = 100;
    private $file;
    private $pos;

    public function __construct()
    {

        if (($this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) == false) {
            throw new \Exception( sprintf( "Unable to create a socket: %s", socket_strerror( socket_last_error())));
        }
        socket_set_block($this->socket);
        socket_set_option($this->socket, SOL_SOCKET, SO_KEEPALIVE, 1);
//         socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_SNDTIMEO,['sec' => 2, 'usec' => 5000]);
//         socket_set_option(self::$_SOCKET,SOL_SOCKET,SO_RCVTIMEO,['sec' => 2, 'usec' => 5000]);

        $flag = ConstCapability::$CAPABILITIES ;//| S::$MULTI_STATEMENTS;
        if (self::$db) {
            $flag |= ConstCapability::$CONNECT_WITH_DB;
        }

        //self::$_FLAG |= S::$MULTI_RESULTS;

        // 连接到mysql
        // create socket
        if(!socket_connect($this->socket, self::$host, self::$port)) {
            throw new \Exception(
                sprintf(
                    'error:%s, msg:%s',
                    socket_last_error(),
                    socket_strerror(socket_last_error())
                )
            );
        }

        // 获取server信息
        $pack   = self::_readPacket();
        ServerInfo::run($pack);
        // 加密salt
        $salt = ServerInfo::getSalt();

        // 认证
        // pack拼接
        $data = PackAuth::initPack($flag, self::$user, self::$password, $salt,  self::$db);

        $this->_write($data);
        //
        $result = $this->_readPacket();

        // 认证是否成功
        PackAuth::success($result);

        //
        self::getBinlogStream();
    }


    private function _write($data) {
        if(socket_write($this->socket, $data, strlen($data))=== false )
        {
            throw new \Exception( sprintf( "Unable to write to socket: %s", socket_strerror( socket_last_error())));
        }
        return true;
    }
    private function _readBytes($data_len) {

        // server gone away
        if ($data_len == 5) {
            throw new \Exception('read 5 bytes from mysql server has gone away');
        }

        try{
            $bytes_read = 0;
            $body       = '';
            while ($bytes_read < $data_len) {
                $resp = socket_read($this->socket, $data_len - $bytes_read);

                //
                if($resp === false) {
                    throw new \Exception(
                        sprintf(
                            'remote host has closed. error:%s, msg:%s',
                            socket_last_error(),
                            socket_strerror(socket_last_error())
                        ));
                }

                // server kill connection or server gone away
                if(strlen($resp) === 0){
                    throw new \Exception("read less " . ($data_len - strlen($body)));
                }
                $body .= $resp;
                $bytes_read += strlen($resp);
            }
            if (strlen($body) < $data_len){
                throw new \Exception("read less " . ($data_len - strlen($body)));
            }
            return $body;
        } catch (Exception $e) {
            throw new \Exception(var_export($e, true));
        }

    }
    private function _readPacket() {
        //消息头
        $header = $this->_readBytes(4);
        if($header === false) return false;
        //消息体长度3bytes 小端序
        $unpack_data = unpack("L",$header[0].$header[1].$header[2].chr(0))[1];
        $result = $this->_readBytes($unpack_data);
        var_dump($result);
        echo "\r\n";
        return $result;
    }
    public function excute($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',$chunk_size, 0x03);
        $this->_write($prelude . $sql);
    }

    /**
     * @breif 注册成slave
     * @return void
     */
    private function _writeRegisterSlaveCommand() {
        $header   = pack('l', 18);

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_REGISTER_SLAVE);
        $data .= pack('L', $this->slave_server_id);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);

        $data .= pack('s', '');

        $data .= pack('L', 0);
        $data .= pack('L', 1);

        $this->_write($data);

        $result = $this->_readPacket();
        PackAuth::success($result);
        var_dump($result);
    }

    public function getBinlogStream() {

        // checksum
        $this->checksum = DBHelper::isCheckSum();
        if($this->checksum){
            $this->excute("set @master_binlog_checksum= @@global.binlog_checksum");
        }
        //heart_period
        $heart = 5;
        if($heart) {
            $this->excute("set @master_heartbeat_period=".($heart*1000000000));
        }

        $this->_writeRegisterSlaveCommand();

        // 开始读取的二进制日志位置
        if(!$this->file) {
            $logInfo = DBHelper::getPos();
            $this->file = $logInfo['File'];
            if(!$this->pos) {
                $this->pos = $logInfo['Position'];
            }
        }

        // 初始化
        BinLogPack::setFilePos($this->file, $this->pos);

        $header   = pack('l', 11 + strlen($this->file));

        // COM_BINLOG_DUMP
        $data  = $header . chr(ConstCommand::COM_BINLOG_DUMP);
        $data .= pack('L', $this->pos);
        $data .= pack('s', 0);
        $data .= pack('L', $this->slave_server_id);
        $data .= $this->file;

        self::_write($data);

        //认证
        $result = self::_readPacket();
        PackAuth::success($result);
        var_dump($result);
    }

    public function analysisBinLog($flag = false) {

        $pack   = $this->_readPacket();

        // 校验数据包格式
        PackAuth::success($pack);

        //todo eof pack 0xfe

        $binlog = BinLogPack::getInstance();
        $result = $binlog->init($pack, $this->checksum);

        // debug
            echo round(memory_get_usage()/1024/1024, 2).'MB',"\r\n";

        //持久化当前读到的file pos

            if($result) var_dump($result);

    }

}

$client = new Client();
while (1)$client->analysisBinLog();