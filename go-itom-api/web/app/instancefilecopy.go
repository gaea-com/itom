package app

import (
	"fmt"
	"github.com/gin-gonic/gin"
	"go-itom-api/internal/filecopy"
	"io"
	"net/http"
)

type instanceFileDownloadGetForm struct {
	Ip   string `name:"ip" form:"ip" binding:"required"`
	Path string `name:"path" form:"path" binding:"required"`
}

// DockerApi docker的批量控制接口
func InstanceFileDownload(c *gin.Context) {
	var formData instanceFileDownloadGetForm
	if err := c.ShouldBindQuery(&formData); err != nil {
		c.JSON(checkErr(err))
		return
	}
	f, err := filecopy.DownloadFromInstance(formData.Ip, formData.Path)
	if err != nil {
		c.JSON(checkErr(err))
		return
	}
	state, err := f.Stat()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"message": err.Error(),
		})
		return
	}
	var r io.ReadCloser
	c.Writer.WriteHeader(http.StatusOK)
	c.Header("Content-Disposition", fmt.Sprintf("attachment; filename=%s", state.Name()))
	c.Header("Content-Type", "application/octet-stream")
	c.Header("Accept-Length", fmt.Sprintf("%d", state.Size()))
	io.Copy(c.Writer, r)
}

func InstanceFileUpload(c *gin.Context) {
	ip := c.Request.FormValue("ip")
	path := c.Request.FormValue("path")
	file, _, err := c.Request.FormFile("file")
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"message": err.Error()})
		return
	}
	err = filecopy.UploadToInstance(ip, path, file)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"message": err.Error()})
	} else {
		c.JSON(http.StatusOK, gin.H{"message": "success"})
	}
	return
}
