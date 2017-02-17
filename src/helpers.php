<?php
/**
 * @author yuyi
 * @created 2016/12/4 9:13
 * @email 297341015@qq.com
 */
if(!function_exists("get_millisecond")) {
    function get_millisecond()
    {
        $time = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($time[0]) + floatval($time[1])) * 1000);
    }
}


$__start_time = 0;

if(!function_exists("set_start_time")) {
    function set_start_time($start)
    {
        global $__start_time;
        $__start_time = $start;
    }
}
if(!function_exists("get_start_time")) {
    function get_start_time()
    {
        global $__start_time;
        return $__start_time;
    }
}

if( !function_exists("enable_time_test")) {
    function enable_time_test()
    {
        set_start_time(get_millisecond());
    }
}
if(!function_exists("time_test_dump")) {
    function time_test_dump($msg = "")
    {
        $time = get_millisecond();
        echo $msg, "耗时", ($time - get_start_time()), "\r\n";
        set_start_time($time);
    }
}

if( !function_exists("str_is_email")) {
    /**
     * @邮箱合法性校验
     *
     * @param string $email 邮箱字符串
     * @return bool true合法，false非法
     */
    function str_is_email(&$email)
    {
        $email = trim($email);
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        return !!preg_match($pattern, $email);
    }
}