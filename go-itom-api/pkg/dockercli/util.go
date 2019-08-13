package dockercli

import (
	"encoding/base64"
	"encoding/json"
	distreference "github.com/docker/distribution/reference"
	"github.com/docker/docker/api/types"
	"github.com/docker/docker/api/types/reference"
	"github.com/docker/docker/api/types/strslice"
	"strings"
)

const (
	// CmdSplitFlag 容器命令的分隔符
	// eg: 输入 "/bin/bash{|}-c{|}'echo hello'"，会被切割成 []string{"/bin/bash","-c","echo hello"}
	CmdSplitFlag string = "{|}"
)

// CmdParse 容器启动命令解析
func CmdParse(cmd string) strslice.StrSlice {
	newCmd := make(strslice.StrSlice, 0)
	for _, args := range strings.Split(cmd, CmdSplitFlag) {
		if strings.TrimSpace(args) == "" {
			continue
		}
		newCmd = append(newCmd, args)
	}
	return newCmd
}

// RegistryAuthParse 生成认证
func RegistryAuthParse(username, password, serverAddress string) string {
	authConfig := types.AuthConfig{
		Username:      username,
		Password:      password,
		ServerAddress: serverAddress,
	}
	encodedJSON, err := json.Marshal(authConfig)
	if err != nil {
		panic(err)
	}
	return base64.URLEncoding.EncodeToString(encodedJSON)
}

// Docker 容器镜像如果是官方镜像，则自动添加 prefix "docker.io/"
func ParseDockerImage(image string) string {
	_, _, err := reference.Parse(image)
	if err != nil {
		if err.Error() == "repository name must be canonical" {
			named, err := distreference.ParseNormalizedNamed(image)
			if err != nil {
				return image
			}
			return named.String()
		}
	}
	return image
}
