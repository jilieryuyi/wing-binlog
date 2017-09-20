<?php namespace Wing\Bin\Constant;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/20
 * Time: 14:56
 */
class Trans
{
    const NO_OPT	                = 0;
    const WITH_CONSISTENT_SNAPSHOT  = 1;
    //仅5.6.5以后的版本支持
    const READ_WRITE				= 2;
    const READ_ONLY					= 4;
}