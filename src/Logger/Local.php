<?php namespace Seals\Logger;
use Psr\Log\LoggerInterface;
use Wing\FileSystem\WDir;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/11
 * Time: 08:59
 * 本地日志存储实现
 */
class Local implements LoggerInterface {

    private $log_dir;
    private $levels;

    public function __construct($log_dir, array $levels)
    {
        $dir = str_replace("\\","/",$log_dir);
        $dir = rtrim($dir,"/");
        $this->log_dir = $dir;
        $dir = new WDir($this->log_dir);
        $dir->mkdir();
        unset($dir);

        $this->levels = $levels;
    }

    private function write($name, $message, array $context)
    {
        //如果是非定义记录的级别 不采取任何操作
        if (!in_array($name,$this->levels)) {
            echo $name," is not defined level\r\n";
            return;
        }
        if ($message || $context) {
            file_put_contents(
                $this->log_dir . "/" . $name . "_" . date("Ymd") . ".log",
                date("Y-m-d H:i:s") . "\r\n" .
                $message . "\r\n" .
                json_encode($context, JSON_UNESCAPED_UNICODE) . "\r\n\r\n",
                FILE_APPEND
            );
        }
    }


    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $this->write($level, $message, $context);
    }
}
