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
    private $type;
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
        return null;
    }
}