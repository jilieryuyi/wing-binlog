<?php namespace Wing\Library;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/24
 * Time: 14:45
 *
 * 缓存文件按行解析实现
 *
 * @property IDb $db_handler
 */
class FileFormat
{

    /**
     * @缓存路径
     */
    private $file;
    /**
     * @事件发生的时间
     */
    private $daytime = null;
    /**
     * @事件类型
     */
    private $event_type = null;
    /**
     * @事件发生次数
     */
    private $events_times = 0;
    /**
     * @数据库句柄
     */
    private $db_handler;
    private $caches = [];
    private $start_time = 0;

    /**
     * @构造函数
     * @param string $file 文件路径
     * @param IDb $db_handler
     */
    public function __construct($file, IDb $db_handler)
    {
        $this->file       = $file;
        $this->db_handler = $db_handler;
        $this->start_time = time();
    }

    /**
     * 按行解析文件
     *
     * @param \Closure $callback 如 function($db,$table,$event){}
     * @return array
     */
//    public function parse()
//    {
//        $fh = fopen($this->file, 'r');
//
//        if (!$fh || !is_resource($fh)) {
//            return null;
//        }
//
//        $file_size = filesize($this->file);
//        $read_size = 0;
//        $lines     = [];
//
//        $all_res   = [];
//
//        while (!feof($fh)) {
//
//            $line  = fgets($fh);
//
//            $read_size += sizeof($line);
//
//            $_line = ltrim($line,"#");
//            $_line = trim($_line);
//
//            $e = strtolower(substr($_line,0,6));
//            unset($_line);
//
//            //遇到分隔符 重置
//            if (preg_match("/#[\s]{1,}at[\s]{1,}[0-9]{1,}/",$line) ||
//                $e == "insert" ||
//                $e == "update" ||
//                $e == "delete"
//            ) {
//
//                if ($lines) {
//					$res = $this->linesParse($lines);
//					foreach ($res as $item) {
//						$all_res[] = $item;
//					}
//                }
//                unset($lines);
//                $lines = [];
//            }
//
//            $lines[] = $line;
//            unset($line);
//
//            if ($read_size >= $file_size)
//                break;
//        }
//
//        if ($lines) {
//            $res = $this->linesParse($lines);
//			foreach ($res as $item) {
//				$all_res[] = $item;
//			}
//        }
//
//        fclose($fh);
//        return $all_res;
//    }

    public function parse()
    {
//        $fh = fopen($this->file, 'r');
//
//        if (!$fh || !is_resource($fh)) {
//            return null;
//        }

        $file_size = strlen($this->file);//filesize($this->file);
        $read_size = 0;
        $all_lines     = explode("\n", $this->file);

        $all_res   = [];
        $lines     = [];

       // while (!feof($fh))
        foreach ($all_lines as $line)
        {

           // $line  = fgets($fh);

           // $read_size += sizeof($line);

            $_line = ltrim($line,"#");
            $_line = trim($_line);

            $e = strtolower(substr($_line,0,6));
            unset($_line);

            //遇到分隔符 重置
            if (preg_match("/#[\s]{1,}at[\s]{1,}[0-9]{1,}/",$line) ||
                $e == "insert" ||
                $e == "update" ||
                $e == "delete"
            ) {

                if ($lines) {
                    $res = $this->linesParse($lines);
                    foreach ($res as $item) {
                        $all_res[] = $item;
                    }
                }
                unset($lines);
                $lines = [];
            }

            $lines[] = $line;
            unset($line);

            if ($read_size >= $file_size)
                break;
        }

        if ($lines) {
            $res = $this->linesParse($lines);
            foreach ($res as $item) {
                $all_res[] = $item;
            }
        }

        //fclose($fh);
        return $all_res;
    }
    /**
     * @获取事件发生的时间
     *
     * @return string
     */
    protected function getEventTime($item)
    {
        preg_match_all("/[0-9]{6}\s+?[0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}/", $item, $time_match);
        if (!isset($time_match[0][0])) {
            return $this->daytime;
        }
        $daytime = $this->daytime = date("Y-m-d H:i:s", strtotime(substr(date("Y"), 0, 2) . $time_match[0][0]));
        return $daytime;
    }

    /**
     * 行解析
     *
     * @param array $lines 行
     */
    protected function linesParse($lines)
    {
    	$result = [];
        do {
            //处理流程
            $item = implode("", $lines);

            //得到事件发生的时间
            $daytime = $this->getEventTime($item);
            if (!$daytime) {
                break;
            }

            //得到事件发生的数据库和表
            list($database_name, $table_name) = $this->getTables($item);
            if (!$database_name || !$table_name) {
                break;
            }

            //得到事件 类型 这里只在乎 Delete_rows|Write_rows|Update_rows
            //因为这三种事件影响了数据，也就是数据发生了变化
            $event_type = $this->getEventType($item);
            if (!$event_type) {
                break;
            }

            unset($item);

            //得到表字段
            $columns = $this->getColumns($database_name, $table_name);
            if (!$columns) {
                break;
            }

            //按行解析
            $event = $this->eventDatasFormat($lines, $daytime, $event_type, $columns);
            unset($columns);

            if ($event) {
                //事件计数器
                $this->events_times++;

                $str1 = md5(rand(0, 999999));
                $str2 = md5(rand(0, 999999));
                $str3 = md5(rand(0, 999999));
                $event["__enevt_id"] = "wing_binlog_" . time() . "_" .
                    substr($str1, rand(0, strlen($str1) - 16), 16) . "_" .
                    substr($str2, rand(0, strlen($str2) - 16), 16) . "_" .
                    substr($str3, rand(0, strlen($str3) - 16), 16);
                //执行事件回调函数
				$result[] = ["database"=>$database_name, "table"=>$table_name, "event"=>$event];
            }
        } while (0);
        return $result;
    }

    /**
     * 获取数据库和数据表
     *
     * @return array
     */
    protected function getTables($item)
    {
        preg_match_all("/`[\s\S].*?`.`[\s\S].*?`/", $item, $match_tables);

        if (!isset($match_tables[0][0])) {
            return [false,false];
        }

        list($database_name, $table_name) = explode(".",$match_tables[0][0]);

        $database_name = trim($database_name,"`");
        $table_name    = trim($table_name,"`");

        return [$database_name, $table_name];
    }

    /**
     * 获取事件类型
     *
     * @return string
     */
    protected function getEventType($item)
    {
        preg_match("/\s(Delete_rows|Write_rows|Update_rows):/", $item, $ematch);

        if (!isset($ematch[1])) {
            $_item = ltrim($item,"#");
            $_item = trim($_item);

            $e = strtolower(substr($_item,0,6));
            if ($e == "insert")
                return "write_rows";

            if ($e == "update")
                return "update_rows";

            if ($e == "delete")
                return "delete_rows";

            return $this->event_type;
        }

        $this->event_type =  strtolower($ematch[1]);
        return $this->event_type;
    }


    /**
     * @事件数据格式化
     *
     * @return array
     */
    protected function eventDatasFormat($target_lines, $daytime, $event_type, $columns)
    {

        $event_data = [
            "event_type" => $event_type,
            "time"       => $daytime
        ];

        $is_old_data = true;
        $old_data    = [];
        $new_data    = [];
        $set_data    = [];
        $index       = 0;

        foreach ($target_lines as $target_line) {
            //去掉行的开始#和空格
            $target_line = ltrim($target_line, "#");
            $target_line = trim($target_line);
            //所有的字段开始的字符都是@
            if (substr($target_line, 0, 1) == "@") {
                $target_line = preg_replace("/@[0-9]{1,}=/", "", $target_line);
                /*
                if (strpos($target_line,"/*")) {
                    $temp = explode("/*",$target_line);
                    $target_line = $temp[0];
                    unset($temp);
                    $target_line = trim($target_line);
                }
                */
                $target_line = trim($target_line, "'");
                //如果是update操作 有两组数据 一组是旧数据 一组是新数据
                if ($event_type == "update_rows") {
                    if ($is_old_data) {
                        $old_data[$columns[$index]] = $target_line;
                    } else {
                        $new_data[$columns[$index]] = $target_line;
                    }
                } else {
                    $set_data[$columns[$index]] = $target_line;
                }

                echo $columns[$index],"====>", $target_line,"\r\n";
                $index++;
            }

            //遇到set关键字 重置索引 开始记录老数据
            if (strtolower($target_line) == "set") {
                $is_old_data = false;
                $index = 0;
            }
        }

        if ($event_type == "update_rows") {
            //这里忽略空数据
            if (count($old_data) <= 0 || count($new_data) <= 0) {
                return null;
            }

            $event_data["data"] = [
                "old_data" => $old_data,
                "new_data" => $new_data
            ];
        } else {
            //这里忽略空数据
            if (count($set_data) <= 0) {
                return null;
            }
            $event_data["data"] = $set_data;
        }

        return $event_data;
    }

    /**
     * @获取数据表行
     *
     * @return array
     */
    protected function getColumns($database_name, $table_name)
    {
        if (isset($this->caches[$database_name][$table_name]) &&
            (time() - $this->start_time) < 5 //5秒缓存
        ) {
            return $this->caches[$database_name][$table_name];
        }
        $sql     = 'SHOW COLUMNS FROM `' . $database_name . '`.`' . $table_name . '`';
        $columns = $this->db_handler->query($sql);

        if (!$columns) {
            return null;
        }
        $columns = array_column($columns, "Field");
        $this->caches[$database_name][$table_name] = $columns;
        $this->start_time = time();
        return $columns;
    }


}