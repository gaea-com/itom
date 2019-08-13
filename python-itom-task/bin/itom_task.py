# -*- coding: utf-8 -*-
"""
__title__ = ''
__author__ = 'jack'
__mtime__ = '2017/4/6'

"""
import sys
import os
try:
    sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
except ImportError as e:
    print e
    sys.exit(127)
# import signal
import logging.config
from conf import config
logging.config.dictConfig(config.LOG_CONFIG)
logger = logging.getLogger("main")
logger.info("itom.init.log.success")
import multiprocessing as mp
from lib.TaskCenter import Workworkwork
from lib import RedisClient


def init():
    """  日志初始化，将所有日志重定向  """
    # 项目路径获取
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

    # 临时目录初始化
    if not os.path.isdir(config.TMP_DIR):
        os.mkdir(config.TMP_DIR)

    # ansible 文件下载存放路径
    file_download_dir = os.path.join(base_dir, "file_download")
    if not os.path.isdir(file_download_dir):
        os.mkdir(file_download_dir)


# def signal_kill(sig, frame):
#     """  捕捉到程序退出信号，进行退出操作及临时文件清理工作。  """
#
#     # 停止监听及任务处理进程
#     try:
#         for _proc in process_pool:
#             _proc.terminate()
#         logger.info(u'任务进程关闭成功。')
#         system_quit.put(True)
#
#     finally:
#         logger.info(u'关闭成功'.format(sig))


def check_unfinished_task():
    # 检查 redis 中是否有未处理任务

    logger.info(u"正在检查 redis 中是否有未处理任务。")
    task_check = redis_cli.HashLen(config.TASK_KEY)
    if task_check.get("changed") and task_check.get("result_len", 0) > 0:
        keys_check = redis_cli.HashGetAll(config.TASK_KEY)
        if keys_check.get("changed"):
            logger.info(u"检测到未处理任务，正在尝试任务重载。。。。。。。")
            result_dict = keys_check.get("result_dict")
            for task_id in result_dict:
                redis_cli.ListLpush(config.UNFINISHED_TASK_KEY, result_dict[task_id])
            logger.info(u"任务重载完成。")
    else:
        logger.info(u"未查询到未处理任务。(^_^)")
    logger.info(u"======>  未处理任务检查  => 完成。")


# def create_work_process():
#     # 创建工作进程
#     logger.info(u"系统总核数 => {cpu},正在启动  {work_num} 个工作进程。".format(
#         cpu=mp.cpu_count(),
#         work_num=concurrency
#     ))
#     for num in range(concurrency):
#         process_name = u"work_process_{}".format(num + 1)
#         _proc = mp.Process(
#             target=Workworkwork,
#             args=(redis_cli, process_name,),
#             name=process_name
#         )
#         _proc.start()
#         logger.info(u"启动 {process_name} 进程  ===>  OK.".format(
#             process_name=process_name
#         ))
#         process_pool.append(_proc)


if __name__ == '__main__':
    # system_quit = mp.Queue()
    if not config.MAX_LISTEN_PROCESS:
        concurrency = mp.cpu_count()
    else:
        concurrency = config.MAX_LISTEN_PROCESS

    # 日志和pid文件初始化
    init()

    # redis 客户端初始化
    redis_cli = RedisClient.RedisConnection()

    # 任务创建器初始化
    # process_pool = []
    # create_work_process()

    # 信号初始化
    # signal.signal(2, signal_kill)
    # signal.signal(15, signal_kill)

    # 未处理任务检查
    if config.UseRedis:
        check_unfinished_task()

    Workworkwork(redis_cli, "main")

    # 等待服务退出
    # system_quit.get()
