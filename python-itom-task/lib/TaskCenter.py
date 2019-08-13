# -*- coding: utf-8 -*-
"""
__title__ = ''
__author__ = 'jack'
__mtime__ = '2017/6/8'

"""
import json
import traceback
import logging
import redis
from lib.AnsibleCenter import AnsibleManager
from lib import RedisClient
from conf import config
redis_cli = RedisClient.RedisConnection()
logger = logging.getLogger("main")


def ErrorMessage(task_id,TYPE):
    """  异常数据投递。  """
    task_error_tag = json.dumps({
        "status": False,
        "error": '({type_str}) parameter missing or do not have this ({type_str}) type'.format(type_str=TYPE),
        "data": ''
    })
    task_stop_tag = json.dumps({
        "task_status": 'done'
    })
    redis_cli.ListLpush(task_id, task_error_tag)
    redis_cli.ListLpush(task_id, task_stop_tag)


def ParameterCheck(stringstring):
    """  参数校验  """
    # 开始处理

    try:
        # json 格式数据加载
        Message = json.loads(stringstring)

        # 关键数据获取
        TASK_ID = Message.get('task_id', None)
        TASK_TYPE = Message.get('task_type', None)
        PARAMETER = Message.get('parameter', None)

        if TASK_ID == None or TASK_TYPE == None or PARAMETER == None:
            raise TypeError('(TASK_ID||TYPE||DATA) miss .')

        else:
            if len(TASK_ID) == 0 or len(TASK_TYPE) == 0 or len(PARAMETER) == 0:
                raise TypeError('(TASK_ID||TYPE||DATA) length is too short.')

    except TypeError,error:
        error = "数据检查不通过 (source data=> {sourceData})  ==>  {error}".format(sourceData=stringstring, error=error)
        logger.error(error)
        if Message and Message.get('task_id', None):
            task_id = Message.get('task_id', None)
            task_error_tag = json.dumps({
                "status": False,
                "error": error,
                "data": ''
            })
            redis_cli.ListLpush(task_id, task_error_tag)
            task_stop_tag = json.dumps({
                "task_status": 'done'
            })
            redis_cli.ListLpush(task_id, task_stop_tag)
        return dict(
            check = False
        )

    except:
        error = "数据检查不通过 (source data=> {sourceData}) ==>  {error}".format(sourceData=stringstring, error=traceback.format_exc())
        logger.error(error)
        if Message and Message.get('task_id', None):
            task_id = Message.get('task_id', None)
            task_error_tag = json.dumps({
                "status": False,
                "error": error,
                "data": ''
            })
            redis_cli.ListLpush(task_id, task_error_tag)
            task_stop_tag = json.dumps({
                "task_status": 'done'
            })
            redis_cli.ListLpush(task_id, task_stop_tag)
        return dict(
            check = False
        )

    # 确认接收到任务
    logger.info("确认收到 {} 请求，开始处理。。。。。。".format(TASK_ID))

    ### 任务分拣器
    # TMP为标识符，不满足基本格式则加一。
    # 大于0 为任务异常，0 为正常，小于 0 为当前任务下的参数不全。

    # 格式检测标识符(0表示正常，其他表示异常。)
    TMP = 0
    ProcessType = None

    if TASK_TYPE in [ 'copy', 'batch_command' , 'fetch']:
        # Ansible 任务接口
        if TASK_TYPE == 'copy':
            if not isinstance(PARAMETER, dict):
                TMP -= 1
            # 检查必备参数是否为空
            if PARAMETER.get('ip_list') == None and len(PARAMETER.get('ip_list')) == 0:
                TMP -= 1
            if PARAMETER.get('dest') == None and len(PARAMETER.get('dest')) == 0:
                TMP -= 1
            if PARAMETER.get('src') == None and len(PARAMETER.get('src')) == 0:
                TMP -= 1

        elif TASK_TYPE == 'fetch':
            if not isinstance(PARAMETER, dict):
                TMP -= 1
            # 检查必备参数是否为空
            if PARAMETER.get('ip_list') == None and len(PARAMETER.get('ip_list')) == 0:
                TMP -= 1
            if PARAMETER.get('src') == None and len(PARAMETER.get('src')) == 0:
                TMP -= 1

        elif TASK_TYPE == 'batch_command':
            if not isinstance(PARAMETER, list):
                TMP -= 1
            for current_task in PARAMETER:
                if current_task.get("ip", None) is None or current_task.get("cmd", None) is None:
                    TMP -= 1
                if TMP == 0:
                    if len(current_task.get("ip")) == 0 or current_task.get("cmd") == 0:
                        TMP -= 1
        if TMP == 0:
            ProcessType = 'ansible'


    # 未匹配到任务接口
    else:
        TMP += 1

    if TMP == 0:
        return dict(
            check = True,
            task_id = TASK_ID,
            task_type = TASK_TYPE,
            parameter = PARAMETER,
            ProcessType = ProcessType
        )

    elif TMP > 0:
        task_error_tag = json.dumps({
            "status": False,
            "error": 'Did not match the task type.',
            "data": ''
        })
        task_stop_tag = json.dumps({
            "task_status": 'done'
        })
        redis_cli.ListLpush(TASK_ID, task_error_tag)
        redis_cli.ListLpush(TASK_ID, task_stop_tag)
        logger.info("任务不匹配，丢弃。")
        return dict(
            check = False
        )

    elif TMP < 0:
        ErrorMessage(TASK_ID, PARAMETER)
        logger.info("处理类型不匹配。(id:{}\ntype:{})".format(TASK_ID, PARAMETER))
        return dict(
            check=False
        )

def AddRedisTask(task_key,task_recording):
    """  将任务添加进 redis 任务队列  """
    if config.UseRedis:
        redis_set_result = redis_cli.HashSet(
            config.UNFINISHED_TASK_KEY,
            task_key,
            task_recording
        )
        if redis_set_result.get("changed"):
            logger.info("======>  任务记录至 redis 完成。(key => {key})".format(key=task_key))
        else:
            logger.error("======>  任务记录至 redis 失败。(key => {key})".format(key=task_key))

def RemoveRedisTask(task_recording):
    """  将处理完毕的任务移除 redis 任务队列  """
    if config.UseRedis:
        remove_result = redis_cli.HashDel(
            config.UNFINISHED_TASK_KEY,
            task_recording
        )
        if remove_result.get('changed'):
            logger.info("======>  移除 redis 中已完成任务  成功。(key => {key})".format(key=task_recording))
        else:
            logger.error("======>  移除 redis 中已完成任务  失败。(key => {key})".format(key=task_recording))

def Workworkwork(redis_cli, Process_name):
    """  主任务函数  """

    while True:
        # 队列中抓取任务
        try:
            task = redis_cli.LRPop(config.TASK_KEY)
            logger.info("进程 {Process_name} 从任务队列中获取到任务  ==> {task}".format(
                Process_name=Process_name,
                task=task[1]
            ))
        except KeyboardInterrupt:
            logger.info("进程 {Process_name} 接收到退出信号，关闭队列接收。".format(
                Process_name=Process_name
            ))
            break

        except redis.RedisError as e:
            error_msg = "进程 {Process_name} 任务获取失败，失败原因  ==> {error}".format(
                Process_name=Process_name,
                error=e
            )
            logger.error(error_msg)
            break

        # 检测参数
        check_obj = ParameterCheck(task[1])

        # 任务设置
        type_center = ['ansible']

        if check_obj.get("check"):
            if check_obj.get("ProcessType") in type_center:
                AddRedisTask(check_obj.get("task_id"), task[1])
            try:
                # ansible
                if check_obj.get("ProcessType") == 'ansible':
                    AnsibleManager.AnsibleRun(
                        task_id = check_obj.get("task_id"),
                        task_type = check_obj.get("task_type"),
                        parameter = check_obj.get("parameter")
                    )

            except:
                error_msg = "进程 {Process_name} 任务执行失败，失败原因  ==> {error}".format(
                    Process_name=Process_name,
                    error=traceback.format_exc()
                )
                logger.error(error_msg)

            finally:
                if check_obj.get("ProcessType") in type_center:
                    RemoveRedisTask(check_obj.get("task_id"))

