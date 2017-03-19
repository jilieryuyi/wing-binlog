<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/18
 * Time: 05:17
 */
posix_kill(file_get_contents(dirname(__DIR__)."/server.pid"),SIGUSR2);