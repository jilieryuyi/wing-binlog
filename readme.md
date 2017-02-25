mysqlbinlog事件采集系统
====
>不改变一句业务代码实现整库数据变化实时监控

###事件数据
* 1、发生数据变化的数据库名称
* 2、发生数据变化的数据表名称
* 3、实际变化的数据

###如以下数据
    array(3) {
      ["event_type"]=> 
      string(11) "update_rows"
      ["time"]=> 
      string(19) "2017-02-13 17:02:56"
      ["data"]=>
      array(2) {
        ["old_data"]=>
        array(5) {
          ["id"]=>
          string(3) "528"
          ["day_payout_money"]=>
          string(8) "2000.000"
          ["day"]=>
          string(1) "1"
          ["created_at"]=>
          string(10) "1486547863"
          ["updated_at"]=>
          string(10) "1486622467"
        }
        ["new_data"]=>
        array(5) {
          ["id"]=>
          string(3) "528"
          ["day_payout_money"]=>
          string(8) "2000.000"
          ["day"]=>
          string(1) "2"
          ["created_at"]=>
          string(10) "1486547863"
          ["updated_at"]=>
          string(10) "1486622467"
        }
      }
    }

>event_type 为事件类型，三者之一 update_rows、delete_rows、write_rows
 如果是update_rows，则data部分包含new_data和old_data两部分，分别代表修改后和修改前的数据
 如果是delete_rows或者write_rows，data部分则仅包含变化的数据，time为事件发生的具体时间
 
 
###启动|状态|停止|重启 服务指令
    php seals server:start
    //可选项 --d以守护进程启动 --n 4启动4个工作进程 --debug启用debug模式
    php seals server:status 
    //查看运行状态
    php seals server:stop
    //停止服务
    php seals server:restart
    //重启

###注意
最后强调一下，仅支持mysqlbinlog的row格式

###如何使用？
* 1、首先复制config目录下的.php.example为.php文件，也就是全部去掉.example
* 2、修改config目录下的配置文件为自己的服务器参数
* 3、redis默认事件队列为 seals:event:list
* 4、不要忘了 composer install
