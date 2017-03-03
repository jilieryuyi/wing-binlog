<?php namespace Seals\Notify;
use Seals\Library\Notify;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/18
 * Time: 10:17
 *
 * http协议事件通知的实现
 *
 */
class Http implements Notify
{

    private $url;
    private $data;

    /**
     * 构造函数
     *
     * @param string $url
     * @param mixed $data
     */
    public function __construct( $url , $data = "" )
    {
        $this->url  = $url;
        $this->data = $data;
    }

    /**
     * 发送数据
     *
     * @param array $event_data
     * @return string
     */
    public function send(array $event_data)
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

        $self_data = $this->data;
        if( is_array($this->data) ) {
            $self_data = json_encode( $this->data );
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "event_data"    => json_encode( $event_data ),
            "data"          => $self_data //自定义部分的数据
        ]);

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}