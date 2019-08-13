<p align="center">
    <img src="assets/itom_logo.png" width="500" hegiht="313" align=center>
</p>

# ITOM-ansible

ITOM-ansible 用于批量发送命令至需要管理的主机

----

## 开始使用 ITOM-ansible

### 代码构建

python2

`pip install -r requirements.txt`

`python bin/itom_task.py`

### 程序功能说明

#### python-itom-task

- 监听 redis 队列，等待针对宿主机的相关命令操作

## 使用须知

1、需要操作各个主机的免密秘钥，存放在 conf/ssh_key 文件中

2、需要可连接的 redis 配置

## 配置文件说明

文件存放在 conf/config.py 中

```text
# 任务监听最大进程数(单个任务接收时，会启动的最大进程数)
MAX_LISTEN_PROCESS = 4

# ansible 并行执行的最大任务数
MAX_TASK_NUMBER = 15
# 临时文件路径
TMP_DIR = os.path.join(base_dir, "tmp_dir")
# 临时下载文件路径
TMP_DOWNLOAD_DIR = os.path.join(base_dir, "file_download")

# jwt 加密时的秘钥
JWT_SECRET_KEY = "%^DSFSD*&"

###############
# Redis 相关配置项
# 是否开启 redis ，只有开关为 True 时，以下 redis 的配置才生效。
UseRedis = True
RedisConnectIP = "localhost"
RedisConnectPort = 6379
RedisDB = 0
RedisPassword = None

# Redis 中，获取任务的队列名称
TASK_KEY = "itom_ansible_task"
# Redis 中，未完成的任务队列名称
UNFINISHED_TASK_KEY = "itom_unfinished_task"
# Redis 存储错误信息 list 表
TASK_ERROR_KEY = "itom_error_message"
###############

###############
# Ansible 相关配置
# 配置的 ssh 登录用户名
SSH_USER = "root"
# ansible 存储的临时 host 路径
InventoryTmp = "/etc/ansible/hosts"
# ansible 执行命令时添加的固定前缀
BecomeMethod = "sudo"
# ansible 执行命令时采用的用户名
BecomeUser = "root"
# 配置的 ssh 登录秘钥
PriviteKeyPath = os.path.join(base_dir, "conf", "ssh_key")
# ansible 执行命令的超时时间
AnsibleTimeout = 1800
###############
```