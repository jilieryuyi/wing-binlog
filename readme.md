基于mysql数据库binlog的增量订阅&消费
====
>wing-binlog是一个高性能php中间件    
wing-binlog是一个轻量化mysql数据库监控系统     
wing-binlog可轻松实现不改变一句业务代码实现整库数据变化实时监控      
......

### 2.2升级要点
1. 去除本地redis依赖    
2. 支持websocket事件通知    
3. 支持tcp事件通知    
4. 简化安装流程      
5. 优化性能问题，使binlog处理速度能达到binlog的写入速度，避免延迟
6. 支持windows
7. mysql协议支持
   
### 安装
1. 开启mysql binlog支持，并且指定格式为row，如下配置   
````
[mysqld]
server_id = 1
log_bin = mysql-bin
binlog_format=ROW
````     
2. 执行 composer install，未安装composer的请自行安装 
````
cd Binlog && composer install
````         
3. 将config下的配置文件.example去除后修改其配置为自己的配置 
````
cd config && cp app.php.example app.php
````  
4. 执行 php wing start 开启服务进程，可选参数 --d 以守护进程执行， --debug 启用debug模式， --n 指定进程数量，如：      
````
php wing start --d --debug --n 8 
````         
5. clients下面有两个测试的客户端，一个websocket和一个php实现的tcp client      
6. 停止所有服务  
````
php wing stop 
````
7. 查看服务状态   
````
php wing status 
````
8. src/Subscribe目录为可选的订阅者服务插件，只需要配置到app.php的subscribe下即可！    
wing-binlog提供tcp和websocket服务，可选使用go或者workerman，workerman仅支持linux，go支持所有的平台。    
使用go服务需要安装go，已安装的忽略。    
编译go服务（如需使用，请先编译后再启动Binlog服务）：
````
cd services
go build -o tcp tcp.go
go build -o websocket websocket.go
````
     
### 使用场景
1. 数据库实时备份 （按业务表自定义或者整库同步）    
2. 异地机房业务，双master机房（两地写入，互相同步）     
3. 业务cache／store数据更新 （根据数据库变更日志，直接更新内存cache或者分布式cache）     
4. 敏感业务数据变更服务（典型的就是金额变化通知，库存变化的通知）    
5. 实时数据增量计算统计      
...... 

### 帮助
   QQ群咨询 535218312  

### 致谢
   https://github.com/fengxiangyun/mysql-replication    
   https://github.com/jeremycole/mysql_binlog.git
   