<?php namespace Wing\Binlog\Notify;
use Wing\Binlog\Library\Notify;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/18
 * Time: 10:17
 */
class Http implements Notify {

    private $url;
    private $data;

    public function __construct( $url , $data = null )
    {
        $this->url  = $url;
        $this->data = $data;
    }

    public function send($database_name, $table_name, array $event_data)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "wing-binlog");

        if( strpos($this->url,"https://") === 0 ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "database_name" => $database_name,
            "table_name"    => $table_name,
            "event_data"    => json_encode( $event_data )
        ]);

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}