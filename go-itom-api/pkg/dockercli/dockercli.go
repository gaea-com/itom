// Dockercli 简单的 docker 客户端封装
// 参考文档：
// go client sdk : https://godoc.org/github.com/docker/docker/client
// docker api    : https://docs.docker.com/engine/api/v1.39/
package dockercli

import (
	"context"
	"encoding/base64"
	"fmt"
	"github.com/docker/docker/api/types"
	"github.com/docker/docker/api/types/container"
	"github.com/docker/docker/api/types/filters"
	"github.com/docker/docker/api/types/network"
	"github.com/docker/docker/client"
	logging "go-itom-api/pkg/logging"
	"go-itom-api/pkg/util"
	"io"
	"io/ioutil"
	"os"
	"regexp"
	"strconv"
	"strings"
	"time"
)

// DockerResponse docker 返回的结构体信息
// TODO: 后期优化该结构
type DockerResponse struct {
	Status            bool             `json:"status"`
	Err               *string          `json:"error,omitempty"`
	Msg               *string          `json:"stdout,omitempty"`
	ContainerID       string           `json:"container_id,omitempty"`
	ID                string           `json:"id,omitempty"`
	ShortID           string           `json:"short_id,omitempty"`
	Name              string           `json:"name,omitempty"`
	ContainerLogs     string           `json:"container_log,omitempty"`
	ContainerHostname string           `json:"hostname,omitempty"`
	ContainerCmd      string           `json:"cmd,omitempty"`
	FileSrc           string           `json:"src,omitempty"`
	FileDest          string           `json:"dest,omitempty"`
	ContainerList     *[]ContainerInfo `json:"container_list,omitempty"`
	ImageList         *[]ImageInfo     `json:"image_list,omitempty"`
	ImageName         string           `json:"image_name,omitempty"`
	IP                string           `json:"ip,omitempty"`
}

// ContainerInfo 查询容器列表时的容器信息
type ContainerInfo struct {
	ContainerID     string `json:"id"`
	ContainerName   string `json:"name"`
	ContainerStatus string `json:"container_status"`
	Image           string `json:"image"`
	Hostname        string `json:"hostname"`
	FinishedAt      string `json:"FinishedAt"`
}

// ImageInfo 查询镜像列表时的镜像信息
type ImageInfo struct {
	ImageName    []string `json:"name_version"`
	ImageLongID  string   `json:"id"`
	ImageShortID string   `json:"short_id"`
}

// DockerSampleClient docker 简易客户端
type DockerSampleClient struct {
	Client       *client.Client
	ctx          context.Context
	RegistryAuth string
}

// parseHost 解析成 tcp://IP:PORT 格式
func parseHost(host string) string {
	protoAddrParts := strings.SplitN(host, ":", 3)
	switch len(protoAddrParts) {
	case 1:
		// only ip
		host = fmt.Sprintf("tcp://%s:2375", host)
	case 2:
		// ip:port
		host = fmt.Sprintf("tcp://%s", host)
	}
	return host
}

// parseIpPort 将 tcp://IP:PORT 解析成 IP 和 PORT
func parseIpPort(host string) (string, int) {
	_host := strings.SplitN(host[6:], ":", 2)
	_port, _ := strconv.Atoi(_host[1])
	return _host[0], _port
}

// Init 初始化 docker 客户端
func (d *DockerSampleClient) Init(host, version string) (err error) {
	d.Client, err = client.NewClient(parseHost(host), version, nil, make(map[string]string))
	if err != nil {
		logging.Logger.Printf("docker.init.error [%s]\n", err.Error())
		return
	}
	d.ctx = context.TODO()

	// 检查对应端口是否开启
	_ip, _port := parseIpPort(parseHost(host))
	if !util.DialPort(_ip, _port) {
		err = fmt.Errorf("docker.init.error [%s]",
			fmt.Sprintf("connect timeout[%s:%d]", host, _port))
		return
	}
	return
}

// UploadFromFile 读取本地文件上传至容器
func (d *DockerSampleClient) ContainerUploadFromFile(containerID, src, dest string) (info DockerResponse) {
	var f *os.File
	var err error
	f, err = os.Open(src)
	if err != nil {
		logging.Logger.Printf("docker.fileUpload.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	defer f.Close()
	err = d.Client.CopyToContainer(
		context.Background(),
		containerID,
		dest,
		f,
		types.CopyToContainerOptions{AllowOverwriteDirWithFile: true})
	if err != nil {
		logging.Logger.Printf("docker.fileUpload.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	info = DockerResponse{
		Status:   true,
		ID:       containerID,
		FileSrc:  src,
		FileDest: dest,
		Err:      util.String2Ptr("upload failed."),
	}
	return
}

type ContainerStatus string

const (
	CSAll     ContainerStatus = "all"
	CSExited                  = "exited"
	CSRunning                 = "running"
)

// ContainerList 容器列表筛选
func (d *DockerSampleClient) ContainerList(status ContainerStatus) (
	info DockerResponse) {

	var cloptions types.ContainerListOptions
	switch status {
	case CSAll:
		cloptions.All = true
	case CSExited:
		cloptions.Quiet = true
		cloptions.Filters = filters.NewArgs()
		cloptions.Filters.Add("status", CSExited)
	case CSRunning:
		cloptions.Filters = filters.NewArgs()
		cloptions.Filters.Add("status", CSRunning)
	default:
		cloptions.Quiet = true
		cloptions.Filters = filters.NewArgs()
		cloptions.Filters.Add("status", CSExited)
	}

	// status
	// all| created|restarting|running|removing|paused|exited|dead
	var containers []types.Container
	var err error
	containers, err = d.Client.ContainerList(
		d.ctx,
		cloptions)
	if err != nil {
		logging.Logger.Printf("docker.containerList.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	_containerList := make([]ContainerInfo, 0)
	var _containerInfo types.ContainerJSON
	var hostname, finishedAt string
	for _, containerInfo := range containers {
		_containerInfo, err = d.ContainerInspect(containerInfo.ID)
		if err != nil {
			hostname = ""
			finishedAt = ""
		} else {
			hostname = _containerInfo.Config.Hostname
			finishedAt = util.Utc2Bj(_containerInfo.State.FinishedAt)
		}
		_containerList = append(_containerList, ContainerInfo{
			ContainerID: util.StringLeftTrim(`^sha256:`, containerInfo.ID),
			//ContainerName:   containerInfo.Names[0],
			ContainerName:   hostname,
			ContainerStatus: containerInfo.State,
			Image:           util.StringLeftTrim(`^docker.io/library/`, containerInfo.Image),
			Hostname:        hostname,
			FinishedAt:      finishedAt,
		})
	}
	info.Status = true
	info.ContainerList = &_containerList
	return
}

// imagePullBackground 拉取镜像，不返回任何数据
func (d *DockerSampleClient) imagePullBackground(image string) (err error) {
	var reader io.ReadCloser
	reader, err = d.Client.ImagePull(d.ctx, image, types.ImagePullOptions{
		RegistryAuth: d.RegistryAuth,
	})
	if err != nil {
		logging.Logger.Printf("docker.imagePull.error [%s|%s]\n", image, err.Error())
		return
	}
	_, err = io.Copy(ioutil.Discard, reader)
	if err != nil {
		logging.Logger.Printf("docker.imagePull.error [%s|%s]\n", image, err.Error())
		return
	}
	return
}

// ImagePull 容器镜像拉取
func (d *DockerSampleClient) ImagePull(image string, username, password, domain *string) (info DockerResponse) {
	_image := image
	image = ParseDockerImage(image)
	info.Name = _image
	if username != nil && password != nil && domain != nil {
		d.RegistryAuth = RegistryAuthParse(*username, *password, *domain)
	}
	var err error
	if err = d.imagePullBackground(image); err != nil {
		info.Err = util.String2Ptr(err.Error())
		return
	}

	var imageInfo types.ImageInspect
	imageInfo, err = d.ImageInspect(image)
	if err != nil {
		logging.Logger.Printf("docker.imagePull.error [%s|%s]\n", _image, err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}

	info = DockerResponse{
		Status:  true,
		ID:      util.StringLeftTrim(`^sha256:`, imageInfo.ID),
		ShortID: util.StringLeftTrim(`^sha256:`, imageInfo.ID)[:10],
		Name:    _image,
	}
	return
}

// ImageList 镜像列表获取
// 当前默认为查询全部镜像列表
func (d *DockerSampleClient) ImageList() (info DockerResponse) {
	var imageList []types.ImageSummary
	var err error
	imageList, err = d.Client.ImageList(d.ctx, types.ImageListOptions{
		All: true,
	})
	if err != nil {
		logging.Logger.Printf("docker.imageList.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	_imageList := make([]ImageInfo, 0)
	for _, imageInfo := range imageList {
		_imageList = append(_imageList, ImageInfo{
			ImageName:    imageInfo.RepoTags,
			ImageLongID:  util.StringLeftTrim(`^sha256:`, imageInfo.ID),
			ImageShortID: util.StringLeftTrim(`^sha256:`, imageInfo.ID)[:10],
		})
	}
	info.Status = true
	info.ImageList = &_imageList
	return
}

// ImageRemove 镜像删除
func (d *DockerSampleClient) ImageRemove(image string) (info DockerResponse) {
	var err error
	_, err = d.Client.ImageRemove(d.ctx, image, types.ImageRemoveOptions{})
	if err != nil {
		logging.Logger.Printf("docker.imageRemove.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	info = DockerResponse{
		Status: true,
		Msg:    util.String2Ptr(fmt.Sprintf("Removed %s success.", image)),
		Name:   image,
	}
	return
}

// ContainerDelete 容器删除
func (d *DockerSampleClient) ContainerDelete(containerID string) (info DockerResponse) {
	var err error
	err = d.Client.ContainerRemove(d.ctx, containerID, types.ContainerRemoveOptions{})
	if err != nil {
		if client.IsErrContainerNotFound(err) {
			err = nil
		} else {
			logging.Logger.Printf("docker.containerRemove.error [%s]\n", err.Error())
			info.Err = util.String2Ptr(err.Error())
			return
		}
	}
	info = DockerResponse{
		Status:      true,
		Msg:         util.String2Ptr(fmt.Sprintf("Removed %s success.", containerID)),
		ContainerID: containerID,
	}
	return
}

// ContainerRun 容器创建并运行
func (d *DockerSampleClient) ContainerRun(option ContainerCreateOption) (info DockerResponse) {
	var config *container.Config
	var chost *container.HostConfig
	var cnetwork *network.NetworkingConfig
	var cname string
	var err error
	config, chost, cnetwork, cname = option.parse()
	var _container container.ContainerCreateCreatedBody
	_container, err = d.Client.ContainerCreate(d.ctx, config, chost, cnetwork, cname)
	if err != nil {
		logging.Logger.Printf("docker.containerCreate.error [%s]", err.Error())
		// 拉取镜像
		if err = d.imagePullBackground(config.Image); err != nil {
			info.Err = util.String2Ptr(err.Error())
			return
		}
		_container, err = d.Client.ContainerCreate(d.ctx, config, chost, cnetwork, cname)
		if err != nil {
			logging.Logger.Printf("docker.containerCreate.error [%s]\n", err.Error())
			info.Err = util.String2Ptr(err.Error())
			return
		}
	}

	err = d.Client.ContainerStart(d.ctx, _container.ID, types.ContainerStartOptions{})
	if err != nil {
		logging.Logger.Printf("docker.containerCreate.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	info = DockerResponse{
		Status:            true,
		Msg:               util.String2Ptr(fmt.Sprintf("Container %s create success.", option.Image)),
		ID:                _container.ID,
		ShortID:           _container.ID[:12],
		ContainerHostname: config.Hostname,
		Name:              option.Image,
		ImageName:         option.Image,
	}
	return
}

// ContainerStart 容器启动
func (d *DockerSampleClient) ContainerStart(contaienrID string) (info DockerResponse) {
	var err error
	err = d.Client.ContainerStart(d.ctx, contaienrID, types.ContainerStartOptions{})
	if err != nil {
		logging.Logger.Printf("docker.containerStart.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	info = DockerResponse{
		Status:      true,
		ContainerID: contaienrID,
		Msg:         util.String2Ptr(fmt.Sprintf("Container %s start success.", contaienrID)),
	}
	return
}

// ContainerStop 容器停止
func (d *DockerSampleClient) ContainerStop(containerID string, timeout *time.Duration) (info DockerResponse) {
	var err error
	err = d.Client.ContainerStop(d.ctx, containerID, timeout)
	if err != nil {
		if regexp.MustCompile(`.*(No such container?)`).MatchString(err.Error()) {
			err = nil
		} else {
			logging.Logger.Printf("docker.containerStop.error [%s]\n", err.Error())
			info.Err = util.String2Ptr(err.Error())
			return
		}
	}
	info = DockerResponse{
		Status: true,
		ID:     containerID,
		Msg:    util.String2Ptr(fmt.Sprintf("Container %s stop success.", containerID)),
	}
	return
}

// ContainerLogs 获取容器日志
// 当前默认返回容器全部日志
func (d *DockerSampleClient) ContainerLogs(containerID string) (info DockerResponse) {
	var reader io.ReadCloser
	var err error
	info.ContainerID = containerID
	reader, err = d.Client.ContainerLogs(d.ctx, containerID, types.ContainerLogsOptions{
		ShowStdout: true,
		ShowStderr: true,
		Tail:       "all",
	})
	if err != nil {
		logging.Logger.Printf("docker.containerLogs.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	var dataByte []byte
	dataByte, err = ioutil.ReadAll(reader)
	if err != nil {
		info.Err = util.String2Ptr(err.Error())
		return
	}

	info = DockerResponse{
		Status:        true,
		ContainerID:   containerID,
		ContainerLogs: base64.StdEncoding.EncodeToString(dataByte),
	}
	return
}

// ContainerCmd 容器内执行命令
// TODO: 后期会尝试加入命令的执行 ID 用于管理容器内执行的命令。
func (d *DockerSampleClient) ContainerCmd(containerID, cmd string, timeout *time.Duration) (info DockerResponse) {
	var containerName string
	var err error
	var resp types.IDResponse
	info.ContainerID = containerID
	// 默认添加 tty 参数，测试过程中，好多命令依赖 tty
	resp, err = d.Client.ContainerExecCreate(d.ctx, containerID, types.ExecConfig{
		Tty:          true,
		AttachStderr: true,
		AttachStdout: true,
		Cmd:          CmdParse(cmd),
	})
	if err != nil {
		logging.Logger.Printf("docker.containerCmd.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}
	var response types.HijackedResponse
	response, err = d.Client.ContainerExecAttach(context.Background(), resp.ID, types.ExecConfig{
		Tty:          true,
		AttachStderr: true,
		AttachStdout: true,
		Cmd:          CmdParse(cmd),
	})
	if err != nil {
		logging.Logger.Printf("docker.containerCmd.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}

	defer response.Close()
	ch := make(chan hijackResponse, 1)
	go readHijackedAll(response, ch)

	var timeoutS string
	if timeout != nil {
		timeoutS = timeout.String()
		select {
		case resp := <-ch:
			if resp.Err != nil {
				logging.Logger.Printf("docker.containerCmd.error [%s]\n", resp.Err.Error())
				info.Err = util.String2Ptr(resp.Err.Error())
				info.Name = containerName
				info.ContainerCmd = cmd
				return
			}
			info = DockerResponse{
				Status:       true,
				ContainerID:  containerID,
				Name:         containerName,
				ContainerCmd: cmd,
				Msg:          util.String2Ptr(resp.Data),
			}
		case <-time.After(*timeout):
			info.Err = util.String2Ptr(
				fmt.Sprintf("timeout[%s]", timeout.String()))
			info.ContainerCmd = cmd
			logging.Logger.Printf(fmt.Sprintf("timeout[%s]", timeout.String()))
		}
	} else {
		timeoutS = "0s"
		resp := <-ch
		if resp.Err != nil {
			logging.Logger.Printf("docker.containerCmd.error [%s]\n", resp.Err.Error())
			info.Err = util.String2Ptr(resp.Err.Error())
			info.Name = containerName
			info.ContainerCmd = cmd
		}
		info = DockerResponse{
			Status:       true,
			ContainerID:  containerID,
			Name:         containerName,
			ContainerCmd: cmd,
			Msg:          util.String2Ptr(resp.Data),
		}
	}

	// check exit code
	var execResponse types.ContainerExecInspect
	execResponse, err = d.Client.ContainerExecInspect(d.ctx, resp.ID)
	if err != nil {
		info.Status = false
		if info.Err == nil {
			info.Err = info.Msg
		}
		logging.Logger.Printf("docker.containerCmd.error [%s]\n", info.Err)
		return
	}
	if execResponse.ExitCode != 0 {
		info.Status = false
		if info.Err == nil {
			info.Err = info.Msg
		}
		return
	}
	if execResponse.Running {
		info.Status = false
		info.Err = util.String2Ptr(
			fmt.Sprintf("itom wait timeout[%s], but cmd still running",
				timeoutS))
		return
	}

	return
}

// hijackResponse ws 返回数据
type hijackResponse struct {
	Data string
	Err  error
}

// readHijackedAll 读取 ws 中的全部数据
func readHijackedAll(resp types.HijackedResponse, ch chan hijackResponse) {
	data, err := ioutil.ReadAll(resp.Reader)
	if err != nil {
		logging.Logger.Printf("docker.readRespponse.error [%s]\n", err.Error())
	}
	ch <- hijackResponse{
		Data: string(data),
		Err:  err,
	}
	return
}

// ContainerStatus 容器状态检查
// 目前只返回容器的运行状态
func (d *DockerSampleClient) ContainerStatus(containerID string) (info DockerResponse) {
	var containerName string
	var err error
	var containerInfo types.ContainerJSON
	containerInfo, err = d.Client.ContainerInspect(d.ctx, containerID)
	if err != nil {
		logging.Logger.Printf("docker.containerStatus.error [%s]\n", err.Error())
		info.Err = util.String2Ptr(err.Error())
		return
	}

	if containerInfo.ContainerJSONBase == nil {
		info.Status = true
		info.ContainerID = containerID
		info.Name = containerName
		info.Err = util.String2Ptr(fmt.Sprintf("docker.containerStatus.error [%s]", "none"))
		return
	}
	info = DockerResponse{
		Status:      true,
		ContainerID: containerID,
		Name:        containerName,
		Msg:         util.String2Ptr(containerInfo.State.Status),
	}
	return
}

// ContainerInspect 容器数据查询
func (d *DockerSampleClient) ContainerInspect(containerID string) (containerInfo types.ContainerJSON, err error) {
	containerInfo, err = d.Client.ContainerInspect(d.ctx, containerID)
	if err != nil {
		return
	}
	return
}

// ImageInspect 镜像数据查询
func (d *DockerSampleClient) ImageInspect(containerID string) (imageInfo types.ImageInspect, err error) {
	imageInfo, _, err = d.Client.ImageInspectWithRaw(d.ctx, containerID)
	if err != nil {
		return
	}
	return
}

// ContainerExecAttach
// 建立 docker 与用户的连接，
// 使用 /bin/sh /bin/bash 等进入容器进行相关操作
// TODO: 后期需要记录 execID 用于管理用户连接的 session
func (d *DockerSampleClient) ContainerExecAttach(containerID, cmd string, cols, rows uint) (execID string, hijack types.HijackedResponse, err error) {
	var resp types.IDResponse
	// 默认添加 tty 参数，测试过程中，好多命令依赖 tty
	resp, err = d.Client.ContainerExecCreate(d.ctx, containerID, types.ExecConfig{
		Tty:          true,
		AttachStdin:  true,
		AttachStderr: true,
		AttachStdout: true,
		Cmd:          CmdParse(cmd),
	})
	if err != nil {
		logging.Logger.Printf("docker.execAttach.error [%s]\n", err.Error())
		return
	}
	execID = resp.ID
	// 启动会话
	hijack, err = d.Client.ContainerExecAttach(d.ctx, resp.ID, types.ExecConfig{
		Tty: true,
	})
	if err != nil {
		logging.Logger.Printf("docker.execAttach.error [%s]\n", err.Error())
		return
	}
	// 更新窗口大小
	if cols > 0 && rows > 0 {
		err = d.Client.ContainerExecResize(d.ctx, resp.ID, types.ResizeOptions{
			Height: cols,
			Width:  rows,
		})
		if err != nil {
			logging.Logger.Printf("docker.execAttach.error [%s]\n", err.Error())
			return
		}
	}
	logging.Logger.Printf("docker.execAttach.success [%s|%s]\n", containerID, execID)
	return
}

// ContainerExecKill 用于清理
func (d *DockerSampleClient) ContainerExecKill(containerID, execID string) (err error) {
	var info types.ContainerExecInspect
	info, err = d.Client.ContainerExecInspect(d.ctx, execID)
	if err != nil {
		logging.Logger.Printf("docker.execKill.error [%s]\n", err.Error())
		return
	}
	if info.Pid == 0 {
		return
	}
	_ = d.ContainerCmd(containerID, fmt.Sprintf("kill{|}-9{|}%d", info.Pid), util.TimeDuration2Ptr(5))
	logging.Logger.Printf("docker.execKill.success [%s|%s]", containerID, execID)
	return
}

// ContainerDownloadFile 从容器中下载指定路径下的文件
func (d *DockerSampleClient) ContainerDownloadFile(containerID, srcPath string) (
	r io.ReadCloser, stat types.ContainerPathStat, err error) {

	r, stat, err = d.Client.CopyFromContainer(d.ctx, containerID, srcPath)
	if err != nil {
		logging.Logger.Printf("docker.download.error [%s]\n", err.Error())
		return
	}
	return
}

// ContainerUploadFile 往容器中上传文件
func (d *DockerSampleClient) ContainerUploadFile(
	containerID, path string,
	content io.Reader,
	options types.CopyToContainerOptions) (err error) {

	err = d.Client.CopyToContainer(d.ctx, containerID, path, content, options)
	if err != nil {
		logging.Logger.Printf("docker.upload.error [%s]\n", err.Error())
		return
	}
	return
}
