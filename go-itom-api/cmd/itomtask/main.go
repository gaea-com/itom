package main

import (
	"encoding/json"
	"fmt"
	"go-itom-api/internal/itom"
	"go-itom-api/pkg/config"
	logging "go-itom-api/pkg/logging"
	"go-itom-api/pkg/rediscli"
	"time"
)

func main() {
	logging.Logger.SetPrefix("[itom-task] ")
	var err error
	var task []string
	for {
		task, err = rediscli.Redis.BRPop(time.Duration(0), config.Config.Cache.ItomTaskKey).Result()
		if err != nil {
			logging.Logger.Printf("get.task.error [%s]\n", err.Error())
			return
		}
		logging.Logger.Printf("get.task [%s]\n", task[1])
		go run(task[1])
	}
}

// run 运行接收到的任务
func run(task string) {
	var taskID string
	var taskType itom.ItomTaskType
	var err error
	if taskID, taskType, err = itom.CheckInputArgs(task); err != nil {
		if taskID != "" {
			itom.CachePush(taskID,
				fmt.Sprintf(`{"status":false,"error":"%s"}`, err.Error()),
				`{"task_status":"done"}`)
		}
		logging.Logger.Printf("task.check.args.error [%s]\n", err.Error())
		return
	}

	logging.Logger.Printf("args.check.complete [%s]\n", taskID)

	switch taskType {
	case itom.ApiDocker:
		var option itom.DockerRequire
		_ = json.Unmarshal([]byte(task), &option)
		itom.DockerMultiController(taskID, option.Parameter)

	case itom.ApiDockerTask:
		var option itom.DockerTaskRequire
		_ = json.Unmarshal([]byte(task), &option)
		itom.DockerTaskMultiController(taskID, option.Parameter)

	default:
		// 默认则将任务发送至另一个队列中
		itom.CachePush(config.Config.Cache.ItomAnsibleTaskKey, task)
		return
	}

	// TODO: 该处逻辑兼容旧时的任务调度格式，后期会统一化任务调度格式。
	itom.CachePush(taskID, fmt.Sprintf(`{"task_status":"done"}`))
}
