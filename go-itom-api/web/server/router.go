package server

import (
	"go-itom-api/web/app"

	"github.com/gin-gonic/gin"
)

// loadRouter 加载路由
func loadRouter(router *gin.Engine) {
	// docker 任务接口
	router.POST("/docker_contrl", func(c *gin.Context) { c.JSON(app.DockerPost(c)) })
	// docker webshell 接口
	router.GET("/ws", func(c *gin.Context) { webshell(c.Writer, c.Request) })
	// docker 文件下载接口
	router.GET("/docker_download", app.DockerDownloadFile)
	// docker 文件上传接口
	router.POST("/docker_upload", app.DockerUploadFile)
	// docker 控制接口
	router.POST("/docker_sync_api", func(c *gin.Context) { c.JSON(app.DockerApi(c)) })
	// 实例文件下载接口
	router.GET("/instance_file", app.InstanceFileDownload)
	// 实例文件上传接口
	router.POST("/instance_file", app.InstanceFileUpload)
}
