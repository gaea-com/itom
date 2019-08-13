package server

import (
	"fmt"
	"net/http"
	"os"
	"os/signal"

	"go-itom-api/pkg/config"
	logging "go-itom-api/pkg/logging"

	"github.com/gin-gonic/gin"
)

// Server http 服务主进程
func Server() {
	defer func() {
		if err := recover(); err != nil {
			logging.Logger.Println(err)
		}
	}()
	logging.Logger.SetPrefix("[itom-api] ")
	gin.DisableConsoleColor()
	gin.SetMode(gin.TestMode)
	router := gin.Default()
	// 添加路由
	loadRouter(router)
	server := &http.Server{
		Addr: fmt.Sprintf("%s:%d",
			config.Config.System.Host,
			config.Config.System.Port),
		Handler: router,
	}
	quit := make(chan os.Signal)
	signal.Notify(quit, os.Interrupt)
	go func() {
		<-quit
		logging.Logger.Println("itom.server.ready.stop")
		if err := server.Close(); err != nil {
			logging.Logger.Printf("itom.server.stop.error [%s]\n", err.Error())
			os.Exit(1)
		}
	}()
	if err := server.ListenAndServe(); err != nil {
		if err != http.ErrServerClosed {
			logging.Logger.Printf("itom.server.stop.error [%s]\n", err.Error())
			os.Exit(1)
		}
	}
	logging.Logger.Println("itom.server.quit")
}
