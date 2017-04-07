<?php
/**
 * @author yuyi
 * @created 2016/12/4 9:13
 * @email 297341015@qq.com
 */
if (!function_exists("get_millisecond")) {
    function get_millisecond()
    {
        $time = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($time[0]) + floatval($time[1])) * 1000);
    }
}


$__start_time = 0;

if (!function_exists("set_start_time")) {
    function set_start_time($start)
    {
        global $__start_time;
        $__start_time = $start;
    }
}
if (!function_exists("get_start_time")) {
    function get_start_time()
    {
        global $__start_time;
        return $__start_time;
    }
}

if (!function_exists("enable_time_test")) {
    function enable_time_test()
    {
        set_start_time(get_millisecond());
    }
}

if (!function_exists("time_test_dump")) {
    function time_test_dump($msg = "")
    {
        $time = get_millisecond();
        echo $msg, "耗时", ($time - get_start_time()), "\r\n";
        set_start_time($time);
    }
}

if(!function_exists("str_is_email")) {
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

if (!function_exists("timelen_format")) {
    function timelen_format($time_len)
    {
        $lang = \Seals\Library\Context::instance()->lang;
        if (!$lang || !in_array($lang, \Seals\Library\Lang::$ltypes))
            $lang = "zh";

        if ($time_len < 60) {
            if ($lang == "en")
                return $time_len . " seconds";
            return $time_len . "秒";
        }

        else if ($time_len < 3600 && $time_len >= 60) {
            $m = intval($time_len / 60);
            $s = $time_len - $m * 60;
            if ($lang == "en")
                return $m . " minutes " . $s . " seconds";
            return $m . "分钟" . $s . "秒";
        } else if ($time_len < (24 * 3600) && $time_len >= 3600) {
            $h = intval($time_len / 3600);
            $s = $time_len - $h * 3600;
            if ($s >= 60) {
                $m = intval($s / 60);
            } else {
                $m = 0;
            }
            $s = $s-$m * 60;
            if ($lang == "en")
                return $h . " hours " . $m . " minutes " . $s . " seconds";
            return $h . "小时" . $m . "分钟" . $s . "秒";
        } else {
            $d = intval($time_len / (24 * 3600));
            $s = $time_len - $d * (24 * 3600);
            $h = 0;
            $m = 0;

            if ($s < 60) {

            } elseif ($s >= 60 && $s < 3600) {
                $m = intval($s / 60);
                $s = $s - $m * 60;
            } else {
                $h = intval($s / 3600);
                $s = $s - $h * 3600;
                $m = 0;
                if ($s >= 60) {
                    $m = intval($s / 60);
                    $s = $s - $m * 60;
                }
            }
            if ($lang == "en")
                return $d." days ".$h . " hours " . $m . " minutes " . $s . " seconds";
            return $d."天".$h . "小时" . $m . "分钟" . $s . "秒";

        }
    }
}

if (!function_exists("logger")) {
    function logger($file_name , $data)
    {
        file_put_contents(
            \Seals\Library\Context::instance()->log_dir . "/" . $file_name,
            date("Y-m-d H:i:s") . "\r\n" . $data . "\r\n",
            FILE_APPEND
        );
    }
}

if (!function_exists("createUuid")) {
    function createUuid()
    {
        $str1 = md5(rand(0, 999999));
        $str2 = md5(rand(0, 999999));
        $str3 = md5(rand(0, 999999));

        return time() . "-" .
        substr($str1, rand(0, strlen($str1) - 16), 16) .
        substr($str2, rand(0, strlen($str2) - 16), 16) .
        substr($str3, rand(0, strlen($str3) - 16), 16);
    }
}