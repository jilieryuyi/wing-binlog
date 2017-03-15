<?php namespace Seals\Web;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 11:55
 */
class Http extends Tcp
{
    const ON_HTTP_RECEIVE = "on_http_msg";

    protected $http_host;
    protected $http_port;
    protected $home;

    public function __construct($home, $ip = "0.0.0.0", $port = 9998)
    {
        parent::__construct($ip, $port);
        $this->on(self::ON_WRITE,   [$this, "onWrite"]);
        $this->on(self::ON_RECEIVE, [$this, "onReceive"]);
        $this->home = $home;
    }

    //http协议在发送后要关闭连接
    public function onWrite($client, $buffer, $id)
    {
        echo "send ok free\r\n";
        fclose($client);
        event_buffer_free($buffer);
        unset($this->clients[$id]);
        unset($this->buffers[$id]);
        $this->index--;
    }

    public function onReceive($client, $buffer, $id, $data)
    {
        $this->call(self::ON_HTTP_RECEIVE, [new HttpResponse($this, $this->home, $buffer, $data, $client, $id)]);
        $this->debug();
    }

    public function send($buffer, $data, $client, $id)
    {
        $success = event_buffer_write($buffer,$data);
        if (!$success) {
            $this->send_fail_times++;
            fclose($client);
            event_buffer_free($buffer);
            unset($this->clients[$id]);
            unset($this->buffers[$id]);
            $this->index--;
        }
        return $success;
    }
}