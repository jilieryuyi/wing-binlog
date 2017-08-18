package main

import (
	"fmt"
	"github.com/go-martini/martini"
	"github.com/gorilla/websocket"
	"log"
	"net/http"
	"strings"
	"bytes"
	"os"
	"os/signal"
	"syscall"
	//"time"
	//"runtime"
//	"os/exec"
	"path/filepath"
	"io"
	"io/ioutil"
	"strconv"
)

const (
	readBufferSize  = 10240
	writeBufferSize = 10240
)

type BODY struct {
	conn *websocket.Conn
	msg bytes.Buffer
}

type SEND_BODY struct {
	conn *websocket.Conn
	msg string
}

//所有的连接进来的客户端
var clients map[int]*websocket.Conn = make(map[int]*websocket.Conn)
//所有的连接进来的客户端数量
var clients_count int = 0
const MAX_SEND_QUEUE int = 102400
//var broadcast chan BODY =  make(chan BODY)   // 广播聊天的chan
var send_msg_chan  chan SEND_BODY =  make(chan SEND_BODY, MAX_SEND_QUEUE)
//var msg_buffer map[string]string = make(map[string]string)
var msg_split string     = "\r\n\r\n\r\n";
const DEBUG bool         = true
var send_times int       = 0
var send_error_times int = 0

func OnConnect(conn *websocket.Conn) {

	clients[clients_count] = conn
	clients_count++
	var buffer bytes.Buffer
	body := BODY{conn, buffer}

	for {
		_, message, err := conn.ReadMessage()
		if err != nil {
			if websocket.IsUnexpectedCloseError(err, websocket.CloseGoingAway) {
				log.Printf("error: %v", err)
			}

			for key, client := range clients {
				if (conn.RemoteAddr().String() == client.RemoteAddr().String()) {
					delete(clients, key)
					//delete(msg_buffer, conn.RemoteAddr().String())
				}
			}

			conn.Close();
			break
		}
		msg := fmt.Sprintf("%s", message)
		Log("收到消息：", msg)
		body.msg.Write(message)
		OnMessage(&body)
	}
}

func OnMessage(conn *BODY) {

	//html := 		"HTTP/1.1 200 OK\r\nContent-Length: 5\r\nContent-Type: text/html\r\n\r\nhello"
	//粘包处理
	temp     := strings.Split(conn.msg.String(), msg_split)
	temp_len := len(temp)

	if (temp_len >= 2) {
		conn.msg.Reset()
		conn.msg.WriteString(temp[temp_len - 1])

		for _, v := range temp {
			if strings.EqualFold(v, "") {
				continue
			}

			v += "\r\n\r\n\r\n"
			send_times++;
			Log("广播次数：", send_times)

			for _, client := range clients {
				if (conn.conn.RemoteAddr().String() == client.RemoteAddr().String()) {
					Log("不给自己发广播...")
					continue
				}
				if (len(send_msg_chan) >= MAX_SEND_QUEUE) {
					Log("发送缓冲区满")
				} else {
					send_msg_chan <- SEND_BODY{client, v}
				}
			}

		}
	}
}

func MainThread() {
	//for i := 0; i < 4; i ++
	//to := time.NewTimer(time.Second*3)
	//cpu := runtime.NumCPU()
	//for i := 0; i < cpu; i ++
	{
		go func() {
			for {
				select {
				case body := <-send_msg_chan:
					//body.conn.SetWriteDeadline(time.Now().Add(time.Second * 3))
					Log("发送：", body.msg)
					err := body.conn.WriteMessage(1, []byte(body.msg))
					if err != nil {
						send_error_times++
						Log("发送失败次数：", send_error_times)
						Log(err)
					}
				//case <-to.C://time.After(time.Second*3):
				//	Log("发送超时...")
				}
			}
		}()
	}
}


func Log(v ...interface{}) {
	if (DEBUG) {
		log.Println(v...)
	}
}

func SignalHandle() {
	c := make(chan os.Signal)
	signal.Notify(c, syscall.SIGINT)

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
	handle, _ := os.OpenFile(dir+"/logs/websocket.log", os.O_WRONLY|os.O_CREATE|os.O_SYNC|os.O_APPEND, 0755)
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
		fmt.Println("1、指定端口为9998：websocket 9998")
		fmt.Println("2、指定端口为9998并且启用debug模式：websocket 9998 --debug")
	} else {
		if (os.Args[1] == "stop") {
			dat, _ := ioutil.ReadFile(GetCurrentPath() + "/websocket.pid")
			fmt.Print(string(dat))
			pid, _ := strconv.Atoi(string(dat))
			syscall.Kill(pid, syscall.SIGINT)
		} else {

			Log(GetParentPath(GetCurrentPath()))
			Log(os.Getpid())

			//写入pid
			handle, _ := os.OpenFile(GetCurrentPath() + "/websocket.pid", os.O_WRONLY | os.O_CREATE | os.O_SYNC, 0755)
			io.WriteString(handle, fmt.Sprintf("%d", os.Getpid()))

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

			m := martini.Classic()

			m.Get("/", func(res http.ResponseWriter, req *http.Request) {
				// res and req are injected by Martini

				u := websocket.Upgrader{ReadBufferSize: readBufferSize, WriteBufferSize: writeBufferSize}
				u.Error = func(w http.ResponseWriter, r *http.Request, status int, reason error) {
					Log(w, r, status, reason)
					// don't return errors to maintain backwards compatibility
				}
				u.CheckOrigin = func(r *http.Request) bool {
					// allow all connections by default
					return true
				}
				conn, err := u.Upgrade(res, req, nil)

				if err != nil {
					log.Println(err)
					return
				}

				Log("新的连接：" + conn.RemoteAddr().String())
				go OnConnect(conn)
			})

			m.RunOnAddr(":" + os.Args[1])
		}
	}
}