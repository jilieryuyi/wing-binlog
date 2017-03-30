![wing-binlog](https://raw.githubusercontent.com/jilieryuyi/wing-binlog/master/wing.png)

mysqlbinlog general log 监控分析系统
====
>不改变一句业务代码实现整库数据变化实时监控

### 事件数据
* 1、发生数据变化的数据库名称
* 2、发生数据变化的数据表名称
* 3、实际变化的数据

### 如以下数据
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
 
 
### 启动|状态|停止|重启 服务指令--请使用root执行
    php seals server:start
    //可选项 --d以守护进程启动 --n 4启动4个工作进程 --debug启用debug模式 --clear自动清理日志和缓存
    php seals server:status 
    //查看运行状态
    php seals server:stop
    //停止服务
    php seals server:restart
    //重启
    
    //master 进程
    php seals master:start 
    //可选参数 --d 以守护进程方式运行 --debug 启用debug模式
    php seals master:stop
    //停止运行
    php seals master:status
    //查看运行状态
    php seals master:restart
    //重启master进程

### wing-binlog 2.0 --建议安装libevent
2.0版本已上线，升级功能点如下：<br/>
* 1、支持一主多从群集部署，任意一台从库下线自动启用另一台工作，实现高可用
* 2、支持集中的升级、重启、配置、下线以及general log开启关闭管理（配置、管理中心）
* 3、支持general log，实现读写分布统计，可以轻松知晓读写峰值，以及每天各时段的读写分布，为优化提供直观参考
* 5、2.0版本内置http服务，默认端口为9998，启动master进程后访问 http://127.0.0.1:9998 ，默认用户（请看如何使用6），
内置php服务采用select、epoll实现（建议安装libevent）

### 注意
* 1、仅支持mysqlbinlog的row格式
* 2、mysql版本必须大于等于5.6.2
* 3、必须与需要采集数据的mysql运行在同一台服务器
* 4、进行分布式部署时，config/redis.php和config/redis_local.php一定不能使用相同的redis，以避免资源冲突

### 如何使用？
* 1、执行 php seals config 初始化配置文件
* 2、修改config目录下的配置文件为自己的服务器参数
* 3、不要忘了 composer install
* 4、已支持redis队列、http和rabbitmq三种方式的事件通知方式，修改config/notify.php 更改通知方式，需要重启进程，默认为redis队列
* 5、已支持数据表过滤，那些数据不感兴趣可以过滤采集
* 6、添加初始化用户 php seals user:add --name admin --password 123456 --role admin
* 7、使admin角色具有全部权限（也可以理解为权限重置），php seals role:admin --name admin
### 常见问题
* 1、什么情况下事件会丢失？
     redis写入异常或者http请求异常、一个事务相关的数据超过8万行，不过发生这种情况的概率很小罢了
* 2、不想记录这么日志怎么处理？
     修改config/app.php下的log_levels，去掉一些不想记录的错误级别即可
* 3、如何实现自定义日志？
     日志的实现默认为 \Seals\Logger\Local::class ，修改config/app.php下的logger即可，实现必须遵循psr/log日志标准，即必须实现Psr\Log\LoggerInterface接口
* 4、怎么增加新的通知方式？如：想要把通知方式修改为mq，怎么处理？
     通知方式的配置为config/notify.php，修改次配置文件即可实现通知方式的自定义化，另外新增加的通知必须实现Seals\Library\Notify接口
* 5、ACCESS_REFUSED - Login was refused using authentication mechanism AMQPLAIN，此错误的解决方式为，添加一个新的用户，如admin，admin，然后服务端使用admin发布队列消息，客户端依然可以使用默认的guest登录接收消息

### wing-binlog的实现原理以及简单的概念介绍
数据实时分析系统与业务系统彻底解耦，一个完整的实时分析系统的基础架构大概如下

        数据采集系统
            ||
        数据分析系统
            ||
        数据展示系统

数据采集系统向数据分析系统提供实时数据流，数据分析系统实现数据分析，并将结果持久化，最后则是数据展示系统。
而数据采集系统，作为采集中间件，与业务无关

基本实现原理：

* 1、通过show binlog events得到基础的事件信息，进一步解析得到一个事务的开始点和结束点，
* 2、采集得到基础事件的详细数据，并将其重定向到指定的缓存文件
* 3、解析缓存文件得到最终的事件信息，再通过指定的通知插件将事件推送至事件的接收端！

wing-binlog使用redis队列实现，采用典型的消费者模型，使用多进程调度(类似于MQ的分发机制)，实现了进程间的均衡负载，
而文件缓存的使用，极大的降低了数据高峰期和大事务数据生成时采集的内存消耗。
redis配置区分本地和队列服务，支持分布式部署，最大限度的友好支持系统的横向扩展！