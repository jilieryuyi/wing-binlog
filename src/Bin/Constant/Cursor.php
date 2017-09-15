<?php namespace Wing\Bin\Constant;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/15
 * Time: 20:23
 */
class Cursor
{
    const TYPE_NO_CURSOR  = 0x00;
    const TYPE_READ_ONLY  = 0x01;
    const TYPE_FOR_UPDATE = 0x02;
    const TYPE_SCROLLABLE = 0x04;
}