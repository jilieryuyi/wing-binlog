<?php namespace Wing\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 06:21
 */
abstract class BaseWorker
{
    protected $workers = 1;
    protected $task    = [];
    const USLEEP       = 1000;
    abstract public function start($daemon = false);
    /**
     * @return string
     */
    protected function getWorker($base_name)
    {
        //dispatch_process
        $target_worker = $base_name."_1";

        if ($this->workers <= 1) {
            $this->task[1] = $this->task[1] + 1;
            if ($this->task[1] > 999999990) {
                $this->task[1] = 0;
            }
            return $target_worker;
        }

        //如果没有空闲的进程 然后判断待处理的队列长度 那个待处理的任务少 就派发给那个进程
        $target_len   = $this->task[1];
        $target_index = 1;

        for ($i = 2; $i <= $this->workers; $i++) {

            if ($this->task[$i] > 999999990) {
                $this->task[$i] = 0;
            }

            $_target_worker = $base_name."_" . $i;
            $len            = $this->task[$i];

            if ($len < $target_len) {
                $target_worker  = $_target_worker;
                $target_len     = $len;
                $target_index   = $i;
            }

        }

        $this->task[$target_index] = $this->task[$target_index] + 1;

        return $target_worker;
    }
}