<?php namespace Wing\Library;

/**
 * 获取cpu数量，已测试linux和mac
 */

class Cpu{
    /**
     * @var int
     */
    public $cpu_num = 1;

    /**
     * @构造函数
     */
    public function __construct()
    {
        switch (PHP_OS) {
            case "Linux":
                $this->sysLinux();
                break;
            case "Darwin":
                exec("sysctl -n machdep.cpu.core_count",$output);
                $this->cpu_num = $output[0];
                /**
                echo -n "CPU型号:    "
                sysctl -n machdep.cpu.brand_string
                echo -n "CPU核心数:  "
                sysctl -n machdep.cpu.core_count
                echo -n "CPU线程数:  "
                sysctl -n machdep.cpu.thread_count
                echo "其它信息："
                system_profiler SPDisplaysDataType SPMemoryDataType SPStorageDataType | grep 'Graphics/Displays:\|Chipset Model:\|VRAM (Total):\|Resolution:\|Memory Slots:\|Size:\|Speed:\|Storage:\|Media Name:\|Medium Type:'
                 */
                break;
            default:
                break;
        }

        $this->cpu_num = intval($this->cpu_num);

        if ($this->cpu_num <= 0) {
        	$this->cpu_num = 1;
		}
    }

    /**
     * linux cpu数量解析获取
     */
    private function sysLinux()
    {
        if (false === ($str = @file("/proc/cpuinfo"))) {
            $this->cpu_num = 1;
            return;
        }

        $str = implode("", $str);
        @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s", $str, $model);

        if (false !== is_array($model[1])) {
            $this->cpu_num  = sizeof($model[1]);
            return;
        }

        $this->cpu_num = 1;
    }

}