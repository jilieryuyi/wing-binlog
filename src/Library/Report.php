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
    public function __construct(RedisInterface $redis)
    {
        $this->redis = $redis;
        $this->cache = new File(__APP_DIR__."/data/report");
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

        //$event show set select update delete
        $key = self::REPORT_LIST.
            ":".$event.
            ":".date("Ymd", $time);

        $num = 0;
        if ($this->redis->hexists($key, $time)) {
            $num = $this->redis->hget($key, $time);
        }
        $num++;

        return $this->redis->hset($key, $time, $num);
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

    /**
     * 当天最高读秒并发数量
     *
     * @param $int $day like 20170302
     * @return int
     */
    public function getDayReadMax($day)
    {
        //$event show select
        $max    = $this->cache->get("read.max.".$day.".report");
        if ($max) {
            return $max;
        }

        $times1 = $this->redis->hkeys(self::REPORT_LIST. ":show". ":".$day);
        $times2 = $this->redis->hkeys(self::REPORT_LIST. ":select". ":".$day);

        $times  = array_merge($times1, $times2);
        $max    = 0;

        foreach ($times as $time) {
            $num = $this->get($time, "show") + $this->get($time, "select");
            if ($num > $max)
                $max = $num;
        }

        $this->cache->set("read.max.".$day.".report", $max);
        return $max;
    }

    /**
     * 历史最高读秒并发数量
     */
    public function getHistoryReadMax()
    {
        $data  = $this->cache->get("history.read.max.report");
        $max      = 0;

        $days  = [];
        if ($data) {
            $max      = $data[0];
            $last_day = $data[1];
            $start    = time();
            $end      = strtotime($last_day);
            for ($time = $end+86400; $time < $start; $time += 86400) {
                $days[] = date("Ymd", $time);
            }
        } else {

            $keys1 = $this->redis->keys(self::REPORT_LIST . ":show" . ":*");
            $keys2 = $this->redis->keys(self::REPORT_LIST . ":select" . ":*");

            $keys = array_merge($keys1, $keys2);
            foreach ($keys as $key) {
                $day = array_pop(explode(":", $key));
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

        $this->cache->set("history.read.max.report",[$max,array_pop($days)]);
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
            $start    = time();
            $end      = strtotime($last_day);
            for ($time = $end+86400; $time < $start; $time += 86400) {
                $days[] = date("Ymd", $time);
            }
        } else {

            $keys1 = $this->redis->keys(self::REPORT_LIST . ":set" . ":*");
            $keys2 = $this->redis->keys(self::REPORT_LIST . ":update" . ":*");
            $keys3 = $this->redis->keys(self::REPORT_LIST . ":delete" . ":*");

            $keys = array_merge($keys1, $keys2, $keys3);
            foreach ($keys as $key) {
                $day = array_pop(explode(":", $key));
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

    /**
     * 获取当天秒写最高并发
     */
    public function getDayWriteMax($day)
    {
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