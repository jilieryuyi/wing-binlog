基于mysql数据库binlog的增量订阅&消费
====
>不改变一句业务代码实现整库数据变化实时监控，轻量化数据库监控系统

### 2.1升级要点
1、去除本地redis依赖    
2、支持websocket事件通知    
3、支持tcp事件通知    
4、简化安装流程  
   
### 安装
1、开启mysql binlog，并且指定格式为row        
2、执行 composer install，未安装composer的请自行安装          
3、将config下的配置文件.example去除后修改其配置为自己的配置     
4、执行 php wing start 开启服务进程，可选参数 --d 以守护进程执行，--with-tcp 启用tcp服务，--with-websocket启用websocket服务，--with-redis启用redis队列通知服务         
     如：php wing start --d --with-tcp --with-websocket --with-redis          
5、clients下面有两个测试的客户端，一个websocket和一个php实现的tcp      
6、执行php wing stop 停止所有服务  
7、执行php wing status 查看服务状态  
         
### 帮助
   QQ群咨询 535218312      