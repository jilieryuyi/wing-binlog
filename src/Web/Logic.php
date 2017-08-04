<?php namespace Seals\Web;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/16
 * Time: 16:50
 */
class Logic
{
    protected $response;
    public function __construct(HttpResponse $response)
    {
        $this->response = $response;
    }
}