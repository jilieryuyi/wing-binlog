<?php namespace Seals\Web;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 12:55
 */
class HttpResponse
{
    protected $method;
    protected $host = "";
    protected $port = 80;
    protected $resource;
    protected $http_protocol;
    protected $buffer;
    protected $home;
    protected $http;
    protected $id;
    protected $client;

    protected $get     = [];
    protected $post    = [];
    protected $headers = [];

    public function __construct(Http $http,$home, $buffer, $data, $client, $id)
    {
        $this->buffer = $buffer;
        $this->home   = $home;
        $this->http   = $http;
        $this->id     = $id;
        $this->client = $client;

        $temp    = explode("\r\n\r\n", $data, 2);
        $headers = isset($temp[0])?$temp[0]:"";
        $content = isset($temp[1])?$temp[1]:"";
        unset($temp);

        $headers = explode("\r\n", $headers);
        $line1   = array_shift($headers);
        $temp    = explode(" ", $line1);
        unset($line1);

        $this->method        = isset($temp[0])?$temp[0]:"unknown";
        $resource            = isset($temp[1])?$temp[1]:"";
        $this->http_protocol = isset($temp[2])?$temp[2]:"unknown";
        unset($temp);

        foreach ($headers as $header) {
            $temp   = explode(":",$header,2);
            $key    = isset($temp[0])?$temp[0]:"";
            $value  = isset($temp[1])?$temp[1]:"";
            unset($temp);
            $this->headers[trim(strtolower($key))] = trim($value);
        }

        if (isset($headers[0])) {
            $temp = explode(":",$headers[0]);
            $this->host = isset($temp[1])?trim($temp[1]):"";
            $this->port = isset($temp[2])?trim($temp[2]):80;
            unset($temp);
        }
        unset($headers);

        if (!$this->port) {
            $this->port = 80;
        }

        $arr = parse_url($resource);
        $this->resource = isset($arr["path"])?$arr["path"]:"";

        //get参数解析
        if (isset($arr["query"])) {
            $query  = $arr["query"];
            $querys = preg_split("/\&+/", $query);
            unset($query);

            foreach ($querys as $query) {
                $query = trim($query);
                list($key, $value) = explode("=", $query);
                unset($query);
                $this->get[$key] = $value;
            }
            unset($querys);
        }
        unset($arr);

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
                unset($temp, $key, $query);
            }
            unset($querys);
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

    public function response()
    {
        $response  = "404 not fund";
        $resource  = $this->getResource();

        if (!$resource || $resource == "/")
            $resource = "/index.php";

        $mime_type = "text/html";

        $_GET     = $this->getAll();
        $_POST    = $this->postAll();
        $_REQUEST = array($_GET,$_POST);

        if (file_exists($this->home.$resource)) {
            $mime_type = MimeType::getMimeType($this->home . $resource);
            if (in_array($mime_type, ["text/x-php", "text/html"])) {
                ob_start();
                include $this->home . $resource;
                $response = ob_get_contents();
                ob_end_clean();
            } else {
                $response = file_get_contents($this->home . $resource);
            }
        }
        unset($_GET,$_POST,$_REQUEST);

        //输出http headers
        $headers            = [
            "HTTP/1.1 200 OK",
            "Connection: Close",
            "Server: wing-binlog-http",
            "Date: " . gmdate("D,d M Y H:m:s")." GMT",
            "Content-Type: ".$mime_type,
            "Content-Length: " . strlen($response)
        ];

        return $this->http->send($this->buffer, implode("\r\n",$headers)."\r\n\r\n".$response, $this->client, $this->id);
    }
}