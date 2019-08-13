package util

import (
	"fmt"
	"net"
	"time"
)

// DialPort 探测指定 IP 端口是否开启
func DialPort(ip string, port int) bool {
	conn, err := net.DialTimeout("tcp",
		fmt.Sprintf("%s:%d", ip, port), time.Second*3)
	if err != nil {
		return false
	}
	conn.Close()
	return true
}