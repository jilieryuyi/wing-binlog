<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:31
 * subscribe
 */
class EventPublish implements Event{

    private $database_name;
    private $table_name;
    private $event_data;

    const EVENT_LIST = "seals:event:list";

    public function __construct( $database_name, $table, array $data ){
        $this->database_name = $database_name;
        $this->table_name = $table;
        $this->event_data = $data;
    }

    /**
     * @事件触发
     *
     * @return bool
     */
    public function trigger(){
        $queue = new Queue( self::EVENT_LIST, Context::instance()->redis );
        $success = $queue->push([
            "database_name" => $this->database_name,
            "table_name"    => $this->table_name,
            "event_data"    => $this->event_data
        ]);

        if( !$success ){
            echo "写redis失败\r\n";
        }

        return $success;
    }
}