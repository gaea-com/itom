package itom

import (
	"go-itom-api/pkg/dockercli"
)

type ItomTaskType string

const (
	// Docker 并行的 docker 调用
	// 可并行执行的的 docker 控制
	ApiDocker ItomTaskType = "docker"
	// DockerTask 顺序的 docker 调用
	// 可并行执行带有顺序的 docker 控制
	ApiDockerTask ItomTaskType = "docker_task"
	// 实例导入任务
	// 可并行执行的 实例导入 任务
	InstanceLoadTask ItomTaskType = "instance_load"
)

// docker 请求结构基础格式
type DockerRequire struct {
	TaskType  string        `json:"task_type" name:"task_type" mapstructure:"task_type"`
	Parameter []interface{} `json:"parameter" name:"parameter" mapstructure:"parameter"`
}

// docker task 请求结构基础格式
type DockerTaskRequire struct {
	TaskType  string          `json:"task_type" name:"task_type" mapstructure:"task_type"`
	Parameter [][]interface{} `json:"parameter" name:"parameter" mapstructure:"parameter"`
}

// taskInfo 并行任务参数
type taskInfo struct {
	Ip       string      `json:"ip" name:"ip" mapstructure:"ip"`
	Type     string      `json:"type" name:"type" mapstructure:"type"`
	WaitTime int         `json:"sleep_time" name:"sleep_time" mapstructure:"sleep_time"`
	Option   interface{} `json:"option" name:"option" mapstructure:"option"`
}

// imagePullOption 镜像拉取选项
type imagePullOption struct {
	Image string `json:"name_version" name:"name_version" mapstructure:"name_version"`
}

// imageDeleteOption 镜像删除选项
type imageDeleteOption struct {
	Image string `json:"id" name:"id" mapstructure:"id"`
}

// containerCreateOption 容器创建选项
type containerCreateOption struct {
	Image       string `json:"image" name:"image" mapstructure:"image"`
	Hostname    string `json:"hostname" name:"hostname" mapstructure:"hostname"`
	NetworkMode string `json:"network_mode" name:"network_mode" mapstructure:"network_mode"`
	Command     string `json:"command" name:"command" mapstructure:"command"`
	Detach      string `json:"detach" name:"detach" mapstructure:"detach"`
	Volumes     map[string]struct {
		Bind string `json:"bind" name:"bind" mapstructure:"bind"`
		Mode string `json:"mode" name:"mode" mapstructure:"mode"`
	} `json:"volumes" name:"volumes" mapstructure:"volumes"`
	Env map[string]string `json:"environment" name:"environment" mapstructure:"environment"`
}

// containerStart 容器启动参数
type containerStartOption struct {
	ContainerID string `json:"id" name:"id" mapstructure:"id"`
}

// containerStop 容器启动参数
type containerStopOption struct {
	ContainerID string `json:"id" name:"id" mapstructure:"id"`
	Timeout     int    `json:"timeout" name:"timeout" mapstructure:"timeout"`
}

// containerDelete 容器启动参数
type containerDeleteOption struct {
	ContainerID string `json:"id" name:"id" mapstructure:"id"`
}

// containerLogs 容器启动参数
type containerLogsOption struct {
	ContainerID string `json:"id" name:"id" mapstructure:"id"`
}

// containerCmd 容器启动参数
type containerCmdOption struct {
	ContainerID string `json:"id" name:"id" mapstructure:"id"`
	Cmd         string `json:"cmd" name:"cmd" mapstructure:"cmd"`
}

// containerStatus 容器启动参数
type containerStatusOption struct {
	ContainerID string `json:"id" name:"id" mapstructure:"id"`
}

// containerUploadFile 容器上传文件操作
type containerUploadFile struct {
	ContainerID   string `json:"id" name:"id" mapstructure:"id"`
	LocalPath     string `json:"src" name:"src" mapstructure:"src"`
	ContainerPath string `json:"dest" name:"dest" mapstructure:"dest"`
}

// containerListOption 容器列表查询参数
type containerListOption struct {
	FilterStatus dockercli.ContainerStatus `json:"filter_status" name:"filter_status" mapstructure:"filter_status"`
}

// instance 请求结构基础格式
type InstanceLoadRequire struct {
	TaskType  string        `json:"task_type" name:"task_type" mapstructure:"task_type"`
	Parameter []interface{} `json:"parameter" name:"parameter" mapstructure:"parameter"`
}
