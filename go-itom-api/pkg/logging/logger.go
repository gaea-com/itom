package log

import (
	"fmt"
	"io"
	"log"
	"os"
	"sync"

	"go-itom-api/pkg/config"
)

// Logger 日志句柄
var Logger = initLogger()
var logger *log.Logger
var once sync.Once

// Init 日志句柄初始化操作，只实例化出一个日志句柄
// TODO: 当前日志较为简陋，后续会优化这块，
// 防止因为日志落盘过慢，或 I/O 等问题，导致部分程序卡在日志打印上。
func initLogger() *log.Logger {
	once.Do(func() {
		var out io.Writer
		var err error
		switch config.Config.System.Log {
		case "/dev/stderr":
			out = os.Stderr
		case "/dev/stdout":
			out = os.Stdout
		default:
			out, err = os.OpenFile(config.Config.System.Log, os.O_CREATE|os.O_RDWR|os.O_APPEND, 0644)
			if err != nil {
				fmt.Printf("itom.init.log.error [%s|%s]\n", err.Error(), config.Config.System.Log)
				os.Exit(128)
			}
		}
		logger = log.New(out, "", log.LstdFlags)
		logger.SetFlags(logger.Flags() | log.LstdFlags | log.Lshortfile)
		logger.Println("logger.init.success")
	})
	return logger
}
