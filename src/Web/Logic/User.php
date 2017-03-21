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

    public function setToken()
    {
        $token = createUuid();
        $appid = substr(md5($this->user_name),2,18);
        $file  = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        $file->set($appid.".token", [$this->user_name, $token], 3600);
        unset($file);
        return [$appid, $token];
    }

    public static function checkToken($appid, $token)
    {
        if (!$appid || !$token)
            return false;
        $file    = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        list(,$_token) = $file->get($appid.".token");// == $token;
        unset($appid, $file);
        return $_token == $token;
    }

    public static function getUserName($appid)
    {
        if (!$appid)
            return "";
        $file    = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        list($user_name,) = $file->get($appid.".token");
        unset($appid, $file);
        return $user_name;
    }

}