<?php namespace Seals\Web\Logic;
use Seals\Cache\File;
use Seals\Library\Context;
use Seals\Web\HttpResponse;
use Wing\FileSystem\WDir;

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
    protected $role = null;

    public function __construct($user_name)
    {
        $this->user_name = trim($user_name);
        $wdir = new WDir(__APP_DIR__."/data/user/");
        $wdir->mkdir();
        unset($wdir);

        $info           = self::getInfo($this->user_name);
        $this->password = isset($info["password"])?$info["password"]:null;
        $this->role     = isset($info["role"])?$info["role"]:null;

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

        $success = password_verify($password, $this->password);//trim($this->password) == $password;

        if ($success) {
            $this->setLoginTimes();
        }

        return $success;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setPassword($password)
    {
        $password  = trim($password);
        $this->password  = password_hash($password, PASSWORD_DEFAULT);
    }

    public function setUserName($user_name)
    {
        $this->user_name = trim($user_name);
    }

    public function setRole($role)
    {
        $this->role = trim($role);
    }

    public function save($timeout = 0)
    {
        $file = new File(__APP_DIR__."/data/user");
        return $file->set(substr(md5(md5($this->user_name)),2,16).".user", ["name" => $this->user_name,"password" => $this->password, "role" => $this->role], $timeout);
    }

    public static function add($user_name, $password, $role, $timeout = 0)
    {
        $user_name = trim($user_name);
        $password  = trim($password);

        $pwd  = password_hash($password, PASSWORD_DEFAULT);
        $file = new File(__APP_DIR__."/data/user");
        return $file->set(substr(md5(md5($user_name)),2,16).".user", ["name" => $user_name,"password" => $pwd, "role" => $role], $timeout);
    }

    public static function getInfo($user_name)
    {
        $user_name = trim($user_name);
        $file      = new File(__APP_DIR__."/data/user");
        return $file->get(substr(md5(md5($user_name)),2,16).".user");
    }


    public function setLoginTimes()
    {
        $key = "wing-binlog-login-success-".$this->user_name;
        Context::instance()->redis_zookeeper->incr($key);
    }

    public function getLoginTimes()
    {
        $key = "wing-binlog-login-success-".$this->user_name;
        $num = Context::instance()->redis_zookeeper->get($key);

        if (!$num)
            return 0;

        return $num;
    }

    public function setToken()
    {
        $token = createUuid();
        $appid = substr(md5($this->user_name),2,18);

        $login = new WDir(__APP_DIR__."/data/user/login");
        $login->mkdir();
        unset($login);

        $file  = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        $file->set($appid.".token", [$this->user_name, $token], 7200);
        unset($file);
        return [$appid, $token];
    }

    public static function loginOut()
    {
        $appid   = $_COOKIE["wing-binlog-appid"];
        $file    = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        $file->del($appid.".token");
    }

    public static function checkToken($appid, $token)
    {
        if (!$appid || !$token)
            return false;
        $file    = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        list(,$_token) = $file->get($appid.".token");
        unset($appid, $file);
        return $_token == $token;
    }

    public static function getUserName()
    {
        $appid   = $_COOKIE["wing-binlog-appid"];
        $file    = new \Seals\Cache\File(__APP_DIR__."/data/user/login");
        list($user_name,) = $file->get($appid.".token");
        unset($appid, $file);
        return $user_name;
    }

    public static function count()
    {
        $path[] = __APP_DIR__.'/data/user/*';
        $count  = 0;
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_file($item)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public static function all()
    {
        $path[] = __APP_DIR__.'/data/user/*';
        $users  = [];
        $file = new File(__APP_DIR__.'/data/user/');
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_file($item)) {
                    $name = pathinfo($item,PATHINFO_BASENAME);
                    $info = $file->get($name);
                    $user = new self($info["name"]);
                    $users[] = [
                        "name"       => $info["name"],
                        "times"      => $user->getLoginTimes(),
                        "role"       => $user->getRole(),
                        "created"    => date("Y-m-d H:i:s", filectime($item)),
                        "last_login" => date("Y-m-d H:i:s", fileatime($item))
                    ];
                }
            }
        }
        return $users;
    }

    public static function addRole(HttpResponse $response)
    {
        $dir = new WDir(__APP_DIR__."/data/user/roles");
        $dir->mkdir();
        unset($dir);

        $role_name = urldecode($response->post("role_name"));
        $old_role  = urldecode($response->post("old_role"));
        $pages     = json_decode(urldecode($response->post("pages")));

        $old_role  = trim($old_role);
        $role_name = trim($role_name);

        $file = new File(__APP_DIR__."/data/user/roles");

        if ($old_role != $role_name) {
            $file->del($old_role.".role");
        }

        $file->set($role_name.".role", $pages);
    }

    public static function roleDelete(HttpResponse $response)
    {
        $role = urldecode($response->post("role"));
        $file = new File(__APP_DIR__."/data/user/roles");
        $file->del($role.".role");
    }

    public static function getAllRoles()
    {
        $path[] = __APP_DIR__.'/data/user/roles/*';
        $roles  = [];
        $file   = new File(__APP_DIR__.'/data/user/roles/');
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_file($item)) {
                    $info  = pathinfo($item);
                    $pages = $file->get($info["basename"]);
                    $roles[] = [
                        "name"       => $info["filename"],
                        "pages"      => $pages,
                        "created"    => date("Y-m-d H:i:s", filectime($item))
                    ];
                }
            }
        }
        return $roles;
    }

    public static function roleInfo($role_name)
    {
        $file   = new File(__APP_DIR__.'/data/user/roles/');
        return $file->get($role_name.".role");
    }

    public static function update(HttpResponse $response)
    {
        $user_name = $response->post("user_name");
        $old_pass  = $response->post("old_pass");
        $password  = $response->post("password");
        $old_user  = $response->post("old_user");
        $role      = $response->post("role");

        $user_name = trim(urldecode($user_name));
        $old_pass  = trim(urldecode($old_pass));
        $password  = trim(urldecode($password));
        $old_user  = trim(urldecode($old_user));
        $role      = trim(urldecode($role));

        $pwd       = $old_pass;

        if ($old_pass != $password)
            $pwd  = password_hash($password, PASSWORD_DEFAULT);

        $file = new File(__APP_DIR__."/data/user");

        if ($user_name != $old_user) {
            //if update user_name, del the old
            $file->del(substr(md5(md5($old_user)),2,16).".user");
        }

        $success = $file->set(substr(md5(md5($user_name)),2,16).".user", ["name" => $user_name,"password" => $pwd, "role" => $role], 0);

        //if update current user, logout
        if ($old_user == self::getUserName()) {
            self::loginOut();
        }

        return $success;
    }

    public static function delete(HttpResponse $response)
    {
        $user = $response->post("user_name");
        $file = new File(__APP_DIR__."/data/user");
        return $file->del(substr(md5(md5($user)),2,16).".user");
    }

    public static function addUser(HttpResponse $response)
    {
        $user_name = $response->post("user_name");
        $password  = $response->post("password");
        $role      = $response->post("role");

        $user_name = trim(urldecode($user_name));
        $password  = trim(urldecode($password));
        $role      = trim(urldecode($role));

        return self::add($user_name, $password, $role );
    }

}