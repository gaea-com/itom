package itom

// docker api 相关的操作 (接收用户请求，然后将任务放入 redis 中的指定任务队列中)
import (
	"encoding/base64"
	"fmt"
	"go-itom-api/pkg/config"
	"go-itom-api/pkg/itomerr"
	logging "go-itom-api/pkg/logging"
	"go-itom-api/pkg/snowflake"
)

// AddDockerTask 创建 docker task 任务
// 然后将任务信息推进 redis 指定队列中
func AddDockerTask(taskType DockerTaskType, taskB64 string) (taskID string, err error) {
	if !isDockerTaskType(taskType) {
		err = fmt.Errorf("docker.task.type.error [%s]", taskType)
		return
	}
	var _taskID uint64
	_taskID, err = snowflake.SnowFlake().NextID()
	if err != nil {
		err = fmt.Errorf(fmt.Sprintf(itomerr.PkgSnowflakeCreateIDErr, err.Error()))
		logging.Logger.Println(err.Error())
		return
	}
	taskID = fmt.Sprintf("%d", _taskID)
	paraBytes, err := base64.StdEncoding.DecodeString(taskB64)
	if err != nil {
		return
	}
	task := `{"task_id":"%s","task_type":"%s","parameter":%s}`
	// 推送任务起始标识符
	CachePush(taskID, `{"task_status":"start"}`)

	// 推送任务进任务队列
	CachePush(config.Config.Cache.ItomTaskKey,
		fmt.Sprintf(task,
			taskID,
			taskType,
			string(paraBytes),
		))
	return
}
