<?php namespace Seals\Library;


/**
 * @author yuyi
 * @created 2016/9/23 8:38
 * @email 297341015@qq.com
 * @property \Redis $redis
 */
class Queue implements QueueInterface
{

    private $queue_name;
    private $redis;

    public function __construct($queue_name, RedisInterface $redis)
    {
        $this->queue_name = $queue_name;
        $this->redis      = $redis;
    }

    public function getQueueName()
    {
        return $this->queue_name;
    }

    public function getAll()
    {
        return $this->redis->lRange($this->queue_name, 0, -1);
    }

    /**
     * @加入到队列
     *
     * @param mixed $data 事件数据
     * @return bool
     */
    public function push($data)
    {
        if (is_array($data))
            $data = json_encode($data);
        return $this->redis->rPush($this->queue_name, $data);
    }
    /**
     * @弹出队列首部数据
     *
     * @return mixed
     */
    public function pop()
    {

        $data = $this->redis->lPop($this->queue_name);

        if ($data === false)
            return null;

        $arr = @json_decode($data,true);
        if (is_array($arr)) {
            return $arr;
        }

        return $data;
    }

    /**
     * @只返回队首部元素 不弹出 不阻塞
     *
     * @return array
     */
    public function peek()
    {
        $data =  $this->redis->lRange( $this->queue_name, 0, 1);
        if (isset($data[0])) {
            $res = @json_decode($data[0],true);
            if (is_array($res))
                return $res;
            return $data[0];
        }
        return null;
    }

    /**
     * @返回消息队列长度
     *
     * @return int
     */
    public function length()
    {
        return $this->redis->lLen($this->queue_name);
    }

    /**
     * @清空队列
     *
     * @return bool
     */
    public function clear()
    {
        return !!$this->redis->del($this->queue_name);
    }
}