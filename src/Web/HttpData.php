<?php namespace Seals\Web;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 12:55
 */
class HttpData
{
    protected $method;
    protected $host;
    protected $port;
    protected $resource;
    protected $http_protocol;

    protected $get     = [];
    protected $post    = [];
    protected $headers = [];

    public function __construct($data)
    {
        list($headers, $content) = explode("\r\n\r\n", $data, 2);

        $headers = explode("\r\n", $headers);
        $line1   = array_shift($headers);

        list($this->method, $resource, $this->http_protocol) = explode(" ", $line1);

        foreach ($headers as $header) {
            list($key, $value) = explode(":",$header,2);
            $this->headers[trim(strtolower($key))] = trim($value);
        }

        list(,$this->host,$this->port) = explode(":",$headers[0]);//$line2;
        $this->host = trim($this->host);
        if (!$this->port)
            $this->port = 80;

        $arr = parse_url($resource);
        $this->resource = $arr["path"];

        //get参数解析
        if (isset($arr["query"])) {
            $query  = $arr["query"];
            $querys = preg_split("/\&+/", $query);

            foreach ($querys as $query) {
                $query = trim($query);
                list($key, $value) = explode("=", $query);
                $this->get[$key] = $value;
            }
        }

        //post数据解析
        if ($content) {
            $querys = preg_split("/--------------------------[\S\s]{1,}?\n/", $content);
            foreach ($querys as $query) {

                if (!$query) {
                    continue;
                }

                $query  = trim($query);
                $temp   = explode("\r\n\r\n", $query);

                preg_match("/\"[\s\S]{1,}?\"/",$temp[0], $m);

                $key    = trim($m[0],"\"");
                $this->post[$key] = isset($temp[1])?$temp[1]:"";
            }
        }
    }

    public function get($key)
    {
        if (!isset($this->get[$key]))
            return null;
        return $this->get[$key];
    }

    public function getAll()
    {
        return $this->get;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function post($key)
    {
        if (!isset($this->post[$key]))
            return null;
        return $this->post[$key];
    }

    public function postAll()
    {
        return $this->post;
    }

    public function request($key)
    {
        $data = array_merge($this->get,$this->post);
        if (!isset($data[$key]))
            return null;
        return $data[$key];
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getProtocol()
    {
        return $this->http_protocol;
    }

    public function getHost()
    {
        return $this->host;
    }
    public function getPort()
    {
        return $this->port;
    }

    public function getHeader($key)
    {
        if (!isset($this->headers[$key]))
            return null;
        return $this->headers[$key];
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getCookie($_key)
    {
        if (!isset($this->headers["cookie"]))
            return null;

        $cookies = explode(";", $this->headers["cookie"]);
        foreach ($cookies as $cookie) {
            list($key,$value) = explode("=",$cookie);

            $key   = trim($key);
            $value = trim($value);

            if ($key == $_key)
                return $value;
        }
        return null;
    }

    public function getCookies()
    {
        if (!isset($this->headers["cookie"]))
            return null;

        $cookies = explode(";", $this->headers["cookie"]);
        $res     = [];

        foreach ($cookies as $cookie) {
            list($key,$value) = explode("=",$cookie);
            $key      = trim($key);
            $value    = trim($value);
            $res[$key]= $value;
        }
        return $res;
    }

    public function getAccepts()
    {
        if (!isset($this->headers["accept"]))
            return null;
        return explode(",",$this->headers["accept"]);
    }
}