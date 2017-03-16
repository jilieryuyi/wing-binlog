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
            "/service/all" => "\\Seals\\Web\\Logic\\Service::getAll"
        ]
    ];

    public function __construct(HttpResponse $response, $resource)
    {
        $this->response = $response;
        $this->resource = $resource;
    }
    public function parse()
    {
        if (strpos($this->resource,"/service") !== 0)
            return "404 not found";

        if (isset(self::$routes[$this->response->getMethod()][$this->resource]) && is_callable(self::$routes[$this->response->getMethod()][$this->resource])) {
            $data = call_user_func_array(self::$routes[$this->response->getMethod()][$this->resource],[$this->response]);

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
     * the url must start with /service
     *
     * @param string $url
     * @param \Closure $callback
     */
    public function get($url, $callback){
        self::$routes["get"][$url] = $callback;
    }

    /**
     * the url must start with /service
     *
     * @param string $url
     * @param \Closure $callback
     */
    public function post($url, $callback){
        self::$routes["post"][$url] = $callback;
    }
}