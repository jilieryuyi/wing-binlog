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
	//"time"
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

func main() {
	go MainThread()
	m := martini.Classic()

	m.Get("/", func(res http.ResponseWriter, req *http.Request) { // res and req are injected by Martini

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

		Log("新的连接："+ conn.RemoteAddr().String())
		go OnConnect(conn)
	})

	m.RunOnAddr(":"+os.Args[1])
}