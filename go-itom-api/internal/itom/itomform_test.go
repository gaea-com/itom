package itom

import (
	"encoding/json"
	"testing"

	"github.com/mitchellh/mapstructure"
)

func TestFormTransform(t *testing.T) {
	require := `{
    "type":"docker",
    "data":[
    	{
    		"ip": "192.168.122.2",
    		"type": "docker_create",
    		"option":{
    			"image":"nginx:latest",
    			"hostname": "nginx",
    			"network_mode": "host",
    			"command": "/bin/bash",
    			"detach": "True",
    			"volumes":{
    				"/data":{
    					"bind": "/container/path",
    					"mode": "rw"
    				}
    			},
    			"environment": {
    				"env1":"value1"
    			}
    		}
    	}
    ]
}`
	var base DockerRequire
	if err := json.Unmarshal([]byte(require), &base); err != nil {
		panic(err)
	}

	if base.Type == "docker" {
		for _, task := range base.Data {
			var ti taskInfo
			if err := mapstructure.Decode(task, &ti); err != nil {
				t.Error(err)
				return
			}
			t.Log()
			if ti.Type == "docker_create" {
				var dco containerCreateOption
				if err := mapstructure.Decode(ti.Option, &dco); err != nil {
					t.Error(err)
					return
				}
				t.Logf("%+v", dco)
			}
		}
	}
}