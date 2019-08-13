package dockercli

import (
	"flag"
	"github.com/docker/docker/api/types/reference"
	"testing"
)

func TestParseDockerImage(t *testing.T) {
	image := "alpine"
	ref, tag, err := reference.Parse(ParseDockerImage(image))
	if err != nil {
		t.Error(err)
		return
	}
	t.Log(ref, tag)
}

func TestCmdParse(t *testing.T) {
	for _, cmd := range CmdParse("/bin/sh -c 'while true;do echo hey && sleep 10;done'") {
		t.Log(cmd)
	}
	flag.NewFlagSet("/bin/sh -c 'while true;do echo hey && sleep 10;done'", flag.ContinueOnError)
	t.Log(flag.Args())
}

func TestParseIpPort(t *testing.T) {
	host := "tcp://192.168.127.1:2375"
	ip, port := parseIpPort(host)
	if ip != "192.168.127.1" || port != 2375 {
		t.Error("want: 192.168.127.1", "got:", ip)
		t.Error("want: 2375", "got:", port)
		return
	}
	t.Log("pass")
}
