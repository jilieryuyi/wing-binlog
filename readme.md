基于mysql数据库binlog的增量订阅&消费
====
>wing-binlog是一个高性能php中间件    
wing-binlog是一个轻量化mysql数据库监控系统     
wing-binlog可轻松实现不改变一句业务代码实现整库数据变化实时监控      
......

### 2.1升级要点
1. 去除本地redis依赖    
2. 支持websocket事件通知    
3. 支持tcp事件通知    
4. 简化安装流程      
5. 优化性能问题，使binlog处理速度能达到binlog的写入速度，避免延迟
6. 支持windows
   
### 安装
1. 开启mysql binlog，并且指定格式为row        
2. 执行 composer install，未安装composer的请自行安装          
3. 将config下的配置文件.example去除后修改其配置为自己的配置   
4. 执行 php wing start 开启服务进程，可选参数 --d 以守护进程执行， --debug 启用debug模式， --n 指定进程数量         
     如：php wing start --d --debug --n 8          
5. clients下面有两个测试的客户端，一个websocket和一个php实现的tcp      
6. 执行php wing stop 停止所有服务  
7. 执行php wing status 查看服务状态   
8. 可选wing-binlog提供tcp和websocket服务，需要安装go，已安装的忽略， 
进入services目录：cd services，编译tcp服务：go build -o tcp tcp.go，编译websocket服务：go build -o websocket websocket.go
     
### 使用场景
1. 数据库实时备份 （按业务表自定义或者整库同步）    
2. 异地机房业务，双master机房（两地写入，互相同步）     
3. 业务cache／store数据更新 （根据数据库变更日志，直接更新内存cache或者分布式cache）     
4. 敏感业务数据变更服务（典型的就是金额变化通知，库存变化的通知）    
5. 实时数据增量计算统计      
...... 

### 帮助
   QQ群咨询 535218312  