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
        ["en" => "Node Configure", "zh" => "节点配置"],
        ["en" => "Enable", "zh" => "启用"],
        ["en" => "Notify Configure", "zh" => "事件通知配置"],
        ["en" => "Notify Mode", "zh" => "通知方式"],
        ["en" => "Param 1", "zh" => "参数1"],
        ["en" => "Param 2", "zh" => "参数2"],
        ["en" => "Rabbitmq Configure", "zh" => "Rabbitmq配置"],
        ["en" => "Host", "zh" => "Ip"],
        ["en" => "Port", "zh" => "端口"],
        ["en" => "Password", "zh" => "密码"],
        ["en" => "Database Configure", "zh" => "数据库配置"],
        ["en" => "User", "zh" => "用户"],
        ["en" => "Database", "zh" => "数据库"],
        ["en" => "Group Configure", "zh" => "群集配置"],
        ["en" => "Group ID", "zh" => "群ID"],
        ["en" => "you can use :null to set the password as null", "zh" => "可以使用:null将密码设置为null"],
        ["en" => "Local Redis Configure", "zh" => "本地redis配置"],
        ["en" => "Event Redis Configure", "zh" => "事件redis配置"],
        ["en" => "set the group configure will change all the nodes in the group, default show the group leader configure info",
            "zh" => "设置群组配置将更新群组下的所有节点的配置，默认显示的是leader的配置信息"],
        ["en" => "all the password are remove from the form, so you need to input the complete password for change configure",
            "zh" => "所有的密码都已经被从表单中移除，如果更新配置信息需要填写完整的密码"],
        ["en" => "Insert Rows", "zh" => "插入行数"],
        ["en" => "Today insert rows", "zh" => "今天插入行数"],
        ["en" => "Delete Rows", "zh" => "删除行数"],
        ["en" => "Today delete rows", "zh" => "今天删除行数"],
        ["en" => "Update Rows", "zh" => "更新行数"],
        ["en" => "Today update rows", "zh" => "今天更新行数"],
        ["en" => "Statistical report", "zh" => "统计报表"],
        ["en" => "Day", "zh" => "日期"],
        ["en" => "Hour", "zh" => "时间"],
        ["en" => " update rows", "zh" => "更新行数"],
        ["en" => " delete rows", "zh" => "删除行数"],
        ["en" => " insert rows", "zh" => "插入行数"],
        ["en" => "Node info", "zh" => "节点详情"],
        ["en" => "Process List", "zh" => "进程列表"],
        ["en" => "Total memory ", "zh" => "总内存"],
        ["en" => "usage ", "zh" => "已使用"],
        ["en" => "Cpu usage ", "zh" => "Cpu已使用"],
        ["en" => "Process ID", "zh" => "进程ID"],
        ["en" => "Memory Peak Usage", "zh" => "内存峰值"],
        ["en" => "Memory Usage", "zh" => "已使用内存"],
        ["en" => "User add", "zh" => "添加用户"],
        ["en" => "User Name", "zh" =>"用户名"],
        ["en" => "Add Role", "zh" => "添加角色"],
        ["en" => "User edit", "zh" => "用户编辑"],
        ["en" => "Save Update", "zh" => "保存更新"],
        ["en" => "Add User", "zh" => "添加用户"],
        ["en" => "Role add", "zh" => "添加角色"],
        ["en" => "Role Name", "zh" => "角色名称"],
        ["en" => "Select All", "zh" => "全选"],
        ["en" => "Role detail", "zh" => "角色详情"],
        ["en" => "Role edit", "zh" => "角色编辑"],
        ["en" => "Next page", "zh" => "下一页"],
        ["en" => "Last page", "zh" => "最后一页"],
        ["en" => "Prev page", "zh" => "上一页"],
        ["en" => "First page", "zh" => "首页"],
        ["en" => "The", "zh" => "第"],
        ["en" => "page", "zh" => "页"],
        ["en" => "jump" ,"zh" => "跳转" ]

    ];

    public static function parse()
    {
        $path[] = __APP_DIR__.'/web/*';
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_dir($item)) {
                    if (strpos($item, "/lang") !== false) {
                        continue;
                    }
                    $path[] = $item . '/*';
                }
                elseif (is_file($item)) {
                    $info    = pathinfo($item);
                    $content = $en_content = $zh_content = file_get_contents($item);

                    if (!isset($info['extension'])) {
                        $info['extension'] = "";
                    }

                    if (in_array($info['extension'], ["php", "js", "html"])) {
                        preg_match_all("/__LANG\([\s\S]{1,}?\)/", $content, $matches);
                        if (count($matches[0]) > 0) {
                            echo $item, "\r\n";
                            var_dump($matches[0]);
                            foreach ($matches[0] as $_lang) {
                                $lang = substr($_lang, 7, strlen($_lang) - 8);//ltrim($_lang, "__LANG(");
                                $en   = $lang;
                                $zh   = $lang;

                                foreach (self::$lang as $_l) {
                                    if (strtolower(trim($_l["en"])) == strtolower(trim($lang)) ||
                                        strtolower(trim($_l["zh"])) == strtolower(trim($lang))
                                    ) {
                                        $en = $_l["en"];
                                        $zh = $_l["zh"];
                                        break;
                                    }
                                }

                                $en_content = str_replace($_lang, $en, $en_content);
                                $zh_content = str_replace($_lang, $zh, $zh_content);
                            }
                        }
                    }

                    $target_file = __APP_DIR__ . "/web/lang/en" . str_replace(__APP_DIR__ . "/web", "", $item);
                    $wfile       = new WFile($target_file);
                    $wfile->touch();

                    file_put_contents($target_file, $en_content);

                    $target_file = __APP_DIR__ . "/web/lang/zh" . str_replace(__APP_DIR__ . "/web", "", $item);
                    $wfile       = new WFile($target_file);
                    $wfile->touch();
                    file_put_contents($target_file, $zh_content);
                    echo "编译完成：";
                    echo $target_file, "\r\n";
                }
            }
        }
    }
}