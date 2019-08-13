package config

import (
	"flag"
	"fmt"
	"go-itom-api/pkg/util"
	"gopkg.in/yaml.v2"
	"io/ioutil"
	"os"
)

func init() {
	Config = new(ItomConfig)
	conf := flag.String("config", "./config.yml", "itom config file path. default: [./config.yaml]")
	flag.Parse()
	if _, err := os.Stat(*conf); err != nil {
		// 加载默认配置
		Config = &ItomConfig{
			System: ItomSys{
				Host:       "127.0.0.1",
				Port:       8000,
				Log:        "/dev/stdout",
				StartTime:  "2019-06-01 00:00:00",
				MachineID:  1,
				RemotePort: 0,
			},
			Cache: Redis{
				Addr:            "127.0.0.1:6379",
				Password:        "",
				DB:              0,
				PoolSize:        100,
				ItomTaskKey:     "itom_batch_task",
				ItomRegistryKey: "hubImageInfo",
			},
		}
	} else {
		if err := Config.ReadFromFile(*conf); err != nil {
			fmt.Printf("itom.init.config.error [%s]\n", err.Error())
			os.Exit(128)
		}
	}

	// 填充部分默认值
	if Config.System.Host == "" {
		Config.System.Host = "127.0.0.1"
	}
	if Config.System.Port == 0 {
		Config.System.Port = 8000
	}
	if Config.System.Log == "" {
		Config.System.Log = "/dev/stdout"
	}
	if Config.System.StartTime == "" {
		Config.System.StartTime = "2019-06-01 00:00:00"
	}
	if Config.System.MachineID == 0 {
		Config.System.MachineID = 1
	}
	if Config.Cache.Addr == "" {
		Config.Cache.Addr = "127.0.0.1:6379"
	}
	if Config.Cache.PoolSize == 0 {
		Config.Cache.PoolSize = 100
	}
	if Config.Cache.ItomTaskKey == "" {
		Config.Cache.ItomTaskKey = "itom_batch_task"
	}
	if Config.Cache.ItomRegistryKey == "" {
		Config.Cache.ItomRegistryKey = "hubImageInfo"
	}
	if Config.System.PrivateKeyPath != "" {
		f, err := os.Open(Config.System.PrivateKeyPath)
		if err != nil {
			return
		}
		defer f.Close()
		p, err := ioutil.ReadAll(f)
		if err != nil {
			return
		}
		Config.System.PrivateKey = p
	}
	if Config.System.RemotePort == 0 {
		Config.System.RemotePort = 22
	}
	if redisHost := os.Getenv("REDIS_HOST"); redisHost != "" {
		redisPort := os.Getenv("REDIS_PORT")
		if redisPort == "" {
			redisPort = "6379"
		}
		Config.Cache.Addr = fmt.Sprintf("%s:%s", redisHost, redisPort)
	}
	if redisDb := os.Getenv("REDIS_DB"); redisDb != "" {
		Config.Cache.DB = util.String2Int(redisDb)
	}
	if redisPassword := os.Getenv("REDIS_PASSWORD"); redisPassword != "" {
		Config.Cache.Password = redisPassword
	}
}

var Config *ItomConfig

// ReadFromFile 从指定文件中读取配置信息
func (i *ItomConfig) ReadFromFile(filePath string) (err error) {
	yamlData, err := ioutil.ReadFile(filePath)
	if err != nil {
		return
	}

	if err = yaml.Unmarshal(yamlData, i); err != nil {
		return
	}

	return
}

// ItomConfig itom 的需要读取的配置信息
type ItomConfig struct {
	System ItomSys    `yaml:"system"`
	Cache  Redis      `yaml:"cache"`
	Docker DockerInfo `yaml:"docker"`
}

const (
	TimeItom string = "2006-01-02 15:04:05"
)

// ItomSys 系统本身需要的配置信息
type ItomSys struct {
	// Host 服务监听的 IP
	// eg: 0.0.0.0 或者 (默认值)127.0.0.1
	Host string `yaml:"host"`

	// Port 服务监听的端口
	// eg: 80 或者 (默认值)8080
	Port int `yaml:"port"`

	// Log 程序日志的存放路径
	// eg: /var/log/itom 或者 (默认值)/dev/stdout
	Log string `yaml:"log"`

	// StartTime 起始时间点
	// 程序使用的唯一 Id 采用的是 Twitter 的雪花算法
	// 需要一个时间点作为其实点用以计算
	// 格式为 (默认值)"2019-06-01 00:00:00"
	StartTime string `yaml:"uniqueidStartTime"`

	// MachineID 机器Id
	// 程序使用的唯一 Id 采用的是 Twitter 的雪花算法
	// 需要设置本节点的 Id (全局不可重复)，用以保证生成的 唯一Id 全局唯一
	// (默认值)1
	MachineID uint16 `yaml:"machineID"`

	// TempDir 临时文件存放路径
	// 用于存放接收用户上传的文件，制作成tar格式后再发送至容器接口中
	// (默认值)/tmp
	TempDir string `yaml:"tempDir"`

	// PrivateKeyPath 私钥路径
	// 用于发送文件至远端服务器
	// 或从远端服务器上获取文件
	PrivateKeyPath string `yaml:"privateKeyPath"`

	// RemoteUser PrivateKey对应的用户名
	RemoteUser string `yaml:"remoteUser"`

	// RemotePort ssh端口
	// (默认值) 22
	RemotePort int `yaml:"remotePort"`

	// privateKey 私钥内容
	// 服务启动时会加载至内存中
	PrivateKey []byte
}

// Redis 缓存配置信息
type Redis struct {
	// Host redis 的 host 配置信息
	// eg: (默认值)127.0.0.1:6379
	Addr string `yaml:"addr"`

	// Password 连接 redis 时的密码，如果没有，则填写 ""
	// eg: 123456 或者 (默认值)""
	Password string `yaml:"password"`

	// Db redis 使用数据的 db 名称
	// eg: (默认值)0
	DB int `yaml:"db"`

	// PoolSize redis 连接的线程池设置
	// eg: (默认值)100
	PoolSize int `yaml:"poolSize"`

	// ItomTaskKey itom 任务队列名称
	// eg: (默认值)itom_batch_task
	ItomTaskKey string `yaml:"itomTaskKey"`

	// ItomAnsibleTaskKey itom 任务队列名称
	// eg: (默认值)itom_batch_task
	ItomAnsibleTaskKey string `yaml:"itomAnsibleTaskKey"`

	// ItomRegistryKey itom 镜像仓库配置信息
	// eg: (默认值)hubImageInfo
	ItomRegistryKey string `yaml:"itomRegistryKey"`
}

// DockerInfo docker 连接配置信息
type DockerInfo struct {
	// Version 所管理的 docker 的版本(默认为空，可能会导致部分返回数据异常)
	Version string `json:"version" name:"version"`
}
