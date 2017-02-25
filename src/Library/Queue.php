<?php namespace Seals\Library;


/**
 * @author yuyi
 * @created 2016/9/23 8:38
 * @email 297341015@qq.com
 *
 * @property \Redis $redis
 * @可靠的redis消息队列实现
 * @先进先出队列 使用redis list
 * @可以通过 XSL::instance()->queue 访问，如 XSL::instance()->queue->pop()
 */
class Queue{

    private $queue_name;

    public function __construct( $queue_name )
    {
        $this->queue_name = $queue_name;
    }

    public function getQueueName(){
        return $this->queue_name;
    }

    /**
     * @加入到队列
     *
     * @param $event_id string 事件id标示
     * @param $data array 事件依附的数据
     * @return bool
     */
    public function push( $data ){
        if( is_array($data) )
            $data = json_encode($data);
        return Context::instance()->redis->rPush( $this->queue_name, $data );
    }
    /**
     * @弹出队列首部数据
     *
     * @return array
     */
    public function pop(){

        $data = Context::instance()->redis->lPop( $this->queue_name );

        if( $data === false )
            return null;

        $arr = @json_decode($data,true);
        if( is_array($arr) ){
            return $arr;
        }

        return $data;
    }

    /**
     * @只返回队首部元素 不弹出 不阻塞
     *
     * @return array
     */
    public function peek(){
        $data =  Context::instance()->redis->lRange(  $this->queue_name, 0, 1 );
        if( isset($data[0]) )
            return $data[0];
        return null;
    }

    /**
     * @返回消息队列长度
     *
     * @return int
     */
    public function length(){
        return Context::instance()->redis->lLen( $this->queue_name );
    }

    /**
     * @清空队列
     *
     * @return bool
     */
    public function clear(){
        return !!Context::instance()->redis->del( $this->queue_name );
    }
}