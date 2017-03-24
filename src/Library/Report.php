<?php namespace Seals\Library;
use Seals\Cache\File;

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
    protected $total_query = 0;
    public function __construct(RedisInterface $redis)
    {
        $this->redis = $redis;
        $this->cache = new File(__APP_DIR__."/data/report");
        $this->total_query = $this->getTotalQueryCount();
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

        $this->total_query++;
        $this->setTotalQueryCount($this->total_query);

        $day = date("Ymd", $time);
        $this->setDayQueryCount($day);


        //$event show set select update delete
        $key = self::REPORT_LIST.
            ":".$event.
            ":".$day;

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
        $key   = "wing-binlog-total-query";
        $count = $this->redis->get($key);
        if (!$count)
            return 0;
        return $count;
    }

    public function setTotalQueryCount($count)
    {
        $key   = "wing-binlog-total-query";
        return $this->redis->set($key, $count);
    }

    public function setDayQueryCount($day)
    {
        $key   = "wing-binlog-day-".$day."-query";
        return $this->redis->incr($key);
    }

    public function getDayQueryCount($day)
    {
        $key   = "wing-binlog-day-".$day."-query";
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
        $key = self::REPORT_LIST.
            ":".$event.
            ":".date("Ymd", $time);

        if ($this->redis->hexists($key, $time)) {
            return $this->redis->hget($key, $time);
        }
        return 0;
    }
//    protected function getDayReadMax($day){
//        $num = $this->cache->get("_day.".$day.".read.max.report");
//        if (!$num)
//            return 0;
//        return $num;
//    }

    protected function setDayReadMax($day, $size)
    {
        $this->cache->set("_day.".$day.".read.max.report", $size);
    }

    public static function eventsIncr()
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();
        $key     = "wing-binlog-events-total-".Context::instance()->session_id;
        $key_day = "wing-binlog-events-day-".date("Ymd")."-".Context::instance()->session_id;

        Context::instance()->redis_zookeeper->incr($key_day);

        return Context::instance()->redis_zookeeper->incr($key);
    }

    public static function getDayEventsCount($day)
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        $key_day = "wing-binlog-events-day-".$day."-".Context::instance()->session_id;
        $count   = Context::instance()->redis_zookeeper->get($key_day);

        if (!$count)
            return 0;
        return $count;
    }

    public static function getEventsCount()
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();
        $keys = Context::instance()->redis_zookeeper->keys("wing-binlog-events-total-*");
        $count = 0;
        foreach ($keys as $key)
            $count += Context::instance()->redis_zookeeper->get($key);
        return $count;
    }
    /**
     * 当天最高读秒并发数量
     *
     * @param $int $day like 20170302
     * @return int
     */
    public function getDayReadMax($day)
    {
        $num = $this->cache->get("_day.".$day.".read.max.report");
        if (!$num)
            return 0;
        return $num;

        //$event show select
        /*enable_time_test();
        $max    = $this->cache->get("read.max.".$day.".report");
        if ($max && $day != date("Ymd")) {
            time_test_dump("1");
            return $max;
        }
        time_test_dump("1");
        $times1 = $this->redis->hkeys(self::REPORT_LIST. ":show". ":".$day);
        $times2 = $this->redis->hkeys(self::REPORT_LIST. ":select". ":".$day);

        $times  = array_merge($times1, $times2);
        $max    = 0;
        time_test_dump("2");
        echo "\r\n",count($times),"\r\n";
        foreach ($times as $time) {
            $num = $this->get($time, "show") + $this->get($time, "select");
            if ($num > $max)
                $max = $num;
        }
        time_test_dump("3");
        if ($day != date("Ymd"))
        $this->cache->set("read.max.".$day.".report", $max);
        time_test_dump("4");
        return $max;*/
    }

    /**
     * 历史最高读秒并发数量
     */
    public function getHistoryReadMax()
    {
        $data  = $this->cache->get("history.read.max.report");
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
        $this->cache->set("history.read.max.report",[$max,$last_day]);
        return $max;
    }

    /**
     * 历史最高写秒并发
     */
    public function getHistoryWriteMax()

    {
        $data  = $this->cache->get("history.write.max.report");
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

        $this->cache->set("history.write.max.report",[$max,array_pop($days)]);
        return $max;
    }


    protected function setDayWriteMax($day, $size)
    {
        $this->cache->set("_day.".$day.".write.max.report", $size);
    }
    /**
     * 获取当天秒写最高并发
     */
    public function getDayWriteMax($day)
    {
        $max = $this->cache->get("_day.".$day.".write.max.report");
        if (!$max)
            return 0;
        return $max;
        /*
        $max    = $this->cache->get("write.max.".$day.".report");
        if ($max && $day != date("Ymd")) {
            return $max;
        }
        //$event set update delete
        $times1 = $this->redis->hkeys(self::REPORT_LIST. ":set". ":".$day);
        $times2 = $this->redis->hkeys(self::REPORT_LIST. ":update". ":".$day);
        $times3 = $this->redis->hkeys(self::REPORT_LIST. ":delete". ":".$day);

        $times  = array_merge($times1, $times2, $times3);
        $max    = 0;

        foreach ($times as $time) {
            $num =
                $this->get($time, "set") +
                $this->get($time, "update") +
                $this->get($time, "delete");

            if ($num > $max)
                $max = $num;
        }
        if ($day != date("Ymd"))
        $this->cache->set("write.max.".$day.".report", $max);
        return $max;*/
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