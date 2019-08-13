package itomapi_test

import (
	"encoding/base64"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"strings"
	"testing"
	"time"

	"go-itom-api/pkg/rediscli"
)

const (
	// docker 原生接口
	ImagePullReq        string = `{"ip":"%s","type":"image_pull","option":{"name_version":"alpine:latest"}}`
	ImageListReq               = `{"ip":"%s","type":"image_list"}`
	ImageDeleteReq             = `{"ip":"%s","type":"image_delete","option":{"id":"%s"}}`
	ContainerListReq           = `{"ip":"%s","type":"container_list"}`
	ContainerCreateReq         = `{"ip":"%s","type":"container_create","option":{"image":"alpine:latest","hostname":"docker-api-test","network_mode":"host","command":"/bin/sh{|}-c{|}while true;do echo hey ; sleep 10;done","detach":"True"}}`
	ContainerStartReq          = `{"ip":"%s","type":"container_start","option":{"id":"%s"}}`
	ContainerStopReq           = `{"ip":"%s","type":"container_stop","option":{"id":"%s","timeout":2}}`
	ContainerDeleteReq         = `{"ip":"%s","type":"container_delete","option":{"id":"%s"}}`
	ContainerLogReq            = `{"ip":"%s","type":"container_log","option":{"id":"%s"}}`
	ContainerCmdReq            = `{"ip":"%s","type":"container_cmd","option":{"id":"%s", "cmd":"echo{|}'hello world'"}}`
	ContainerStatusReq         = `{"ip":"%s","type":"container_status","option":{"id":"%s"}}`
	DockerFileUploadReq        = `{"ip":"%s","type":"docker_file_upload","option":{"id":"%s","src":"/tmp/hello.txt","dest":"/tmp/hello.txt"}}`

	//docker task 接口
	TaskContainerCreateReq = `{"ip":"%s","type":"container_create","option":{"image":"alpine:latest","hostname":"docker-api-test","network_mode":"host","command":"/bin/sh{|}-c{|}while true;do echo hey ; sleep 10;done","detach":"True"}}`
	TaskContainerStartReq  = `{"ip":"%s","type":"container_start","option":{"id":"%s"}}`
	TaskContainerStopReq   = `{"ip":"%s","type":"container_stop","option":{"id":"%s","timeout":2}}`
	TaskContainerCmdReq    = `{"ip":"%s","type":"container_cmd","option":{"id":"%s", "cmd":"echo{|}'hello world'"}}`
)

// ArgsMaker 生成对应任务参数
func argsMaker(ip, containerId, currentReq string) (req string, err error) {
	switch currentReq {
	case ImagePullReq, ImageListReq, ContainerListReq, ContainerCreateReq:
		req = fmt.Sprintf(currentReq, ip)
	default:
		req = fmt.Sprintf(currentReq, ip, containerId)
	}
	return
}

// MakeDockerB64Args 将参数制作成 json 格式，并 base64
// TODO: 后期该接口的协议会做对应的改动
func makeDockerB64Args(
	ip string,
	target map[string]string,
	currentReq string,
	maker func(ip, containerId, currentReq string) (req string, err error)) string {
	// 拼接 json 数据
	if len(target) == 0 {
		return ""
	}

	args := "["
	var newArgs string
	var err error

	for _, containerId := range target {
		newArgs, err = maker(ip, containerId, currentReq)
		if err != nil {
			panic(err)
		}
		args = args + newArgs + ","
	}

	args = strings.TrimRight(args, ",") + "]"

	// 转换成 base64
	return base64.StdEncoding.EncodeToString([]byte(args))
}

// MakeDockerTaskB64Args 将参数制作成 json 格式，并 base64
// TODO: 后期该接口的协议会做对应的改动
func makeDockerTaskB64Args(
	ip string,
	target map[string]string,
	currentReq string,
	maker func(ip, containerId, currentReq string) (req string, err error)) string {

	args := "[["
	var newArgs string
	var err error
	for _, containerId := range target {
		newArgs, err = maker(ip, containerId, currentReq)
		if err != nil {
			panic(err)
		}
		args = args + newArgs + ","
	}
	args = strings.TrimRight(args, ",") + "]]"

	// 转换成 base64
	return base64.StdEncoding.EncodeToString([]byte(args))
}

// SendPostReq 发送 post 请求至接口中
func sendPostReq(url, body string) (respnse string, err error) {
	resp, err := http.Post(url,
		"application/x-www-form-urlencoded",
		strings.NewReader(body))
	if err != nil {
		return "", fmt.Errorf("send.post.error [%s]", err.Error())
	}

	defer resp.Body.Close()
	res, err := ioutil.ReadAll(resp.Body)
	if err != nil {
		return "", fmt.Errorf("read.respbody.error [%s]", err.Error())
	}

	return string(res), nil
}

// TestDockerAPI 容器相关接口测试
func TestDockerAPI(t *testing.T) {

	var err error

	// 可测试的容器列表
	dockerIpList := []string{
		"192.168.131.185",
	}

	taskList := []map[string]bool{
		{ImagePullReq: true},
		{ImageListReq: true},
		{ContainerCreateReq: true},
		{ContainerListReq: true},
		{ContainerStatusReq: true},
		{ContainerStopReq: true},
		{ContainerStatusReq: true},
		{ContainerStartReq: true},
		{ContainerLogReq: true},
		{ContainerCmdReq: true},
		{ContainerStopReq: true},
		{ContainerDeleteReq: true},
		{TaskContainerCreateReq: true},
		{TaskContainerStopReq: true},
		{ContainerStatusReq: true},
		{TaskContainerStartReq: true},
		{ContainerStatusReq: true},
		{TaskContainerCmdReq: true},
		{TaskContainerStopReq: true},
		{ContainerDeleteReq: true},
		{ImageDeleteReq: true},
		{ImageListReq: true},
	}

	reqType := map[string]string{
		// docker api 请求格式
		"docker": "type=docker&data=%s",
		// docker task api 请求格式
		"docker_task": "type=docker_task&data=%s",
	}

	url := "http://localhost:8000/docker_contrl"

	// 容器信息
	//{
	//    "${ContainerIP}": {
	//    		"${ContainerName}": "${ContainerID}"
	//    }
	//}
	containerInfo := make(map[string]string)
	containerInfo["docker-api-test"] = ""

	var newContainerInfo map[string]string

	// 接口测试
	var response string
	var b64Args string
	var req string
	for _, ip := range dockerIpList {
		for _, taskInfo := range taskList {
			for task, except := range taskInfo{
				switch task {
				case TaskContainerCreateReq, TaskContainerStartReq, TaskContainerStopReq, TaskContainerCmdReq:
					b64Args = makeDockerTaskB64Args(ip, containerInfo, task, argsMaker)
					req = reqType["docker_task"]
				default:
					b64Args = makeDockerB64Args(ip, containerInfo, task, argsMaker)
					req = reqType["docker"]
				}

				response, err = sendPostReq(url, fmt.Sprintf(req, b64Args))
				if err != nil {
					t.Error(b64Args, err.Error())
				}

				// 检测对应的操作是否合理
				newContainerInfo, err = redisTaskGet(response, task, fmt.Sprintf(req, b64Args), except)
				if err != nil {
					t.Error(err.Error())
				}

				if len(newContainerInfo) > 0 {
					containerInfo = newContainerInfo
				}

				time.Sleep(time.Second * 5)
			}
		}
	}
}

// redis 任务监听
func redisTaskGet(response, taskType, base64Data string, except bool) (newContainerInfo map[string]string, err error) {

	newContainerInfo = make(map[string]string)
	var taskInfo struct {
		TaskId string `json:"task_id"`
	}
	if err = json.Unmarshal([]byte(response), &taskInfo); err != nil {
		return
	}

	// 监听指定 redis 队列
	// 获取数据 start 开始，stop 结束。
	var buildErr error
	for {
		var listData []string
		listData, err = rediscli.Redis.BRPop(time.Second*0, taskInfo.TaskId).Result()
		if err != nil {
			err = fmt.Errorf("redis.lpop.error [%s]", err.Error())
			return
		}

		var taskStatus struct{
			TaskStatus string `json:"task_status" name:"task_status"`
		}
		if err = json.Unmarshal([]byte(listData[1]), &taskStatus); err != nil {
			return
		}
		switch taskStatus.TaskStatus {
		case "start":
			continue
			//fmt.Println("task.require: ", taskType)
			//fmt.Printf("get.task.start.response[%s] \n", listData[1])
		case "done":
			//fmt.Printf("get.task.done.response[%s] \n", listData[1])
			if buildErr != nil {
				err = buildErr
			}
			return
		default:
			//fmt.Printf("get.task.response[%s] \n", listData[1])
			var responseInfo struct {
				Status    bool   `json:"status" name:"status"`
				Stdout    string `json:"stdout" name:"stdout"`
				Id        string `json:"id" name:"id"`
				Name      string `json:"name" name:"name"`
				Hostname  string `json:"hostname" name:"hostname"`
				ImageName string `json:"image_name" name:"image_name"`
			}
			if err = json.Unmarshal([]byte(listData[1]), &responseInfo); err != nil {
				fmt.Printf("json.error [%s]\n", err.Error())
				return
			}
			if responseInfo.Status != except {
				fmt.Println("================================================")
				fmt.Println("require body:", taskType)
				fmt.Println("require base64:", base64Data)
				fmt.Println("except:", except, "but got:", responseInfo.Status)
				fmt.Println("response:", listData[1])
				fmt.Println("================================================")
				buildErr = fmt.Errorf("watch output")
			}else{
				var _type struct{
					Type string `name:"type"`
				}
				_ = json.Unmarshal([]byte(taskType), &_type)
				fmt.Println(_type.Type, ">>> pass")
			}
			if responseInfo.Id != "" {
				newContainerInfo[responseInfo.Hostname] = responseInfo.Id
			}
		}
		taskStatus.TaskStatus = ""
	}
}