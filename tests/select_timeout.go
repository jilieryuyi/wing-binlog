package main

import (
	"time"
	"fmt"
)

var send_msg_chan chan int = make(chan int)

func MainThread() {
	//for i := 0; i < 4; i ++
	to := time.NewTimer(time.Second*3)
	{
		go func() {
			for {
				select {
				case body := <-send_msg_chan:
					//time.Sleep(time.Second*4)
					fmt.Println(body)
				case <-to.C://time.After(time.Second*3):
					fmt.Println("发送超时...")
				}
			}
		}()
	}
}

func main() {

	go MainThread()

	for i:=0; i < 10; i++  {
		time.Sleep(time.Second*4)
		send_msg_chan <- i
	}

	for {
		time.Sleep(time.Second*100000)
	}
}
