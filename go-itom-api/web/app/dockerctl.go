package app

import (
	"archive/tar"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"github.com/docker/docker/api/types"
	"go-itom-api/pkg/config"
	"go-itom-api/pkg/dockercli"
	"io"
	"log"
	"mime/multipart"
	"net/http"
	"os"
	"path/filepath"
	"time"

	"go-itom-api/internal/itom"

	"github.com/gin-gonic/gin"
	logging "go-itom-api/pkg/logging"
)

type dockerPostForm struct {
	Type itom.DockerTaskType `form:"type" binding:"required"`
	Data string              `form:"data" binding:"required"`
}

// DockerPost 创建 docker 相关的任务数据
func DockerPost(c *gin.Context) (int, interface{}) {
	var formData dockerPostForm
	if err := c.ShouldBind(&formData); err != nil {
		return checkErr(err)
	}

	taskID, err := itom.AddDockerTask(formData.Type, formData.Data)
	if err != nil {
		return http.StatusBadRequest, gin.H{
			"error": err.Error(),
		}
	}

	return http.StatusOK, gin.H{
		"task_id": taskID,
	}
}

// DockerDownloadFile 下载容器中指定的文件或者目录
func DockerDownloadFile(c *gin.Context) {
	ip := c.Request.FormValue("target_ip")
	port := c.Request.FormValue("target_port")
	containerID := c.Request.FormValue("container_id")
	containerDownloadPath := c.Request.FormValue("path")
	if port == "" {
		port = "2375"
	}
	// 容器连接初始化
	var dk dockercli.DockerSampleClient
	err := dk.Init(fmt.Sprintf("tcp://%s:%s", ip, port), "")
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"message": err.Error()})
		return
	}
	var r io.ReadCloser
	var stat types.ContainerPathStat
	r, stat, err = dk.ContainerDownloadFile(containerID, containerDownloadPath)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"message": err.Error()})
		return
	}

	c.Writer.WriteHeader(http.StatusOK)
	c.Header("Content-Disposition", fmt.Sprintf("attachment; filename=%s.tar", stat.Name))
	c.Header("Content-Type", "application/x-tar")
	c.Header("Accept-Length", fmt.Sprintf("%d", stat.Size))
	io.Copy(c.Writer, r)
	return
}

// DockerUploadFile 上传文件到容器中指定的路径下
func DockerUploadFile(c *gin.Context) {
	ip := c.Request.FormValue("target_ip")
	port := c.Request.FormValue("target_port")
	containerID := c.Request.FormValue("container_id")
	containerUploadPath := c.Request.FormValue("path")
	if port == "" {
		port = "2375"
	}
	host := fmt.Sprintf("tcp://%s:%s", ip, port)

	// 检查容器是否在运行，不在运行的容器无法进行文件上传
	if err := checkContainerStatus(
		host, containerID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"message": err.Error()})
		return
	}

	// 读取文件，并落盘制作成 tar 文件
	file, header, err := c.Request.FormFile("file")
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"message": err.Error()})
		return
	}
	localPath, err := tarFile(file, header)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"message": err.Error()})
		return
	}
	defer cleanTempFile(localPath)

	// 将 tar 文件上传至容器指定路径下
	if err = containerUploadFile(host, containerID, localPath, containerUploadPath); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"message": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "success"})
	return
}

// checkContainerStatus 检查容器是否为 running 状态
func checkContainerStatus(host, containerID string) (err error) {
	// 容器连接初始化
	var dk dockercli.DockerSampleClient
	err = dk.Init(fmt.Sprintf("%s", host), "")
	if err != nil {
		return
	}
	var containerInfo types.ContainerJSON
	containerInfo, err = dk.ContainerInspect(containerID)
	if err != nil {
		return
	}
	if containerInfo.State == nil {
		err = fmt.Errorf("container.not.running")
		return
	}
	if !containerInfo.State.Running {
		err = fmt.Errorf("container.not.running")
		return
	}
	return
}

// 将本地文件上传至指定容器中
func containerUploadFile(host, containerID, src, dest string) (err error) {
	var dk dockercli.DockerSampleClient
	err = dk.Init(host, "")
	if err != nil {
		return
	}
	info := dk.ContainerUploadFromFile(containerID, src, dest)
	if !info.Status {
		err = fmt.Errorf(*info.Err)
		return
	}
	return
}

// tarFile 读取文件内容，将文件制作成 tar 文件
func tarFile(r io.Reader, fileInfo *multipart.FileHeader) (filePath string, err error) {
	filePath = filepath.Join(
		config.Config.System.TempDir,
		fmt.Sprintf("%d", time.Now().UnixNano()))
	f, err := os.Create(filePath)
	if err != nil {
		return
	}
	defer f.Close()
	tw := tar.NewWriter(f)
	hdr := &tar.Header{
		Name: fileInfo.Filename,
		Mode: 0600,
		Size: fileInfo.Size,
	}
	if err := tw.WriteHeader(hdr); err != nil {
		log.Fatal(err)
	}
	if _, err = io.Copy(tw, r); err != nil {
		return
	}
	return
}

// cleanTempFile 清理本地临时文件
func cleanTempFile(localPath string) {
	f, err := os.Stat(localPath)
	if err != nil {
		return
	}
	if f.IsDir() {
		return
	}
	_ = os.Remove(localPath)
}

type dockerApiPostForm struct {
	Type string `json:"type" name:"type" form:"type" binding:"required"`
	Data string `json:"data" name:"data" form:"data" binding:"required"`
}

// DockerApi docker的批量控制接口
func DockerApi(c *gin.Context) (int, interface{}) {
	var formData dockerApiPostForm
	if err := c.ShouldBind(&formData); err != nil {
		return checkErr(err)
	}
	data, err := base64.StdEncoding.DecodeString(formData.Data)
	if err != nil {
		return http.StatusInternalServerError, gin.H{
			"error": err.Error(),
		}
	}
	var task []interface{}
	err = json.Unmarshal(data, &task)
	if err != nil {
		return http.StatusInternalServerError, gin.H{
			"error": err.Error(),
		}
	}
	switch formData.Type {
	case "docker":
		b, _ := json.Marshal(task)
		logging.Logger.Println(string(b))
		res := itom.DockerMultiControllerSync(task)
		b, _ = json.Marshal(res)
		logging.Logger.Println(string(b))
		return http.StatusOK, res
	default:
		return http.StatusBadRequest, gin.H{
			"error": fmt.Sprintf("unknown type [%s]", formData.Type),
		}
	}
}
