<<<<<<< HEAD
<?php namespace Wing\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:54
 */
interface ISubscribe
{
    public function onchange($database_name, $table_name, $event);
=======
<?php namespace Wing\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:54
 */
interface ISubscribe
{
    /**
     * @param array $config
     */
    public function __construct($config);
    /**
     * @param array $config
     */
    public function onchange($event);
>>>>>>> 6ee3cbd6544d951ff92c5114316e3e698587ea1a
}