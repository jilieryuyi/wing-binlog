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
        $hour = date("YmdH", $time);

        $this->setDayQueryCount($day);
        $this->setTotalQueryCount();
        $this->setDayEvents($day, $event);
        $this->setHourEvents($hour, $event);

        //$event show set select update delete
        $key = self::REPORT_LIST. "-".$event. "-".$day."-".$time;
        $num = $this->redis->incr($key);


        if ($event == "show" || $event == "select") {
            $this->setDayReadCount($day);
            $this->setHourReadCount($hour);
            $tmax = $this->getDayReadMax($day);
            if ($num > $tmax) {
                $this->setDayReadMax($day, $num);
            }

            $hmax = $this->getHourReadMax($hour);
            if ($num > $hmax) {
                $this->setHourReadMax($hour, $num);
            }

            $rmax = $this->getHistoryReadMax();
            if ($num > $rmax)
                $this->setHistoryReadMax($num);


        } else {
            $this->setDayWriteCount($day);
            $this->setHourWriteCount($hour);
            $tmax = $this->getDayWriteMax($day);
            if ($num > $tmax) {
                $this->setDayWriteMax($day, $num);
            }

            $hmax = $this->getHourWriteMax($hour);
            if ($num > $hmax) {
                $this->setHourWriteMax($hour, $num);
            }

            $wmax = $this->getHistoryWriteMax();
            if ($num > $wmax)
                $this->setHistoryWriteMax($num);
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

    public function setHourWriteCount($day_hour)
    {
        $key   = self::REPORT_LIST."-write-hour-".$day_hour."-query";
        return $this->redis->incr($key);
    }

    public function getHourWriteCount($day_hour)
    {
        $key   = self::REPORT_LIST."-write-hour-".$day_hour."-query";
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

    public function setHourReadCount($day_hour)
    {
        $key   = self::REPORT_LIST."-read-hour-".$day_hour."-query";
        return $this->redis->incr($key);
    }

    public function getHourReadCount($day_hour)
    {
        $key   = self::REPORT_LIST."-read-hour-".$day_hour."-query";

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
        $num  = $this->redis->get($key);

        if (!$num)
            return 0;

        return $num;
    }

    public function setHourEvents($hour, $event)
    {
        $key  = self::REPORT_LIST. "-hour-events-".$event. "-".$hour;
        return $this->redis->incr($key);
    }


    public function getHourReadMax($hour)
    {
        $num = $this->redis->get(self::REPORT_LIST."-hour-".$hour."-read-max-report");
        if (!$num)
            return 0;
        return $num;
    }
    public function setHourReadMax($hour, $size)
    {
        return $this->redis->set(self::REPORT_LIST."-hour-".$hour."-read-max-report", $size);
    }

    protected function setDayReadMax($day, $size)
    {
        $this->redis->set(self::REPORT_LIST."-day-".$day."-read-max-report", $size);
    }
    /**
     * 当天最高读秒并发数量
     *
     * @param $int $day like 20170302
     * @return int
     */
    public function getDayReadMax($day)
    {
        $num = $this->redis->get(self::REPORT_LIST."-day-".$day."-read-max-report");
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
        Context::instance()->logger->debug("events happened", [$daytime, $event_type]);

        $day  = date("Ymd", strtotime($daytime));
        $hour = date("YmdH", strtotime($daytime));

        $key            = self::REPORT_LIST."-events-total-".Context::instance()->session_id;
        $key_day        = self::REPORT_LIST."-events-day-".$day."-".Context::instance()->session_id;
        $key_day_event  = self::REPORT_LIST."-events-type-".$event_type."-day-".$day."-".Context::instance()->session_id;
        $key_hour_event = self::REPORT_LIST."-events-type-".$event_type."-hour-".$hour."-".Context::instance()->session_id;

        $this->redis->incr($key_day);
        $this->redis->incr($key_day_event);
        $this->redis->incr($key_hour_event);

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
    public function getHourEventTypeCount($hour, $event_type)
    {
        $key_hour_event = self::REPORT_LIST."-events-type-".$event_type."-hour-".$hour."-".Context::instance()->session_id;

        $num            = $this->redis->get($key_hour_event);
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


    public function setHistoryReadMax($num)
    {
        $this->redis->set(self::REPORT_LIST . "-history-read-max-report", $num);
    }
    /**
     * 历史最高读秒并发数量
     */
    public function getHistoryReadMax()
    {
        $data  = $this->redis->get(self::REPORT_LIST."-history-read-max-report");
        if (!$data)
            return 0;
        return $data;
    }

    public function setHistoryWriteMax($num)
    {
        $this->redis->set(self::REPORT_LIST . "-history-write-max-report", $num);
    }
    /**
     * 历史最高写秒并发
     */
    public function getHistoryWriteMax()
    {
        $data  = $this->redis->get(self::REPORT_LIST."-history-write-max-report");
        if (!$data)
            return 0;
        return $data;
    }


    public function getHourWriteMax($day_hour)
    {
        $max = $this->redis->get(self::REPORT_LIST."-hour-".$day_hour."-write-max-report");
        if (!$max)
            return 0;
        return $max;
    }

    protected function setHourWriteMax($day_hour, $size)
    {
        $this->redis->set(self::REPORT_LIST."-hour-".$day_hour."-write-max-report", $size);
    }

    protected function setDayWriteMax($day, $size)
    {
        $this->redis->set(self::REPORT_LIST."-day-".$day."-write-max-report", $size);
    }
    /**
     * 获取当天秒写最高并发
     */
    public function getDayWriteMax($day)
    {
        $max = $this->redis->get(self::REPORT_LIST."-day-".$day."-write-max-report");
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