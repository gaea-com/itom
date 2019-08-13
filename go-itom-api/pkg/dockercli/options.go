package dockercli

import (
	"fmt"
	"github.com/docker/docker/api/types/container"
	"github.com/docker/docker/api/types/network"
)

type ContainerCreateOption struct {
	// Image 镜像名称
	Image string `json:"image" name:"image" mapstructure:"image"`
	// Hostname 容器的 hostname 设置
	Hostname string `json:"hostname" name:"hostname" mapstructure:"hostname"`
	// NetworkMode 容器网络设置 (默认为 host)
	NetworkMode string `json:"network_mode" name:"network_mode" mapstructure:"network_mode"`
	// Command 容器启动命令 (默认为空字符串)
	Command string `json:"command" name:"command" mapstructure:"command"`
	// Volumes 容器磁盘挂载设置
	Volumes typeVolumes `json:"volumes" name:"volumes" mapstructure:"volumes"`
	// Env 容器环境变量设置
	Env map[string]string `json:"environment" name:"environment" mapstructure:"environment"`
}

type typeVolumes map[string]struct {
	Bind string `json:"bind" name:"bind" mapstructure:"bind"`
	Mode string `json:"mode" name:"mode" mapstructure:"mode"`
}

// Parse 容器磁盘解析
func volumesParse(volumes typeVolumes) []string {
	binds := make([]string, 0)
	for hostPath, bindInfo := range volumes {
		binds = append(binds, fmt.Sprintf("%s:%s:%s",
			hostPath, bindInfo.Bind, bindInfo.Mode))
	}
	return binds
}

func envParse(envMap map[string]string) []string {
	env := make([]string, 0)
	for k, v := range envMap {
		env = append(env, fmt.Sprintf("%s=%s", k, v))
	}
	return env
}

// Parse 配置解析
// 添加部分特殊配置
func (c *ContainerCreateOption) parse() (
	config *container.Config,
	chost *container.HostConfig,
	cnetwork *network.NetworkingConfig,
	cname string) {

	config = &container.Config{
		Image:    c.Image,
		Hostname: c.Hostname,
		Cmd:      CmdParse(c.Command),
		Env:      envParse(c.Env),
	}

	chost = &container.HostConfig{
		NetworkMode: container.NetworkMode(c.NetworkMode),
		Binds:       volumesParse(c.Volumes),
		// TODO: 目前如下配置写死
		LogConfig: container.LogConfig{
			Type: "json-file",
			Config: map[string]string{
				"max-size": "10m",
				"max-file": "3",
			},
		},
	}

	cnetwork = &network.NetworkingConfig{}

	return
}
