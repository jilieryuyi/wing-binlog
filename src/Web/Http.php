<?php namespace Seals\Web;
use Seals\Library\Context;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 11:55
 *
 * http server
 *
 */
class Http extends Tcp
{
    const ON_HTTP_RECEIVE = "on_http_msg";

    protected $http_host;
    protected $http_port;
    protected $home;

    /**
     * construct
     *
     * @param string $home web home path
     * @param string $ip
     * @param int $port
     */
    public function __construct($home, $ip = "0.0.0.0", $port = 9998)
    {
        parent::__construct($ip, $port);
        $this->on(self::ON_WRITE,   [$this, "_onWrite"]);
        $this->on(self::ON_RECEIVE, [$this, "_onReceive"]);
        $this->home = $home;
    }

    /**
     * http send msg callback
     */
    protected function _onWrite($client, $buffer)
    {
        echo "http on write\r\n";
        var_dump($client, $buffer);
        $i = array_search($client, $this->clients);
        echo "send ok free\r\n";
        fclose($client);
        if ($buffer) {
            echo "free buffer\r\n";
            event_buffer_free($buffer);
            unset($this->buffers[$i]);
        }
        unset($this->clients[$i]);

    }

    /**
     * http on receive
     *
     * @param resource $client
     * @param resource $buffer
     * @param int $id
     * @param string $data
     */
    protected function _onReceive($client, $buffer, $data)
    {
        $this->call(self::ON_HTTP_RECEIVE, [new HttpResponse($this, $this->home, $buffer, $data, $client)]);
        $this->debug();
    }

    /**
     * http send msg
     */
    public function send($buffer, $data, $client)
    {
        if ($buffer) {
            echo "------event_buffer_write\r\n";
            $success = event_buffer_write($buffer,$data);
        }
        else
            $success = $this->sendSocket($client, $data);
        if (!$success) {
            echo "send fail =======\r\n";
            $this->send_fail_times++;
            $i = array_search($client, $this->clients);
            fclose($client);
            if ($buffer) {
                event_buffer_free($buffer);
                unset($this->buffers[$i]);
            }
            unset($this->clients[$i]);
        }
        return $success;
    }
}