<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/19
 * Time: 12:17
 */
class FieldType extends \Wing\Bin\Constant\FieldType
{
    private $value;
    public $type;

    public function __construct($value, $type = -1)
    {
        $this->value = $value;
        if ($type === -1) {
            $this->type = $this->getType();
        } else {
            $this->type = $type;
        }
    }

    private function isInt()
    {
        return (intval($this->value) - $this->value) == 0;
    }

    /**
     * @return int
     */
    public function getType()
    {
        if(!is_numeric($this->value)) {
            return self::VAR_STRING;
        }

        if ($this->isInt()) {
            $this->value = intval($this->value);
            //tinyint
            if((-1 << 7) <= $this->value && $this->value <= ((1 << 7)-1)) {
                return self::TINY;
            }
            //2字节
            else if((-1 << 15) <= $this->value && $this->value <= ((1 << 15)-1)) {
                self::SHORT;
            }
            //3个字节
            else if((-1 << 23) <= $this->value && $this->value <= ((1 << 23)-1)) {
               self::INT24;
            }
            //4个字节
            else if((-1 << 31) <= $this->value && $this->value <= ((1 << 31)-1)) {
                return self::BIGINT;
            }
            return self::BIGINT;
        } else {
            //浮点数


        }

        return self::VAR_STRING;
    }

    /**
     * string
     */
    private function storeLength()
    {
        $length = strlen($this->value);
        if ($length < 251) {
            return chr($length);
        }

        /* 251 is reserved for NULL */
        if ($length < 65536) {
            return chr(252).chr($length).chr($length >> 8);
            //pack("v", $length);
        }

        if ($length < 16777216) {
            $data = chr(253);
            $data .= chr($length).chr($length >> 8).chr($length >> 16);
            return $data;
        }
        return chr(254).self::packIn64($length);//pack("P", $length);
    }

    private function packIn64($value)
    {
        $def_temp  = $value;
        $def_temp2 = ($value >> 32);
        $data = chr($def_temp).chr($def_temp>> 8).chr($def_temp >> 16).chr($def_temp >> 24);
        $data .= chr($def_temp2).chr($def_temp2>> 8).chr($def_temp2 >> 16).chr($def_temp2 >> 24);
        return $data;
    }

    public function pack()
    {
        switch ($this->type) {
            case self::TINY:
                return chr($this->value);
                break;
            case self::SHORT:
                return chr($this->value).chr($this->value >> 8);
                break;
            case self::INT24:
                return chr($this->value).chr($this->value >> 8).chr($this->value >> 16);
                break;
            case self::BIGINT:
                $def_temp  = $this->value;
                $def_temp2 = ($this->value >> 32);
                $data      = chr($def_temp).chr($def_temp>> 8).chr($def_temp >> 16).chr($def_temp >> 24);
                $data     .= chr($def_temp2).chr($def_temp2>> 8).chr($def_temp2 >> 16).chr($def_temp2 >> 24);
                return $data;
                break;
            case self::VAR_STRING:
                return $this->storeLength().$this->value;

        }

        //include/big_endian.h
        //self::FLOAT;
        //float4store

        //self::DOUBLE;
        //float8store

        return null;
    }


    public static function parse(array $params)
    {
        $res = [];
        foreach ($params as $value) {
            $res[] = new self($value);
        }
        return $res;
    }

public static function fieldtype2str($type)
{
  switch ($type) {
    case self::BIT:         return "BIT";
    case self::BLOB:        return "BLOB";
    case self::DATE:        return "DATE";
    case self::DATETIME:    return "DATETIME";
    case self::NEWDECIMAL:  return "NEWDECIMAL";
    case self::DECIMAL:     return "DECIMAL";
    case self::DOUBLE:      return "DOUBLE";
    case self::ENUM:        return "ENUM";
    case self::FLOAT:       return "FLOAT";
    case self::GEOMETRY:    return "GEOMETRY";
    case self::INT24:       return "INT24";
    case self::JSON:        return "JSON";
    case self::LONG:        return "LONG";
    case self::LONGLONG:    return "LONGLONG";
    case self::LONG_BLOB:   return "LONG_BLOB";
    case self::MEDIUM_BLOB: return "MEDIUM_BLOB";
    case self::NEWDATE:     return "NEWDATE";
    case self::NULL:        return "NULL";
    case self::SET:         return "SET";
    case self::SHORT:       return "SHORT";
    case self::STRING:      return "STRING";
    case self::TIME:        return "TIME";
    case self::TIMESTAMP:   return "TIMESTAMP";
    case self::TINY:        return "TINY";
    case self::TINY_BLOB:   return "TINY_BLOB";
    case self::VAR_STRING:  return "VAR_STRING";
    case self::YEAR:        return "YEAR";
    default:                     return "?-unknown-?";
  }
}
}