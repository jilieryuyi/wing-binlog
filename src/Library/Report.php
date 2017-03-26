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

    public function __construct(RedisInterface $redis)
    {
        $this->redis = $redis;
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

        if (!in_array($event,["show","set","insert","select","update","delete"]))
            return false;

        $day = date("Ymd", $time);
        $this->setDayQueryCount($day);
        $this->setTotalQueryCount();
        $this->setDayEvents($day, $event);
        $this->setHourEvents(date("YmdH", $time), $event);

        //$event show set select update delete
        $key = self::REPORT_LIST. "-".$event. "-".$day."-".$time;

        $num = $this->redis->incr($key);


        if ($event == "show" || $event == "select") {
            $this->setDayReadCount($day);
            $tmax = $this->getDayReadMax($day);
            if ($num > $tmax) {
                $this->setDayReadMax($day, $num);
            }
        } else {
            $this->setDayWriteCount($day);
            $tmax = $this->getDayWriteMax($day);
            if ($num > $tmax) {
                $this->setDayWriteMax($day, $num);
            }
        }

        return $num;
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

    public function setDayWriteCount($day)
    {
        $key   = self::REPORT_LIST."-write-day-".$day."-query";
        return $this->redis->incr($key);
    }

    public function getDayWriteCount($day)
    {
        $key   = self::REPORT_LIST."-write-day-".$day."-query";
        $num   = $this->redis->get($key);

        if (!$num)
            return 0;
        return $num;
    }
    public function setDayReadCount($day)
    {
        $key   = self::REPORT_LIST."-read-day-".$day."-query";
        return $this->redis->incr($key);
    }

    public function getDayReadCount($day)
    {
        $key   = self::REPORT_LIST."-read-day-".$day."-query";
        $num   = $this->redis->get($key);

        if (!$num)
            return 0;
        return $num;
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
        $key = self::REPORT_LIST. "-".$event. "-".date("Ymd", $time)."-".$time;

        $num = $this->redis->get($key);

        if (!$num)
            return 0;
        return $num;
    }

    //"show","set","select","update","delete"
    public function getDayEvents($day, $event)
    {
        $key  = self::REPORT_LIST. "-day-events-".$event. "-".$day;
        $num = $this->redis->get($key);
        if (!$num)
            return 0;
        return $num;
    }

    public function setDayEvents($day, $event)
    {
        $key  = self::REPORT_LIST. "-day-events-".$event. "-".$day;
        return $this->redis->incr($key);
    }

    public function getHourEvents($hour, $event)
    {
        $key  = self::REPORT_LIST. "-hour-events-".$event. "-".$hour;
        return $this->redis->get($key);
    }

    public function setHourEvents($hour, $event)
    {
        $key  = self::REPORT_LIST. "-hour-events-".$event. "-".$hour;
        return $this->redis->incr($key);
    }

    protected function setDayReadMax($day, $size)
    {
        $this->redis->set(self::REPORT_LIST."-day-".$day."-read-max.report", $size);
    }
    /**
     * 当天最高读秒并发数量
     *
     * @param $int $day like 20170302
     * @return int
     */
    public function getDayReadMax($day)
    {
        $num = $this->redis->get(self::REPORT_LIST."-day-".$day."-read-max.report");
        if (!$num)
            return 0;
        return $num;
    }

    /**
     * 面向全局，统计事件发生的次数，binlog解析使用
     *
     * @param string $daytime
     * @param string $event_type write_rows delete_rows update_rows
     * @return int
     */
    public function eventsIncr($daytime, $event_type)
    {

        $day = date("Ymd", strtotime($daytime));

        $key           = self::REPORT_LIST."-events-total-".Context::instance()->session_id;
        $key_day       = self::REPORT_LIST."-events-day-".$day."-".Context::instance()->session_id;
        $key_day_event = self::REPORT_LIST."-events-type-".$event_type."-day-".$day."-".Context::instance()->session_id;

        $this->redis->incr($key_day);
        $this->redis->incr($key_day_event);

        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();
        //global key
        $key_all       = self::REPORT_LIST."-global-events-all-count";
        $key_day       = self::REPORT_LIST."-global-events-day-".$day."-".Context::instance()->session_id;

        Context::instance()->redis_zookeeper->incr($key_all);
        Context::instance()->redis_zookeeper->incr($key_day);


        return $this->redis->incr($key);
    }

    public function getDayEventTypeCount($day, $event_type)
    {
        $key_day_event = self::REPORT_LIST."-events-type-".$event_type."-day-".$day."-".Context::instance()->session_id;
        $num           = $this->redis->get($key_day_event);
        if (!$num)
            return 0;
        return $num;
    }

    /**
     * local
     */
    public function getLocalDayEventsCount($day)
    {
        $key_day = self::REPORT_LIST."-events-day-".$day."-".Context::instance()->session_id;
        $count   = $this->redis->get($key_day);

        if (!$count)
            return 0;
        return $count;
    }
    /**
     * global, get binlog events count
     *
     * @param string $day format Ymd
     * @return int
     */
    public static function getDayEventsCount($day)
    {

        $key_day = self::REPORT_LIST."-global-events-day-".$day."-".Context::instance()->session_id;

        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        $count   = Context::instance()->redis_zookeeper->get($key_day);

        if (!$count)
            return 0;
        return $count;
    }

    /**
     * global, get history all events count
     */
    public static function getEventsCount()
    {
        $key_all = self::REPORT_LIST."-global-events-all-count";

        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        $num     = Context::instance()->redis_zookeeper->get($key_all);

        if (!$num)
            return 0;

        return $num;
    }

    /**
     * 历史最高读秒并发数量
     */
    public function getHistoryReadMax()
    {
        $data  = $this->redis->get(self::REPORT_LIST."-history-read-max.report");
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

            $keys1 = $this->redis->keys(self::REPORT_LIST . "-show" . ":*");
            $keys2 = $this->redis->keys(self::REPORT_LIST . "-select" . ":*");

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
            $this->redis->set(self::REPORT_LIST."-history-read-max-report",[$max,$last_day]);
        return $max;
    }

    /**
     * 历史最高写秒并发
     */
    public function getHistoryWriteMax()

    {
        $data  = $this->redis->get(self::REPORT_LIST."-history-write-max.report");
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

            $keys1 = $this->redis->keys(self::REPORT_LIST . "-set" . ":*");
            $keys2 = $this->redis->keys(self::REPORT_LIST . "-update" . ":*");
            $keys3 = $this->redis->keys(self::REPORT_LIST . "-delete" . ":*");

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
        $this->redis->set(self::REPORT_LIST."-history-write-max.report",[$max,array_pop($days)]);
        return $max;
    }


    protected function setDayWriteMax($day, $size)
    {
        $this->redis->set(self::REPORT_LIST."-day-".$day."-write-max.report", $size);
    }
    /**
     * 获取当天秒写最高并发
     */
    public function getDayWriteMax($day)
    {
        $max = $this->redis->get(self::REPORT_LIST."-day-".$day."-write-max.report");
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