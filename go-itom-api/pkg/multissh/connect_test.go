package multissh

import (
	"encoding/json"
	"testing"
)

func TestSshCommand(t *testing.T) {
	res := CmdRun(
		"mengjie.sun",
		"",
		"192.168.22.207",
		"/Users/jack/.ssh/id_rsa",
		"#!/bin/bash\ndocker login",
		22,
		6,
	)
	b, _ := json.Marshal(res)
	t.Log(string(b))
}
