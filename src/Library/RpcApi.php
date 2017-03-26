<?php namespace Seals\Library;
use Seals\Cache\File;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/26
 * Time: 10:09
 */
class RpcApi
{
    /**
     * rpc api, get databases list
     *
     * @return array
     */
    public static function getDatabases()
    {
        return Context::instance()->activity_pdo->getDatabases();
    }

    /**
     * rpc api, open or close mysql generallog
     *
     * @param bool $open
     * @return int
     */
    public static function openGenerallog($open)
    {
        $general = new GeneralLog(Context::instance()->activity_pdo);
        if ($open) {
            $general->open();
        } else {
            $general->close();
        }
        unset($general);
        return 1;
    }

    public static function getHistoryReadMax()
    {
        $report = new Report(Context::instance()->redis_local);
        $max = $report->getHistoryReadMax();
        unset($report);
        return $max;
    }

    public static function getHistoryWriteMax()
    {
        $report = new Report(Context::instance()->redis_local);
        $max = $report->getHistoryWriteMax();
        unset($report);
        return $max;
    }

    public static function getDayEvents($day, $event)
    {
        $report = new Report(Context::instance()->redis_local);
        $num    = $report->getDayEvents($day, $event);
        unset($report);
        return $num;
    }

    public static function getTodayHoursReadEvents()
    {
        $start  = strtotime(date("Y-m-d 00:00:00"));
        $end    = strtotime(date("Y-m-d H:00:00"));
        $res    = [];

        $report = new Report(Context::instance()->redis_local);

        for ($time = $start; $time <= $end; $time += 3600) {
            $hour = date("YmdH", $time);

            $num1    = $report->getHourEvents($hour, "show");
            $num2    = $report->getHourEvents($hour, "select");

            if (!$num1)
                $num1 = 0;
            if ($num2)
                $num2 = 0;

            $res[] = $num1 + $num2;

        }
        unset($report);
        return $res;
    }

    public static function getTodayHoursWriteEvents()
    {
        $start  = strtotime(date("Y-m-d 00:00:00"));
        $end    = strtotime(date("Y-m-d H:00:00"));
        $res    = [];

        $report = new Report(Context::instance()->redis_local);

        for ($time = $start; $time <= $end; $time += 3600) {
            $hour = date("YmdH", $time);

            $num1    = $report->getHourEvents($hour, "delete");
            $num2    = $report->getHourEvents($hour, "update");
            $num3    = $report->getHourEvents($hour, "insert");


            if (!$num1)
                $num1 = 0;
            if ($num2)
                $num2 = 0;

            if ($num3)
                $num3 = 0;

            $res[] = $num1 + $num2 + $num3;

        }
        unset($report);
        return $res;
    }

    public static function getHourEvents($hour, $event)
    {
        $report = new Report(Context::instance()->redis_local);
        $num    = $report->getHourEvents($hour, $event);
        unset($report);
        return $num;
    }

    public static function getDayReadMax($day)
    {
        $report = new Report(Context::instance()->redis_local);
        $max = $report->getDayReadMax($day);
        unset($report);
        return $max;
    }

    public static function getDayWriteMax($day)
    {
        $report = new Report(Context::instance()->redis_local);
        $max = $report->getDayWriteMax($day);
        unset($report);
        return $max;
    }

    public static function getTotalQueryCount()
    {
        $report = new Report(Context::instance()->redis_local);
        $max    = $report->getTotalQueryCount();
        unset($report);
        return $max;
    }

    public static function getDayQueryCount($day)
    {
        $report = new Report(Context::instance()->redis_local);
        $max    = $report->getDayQueryCount($day);
        unset($report);
        return $max;
    }

    ////////////////

    /**
     * set workers num and open debug or close debug in runtime
     *
     * @param int $_workers
     * @param bool $_debug
     * @return int
     */
    public static function setRuntimeConfig($_workers, $_debug = false)
    {
        $cache = new File(__APP_DIR__);
        list($deamon, $workers, $debug, $clear) = $cache->get(Worker::RUNTIME);

        //if config not change
        if ($workers == $_workers && !!$debug == !!$_debug )
            return 1;

        $_workers = intval($_workers) > 0 ? $_workers : $workers;

        $cache->set(Worker::RUNTIME,[
            $deamon, $_workers, !!$_debug, $clear
        ]);
        unset($cache);
        //after update, restart node
        Worker::restart();
        return 1;
    }

    /**
     * rpc api, update notify config
     *
     * @param string $class
     * @param array $params
     * @return int
     */
    public static function setNotifyConfig($class, $params)
    {
        $class       = urldecode($class);
        $config_file = __APP_DIR__."/config/notify.php";

        $params_str  = '[';
        $temp        = [];

        foreach ($params as $param) {
            $temp[] = '"'.urldecode($param).'"';
        }

        $params_str .= implode(",", $temp);
        $params_str .= ']';
        $template    = "<?php\r\nreturn [
        \"handler\" => \"".$class."\",
        \"params\"  => ".$params_str."\r\n];";

        file_put_contents($config_file, $template);
        Worker::restart();
        return 1;
    }

    public static function setLocalRedisConfig($host, $port, $password = null)
    {
        $config_file = __APP_DIR__."/config/redis_local.php";
        $config      = new Config([
            "host"     => $host,
            "port"     => $port,
            "password" => $password
        ]);
        $config->write($config_file);

        Worker::restart();
        return 1;
    }

    public static function setRedisConfig($host, $port, $password = null)
    {
        $config_file = __APP_DIR__."/config/redis.php";
        $config      = new Config([
            "host" => $host,
            "port" => $port,
            "password" =>$password
        ]);
        $config->write($config_file);
        Worker::restart();
        return 1;
    }

    public static function setRabbitmqConfig($host, $port, $user, $password, $vhost)
    {

        $config_file = __APP_DIR__."/config/rabbitmq.php";
        $config      = new Config([
            "host" => $host,
            "port" => $port,
            "user" => $user,
            "password" => $password,
            "vhost" => $vhost
        ]);

        $config->write($config_file);
        unset($config);
        Worker::restart();
        return 1;
    }

    public static function setZookeeperConfig($group_id, $host, $port, $password = null)
    {
        $config_file = __APP_DIR__."/config/zookeeper.php";
        $config      = new Config([
            "group_id" => $group_id,
            "host"     => $host,
            "port"     => $port,
            "password" => $password
        ]);

        $config->write($config_file);
        unset($config);
        Worker::restart();
        return 1;
    }

    /**
     * rpc api, set database config
     *
     * @param string $db_name
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     * @return int
     */
    public static function setDbConfig($db_name, $host, $user, $password, $port)
    {
        $config_file = __APP_DIR__."/config/db.php";
        $config      = new Config([
            "db_name"  => $db_name,
            "host"     => $host,
            "user"     => $user,
            "password" => $password,
            "port"     => $port
        ]);

        $config->write($config_file);
        unset($config);
        Worker::restart();
        return 1;
    }

    public static function getDayReport($start_day, $end_day)
    {
        //Context::instance()->logger->debug($start_day."=>".$end_day);
        $report = new Report(Context::instance()->redis_local);

        $res        = [];
        $start_time = strtotime($start_day);
        $end_time   = strtotime($end_day);

        $events     = ["show","set","select","update","delete","insert"];
        $event_types = ["write_rows", "delete_rows", "update_rows"];

        for ($time = $start_time; $time <= $end_time; $time += 86400) {
            $day = date("Ymd", $time);
            foreach ($events as $event) {
                $times = $report->getDayEvents($day, $event);
                $res[$day][$event] = $times;
            }
            foreach ($event_types as $event_type) {
                $times = $report->getDayEventTypeCount($day, $event_type);
                $res[$day][$event_type] = $times;
            }

            $res[$day]["read_max"]  = $report->getDayReadMax($day);
            $res[$day]["write_max"] = $report->getDayWriteMax($day);

            $res[$day]["read_total"]  = $report->getDayReadCount($day);
            $res[$day]["write_total"] = $report->getDayWriteCount($day);
        }
        //Context::instance()->logger->debug($start_day."=>".$end_day,$res);

        return $res;
    }

}