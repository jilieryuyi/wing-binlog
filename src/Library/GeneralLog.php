<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/17
 * Time: 13:04
 */
class GeneralLog
{
    protected $pdo;
    public $last_time = 0;
    public function __construct(DbInterface $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open()
    {
        $sql = 'set @@global.general_log=1';
        return $this->pdo->query($sql);
    }

    public function close()
    {
        $sql = 'set @@global.general_log=0';
        return $this->pdo->query($sql);
    }
    public function getLogPath()
    {
        $sql  = 'select @@general_log_file';
        $data = $this->pdo->row($sql);

        if (!isset($data["@@general_log_file"]))
            return null;

        return $data["@@general_log_file"];
    }
    public function isOpen()
    {
        $sql = 'select @@general_log';
        $data = $this->pdo->row($sql);

        return isset($data["@@general_log"]) && $data["@@general_log"] == 1;
    }

    public function logOutput()
    {
        $sql   = 'select @@log_output';
        $row   = $this->pdo->row($sql);
        $type1 = $row["@@log_output"];
        if (strpos($row["@@log_output"],",") !== false) {
            list($type1, $type2) = explode(",", $row["@@log_output"]);
        }
        return strtolower($type1);
    }

    public function query($last_time = 0, $limit = 10000)
    {
        if ($last_time <= 0)
            $last_time = date("Y-m-d 00:00:00.000000");
        $sql  = 'select * from mysql.general_log where 
event_time >= "'.$last_time.'" limit '.$limit;
        $data = $this->pdo->query($sql);

        if (!$data)
            return null;
        $this->last_time = $data[count($data)-1]["event_time"];

        return $data;
    }

//    public function onQuery($callback)
//    {
//
//        while (!$this->isOpen()){
//            sleep(1);
//        }
//        $type = $this->logOutput();
//
//        echo $type,"\r\n";
//        if ($type == "table") {
//            while (1) {
//                ob_start();
//                try {
//                    if ($this->logOutput() != "table") {
//                        echo "切换格式 table\r\n";
//                        exit;
//                    }
//                    do {
//                        $data = $this->query($this->last_time);
//                        if (!$data) break;
//
//                        foreach ($data as $row) {
//                            echo $row["argument"],"\r\n";
//                            $callback(strtotime($row["event_time"]), $row["command_type"]);
//                        }
//
//                    } while(0);
//                } catch (\Exception $e) {
//
//                }
//                usleep(100000);
//                $content = ob_get_contents();
//                ob_end_clean();
//                echo $content;
//            }
//        }
//
//        elseif ($type == "file") {
//
//            define("MAX_SHOW", 102400);
//
//            $file_size = 0;
//            $file_size_new = 0;
//            $add_size = 0;
//            $ignore_size = 0;
//            $file_name = $this->getLogPath();
//            $fp = fopen($file_name, "r");
//            while (1) {
//                try {
//                    ob_start();
//
//                    if ($this->logOutput() != "file") {
//                        echo "切换格式 file\r\n";
//                        exit;
//                    }
//
//                    clearstatcache();
//                    $file_size_new = filesize($file_name);
//                    $add_size = $file_size_new - $file_size;
//                    if ($add_size > 0) {
//                        if ($add_size > MAX_SHOW) {
//                            $ignore_size = $add_size - MAX_SHOW;
//                            $add_size = MAX_SHOW;
//                            fseek($fp, $file_size + $ignore_size);
//                        }
//
//                        $new_lines = fread($fp, $add_size);
//
//
//                        $lines = explode("\n", $new_lines);
//                        foreach ($lines as $line) {
//                            $temp = preg_split("/[\s]+/", $line);
//                            $datetime = strtotime($temp[0]);
//
//                            if ($datetime <= 0)
//                                continue;
//
//                            $event_type = trim($temp[2]);
//                            if ($event_type == "Init")
//                                $event_type = "Init DB";
//                            $callback($datetime, $event_type);
//                        }
//
//                        $file_size = $file_size_new;
//                    }
//                    $content = ob_get_contents();
//                    usleep(100000);
//                    ob_end_clean();
//                    echo $content;
//                } catch (\Exception $e) {
//
//                }
//            }
//
//            fclose($fp);
//        }
//
//    }
}