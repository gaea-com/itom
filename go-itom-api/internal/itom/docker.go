package itom

import (
	"encoding/base64"
	"encoding/json"
	"fmt"
	"github.com/mitchellh/mapstructure"
	"github.com/panjf2000/ants"
	"go-itom-api/pkg/config"
	"go-itom-api/pkg/dockercli"
	logging "go-itom-api/pkg/logging"
	"go-itom-api/pkg/rediscli"
	"go-itom-api/pkg/util"
	"net/url"
	"strings"
	"sync"
)

// DockerMultiController 容器批量操作控制器
func DockerMultiController(taskID string, require []interface{}) {
	var err error
	var wg sync.WaitGroup
	// TODO: 测试完成后，此处的协程池大小修改为可配置项
	p, _ := ants.NewPoolWithFunc(10, func(task interface{}) {
		info := dockerControl(apiDockerArgsParse(task))
		b, _ := json.Marshal(info)
		CachePush(taskID, string(b))
		wg.Done()
	})
	defer p.Release()
	for _, task := range require {
		wg.Add(1)
		// TODO: 创建协程的逻辑目前比较简单粗暴，后期需要加入错误处理流程
		if err = p.Invoke(task); err != nil {
			err = fmt.Errorf(DockerCreateGoroutineError, err.Error())
			logging.Logger.Println(err.Error())
		}
	}
	wg.Wait()
	return
}

func DockerMultiControllerSync(require []interface{}) (result []dockercli.DockerResponse) {
	var err error
	var wg sync.WaitGroup
	var mu sync.Mutex
	result = make([]dockercli.DockerResponse, 0)
	// TODO: 测试完成后，此处的协程池大小修改为可配置项
	p, _ := ants.NewPoolWithFunc(10, func(task interface{}) {
		info := dockerControl(apiDockerArgsParse(task))
		mu.Lock()
		defer mu.Unlock()
		result = append(result, info)
		wg.Done()
	})
	defer p.Release()
	for _, task := range require {
		wg.Add(1)
		// TODO: 创建协程的逻辑目前比较简单粗暴，后期需要加入错误处理流程
		if err = p.Invoke(task); err != nil {
			err = fmt.Errorf(DockerCreateGoroutineError, err.Error())
			logging.Logger.Println(err.Error())
		}
	}
	wg.Wait()
	return
}

// apiDockerArgsParse 将 interface{} 类型解析为 taskInfo 结构
func apiDockerArgsParse(args interface{}) taskInfo {
	var option taskInfo
	_ = mapstructure.Decode(args, &option)
	return option
}

// dockerControl docker 控制接口
// 执行错误的信息，会插入到指定的 redis 中
func dockerControl(task taskInfo) (info dockercli.DockerResponse) {
	var dk dockercli.DockerSampleClient
	var err error
	if err = dk.Init(task.Ip, config.Config.Docker.Version); err != nil {
		info.IP = task.Ip
		info.Err = util.String2Ptr(err.Error())
		return
	}

	// TODO: 数据格式需要重新设计，当前接口沿用旧格式。
	switch task.Type {
	case "image_pull":
		var option imagePullOption
		_ = mapstructure.Decode(task.Option, &option)
		u, p, s := getRegistryAccount(option.Image)
		info = dk.ImagePull(option.Image, u, p, s)

	case "image_list":
		info = dk.ImageList()

	case "image_delete":
		var option imageDeleteOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ImageRemove(option.Image)

	case "container_list":
		var option containerListOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerList(option.FilterStatus)

	case "container_create":
		var option dockercli.ContainerCreateOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerRun(option)

	case "container_start":
		var option containerStartOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerStart(option.ContainerID)

	case "container_stop":
		var option containerStopOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerStop(option.ContainerID, util.TimeDuration2Ptr(option.Timeout))

	case "container_delete":
		var option containerStopOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerDelete(option.ContainerID)

	case "container_log":
		var option containerLogsOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerLogs(option.ContainerID)

	case "container_cmd":
		var option containerCmdOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerCmd(option.ContainerID, option.Cmd, nil)

	case "container_status":
		var option containerStatusOption
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerStatus(option.ContainerID)

	case "docker_file_upload":
		var option containerUploadFile
		_ = mapstructure.Decode(task.Option, &option)
		info = dk.ContainerUploadFromFile(option.ContainerID, option.LocalPath, option.ContainerID)

	default:
		info.Err = util.String2Ptr(fmt.Sprintf("unknown docker.type[%s]", task.Type))
	}

	info.IP = task.Ip
	return
}

// 从缓存中读取镜像仓库的用户名和密码(目前仅为设置一种镜像仓库)
func getRegistryAccount(image string) (username, password, serverAddr *string) {
	domain_ := strings.SplitN(image, "/", 2)
	if len(domain_) != 2 {
		return nil, nil, nil
	}
	serverAddr = util.String2Ptr(domain_[0])
	urlB64 := rediscli.Redis.HGet(config.Config.Cache.ItomRegistryKey, "hub_url").String()
	if urlB64 == "" {
		return nil, nil, nil
	}
	urlEncode, err := base64.StdEncoding.DecodeString(urlB64)
	if err != nil {
		return nil, nil, nil
	}
	urlStr, err := url.PathUnescape(string(urlEncode))
	if err != nil {
		return nil, nil, nil
	}
	var domainS string
	domainS = util.StringLeftTrim(`^http://`, util.StringLeftTrim(`^https://`, urlStr))
	if *serverAddr != domainS {
		return nil, nil, nil
	}
	username = util.String2Ptr(
		rediscli.Redis.HGet(
			config.Config.Cache.ItomRegistryKey,
			"hub_account").String())
	password = util.String2Ptr(
		rediscli.Redis.HGet(
			config.Config.Cache.ItomRegistryKey,
			"hub_password").String())
	return
}
