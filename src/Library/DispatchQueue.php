<?php namespace Seals\Library;

/**
 * @author yuyi
 * @created 2016/11/22 6:24
 * @email 297341015@qq.com
 * @property Worker $worker
 */
class DispatchQueue implements Dispatch{
    private $worker;
    public function __construct( $worker )
    {
        $this->worker = $worker;
    }
    public function get( $data = null )
    {
        $queue_name    = $this->worker->getQueueName();
        $target_worker = $queue_name . "1";

        //那个工作队列的待处理任务最少 就派发给那个队列
        $num        = $this->worker->getWorkersNum();

        if( $num <= 1 )
        {
            return $target_worker;
        }

        $target_len = Context::instance()->redis_local->lLen($target_worker);


        for ($i = 2; $i <= $num; $i++) {
            $len = Context::instance()->redis_local->lLen($queue_name . $i);
            if ($len < $target_len) {
                $target_worker = $queue_name . $i;
                $target_len    = $len;
            }
        }
        return $target_worker;
    }
}