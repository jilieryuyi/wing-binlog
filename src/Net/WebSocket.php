<?php namespace Wing\Net;
/**
 * @author yuyi
 * @created 2016/8/13 20:32
 * @email 297341015@qq.com
 */
class WebSocket extends Tcp
{
    const WEBSOCKET_ON_MESSAGE = "websocket_on_message";
    const WEBSOCKET_ON_CONNECT = "websocket_on_connect";
    const WEBSOCKET_ON_CLOSE   = "websocket_on_close";
    public function __construct($ip = "0.0.0.0", $port = 9998)
    {
        parent::__construct($ip, $port);
        $this->on(self::ON_RECEIVE,[$this, "onMessage"]);
        $this->on(self::ON_CONNECT, [$this, "onConnect"]);
        $this->on(self::ON_CLOSE, [$this, "onClose"]);
        $this->on(self::ON_ERROR, [$this, "onClose"]);
    }


    public function onMessage($client, $buffer, $recv_msg)
    {
        if (0 === strpos($recv_msg, 'GET')) {
            $this->handshake($buffer, $recv_msg, $client);//, $recv_msg), $client );
            return;
        }

        $_client = new WebSocketClient($this,$client, $buffer);
        $this->call(self::WEBSOCKET_ON_MESSAGE, [$_client, $recv_msg]);
    }

    public function onConnect($client, $buffer)
    {
        $_client = new WebSocketClient($this,$client, $buffer);
        $this->call(self::WEBSOCKET_ON_CONNECT, [$_client]);
    }

    public function onClose($client, $buffer, $error=null)
    {
        $_client = new WebSocketClient($this,$client, $buffer);
        $this->call(self::WEBSOCKET_ON_CLOSE, [$_client]);
    }

    /**
     * @获取websocket握手消息
     */
    public function handshake($buffer, $recv_msg, $client ){

        $heder_end_pos = strpos($recv_msg, "\r\n\r\n");

        if ( !$heder_end_pos ) {
            return '';
        }

        $Sec_WebSocket_Key = '';
        if ( preg_match("/Sec-WebSocket-Key: *(.*?)\r\n/i", $recv_msg, $match) ) {
             $Sec_WebSocket_Key = $match[1];
        }

        $new_key            = base64_encode(sha1($Sec_WebSocket_Key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        $handshake_message  = "HTTP/1.1 101 Switching Protocols\r\n";
        $handshake_message .= "Upgrade: websocket\r\n";
        $handshake_message .= "Sec-WebSocket-Version: 13\r\n";
        $handshake_message .= "Connection: Upgrade\r\n";
        $handshake_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";

        return $this->send($buffer, $handshake_message, $client);
    }

    /**
     * @消息编码
     */
    public static function encode( $buffer )
    {
        $len = strlen($buffer);
        $first_byte = "\x81";

        if ($len <= 125) {
            $encode_buffer = $first_byte . chr($len) . $buffer;
        } else {
            if ($len <= 65535) {
                $encode_buffer = $first_byte . chr(126) . pack("n", $len) . $buffer;
            } else {
                $encode_buffer = $first_byte . chr(127) . pack("xxxxN", $len) . $buffer;
            }
        }

        return $encode_buffer;
    }

    /**
     * @消息解码
     */
    public static function decode($buffer)
    {
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data  = substr($buffer, 8);
        } else {
            if ($len === 127) {
                $masks = substr($buffer, 10, 4);
                $data  = substr($buffer, 14);
            } else {
                $masks = substr($buffer, 2, 4);
                $data  = substr($buffer, 6);
            }
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }

        return $decoded;

    }

    public function send($buffer, $data, $client)
    {
        $data = self::encode($data);
        if ($buffer) {
            $success = event_buffer_write($buffer,$data);
        }
        else{
            $success = $this->sendSocket($client, $data);
        }
        if (!$success) {
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