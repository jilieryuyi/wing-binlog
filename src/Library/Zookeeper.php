<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/12
 * Time: 21:36
 * beta-分布式，配置管理、服务发现，使用redis实现
 * @property RedisInterface $redis
 */
class Zookeeper
{
    protected $redis;
    protected $session_id;

    const SERVICE_KEY       = "wing-binlog-services";
    const NOTIFY_TYPE_REDIS = "redis";
    const NOTIFY_TYPE_HTTP  = "http";
    const NOTIFY_TYPE_MQ    = "rabbitmq";

    public function __construct($redis)
    {
        $this->redis      = $redis;
        $this->session_id = Context::instance()->session_id;//$this->createSessionId();
    }

    /**
     * create a rand session id
     *
     * @return string
     */
    protected function createSessionId()
    {
        $str1 = md5(rand(0,999999));
        $str2 = md5(rand(0,999999));
        $str3 = md5(rand(0,999999));

        return time()."-".
            substr($str1,rand(0,strlen($str1)-16),16).
            substr($str2,rand(0,strlen($str2)-16),16).
            substr($str3,rand(0,strlen($str3)-16),16);
    }

    /**
     * service report
     */
    public function serviceReport()
    {
        echo $this->session_id,"\r\n";
        echo Context::instance()->session_id,"\r\n";
        $key = self::SERVICE_KEY.":is_close:". Context::instance()->zookeeper_config["group_id"].":".$this->session_id;
        $this->redis->set($key,Context::instance()->zookeeper_config["enable"]?0:1);
        $this->redis->expire($key,10);

        if (!$this->redis)
            return false;
        return $this->redis->hset(
            self::SERVICE_KEY.":services:". Context::instance()->zookeeper_config["group_id"],
            $this->session_id,
            time()
        );
    }

    /**
     * if group is enable , set leader's last binlog to all group node
     */
    public function setLastBinlog($last_binlog)
    {
        //onle leader can report last pos
        if (!$this->isLeader() || !Context::instance()->zookeeper_config["enable"])
            return;
        $key = self::SERVICE_KEY.":last:binlog:". Context::instance()->zookeeper_config["group_id"];
        $this->redis->set($key, $last_binlog);
        $this->redis->expire($key,10);
    }

    public function getLastBinlog()
    {
        $key = self::SERVICE_KEY.":last:binlog:". Context::instance()->zookeeper_config["group_id"];
        return  $this->redis->get($key);
    }

    /**
     * if group is enable , set leader's last pos to all group node
     */
    public function setLastPost($start_pos, $end_pos)
    {
        //onle leader can report last pos
        if (!$this->isLeader() || !Context::instance()->zookeeper_config["enable"])
            return;
        $key = self::SERVICE_KEY.":last:pos:". Context::instance()->zookeeper_config["group_id"];
        $this->redis->set($key, $start_pos.":".$end_pos);
        $this->redis->expire($key,10);
    }

    public function getLastPost()
    {
        $key = self::SERVICE_KEY.":last:pos:". Context::instance()->zookeeper_config["group_id"];
        $res = $this->redis->get($key);
        return explode(":", $res);
    }

    public static function getGroupLastPost($group_id)
    {
        if (!Context::instance()->redis_zookeeper)
            return false;
        $key = self::SERVICE_KEY.":last:pos:". $group_id;
        $res = Context::instance()->redis_zookeeper->get($key);
        return explode(":", $res);
    }


    public static function getGroupLastBinlog($group_id)
    {
        if (!Context::instance()->redis_zookeeper)
            return "";
        $key = self::SERVICE_KEY.":last:binlog:". $group_id;
        return Context::instance()->redis_zookeeper->get($key);
    }

    /**
     * is close group
     *
     * @return bool if return true, it means that group is close
     */
    public static function isClose($group_id, $session_id)
    {
        if (!Context::instance()->redis_zookeeper)
            return true;
        $key = self::SERVICE_KEY.":is_close:". $group_id.":".$session_id;
        return Context::instance()->redis_zookeeper->get($key);
    }

    public static function delSessionId($group_id, $session_id)
    {
        if (!Context::instance()->redis_zookeeper)
            return false;
        return Context::instance()->redis_zookeeper->hDel(
            self::SERVICE_KEY.":services:". Context::instance()->zookeeper_config["group_id"],
            $session_id,
            time()
        );
    }

    /**
     * get all services
     *
     * @return array like this
     *  [
     *     session_id => 1489478544
     *  ]
     */
    public static function getServices()
    {
        if (!Context::instance()->redis_zookeeper)
            return [];
        $services = Context::instance()->redis_zookeeper->keys(self::SERVICE_KEY.":services:*");
        $res = [];
        foreach ($services as $service) {
            $temp = explode(":",$service);
            $key  = array_pop($temp);

            $data = Context::instance()->redis_zookeeper->hgetall($service);

            if (!$data) {
                continue;
            }

            if (!isset($res[$key]))
                $res[$key] = [];
            
            foreach ($data as $session_id => $last_updated) {
                if ((time()-$last_updated)<=20) {
                    $res[$key][$session_id] = $last_updated;
                }
            }

            if (count($res[$key]) <= 0)
                unset($res[$key]);
            unset($temp,$key,$data);
        }
        return $res;
    }

    /**
     * check current node is leader
     */
    public function isLeader()
    {
        //没有启用群集功能 不判断leader
        if (!Context::instance()->zookeeper_config["enable"])
            return true;

        if (!$this->redis)
            return true;

        $key = self::SERVICE_KEY.":leader:". Context::instance()->zookeeper_config["group_id"];
        return $this->redis->get($key) == $this->session_id;
    }

    /**
     * check group has leader
     *
     * @return string current leader session_id
     */
    public static function getLeader($group_id)
    {
        if (!Context::instance()->redis_zookeeper)
            return null;
        return Context::instance()->redis_zookeeper->get(self::SERVICE_KEY.":leader:".$group_id);
    }

    public static function delLeader($group_id)
    {
        if (!Context::instance()->redis_zookeeper)
            return false;
        return Context::instance()->redis_zookeeper->del(self::SERVICE_KEY.":leader:".$group_id);
    }

    public static function setLeader($group_id, $session_id)
    {
        if (!Context::instance()->redis_zookeeper)
            return false;
        return Context::instance()->redis_zookeeper->set(self::SERVICE_KEY.":leader:".$group_id, $session_id);
    }

    /**
     * 配置管理，实现配置下发，针对通知的实现
     *
     * @return array
     */
    public function getNotify()
    {
        /**
        "host"     => "localhost",
        "user"     => "admin",
        "password" => "admin",
        "port"     => 5672,
        "vhost"    => "/"
         */
        return [
            "type"     => self::NOTIFY_TYPE_REDIS,
            "host"     => "127.0.0.1",
            "port"     => 6379,
            "password" => null,
            "user"     => null,  //仅针对mq
            "url"      => null   //仅针对http
        ];
    }

    /**
     * app配置下发实现
     */
    public function getAppConfig()
    {
        return [
            "app_id" => "wing-binlog",
            //app_id可以定义不同的名称，用于区分不同的服务器，
            //在分布式多服务器部署的时候，如果遇到库和表的名字都相同即可区分来源

            "memory_limit" => "10240M",
            //最大内存限制

            "log_dir" => __APP_DIR__."/logs",
            //日志目录 默认为当前路径下的logs文件夹 log_dir目录下的文件，
            //在指定--clear参数后 在重启或者停止进程后将全部被删除
            //在设定目录和使用--clear参数时请注意

            "binlog_cache_dir" => __APP_DIR__."/cache",
            //binlog采集中金生成的临时文件目录 binlog_cache_dir目录下的文件，
            //在指定--clear参数后 在重启或者停止进程后将全部被删除
            //在设定目录和使用--clear参数时请注意

            "process_cache_dir" => __APP_DIR__."/process_cache",
            //生成的一些进程控制的缓存文件目录

            "mysqlbinlog_bin"   => "mysqlbinlog",
            //如果mysqlbinlog没有加到环境变量或者无法识别，这里可以写上绝对路径

            "logger"     => \Seals\Logger\Local::class,
            //日志实现，可以自定义 必须继承psr/log标准的日志实现
            //比如需要将日志推送到别的服务器等需求 可以自定义日志的实现
            "log_levels" => [
                \Psr\Log\LogLevel::ALERT,
                \Psr\Log\LogLevel::CRITICAL,
                \Psr\Log\LogLevel::DEBUG,
                \Psr\Log\LogLevel::EMERGENCY,
                \Psr\Log\LogLevel::ERROR,
                \Psr\Log\LogLevel::INFO,
                \Psr\Log\LogLevel::NOTICE,
                \Psr\Log\LogLevel::WARNING
            ],
            //记录那些级别的日志

        ];
    }
}