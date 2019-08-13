package publish

import (
	logging "go-itom-api/pkg/logging"
	"go-itom-api/pkg/rediscli"
)

// Publish 发布信息到指定用户信息渠道中
func Publish(userID, msg string) {
	pushNum, err := rediscli.Redis.Publish(userID, msg).Result()
	if err != nil {
		logging.Logger.Printf("redis.publish.error [%s|%s|%s]\n", userID, msg, err.Error())
		return
	}
	logging.Logger.Printf("redis.publish.success [%d|%s|%s]\n", pushNum, userID, msg)
}
