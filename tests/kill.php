<?php
/**
 * kill.php
 * User: huangxiaoan
 * Created: 2017/8/4 17:28
 * Email: huangxiaoan@xunlei.com
 */

exec("ps aux|grep wing|grep -v grep|cut -c 9-15|xargs kill -9");