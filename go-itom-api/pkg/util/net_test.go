package util

import (
	"testing"
)

func TestDialPort(t *testing.T) {
	t.Log(DialPort("127.0.0.1", 80))
}