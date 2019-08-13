package itom

type DockerTaskType string

const (
	// 普通的 docker api 接口相关操作
	onlyDocker DockerTaskType = "docker"

	// 会顺序执行针对 docker 执行的相关操作命令
	// 且上一个命令执行完成后，会等待一定时间(默认为 5 秒)
	// 然后再执行下一个命令
	taskDocker DockerTaskType = "docker_task"
)

func isDockerTaskType(task DockerTaskType) bool {
	switch task {
	case onlyDocker:
		return true
	case taskDocker:
		return true
	default:
		return true
	}
}
