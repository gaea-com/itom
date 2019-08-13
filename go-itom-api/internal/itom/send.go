package itom

import (
	"fmt"

	logging "go-itom-api/pkg/logging"
	"go-itom-api/pkg/rediscli"
)

// CachePush 将数据推送至 redis 中指定的队列中
func CachePush(key string, values ...interface{}){
	var err error
	_, err = rediscli.Redis.LPush(
		key,
		values...).Result()
	if err != nil {
		err = fmt.Errorf(DockerSendMessageError, err.Error())
		logging.Logger.Println(err.Error())
		return
	}
	for _, value := range values {
		logging.Logger.Printf("redis.push [%s|%s]\n", key, value)
	}
	return
}