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
	//"time"
	//"text/template"
)

const (
	readBufferSize  = 10240
	writeBufferSize = 10240
)

//type BODY struct {
//	conn *websocket.Conn
//	msg string
//}



//所有的连接进来的客户端
var clients map[int]*websocket.Conn = make(map[int]*websocket.Conn)
//所有的连接进来的客户端数量
var clients_count int = 0

//var broadcast chan BODY =  make(chan BODY)   // 广播聊天的chan
//var receive_msg  chan BODY =  make(chan BODY)
var msg_buffer map[string]bytes.Buffer = make(map[string]bytes.Buffer)
var msg_split string     = "\r\n\r\n\r\n";
const DEBUG bool         = true
var send_times int       = 0
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
				if (conn.RemoteAddr().String() == client.RemoteAddr().String()) {
					delete(clients, key)
					delete(msg_buffer, conn.RemoteAddr().String())
				}
			}

			conn.Close();
			break
		}
		msg := fmt.Sprintf("%s", message)
		Log("收到消息：", msg)
		//receive_msg <- BODY{conn, msg}
		OnMessage(conn, msg)
	}
}

func OnMessage(conn *websocket.Conn , msg string) {

	//html := 		"HTTP/1.1 200 OK\r\nContent-Length: 5\r\nContent-Type: text/html\r\n\r\nhello"
	msg_buffer[conn.RemoteAddr().String()].WriteString(msg)// += msg

	_buffer := msg_buffer[conn.RemoteAddr().String()].String();
	//粘包处理
	temp     := strings.Split(_buffer, msg_split)
	temp_len := len(temp)

	if (temp_len >= 2) {
		msg_buffer[conn.RemoteAddr().String()].Reset()
		msg_buffer[conn.RemoteAddr().String()].WriteString(temp[temp_len - 1])

		for _, v := range temp {
			if strings.EqualFold(v, "") {
				continue
			}

			v += "\r\n\r\n\r\n"
			send_times++;
			Log("广播次数：", send_times)

			for _, client := range clients {
				if (conn.RemoteAddr().String() == client.RemoteAddr().String()) {
					Log("不给自己发广播...")
					continue
				}
				//client.SetWriteDeadline(time.Now().Add(time.Second * 3))
				err := client.WriteMessage(1, []byte(v))
				if err != nil {
					send_error_times++
					Log("发送失败次数：", send_error_times)
					Log(err)
				}
			}

		}
	}
}

func Log(v ...interface{}) {
	if (DEBUG) {
		log.Println(v...)
	}
}

func main() {

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