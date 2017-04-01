<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/4/1
 * Time: 09:20
 */
class System
{
    //ip
//    public static $ip = [];
//
//    public function __construct()
//    {
//        $this->getIp();
//    }

    public static function getIp()
    {
        $command = new \Seals\Library\Command("ifconfig");
        $res     = $command->run();
        preg_match_all("/[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}/",$res,$m);

        //preg_match_all("/[\d]{1,3}.[\d]{1,3}.[\d]{1,3}.[\d]{1,3}/",$res,$m);
        return $m[0];
    }

    /**
    ps aux输出格式：

    USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND
    格式说明：

    USER: 行程拥有者

    PID: pid

    %CPU: 占用的 CPU 使用率

    %MEM: 占用的记忆体使用率

    VSZ: 占用的虚拟记忆体大小

    RSS: 占用的记忆体大小

    TTY: 终端的次要装置号码 (minor device number of tty)

    STAT: 该行程的状态，linux的进程有5种状态：

    D 不可中断 uninterruptible sleep (usually IO)

    R 运行 runnable (on run queue)

    S 中断 sleeping

    T 停止 traced or stopped

    Z 僵死 a defunct (”zombie”) process

    注: 其它状态还包括W(无驻留页), <(高优先级进程), N(低优先级进程), L(内存锁页).

    START: 行程开始时间

    TIME: 执行的时间

    COMMAND:所执行的指令
     */
    public static function getProcessInfo($process_id)
    {
        $command = new Command("ps aux | grep ".$process_id);
        $res = $command->run();

        $temp = explode("\n", $res);//preg_split("/[\s]+/", $res, 10);
        $data = [];

        $status = [
            "D" => "不可中断 uninterruptible sleep (usually IO)",

            "R" => "运行 runnable (on run queue)",

    "S" =>  "中断 sleeping",

    "T" =>  "停止 traced or stopped",

    "Z" => "僵死 a defunct (”zombie”) process",

    "W" =>"无驻留页","<" =>"高优先级进程", "N" => "低优先级进程",
            "L"=>"内存锁页"

        ];

        foreach ($temp as $_item) {
            $item = preg_split("/[\s]+/", $_item, 11);

            $data[$item[1]] = [
                "user"       => $item[0],
                "process_id" => $item[1],
                "cpu"        => $item[2]."%",
                "memory"     => $item[3]."%",
                //"memory"  => $item[4]/1024,
                "status"     => $item[7]." ".$status[$item[7]],
                "start"      => $item[8],
                "time"       => $item[9],
                "command"    => $item[10]
            ];
        }

        var_dump($data);

        if (!isset($data[$process_id]))
            return null;

        return $data[$process_id];
    }

    public static function getMemory()
    {
        //echo -e "$(top -l 1 | awk '/PhysMem/';)"
        //free

        switch (PHP_OS) {
            case "Linux": {
                $command = new Command("free -m");
                $res = $command->run();
                $res = $command->run();
                echo $res;
                preg_match_all("/[\d]+/", $res, $m);
                var_dump($m);
                return [$m[0][0]."M", $m[0][1]."M"];
            } break;
            case "Darwin": {
                $command = new Command("echo -e \"$(top -l 1 | awk '/PhysMem/';)\"");
                $res = $command->run();
                echo $res;
                preg_match_all("/[\d]+/", $res, $m);
                var_dump($m);
                return [$m[0][0]."M", $m[0][1]."M"];
            } break;
        }

    }
}