package main

import (
	"fmt"
	"github.com/go-martini/martini"
	"github.com/gorilla/websocket"
	"log"
	"net/http"
	"strings"
	"bytes"
	//"encoding/json"
	"os"
	"time"
	//"text/template"
)

const (
	readBufferSize  = 10240
	writeBufferSize = 10240
)

type BODY struct {
	conn *websocket.Conn
	msg string
}



//所有的连接进来的客户端
var clients map[int]*websocket.Conn = make(map[int]*websocket.Conn)
//所有的连接进来的客户端数量
var clients_count int = 0

var broadcast chan BODY =  make(chan BODY)   // 广播聊天的chan
var receive_msg  chan BODY =  make(chan BODY)
var msg_buffer bytes.Buffer
var msg_split string  = "\r\n\r\n\r\n";
const DEBUG bool = true
var send_times int    = 0
var send_error_times int = 0

func OnConnect(conn *websocket.Conn) {

	clients[clients_count] = conn
	clients_count++
	for {
		_, message, err := conn.ReadMessage()
		if err != nil {
			if websocket.IsUnexpectedCloseError(err, websocket.CloseGoingAway) {
				log.Printf("error: %v", err)
			}

			for key, client := range clients {
				if (conn == client) {
					delete(clients, key)
				}
			}

			conn.Close();
			break
		}
		msg := fmt.Sprintf("%s", message)
		Log("收到消息：", msg)
		receive_msg <- BODY{conn, msg}
	}
}

func OnMessage(conn *websocket.Conn , msg string) {

	//html := 		"HTTP/1.1 200 OK\r\nContent-Length: 5\r\nContent-Type: text/html\r\n\r\nhello"
	msg_buffer.WriteString(msg)// += msg

	//粘包处理
	temp     := strings.Split(msg_buffer.String(), msg_split)
	temp_len := len(temp)

	if (temp_len >= 2) {
		msg_buffer.Reset()
		msg_buffer.WriteString(temp[temp_len - 1])

		for _, v := range temp {
			if strings.EqualFold(v, "") {
				continue
			}
			//Log("写入广播：", v)
			//broadcast <- BODY{conn, v}

			for _, client := range clients {
				if (conn == client) {
					continue
				}
				broadcast <- BODY{client, v}
			}

		}
	}
}

func Broadcast(_msg BODY) {
	msg := _msg.msg
	//var data map[string]interface{}
	//json.Unmarshal([]byte(msg), &data)
	//Log("索引：", data["event_index"])
	msg += "\r\n\r\n\r\n"
	//Log("广播消息：", msg)
	send_times++;
	Log("广播次数：", send_times)
	//for _, v := range clients {
		//非常关键的一步 如果这里也给接发来的人广播 接收端不消费
		//发送会被阻塞
		//if v == _msg.conn {
		//	Log("广播不发送给自己...")
		//	continue
		//}
		_msg.conn.SetWriteDeadline(time.Now().Add(time.Second * 3))
		err := _msg.conn.WriteMessage(1, []byte(msg))
		if err != nil {
			send_error_times++
			Log("发送失败次数：", send_error_times)
			Log(err)
		}
	//}
}
func Log(v ...interface{}) {
	if (DEBUG) {
		log.Println(v...)
	}
}

func manager() {
	go func() {
		for {
			select {
			//case body := <-broadcast:
			//	Log("开始处理广播：", body)
			//	go func() {
			//		Broadcast(body)
			//	} ()
			case res := <-receive_msg:
				OnMessage(res.conn, res.msg)
			}
		}
	} ()

	go func() {
		for {
			select {
			case body := <-broadcast:
				//Log("开始处理广播：", body)
				//go func() {
					 go Broadcast(body)
				//} ()
			//case res := <-receive_msg:
			//	OnMessage(res.conn, res.msg)
			}
		}
	} ()
}

func main() {

	m := martini.Classic()
	go manager()

	m.Get("/", func(res http.ResponseWriter, req *http.Request) { // res and req are injected by Martini

		u := websocket.Upgrader{ReadBufferSize: readBufferSize, WriteBufferSize: writeBufferSize}
		u.Error = func(w http.ResponseWriter, r *http.Request, status int, reason error) {
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

		Log("新的连接："+ conn.RemoteAddr().String())
		go OnConnect(conn)
	})

	m.RunOnAddr(":"+os.Args[1])
}