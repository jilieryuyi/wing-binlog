<?php namespace Seals\Web\Logic;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/21
 * Time: 15:00
 */
class User
{
    protected $user_name;
    protected $password = null;
    public function __construct($user_name)
    {
        $this->user_name = trim($user_name);
        if (file_exists(__APP_DIR__."/data/user/".$user_name))
        $this->password  = file_get_contents(__APP_DIR__."/data/user/".$user_name);
    }

    public function checkPassword($password)
    {
        if (!$password)
            return false;
        $password = trim($password);

        if (!$password)
            return false;

        if (!$this->password)
            return false;

        return trim($this->password) == $password;
    }

    public static function createToken()
    {
        $str1 = md5(rand(0,999999));
        $str2 = md5(rand(0,999999));
        $str3 = md5(rand(0,999999));

        return time()."-".
        substr($str1,rand(0,strlen($str1)-16),16).
        substr($str2,rand(0,strlen($str2)-16),16).
        substr($str3,rand(0,strlen($str3)-16),16);
    }

    public function setToken()
    {
        $token = \Seals\Web\Logic\User::createToken();
        $appid = substr(md5($this->user_name),2,18);
        $file  = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        $file->set($appid, $token, 3600);
        unset($file);
        return [$appid, $token];
    }

    public static function checkToken($appid, $token)
    {
        if (!$appid || !$token)
            return false;
        $file    = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        $success = $file->get($appid) == $token;
        unset($appid, $file, $token);
        return $success;
    }

}