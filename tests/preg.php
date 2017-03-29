<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/29
 * Time: 18:05
 */
$str = '1232435243<div class="insert-wrap"><iframe class="orderTicket" src="https://v.qq.com/iframe/player.html?vid=g0378faiwpm&amp;tiny=0&amp;auto=0&quot;frameborder=&quot;0&quot;" width="100%" height="400px"></iframe><span class="insert-title">视频描述</span></div>34252345234';
preg_match_all("/\<div[\s]+class=\"insert-wrap\"\>\<iframe[\s\S]{1,}?\<\/div\>/", $str, $m);
var_dump($m);