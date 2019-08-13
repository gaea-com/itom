# -*- coding: UTF-8 -*-
import os
base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
# 日志级别设置
#    CRITICAL = 50
#    ERROR = 40
#    WARNING = 30
#    INFO = 20
#    DEBUG = 10
LOG_CONFIG = {
    "version": 1,
    "disable_existing_loggers": False,

    "formatters": {
        "simple": {
            'format': '%(asctime)s [%(name)s:%(lineno)d] [%(levelname)s]- %(message)s'
        },
        'standard': {
            'format': '%(asctime)s [%(threadName)s:%(thread)d] [%(name)s:%(lineno)d] [%(levelname)s]- %(message)s'
        },
    },

    "handlers": {
        "console": {
            "class": "logging.StreamHandler",
            "level": "INFO",
            "formatter": "simple",
            "stream": "ext://sys.stdout"
        },
        "file": {
            "class": "logging.handlers.RotatingFileHandler",
            "level": "DEBUG",
            "formatter": "standard",
            "filename": os.path.join(base_dir, "log", "itom_task.log"),
            "backupCount": 3,
        },
    },
    "loggers": {
        "main": {
            "handlers": ["console"],
            "level": "INFO",
            "propagate": False,
        },
        "ansible": {
            "handlers": ["console"],
            "level": "INFO",
            "propagate": False,
        },
        "root": {
            "handlers": ["console"],
            "level": "INFO",
            "propagate": False,
        },
        "redis": {
            "handlers": ["console"],
            "level": "INFO",
            "propagate": False,
        },
    }
}

# 任务监听最大进程数
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
RedisConnectIP = os.getenv("REDIS_HOST", "localhost")
RedisConnectPort = os.getenv("REDIS_PORT", 6379)
RedisDB = os.getenv("REDIS_DB", 0)
RedisPassword = os.getenv("REDIS_PASSWORD", None)

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
SSH_USER = os.getenv("ANSIBLE_SSH_USER", "root")
# ansible 存储的临时 host 路径
InventoryTmp = "/etc/ansible/hosts"
# ansible 执行命令时添加的固定前缀
BecomeMethod = "sudo"
# ansible 执行命令时采用的用户名
BecomeUser = os.getenv("ANSIBLE_BECOME_USER", "root")
# 配置的 ssh 登录秘钥
PriviteKeyPath = os.path.join(base_dir, "conf", "ssh_key")
# ansible 执行命令的超时时间
AnsibleTimeout = 1800
###############
