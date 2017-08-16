<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/16
 * Time: 21:47
 */
interface C
{
    public function a();
}

class A implements C
{
    public function a(){

    }
}

class Test
{
    private $inc;
    public function setC(C $inc)
    {
        $this->inc = $inc;
    }
    public function t()
    {
        $this->inc->a();
    }
}

$a = new A;
$t = new Test;
$t->setC($a);
$t->t();