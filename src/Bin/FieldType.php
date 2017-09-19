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
        return is_numeric($this->value) &&
            (intval($this->value) - $this->value) == 0;
    }

    /**
     * @return int
     */
    public function getType()
    {
        if(is_numeric($this->value)) {
            if ($this->isInt()) {
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
                    return self::INT24;
                }
                return self::BIGINT;
            }
        } else {
            return self::VAR_STRING;
        }
        return self::VAR_STRING;
    }
}