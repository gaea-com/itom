package itom

import (
	"encoding/json"
	"go-itom-api/pkg/dockercli"
	"sync"
	"time"
)

// DockerTaskMultiController 容器任务批量操作控制器
// 任务的执行有点特殊，需要按照顺序，一个一个执行，不可以并行处理
func DockerTaskMultiController(taskId string, require [][]interface{}) {
	var wg sync.WaitGroup
	for _, task := range require {
		wg.Add(1)
		// TODO: 后期如果线程池压力较大，可以加入携程池的控制
		go dockerTaskControl(taskId, &wg, task)
	}
	wg.Wait()
	return
}

// taskStepInfo docker 任务的 response
type taskStepInfo struct {
	dockercli.DockerResponse
	Ip          string `json:"ip"`
	CurrentStep int    `json:"current_step"`
	TotalStep   int    `json:"total_step"`
}

// dockerTaskControl docker 任务控制程序
func dockerTaskControl(taskID string, wg *sync.WaitGroup, task []interface{}) {
	total := len(task)
	for tmp, _task := range task {
		i := apiDockerArgsParse(_task)
		// 运行参数
		b, _ := json.Marshal(taskStepInfo{
			DockerResponse: dockerControl(i),
			Ip:             i.Ip,
			CurrentStep:    tmp + 1,
			TotalStep:      total,
		})
		// 推送数据
		CachePush(taskID, string(b))
		time.Sleep(time.Second * time.Duration(i.WaitTime))
	}
	wg.Done()
}
