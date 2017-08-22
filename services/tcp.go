package main

import (
	"fmt"
	"net"
	"log"
	"os"
	"strings"
	"runtime"
	"bytes"
	"strconv"
	"syscall"
	"io/ioutil"
	"os/signal"
	"path/filepath"
)

type BODY struct {
	conn net.Conn
	msg bytes.Buffer
}

type SEND_BODY struct {
	conn net.Conn
	msg string
}

//所有的连接进来的客户端
var clients map[int]net.Conn = make(map[int]net.Conn)
//所有的连接进来的客户端数量
var clients_count int = 0
//收到的消息缓冲区 用于解决粘包
//var msg_buffer bytes.Buffer// = ""
//粘包分隔符
var msg_split string  = "\r\n\r\n\r\n";
//发送次数
var send_times int    = 0
//收到消息次数
var msg_times int     = 0
//发送失败次数
var failure_times int = 0

const DEBUG bool = true
//最大的频道长度 可用于并发控制
const MAX_QUEUE       = 102400
var MSG_SEND_QUEUE chan SEND_BODY   = make(chan SEND_BODY, MAX_QUEUE)
//var MSG_RECEIVE_QUEUE = make(chan BODY, MAX_QUEUE)
func SignalHandle() {
	c := make(chan os.Signal)
	signal.Notify(c, syscall.SIGTERM)

	//当调用了该方法后，下面的for循环内<-c接收到一个信号就退出了。
	signal.Stop(c)

	for {
		s := <-c
		Log("进程收到退出信号",s)
		os.Exit(0)
	}
}

func ResetStd() {
	dir := GetParentPath(GetCurrentPath())
	handle, _ := os.OpenFile(dir+"/logs/tcp.log", os.O_WRONLY|os.O_CREATE|os.O_SYNC|os.O_APPEND, 0755)
	os.Stdout = handle
	os.Stderr = handle
}

func GetCurrentPath() string {
	dir, err := filepath.Abs(filepath.Dir(os.Args[0]))
	if err != nil {
		log.Fatal(err)
	}
	return strings.Replace(dir, "\\", "/", -1)
}

func substr(s string, pos, length int) string {
	runes := []rune(s)
	l := pos + length
	if l > len(runes) {
		l = len(runes)
	}
	return string(runes[pos:l])
}
func GetParentPath(dirctory string) string {
	return substr(dirctory, 0, strings.LastIndex(dirctory, "/"))
}

func main() {
	if len(os.Args) < 2 {
		fmt.Println("请使用如下模式启动")
		fmt.Println("1、指定端口为9998：tcp 9997")
		fmt.Println("2、指定端口为9998并且启用debug模式：tcp 9997 --debug")
		return
	}

	if (os.Args[1] == "stop") {
		dat, _ := ioutil.ReadFile(GetCurrentPath() + "/tcp.pid")
		fmt.Print(string(dat))
		pid, _ := strconv.Atoi(string(dat))
		Log("给进程发送终止信号：", pid)
		err := syscall.Kill(pid, syscall.SIGTERM)
		Log(err)
		return
	}

	Log(GetParentPath(GetCurrentPath()))
	Log(os.Getpid())

	//写入pid
	//handle, _ := os.OpenFile(GetCurrentPath() + "/tcp.pid", os.O_WRONLY | os.O_CREATE | os.O_SYNC, 0755)
	//io.WriteString(handle, fmt.Sprintf("%d", os.Getpid()))

	var data_str = []byte(fmt.Sprintf("%d", os.Getpid()));
	ioutil.WriteFile(GetCurrentPath() + "/tcp.pid", data_str, 0777)  //写入文件(字节数组)


	debug := false
	if len(os.Args) == 3 {
		if os.Args[2] == "debug" || os.Args[2] == "--debug" {
			debug = true
		}
	}
	Log(debug)
	if !debug {
		ResetStd()
	} else {
		Log("debug模式")
	}

	go MainThread()
	go SignalHandle()
	//建立socket，监听端口
	listen, err := net.Listen("tcp", "0.0.0.0:" + os.Args[1])
	DealError(err)
	defer func() {
		listen.Close();
		close(MSG_SEND_QUEUE)
		//close(MSG_RECEIVE_QUEUE)
	}()
	Log("等待新的连接...")

	// runtime.GOMAXPROCS(32)
	// 限制同时运行的goroutines数量
	go MainThread()

	for {
		conn, err := listen.Accept()
		if err != nil {
			continue
		}
		go OnConnect(conn)
	}
}

//添加客户端到集合
func AddClient(conn net.Conn) {
	clients[clients_count] = conn
	clients_count++
}

//将客户端从集合移除 由于移除操作不会重建索引clients_count就是当前最后的索引
func RemoveClient(conn net.Conn){
	// 遍历map
	for k, v := range clients {
		if v == conn {
			delete(clients, k)
		}
	}
}

func Broadcast(msg SEND_BODY) {
	size, err := msg.conn.Write([]byte(msg.msg+"\r\n\r\n\r\n"))
	if (size <= 0 || err != nil) {
		failure_times++
	}
	Log("失败次数：", failure_times)
}

/**
 * 广播
 *
 * @param string msg
 */
func MainThread() {
	//to := time.NewTimer(time.Second*3)
	cpu := runtime.NumCPU()
	for i := 0; i < cpu; i ++ {
		go func() {
			for {
				select {
					case body := <-MSG_SEND_QUEUE:
							Broadcast(body)
				//case <-to.C://time.After(time.Second*3):
				//	Log("发送超时...")
				}
			}
		} ()
	}
}


//处理连接
func OnConnect(conn net.Conn) {
	Log(conn.RemoteAddr().String(), "连接成功")
	AddClient(conn)
	read_buffer := make([]byte, 20480)
	var msg_buffer bytes.Buffer
	body := BODY{conn, msg_buffer}
	for {

		size, err := conn.Read(read_buffer)

		if err != nil {
			Log(conn.RemoteAddr().String(), "连接发生错误: ", err)
			OnClose(conn);
			conn.Close();
			return
		}

		msg_times++
		Log("收到消息的次数：", msg_times)

		body.msg.Write(read_buffer[:size])
		//MSG_RECEIVE_QUEUE <- BODY{conn, string(buffer[:size])}
		OnMessage(&body)
	}

}

//收到消息回调函数
func OnMessage(body *BODY) {

	//html := 		"HTTP/1.1 200 OK\r\nContent-Length: 5\r\nContent-Type: text/html\r\n\r\nhello"
	//msg_buffer.WriteString(msg)// += msg

	//粘包处理
	temp     := strings.Split(body.msg.String(), msg_split)
	temp_len := len(temp)

	if (temp_len >= 2) {
		body.msg.Reset()
		body.msg.WriteString(temp[temp_len - 1])

		for _, v := range temp {
			if strings.EqualFold(v, "") {
				continue
			}


			send_times++;
			Log("广播次数：", send_times)

			for _, client := range clients {
				//非常关键的一步 如果这里也给接发来的人广播 接收端不消费
				//发送会被阻塞
				if client.RemoteAddr().String() == body.conn.RemoteAddr().String() {
					continue
				}
				//fmt.Println("广播----", v, msg)
				//v.SetWriteDeadline()
				//go func () {
				//wg.Add(1)//为同步等待组增加一个成员
				//v.SetWriteDeadline(time.Now().Add(time.Millisecond * 100))
				//size, err := v.Write([]byte(msg))

				MSG_SEND_QUEUE <- SEND_BODY{client, v}

				//if (size <= 0 || err != nil) {
				//	failure_times++
				//}
				//}()
			}


		}
	}
}

func OnClose(conn net.Conn) {
	RemoveClient(conn)
}

func Log(v ...interface{}) {
	if (DEBUG) {
		log.Println(v...)
	}
}

func DealError(err error) {
	if err != nil {
		fmt.Fprintf(os.Stderr, "发生严重错误: %s", err.Error())
		os.Exit(1)
	}
}