<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/21
 * Time: 16:23
 */
include __DIR__."/Tcp.php";

$tcp = new Tcp("127.0.0.1", 6998);

$tcp->on(Tcp::ON_RECEIVE, function($client, $buffer, $data){
	$data = str_replace("\n","\r\n", $data);
	var_dump($client, $buffer, $data);
});

$tcp->start();