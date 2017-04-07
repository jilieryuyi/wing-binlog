<?php namespace Seals\Library;
use Wing\FileSystem\WFile;


/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/4/7
 * Time: 12:15
 */
class Lang
{
    static $ltypes = ["zh","en"];
    static $lang = [
        ["en" => "Welcome",  "zh" => "欢迎"],
        ["en" => "General",  "zh" => "日常"],
        ["en" => "Home",     "zh" => "首页"],
        ["en" => "Servers",  "zh" => "服务器列表"],
        ["en" => "Users",    "zh" => "用户"],
        ["en" => "Roles",    "zh" => "角色"],
        ["en" => "Other",    "zh" => "其他"],
        ["en" => "Logs",     "zh" => "日志"],
        ["en" => "Doing",    "zh" => "正在操作"],
        ["en" => "Success",  "zh" => "成功"],
        ["en" => "Help",     "zh" => "帮助"],
        ["en" => "Total Servers", "zh" => "服务器数量"],
        ["en" => "Total Events", "zh" => "事件数量"],
        ["en" => "Total Logs", "zh" => "日志数量"],
        ["en" => "From last Day", "zh" => "相比昨天"],
        ["en" => "Groups And Servers", "zh" => "服务器列表"],
        ["en" => "manager", "zh" => "管理"],
        ["en" => "Restart Master Process", "zh" => "重启Master进程"],
        ["en" => "Update Master", "zh" => "更新Master"],
        ["en" => "Group", "zh" => "群组"],
        ["en" => "Nodes Count", "zh" => "节点数量"],
        ["en" => "Operate", "zh" => "操作"],
        ["en" => "Report", "zh" => "报表"],
        ["en" => "Server Detail", "zh" => "服务器详情"],
        ["en" => "Logs", "zh" => "日志"],
        ["en" => "Restart", "zh" => "重启"],
        ["en" => "Update", "zh" => "更新"],
        ["en" => "Configure", "zh" => "配置"],
        ["en" => "Node", "zh" => "节点"],
        ["en" => "Status", "zh" => "状态"],
        ["en" => "Leader", "zh" => "Leader"],
        ["en" => "Last Read", "zh" => "读取到"],
        ["en" => "Version", "zh" => "版本号"],
        ["en" => "Start Time", "zh" => "开始时间"],
        ["en" => "Running Time", "zh" => "运行时长"],
        ["en" => "Offline", "zh" => "离线"],
        ["en" => "Online", "zh" => "在线"],
        ["en" => "Users manager", "zh" => "用户管理"],
        ["en" => "Index", "zh" => "序号"],
        ["en" => "User Name", "zh" => "用户姓名"],
        ["en" => "Role", "zh" => "角色"],
        ["en" => "Created", "zh" => "创建时间"],
        ["en" => "Login Times", "zh" => "登陆次数"],
        ["en" => "Last Login", "zh" => "上次登录"],
        ["en" => "Edit", "zh" => "编辑"],
        ["en" => "Delete", "zh" => "删除"],
        ["en" => "Add", "zh" => "添加"],
        ["en" => "Roles manager", "zh" => "角色管理"],
        ["en" => "Detail", "zh" => "详情"],
        ["en" => "Powers", "zh" => "权限"],
        ["en" => "Level", "zh" => "级别"],
        ["en" => "Message","zh" => "内容"],
        ["en" => "Context", "zh" => "上下文"],
        ["en" => "Time", "zh" => "时间"],
        ["en" => "All Level", "zh" => "所有级别"],
        ["en" => "Login Form", "zh" => "登录"],
        ["en" => "Login", "zh" => "登录"],
        ["en" => "Lost your password?", "zh" => "忘记密码？"],
        ["en" => "Process Runtime Configure", "zh" => "进程运行时配置"],
        ["en" => "Workers Num", "zh" => "进程数量"],
        ["en" => "Debug", "zh" => "调试模式"],
        ["en" => "Update Configure", "zh" => "保存配置"],
        ["en" => "Node Configure", "zh" => "节点配置"]

    ];

    public static function parse()
    {
        $path[] = __APP_DIR__.'/web/*';
        $files  = [];
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_dir($item)) {
                    if(strpos($item,"/lang") !== false //||
                      //  strpos($item,"/vendors") !== false
                    ) {
                        //echo $item,"\r\n";
                        continue;
                    }
                    $path[] = $item . '/*';
                } elseif (is_file($item)) {
                    $info = pathinfo($item);
                       // continue;
                    //$files[] = $item;
                    $content = $en_content = $zh_content = file_get_contents($item);
                    if (!isset($info['extension']))
                        $info['extension'] = "";
                    if (in_array($info['extension'],["php","js","html"])) {
                        preg_match_all("/__LANG\([\s\S]{1,}?\)/", $content, $matches);
                        if (count($matches[0]) > 0) {
                            echo $item, "\r\n";
                            var_dump($matches[0]);
                            foreach ($matches[0] as $_lang) {



                                $lang = substr($_lang,7,strlen($_lang)-8);//ltrim($_lang, "__LANG(");
                                $en   = $lang;
                                $zh   = $lang;

                                foreach (self::$lang as $_l) {
                                    if ($_l["en"] == $lang ||
                                        $_l["zh"] == $lang
                                    ) {
                                        $en = $_l["en"];
                                        $zh = $_l["zh"];
                                        break;
                                    }
                                }

//                                echo $en,$zh,"\r\n";
//                                if (strpos($_lang,"Logs") !== false)
//                                    exit;
                                $en_content = str_replace($_lang, $en, $en_content);
                                $zh_content = str_replace($_lang, $zh, $zh_content);
                            }
                        }
                    }

                    $target_file = __APP_DIR__."/web/lang/en".str_replace(__APP_DIR__."/web","",$item);
                    $wfile = new WFile($target_file);
                    $wfile->touch();

                    file_put_contents($target_file, $en_content);

                    $target_file = __APP_DIR__."/web/lang/zh".str_replace(__APP_DIR__."/web","",$item);
                    $wfile = new WFile($target_file);
                    $wfile->touch();
                    file_put_contents($target_file, $zh_content);
                    echo "编译完成：";
                    echo $target_file,"\r\n";
                }
            }
        }
        //return $files;

    }
}