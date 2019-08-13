package rediscli

import "testing"

func TestRedClient(t *testing.T) {

	sub := Redis.Subscribe()
	sub.ReceiveMessage()

}
