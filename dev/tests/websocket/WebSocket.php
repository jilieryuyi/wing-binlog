<?php
/**
 * @author yuyi
 * @created 2016/8/13 20:32
 * @email 297341015@qq.com
 */
class WebSocket{
    /**
     * @获取websocket握手消息
     */
    public static function handshake( $recv_msg ){

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

        return $handshake_message;
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
}