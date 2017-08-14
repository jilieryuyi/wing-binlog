package main

import (
	"fmt"
	"net"
	"log"
	"os"
	"strings"
)

type BODY struct {
	conn net.Conn
	msg string
}

var clients map[int]net.Conn = make(map[int]net.Conn)
var clients_count int = 0
var msg_buffer string = ""
var msg_split string  = "\r\n\r\n\r\n";
var send_times int = 0
var msg_times int = 0
var failure_times int = 0

const MAX_B_QUEUE = 10240
var MSG_SEND_QUEUE = make(chan BODY, 10240)
var MSG_RECEIVE_QUEUE = make(chan BODY, 10240)

func main() {

	//建立socket，监听端口
	listen, err := net.Listen("tcp", "0.0.0.0:9996")
	DealError(err)
	defer func () {
		listen.Close();
		close(MSG_SEND_QUEUE)
		close(MSG_RECEIVE_QUEUE)
	}()
	Log("Waiting for clients")

	go MainThread()

	for {
		conn, err := listen.Accept()
		if err != nil {
			continue
		}
		go OnConnect(conn)
	}
}


func AddClient(conn net.Conn) {
	clients[clients_count] = conn
	clients_count++
}

func RemoveClient(conn net.Conn){
	// 遍历map
	for k, v := range clients {
		if v == conn {
			delete(clients, k)
		}
	}
	clients_count--
}


func Broadcast(_msg BODY) {
	msg := _msg.msg
	msg += "\r\n\r\n\r\n"
	send_times++;
	fmt.Println("广播次数===>", send_times)
	for _, v := range clients {
		//非常关键的一步 如果这里也给接发来的人广播 接收端不消费
		//发送会被阻塞
		if v == _msg.conn {
			continue
		}
		//fmt.Println("广播----", v, msg)
		size, err := v.Write([]byte(msg))
		if (size <= 0 || err != nil) {
			failure_times++
		}
	}
	fmt.Println("失败次数===>", failure_times)
}

/**
 * 广播
 *
 * @param string msg
 */
func MainThread() {
	for i := 0; i < 4; i ++ {
		go func() {
			for {
				select {
				case msg := <-MSG_SEND_QUEUE:
					Broadcast(msg)
				case res := <-MSG_RECEIVE_QUEUE:
					OnMessage(res.conn, res.msg)
				default:
				//	//warnning!
				//fmt.Println("TASK_CHANNEL is full!")
				}
			}
		} ()
	}
	//for {
	//	//fmt.Println("广播...")
	//	select {
	//	case msg := <-MSG_SEND_QUEUE:
	//	//do nothing
	//	//task
	//
	//		msg += "\r\n\r\n\r\n"
	//		send_times++;
	//		fmt.Println("广播次数===>", send_times)
	//		for _, v := range clients {
	//			//fmt.Println("广播----", v, msg)
	//			size, err := v.Write([]byte(msg))
	//			if (size <= 0 || err != nil) {
	//				failure_times++
	//			}
	//		}
	//		fmt.Println("失败次数===>", failure_times)
	//		//OnMessage(nil, "");
	//	default:
	//	//warnning!
	//		//fmt.Println("TASK_CHANNEL is full!")
	//	}
	//}

}


//处理连接
func OnConnect(conn net.Conn) {
	Log(conn.RemoteAddr().String(), " tcp connect success")
	AddClient(conn)
	buffer := make([]byte, 20480)

	for {

		n, err := conn.Read(buffer)

		if err != nil {
			Log(conn.RemoteAddr().String(), " connection error: ", err)
			onClose(conn);
			conn.Close();

			return
		}


		//Log(conn.RemoteAddr().String(), "receive data string:\n", string(buffer[:n]))
		//go OnMessage(conn, string(buffer[:n]))
		//if len(MSG_RECEIVE_QUEUE) < MAX_B_QUEUE {
			MSG_RECEIVE_QUEUE <- BODY{conn, string(buffer[:n])}
		//}
	}

}
//conn net.Conn,
func OnMessage(conn net.Conn, msg string) {
	msg_times++
	fmt.Println("收到消息的次数==>", msg_times)
	//html := 		"HTTP/1.1 200 OK\r\nContent-Length: 5\r\nContent-Type: text/html\r\n\r\nhello"
	msg_buffer += msg

	var queue_len int = 0

	queue_len = len(MSG_SEND_QUEUE)
	fmt.Println("队列长度",queue_len)
	//if (queue_len >= 64) {
	//	return
	//}

	//Broadcast(msg);
	//粘包处理
	temp := strings.Split(msg_buffer, msg_split)
	temp_len := len(temp)

	//fmt.Println("切割之后===》",temp)
	//fmt.Println("长度===》",temp_len)



	if (temp_len >= 2) {
		msg_buffer = temp[temp_len - 1];
		//fmt.Println("msg_buffer===》",msg_buffer)
		for _, v := range temp {
			if strings.EqualFold(v, "") {
				//fmt.Println("v为空==》", v)
				continue
			}

			//fmt.Println("广播==》", v)
			//加上并发限制 如果同时广播数量达到一定数量 等待其返回 再发起新的广播
			//Broadcast(v);
			MSG_SEND_QUEUE <- BODY{conn, v}
			queue_len = len(MSG_SEND_QUEUE)
			fmt.Println("队列长度",queue_len)

			//if (queue_len >= 64) {
			//	return
			//}
		}
		//foreach ($temp as $v) {
		//if (!$v) {
		//continue;
		//}
		//$count++;
		//echo $v, "\r\n";
		//echo "收到消息次数：", $count, "\r\n\r\n";
		//}
		//}
		//unset($temp);

	}

	fmt.Println(msg_buffer)
}

func onClose(conn net.Conn) {
	RemoveClient(conn)
}

func Log(v ...interface{}) {
	log.Println(v...)
}

func DealError(err error) {
	if err != nil {
		fmt.Fprintf(os.Stderr, "Fatal error: %s", err.Error())
		os.Exit(1)
	}
}