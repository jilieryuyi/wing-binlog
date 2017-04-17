<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/17
 * Time: 13:10
 */
include __DIR__."/../vendor/autoload.php";

define("__APP_DIR__", dirname(__DIR__));
\Seals\Library\Context::instance()->initPdo();
//$a = \Seals\Library\Context::instance()->activity_pdo->query("set @@global.general_log=1");
//var_dump($a);

//$g = new \Seals\Library\GeneralLog(\Seals\Library\Context::instance()->activity_pdo);

//var_dump($g->getLogPath());
//var_dump($g->isOpen());
//var_dump($g->open());
//var_dump($g->isOpen());

//$data = \Seals\Library\Context::instance()->activity_pdo->getDatabases();
//var_dump($data);

while (1) {

    $sql = 'INSERT INTO new_yonglibao_c.`bl_provinces`( `provinces_name`) SELECT  `provinces_name` FROM new_yonglibao_c.`bl_provinces` WHERE 1';
    echo $sql,"\r\n";
    \Seals\Library\Context::instance()->activity_pdo->query($sql);
//    $sql = 'INSERT INTO new_yonglibao_c.`bl_provinces`( `provinces_name`) SELECT  `provinces_name` FROM new_yonglibao_c.`bl_provinces` WHERE 1';
//    echo $sql,"\r\n";
//    \Seals\Library\Context::instance()->activity_pdo->query($sql);
//    $sql = 'INSERT INTO new_yonglibao_c.`bl_provinces`( `provinces_name`) SELECT  `provinces_name` FROM new_yonglibao_c.`bl_provinces` WHERE 1';
//    echo $sql,"\r\n";
//    \Seals\Library\Context::instance()->activity_pdo->query($sql);
//    $sql = 'INSERT INTO new_yonglibao_c.`bl_provinces`( `provinces_name`) SELECT  `provinces_name` FROM new_yonglibao_c.`bl_provinces` WHERE 1';
//    echo $sql,"\r\n";
//    \Seals\Library\Context::instance()->activity_pdo->query($sql);

    $sql = 'UPDATE new_yonglibao_c.`bl_provinces` SET `provinces_name`="121212" WHERE 1';
    echo $sql,"\r\n";
    \Seals\Library\Context::instance()->activity_pdo->query($sql);

    $sql = 'UPDATE new_yonglibao_c.`bl_provinces` SET `provinces_name`=concat(`provinces_name`,`id`) WHERE 1';
    echo $sql,"\r\n";
    \Seals\Library\Context::instance()->activity_pdo->query($sql);
    $sql = 'delete from new_yonglibao_c.`bl_provinces` where id>105';
    echo $sql,"\r\n";
    \Seals\Library\Context::instance()->activity_pdo->query($sql);

    sleep(10);
}