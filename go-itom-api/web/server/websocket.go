package server

import (
	"fmt"
	"github.com/docker/docker/api/types"
	"github.com/gorilla/websocket"
	"go-itom-api/pkg/dockercli"
	logging "go-itom-api/pkg/logging"
	"io"
	"net/http"
	"strconv"
	"time"
)

const (
	// Time allowed to write a message to the peer.
	writeWait = 10 * time.Second

	// Maximum message size allowed from peer.
	maxMessageSize = 8192

	// Time to wait before force close on connection.
	closeGracePeriod = 10 * time.Second
)

// 接收客户端传入数据，转发至 docker 中
func wsRecvMsg(ws *websocket.Conn, hijack *types.HijackedResponse, exit chan bool) {
	ws.SetReadLimit(maxMessageSize)
	var err error
	var message []byte
	for {
		_, message, err = ws.ReadMessage()
		if err != nil {
			err = fmt.Errorf("ws.read.error [%s]", err.Error())
			logging.Logger.Println(err.Error())
			break
		}
		if _, err = hijack.Conn.Write(message); err != nil {
			logging.Logger.Printf("ws.write2docker.error [%s]", err.Error())
			break
		}
	}
	logging.Logger.Println("ws.disconnect.client.complete")
	exit <- true
}

// 监听容器输出数据，将数据转发至客户端
func wsSendMsg(ws *websocket.Conn, r io.Reader, exit chan bool) {
	var err error
	size := 32 * 1024
	buf := make([]byte, size)
	for {
		nr, er := r.Read(buf)
		if nr > 0 {
			_ = ws.SetWriteDeadline(time.Now().Add(writeWait))
			if err = ws.WriteMessage(websocket.TextMessage, buf[0:nr]); err != nil {
				break
			}
		}
		if er != nil {
			if er != io.EOF {
				err = er
			}
			break
		}
	}
	logging.Logger.Println("ws.disconnect.docker.complete")
	exit <- true
}

// checkSameOrigin returns true if the origin is not set or is equal to the request host.
func checkSameOrigin(r *http.Request) bool {
	return true
}

var upgrader = websocket.Upgrader{
	CheckOrigin: checkSameOrigin,
}

// webshell 为用户创建 webshell
func webshell(w http.ResponseWriter, r *http.Request) {
	host := r.FormValue("target_ip")
	port := r.FormValue("target_port")
	_rows := r.FormValue("rows")
	_cols := r.FormValue("cols")
	command := r.FormValue("cmd")
	containerID := r.FormValue("container_id")
	if port == "" {
		port = "2375"
	}
	if command == "" {
		command = "/bin/bash"
	}
	var rows, cols int
	rows, _ = strconv.Atoi(_rows)
	cols, _ = strconv.Atoi(_cols)

	ws, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		err = fmt.Errorf("ws.init.error [%s:%s|%d|%d|%s|%s]\n",
			host, port, rows, cols, command, err.Error())
		logging.Logger.Println(err.Error())
		_ = ws.WriteMessage(websocket.TextMessage, []byte(err.Error()))
		return
	}
	defer ws.Close()
	logging.Logger.Printf("ws.init.complete [%s:%s|%d|%d|%s]\n",
		host, port, rows, cols, command)

	// 创建 webshell 会话
	var dkcli dockercli.DockerSampleClient
	if err = dkcli.Init(fmt.Sprintf("%s:%s", host, port), ""); err != nil {
		_ = ws.WriteMessage(websocket.TextMessage, []byte(err.Error()))
		return
	}
	logging.Logger.Printf("ws.init.docker.complete [%s:%s|%d|%d|%s]\n",
		host, port, rows, cols, command)
	var hijack types.HijackedResponse
	var execID string
	execID, hijack, err = dkcli.ContainerExecAttach(containerID, command, uint(rows), uint(cols))
	if err != nil {
		_ = ws.WriteMessage(websocket.TextMessage, []byte(err.Error()))
		return
	}
	defer hijack.Close()

	logging.Logger.Printf("ws.connect.docker.complete [%s:%s|%d|%d|%s]\n",
		host, port, rows, cols, command)
	exit := make(chan bool, 2)

	// 数据交换
	go wsRecvMsg(ws, &hijack, exit)
	go wsSendMsg(ws, hijack.Conn, exit)
	<- exit

	ws.SetWriteDeadline(time.Now().Add(writeWait))
	ws.WriteMessage(websocket.CloseMessage,
		websocket.FormatCloseMessage(websocket.CloseNormalClosure, ""))
	// 执行 exec 销毁操作
	dkcli.ContainerExecKill(containerID, execID)
}