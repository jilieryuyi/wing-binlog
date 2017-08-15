package main

import (
	"fmt"
)

func main() {
	var a map[int]int = make(map[int]int)
	a[0] = 1
	a[1] = 2
	a[2] = 3
	a[3] = 4
	fmt.Println(a)
	delete(a, 2)
	fmt.Println(a)
}
