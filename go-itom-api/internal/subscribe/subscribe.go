package subscribe

import (
	"github.com/go-redis/redis"
	"go-itom-api/pkg/rediscli"
)

// Subscribe 订阅指定消息队列中的消息
func Subscribe(userID string) (msgChan <-chan *redis.Message) {
	return rediscli.Redis.Subscribe(userID).Channel()
}
