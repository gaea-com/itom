package rediscli

import (
	"fmt"
	"os"
	"time"

	"go-itom-api/pkg/config"

	"github.com/go-redis/redis"
)

// NewClient 初始化新的 redis 连接句柄
func newClient() *redis.Client {
	client := redis.NewClient(&redis.Options{
		Addr:     config.Config.Cache.Addr,
		Password: config.Config.Cache.Password,
		DB:       config.Config.Cache.DB,
		PoolSize: config.Config.Cache.PoolSize,
	})

	_, err := client.Ping().Result()
	if err != nil {
		fmt.Println(err.Error())
		os.Exit(71)
	}

	go func() {
		for true {
			_, err := client.Ping().Result()
			if err != nil {
				break
			}
			time.Sleep(time.Second * 10)
		}
		os.Exit(71)
	}()

	return client
}

var Redis *redis.Client = newClient()
