<?php namespace Seals\Library;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/23
 * Time: 13:59
 */
class Report
{
    const REPORT_LIST = "wing-binlog-report";
    protected $redis;
    protected $cache;

    public function __construct(RedisInterface $redis)
    {
        $this->redis = $redis;
        $this->cache = new \Seals\Cache\Redis(Context::instance()->redis_local);
        //\Seals\Cache\File(__APP_DIR__."/data/report");
    }

    /**
     * report set data
     *
     * @param int $time time stamp
     * @param string $commant_type
     * @param string $event
     * @return bool
     */
    public function set($time, $event)
    {
        if (!$event)
            return false;

        if (!in_array($event,["show","set","select","update","delete"]))
            return false;

        $day = date("Ymd", $time);
        $this->setDayQueryCount($day);
        $this->setTotalQueryCount();
        $this->setDayEvents($day, $event);
        $this->setHourEvents(date("YmdH", $time), $event);

        //$event show set select update delete
        $key = self::REPORT_LIST. ":".$event. ":".$day;
        $num = 0;

        if ($this->redis->hexists($key, $time)) {
            $num = $this->redis->hget($key, $time);
        }
        $num++;

        if ($event == "show" || $event == "select") {
            $tmax = $this->getDayReadMax($day);
            if ($num > $tmax) {
                $this->setDayReadMax($day, $num);
            }
        } else {
            $tmax = $this->getDayWriteMax($day);
            if ($num > $tmax) {
                $this->setDayWriteMax($day, $num);
            }
        }

        return $this->redis->hset($key, $time, $num);
    }


    public function getTotalQueryCount()
    {
        $key   = self::REPORT_LIST."-total-query";
        $count = $this->redis->get($key);
        if (!$count)
            return 0;
        return $count;
    }

    public function setTotalQueryCount()
    {
        $key   = self::REPORT_LIST."-total-query";
        return $this->redis->incr($key);
    }

    public function setDayQueryCount($day)
    {
        $key   = self::REPORT_LIST."-day-".$day."-query";
        return $this->redis->incr($key);
    }

    public function getDayQueryCount($day)
    {
        $key   = self::REPORT_LIST."-day-".$day."-query";
        $count = $this->redis->get($key);
        if (!$count)
            return 0;
        return $count;
    }

    /**
     * get time stamp event num
     *
     * @param int $time time stamp
     * @param string $commant_type
     * @param string $event
     * @return int
     */
    public function get($time, $event)
    {
        $key = self::REPORT_LIST. ":".$event. ":".date("Ymd", $time);

        if ($this->redis->hexists($key, $time)) {
            return $this->redis->hget($key, $time);
        }
        return 0;
    }

    public function getDayEvents($day, $event)
    {
        $key  = self::REPORT_LIST. ":day:events:".$event. ":".$day;
        return $this->redis->get($key);
    }

    public function setDayEvents($day, $event)
    {
        $key  = self::REPORT_LIST. ":day:events:".$event. ":".$day;
        return $this->redis->incr($key);
    }

    public function getHourEvents($hour, $event)
    {
        $key  = self::REPORT_LIST. ":hour:events:".$event. ":".$hour;
        return $this->redis->get($key);
    }

    public function setHourEvents($hour, $event)
    {
        $key  = self::REPORT_LIST. ":hour:events:".$event. ":".$hour;
        return $this->redis->incr($key);
    }

    protected function setDayReadMax($day, $size)
    {
        $this->cache->set(self::REPORT_LIST.".day.".$day.".read.max.report", $size);
    }
    /**
     * 当天最高读秒并发数量
     *
     * @param $int $day like 20170302
     * @return int
     */
    public function getDayReadMax($day)
    {
        $num = $this->cache->get(self::REPORT_LIST.".day.".$day.".read.max.report");
        if (!$num)
            return 0;
        return $num;
    }

    public static function eventsIncr($daytime)
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        $day = date("Ymd", strtotime($daytime));

        $key     = self::REPORT_LIST."-events-total-".Context::instance()->session_id;
        $key_day = self::REPORT_LIST."-events-day-".$day."-".Context::instance()->session_id;

        Context::instance()->redis_zookeeper->incr($key_day);

        return Context::instance()->redis_zookeeper->incr($key);
    }

    public static function getDayEventsCount($day)
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        $key_day = self::REPORT_LIST."-events-day-".$day."-".Context::instance()->session_id;
        $count   = Context::instance()->redis_zookeeper->get($key_day);

        if (!$count)
            return 0;
        return $count;
    }

    public static function getEventsCount()
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();
        $keys = Context::instance()->redis_zookeeper->keys(self::REPORT_LIST."-events-total-*");
        $count = 0;
        foreach ($keys as $key)
            $count += Context::instance()->redis_zookeeper->get($key);
        return $count;
    }

    /**
     * 历史最高读秒并发数量
     */
    public function getHistoryReadMax()
    {
        $data  = $this->cache->get(self::REPORT_LIST.".history.read.max.report");
        $max   = 0;

        $days  = [];
        if ($data) {
            $max      = $data[0];
            $last_day = $data[1];

            if ($last_day == date("Ymd", time()-86400))
                return $max;

            $start    = time();
            $end      = strtotime($last_day);
            echo  date("Y-m-d",$start),"\r\n";
            echo  date("Y-m-d",$end),"\r\n";

            for ($time = $end+86400; $time <= $start-86400; $time += 86400) {
                $days[] = date("Ymd", $time);
            }

        } else {

            $keys1 = $this->redis->keys(self::REPORT_LIST . ":show" . ":*");
            $keys2 = $this->redis->keys(self::REPORT_LIST . ":select" . ":*");

            $keys = array_merge($keys1, $keys2);
            foreach ($keys as $key) {
                $temp = explode(":", $key);
                $day = array_pop($temp);
                if (!in_array($day, $days))
                    $days[] = $day;
            }

            sort($days);
        }

        foreach ($days as $day) {
            $num = $this->getDayReadMax($day);
            if ($num > $max)
                $max = $num;
        }

        $last_day = array_pop($days);

        if (is_numeric($max) && $max >0)
            $this->cache->set(self::REPORT_LIST.".history.read.max.report",[$max,$last_day]);
        return $max;
    }

    /**
     * 历史最高写秒并发
     */
    public function getHistoryWriteMax()

    {
        $data  = $this->cache->get(self::REPORT_LIST.".history.write.max.report");
        $max      = 0;

        $days  = [];
        if ($data) {
            $max      = $data[0];
            $last_day = $data[1];

            if ($last_day == date("Ymd", time()-86400))
                return $max;

            $start    = time();
            $end      = strtotime($last_day);
            for ($time = $end+86400; $time <= $start-86400; $time += 86400) {
                $days[] = date("Ymd", $time);
            }
        } else {

            $keys1 = $this->redis->keys(self::REPORT_LIST . ":set" . ":*");
            $keys2 = $this->redis->keys(self::REPORT_LIST . ":update" . ":*");
            $keys3 = $this->redis->keys(self::REPORT_LIST . ":delete" . ":*");

            $keys = array_merge($keys1, $keys2, $keys3);
            foreach ($keys as $key) {
                $temp = explode(":", $key);
                $day = array_pop($temp);
                if (!in_array($day, $days))
                    $days[] = $day;
            }

            sort($days);
        }

        foreach ($days as $day) {
            $num = $this->getDayWriteMax($day);
            if ($num > $max)
                $max = $num;
        }

        if (is_numeric($max) && $max >0)
        $this->cache->set(self::REPORT_LIST.".history.write.max.report",[$max,array_pop($days)]);
        return $max;
    }


    protected function setDayWriteMax($day, $size)
    {
        $this->cache->set(self::REPORT_LIST.".day.".$day.".write.max.report", $size);
    }
    /**
     * 获取当天秒写最高并发
     */
    public function getDayWriteMax($day)
    {
        $max = $this->cache->get(self::REPORT_LIST.".day.".$day.".write.max.report");
        if (!$max)
            return 0;
        return $max;
    }

    /**
     * 删除所有redis缓存数据
     */
    public function clearAll()
    {
        $keys = $this->redis->keys(self::REPORT_LIST."*");
        return $this->redis->del($keys);
    }

}