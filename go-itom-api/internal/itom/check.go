package itom

import (
	"encoding/json"
	"fmt"
)

// checkInputArgs 检查用户传入的格式是否正确
func CheckInputArgs(args string) (taskId string, taskType ItomTaskType, err error) {
	var packageHeade struct {
		TaskID    string       `json:"task_id" name:"task_id"`
		TaskType  ItomTaskType `json:"task_type" name:"task_type"`
		Parameter interface{}  `json:"parameter" name:"parameter"`
	}

	err = json.Unmarshal([]byte(args), &packageHeade)
	if err != nil {
		err = fmt.Errorf(DockerArgsCheckError, err.Error())
		return
	}

	if packageHeade.TaskID == "" {
		err = fmt.Errorf(DockerArgsCheckError, "missing 'task_id'")
		return
	}

	taskId = packageHeade.TaskID

	if packageHeade.TaskType == "" {
		err = fmt.Errorf(DockerArgsCheckError, "missing 'task_type'")
		return
	}

	if packageHeade.TaskType == "" {
		err = fmt.Errorf(DockerArgsCheckError, "missing 'parameter'")
		return
	}

	taskType = packageHeade.TaskType
	switch packageHeade.TaskType {
	case ApiDocker:
		err = dockerArgsCheck(packageHeade.Parameter)
	case ApiDockerTask:
		err = dockerTaskArgsCheck(packageHeade.Parameter)
	case InstanceLoadTask:
		err = instanceLoadTaskCheck(packageHeade.Parameter)
	default:
		err = nil
		//err = fmt.Errorf(
		//	DockerArgsCheckError,
		//	fmt.Sprintf("unknown task_type [%s]", packageHeade.TaskType))
	}
	return
}

// dockerArgsCheck 检查 docker 的操作参数是否正确
func dockerArgsCheck(args interface{}) (err error) {
	return
}

// dockerTaskArgsCheck 检查 docker task 的操作参数是否正确
func dockerTaskArgsCheck(args interface{}) (err error) {
	return
}

// instanceLoadTaskCheck 实例加载的参数检查
func instanceLoadTaskCheck(args interface{}) (err error) {
	return
}
