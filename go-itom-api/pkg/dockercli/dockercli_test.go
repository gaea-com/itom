package dockercli

import (
	"context"
	"encoding/json"
	"github.com/docker/docker/api/types"
	"github.com/docker/docker/client"
	"go-itom-api/pkg/util"
	"io"
	"os"
	"strings"
	"testing"
)

const (
	testHost string = "192.168.131.185"
)

func TestSplit(t *testing.T) {
	newArgs := make([]string, 0)
	for _, args := range strings.Split("/bin/bash -c  'while true;do sleep 10;done'", " ") {
		if strings.TrimSpace(args) == "" {
			continue
		}
		newArgs = append(newArgs, args)
	}
	t.Log(newArgs)
}

func TestDockerParse(t *testing.T) {
	t.Log(client.ParseHost("tcp://192.168.120.1:2375"))
}

// 测试 webshell 的连接
func TestDockerAttachExec(t *testing.T) {

	var err error
	var dk *client.Client
	dk, err = client.NewClient(parseHost(testHost), "", nil, make(map[string]string))
	if err != nil {
		t.Error(err)
		return
	}

	var hijack types.HijackedResponse
	hijack, err = dk.ContainerExecAttach(context.TODO(), "", types.ExecConfig{
		Tty:          true,
		AttachStdin:  true,
		AttachStderr: true,
		AttachStdout: true,
		Cmd:          []string{"/bin/sh"},
	})
	if err != nil {
		t.Error(err.Error())
	}

	// webshell 连接
	go io.Copy(hijack.Conn, os.Stdin)
	go io.Copy(os.Stdout, hijack.Conn)

}

func TestDockerGetContainerList(t *testing.T) {

	var cli DockerSampleClient
	if err := cli.Init(testHost, ""); err != nil {
		t.Error(err.Error())
		return
	}
	b, _ := json.Marshal(cli.ContainerList("running"))
	t.Log(string(b))

}

func TestDockerCmd(t *testing.T) {
	var cli DockerSampleClient
	if err := cli.Init(testHost, ""); err != nil {
		t.Error(err.Error())
		return
	}

	tester := []map[bool]string{
		// 正常命令测试
		{
			true: `sh{|}-c{|}echo "hello world"; exit 0`,
		},
		// 异常命令测试（正常命令，退出状态码不为0）
		{
			false: `sh{|}-c{|}echo "hello world"; exit 127`,
		},
		// 超时命令测试
		{
			false: `sh{|}-c{|}echo "hello world"; sleep 20`,
		},
		// 异常命令测试（错误命令）
		{
			false: `wrong_cmd`,
		},
	}
	testContainerID := "2560b9b0519b"

	for _, cmdInfo := range tester {
		for except, cmd := range cmdInfo {
			r := cli.ContainerCmd(testContainerID, cmd, util.TimeDuration2Ptr(5))
			b, _ := json.Marshal(r)
			if r.Status != except {
				t.Error("=======================+ Error +========================")
				t.Error("want: ", except, "got: ", r.Status, "details:", string(b))
				t.Error("========================================================")
			} else {
				t.Log("======================+ Success +=======================")
				t.Log("details:", string(b))
				t.Log("========================================================")
			}
		}
	}

}
