package mariadb

//import (
//	"fmt"
//	"go-itom-api/pkg/config"
//	"os"
//	"time"
//
//	_ "github.com/go-sql-driver/mysql"
//	// _ "github.com/mattn/go-sqlite3"
//	"github.com/go-xorm/core"
//	"github.com/go-xorm/xorm"
//)
//
//var DBEngine *xorm.Engine = db_engine()
//
//func db_engine() *xorm.Engine {
//	engineType := "mysql"
//	connectAdd := fmt.Sprintf("%s:%s@%s/%s?%s",
//		config.Config.DB.Username,
//		config.Config.DB.Password,
//		config.Config.DB.Host,
//		config.Config.DB.DBName,
//		"charset=utf8")
//	// connectAdd := fmt.Sprintf("root:onetwothree@tcp(127.0.0.1)/permiMg?charset=utf8")
//
//	engine, err := xorm.NewEngine(engineType, connectAdd)
//	if err != nil {
//		fmt.Println(err.Error())
//		os.Exit(128)
//	}
//	engine.SetMaxIdleConns(100)
//	engine.SetMaxOpenConns(100)
//	//设置日志级别
//	engine.SetLogLevel(core.LOG_OFF)
//	//engine.ShowSQL(true)
//	go func() {
//		for true {
//			err = engine.Ping()
//			if err != nil {
//				fmt.Println(err.Error())
//			}
//			time.Sleep(time.Second * 30)
//		}
//	}()
//
//	return engine
//}
