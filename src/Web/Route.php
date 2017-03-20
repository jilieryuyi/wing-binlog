<?php namespace Seals\Web;
use Seals\Web\Logic\Service;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/16
 * Time: 15:28
 */
class Route
{

    protected $response;
    protected $resource;

    static $routes = [
        "post" => [
            "/service/node/refresh" => "\\Seals\\Web\\Logic\\Node::info",
            "/service/all"          => "\\Seals\\Web\\Logic\\Service::getAll",
            "/service/node/restart" => "\\Seals\\Web\\Logic\\Node::restart",
            "/service/node/update"  => "\\Seals\\Web\\Logic\\Node::update",
            "/service/node/offline" => "\\Seals\\Web\\Logic\\Node::offline",
            "/service/node/runtime/config/save" => "\\Seals\\Web\\Logic\\Node::setRuntimeConfig",
            "/service/node/notify/config/save"  => "\\Seals\\Web\\Logic\\Node::setNotifyConfig",
        ]
    ];

    public function __construct(HttpResponse $response, $resource)
    {
        $this->response = $response;
        $this->resource = $resource;
    }
    public function parse()
    {
        echo $this->response->getMethod(),"\r\n";
        echo $this->resource,"\r\n";

        if (isset(self::$routes[$this->response->getMethod()][$this->resource])) {

            if(is_callable(self::$routes[$this->response->getMethod()][$this->resource]))
                $data = call_user_func_array(self::$routes[$this->response->getMethod()][$this->resource],[$this->response]);
            else
                $data = "";
            if (is_array($data))
                $data = json_encode($data);

            if ($data === false)
                $data = 0;

            if ($data === true)
                $data = 1;

            if (!is_scalar($data))
                return "";

            return $data;
        }

        return "404 not found";
    }

    /**
     * register get route
     * the url must start with /service
     *
     * @param string $url
     * @param \Closure $callback
     */
    public function get($url, $callback){
        self::$routes["get"][$url] = $callback;
    }

    /**
     * register post route
     * the url must start with /service
     *
     * @param string $url
     * @param \Closure $callback
     */
    public function post($url, $callback){
        self::$routes["post"][$url] = $callback;
    }
}