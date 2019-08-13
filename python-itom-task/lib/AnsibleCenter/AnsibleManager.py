# -*- coding: utf-8 -*-
"""
__title__ = ''
__author__ = 'jack'
__mtime__ = '2017/6/8'

"""
import traceback
import logging
import json
import multiprocessing as mp
from lib.AnsibleCenter import AnsibleAPI
from conf import config
from lib import RedisClient

redis_cli = RedisClient.RedisConnection()
logger = logging.getLogger("ansible")


def AnsibleRun(task_id, task_type, parameter):
    """  ansible 执行命令接口  """

    info_msg = "Ansible 接收到任务：\n\t任务类型：{}\n\t" +\
               "任务内容：{}\n\tAnsible 任务 id ==> {}\n"
    logger.info(info_msg.format(task_type,parameter,task_id))

    if task_type == 'batch_command':
        task_sum = len(parameter)
        current_step = 0
        task_pool = mp.Manager().Queue()

        for job in parameter:
            current_step += 1
            task_message = dict(
                ip=job.get("ip", None),
                cmd=job.get("cmd", None),
                current_step=current_step,
                task_sum=task_sum,
                task_id=task_id
            )
            task_pool.put(task_message)
            logger.info("添加任务 ==> {} 完成。".format(task_message))

        if task_sum < config.MAX_TASK_NUMBER:
            work_num = task_sum
        else:
            work_num = config.MAX_TASK_NUMBER
        work_processes = Create_work_process(
            task_pool,
            work_num
        )
        logger.info("任务进程创建完成，正在等待任务执行结果。")
        for task in work_processes:
            task.join()

        del task_pool
        logger.info("任务完成。")

    elif task_type == 'copy':
        _proce = mp.Process(target=AnsilbeCopyCli, args=(parameter,task_id))
        _proce.start()
        _proce.join()

    elif task_type == 'fetch':
        _proce = mp.Process(target=AnsibleGetFileCli, args=(parameter,task_id))
        _proce.start()
        _proce.join()

    # 打印任务结束标识
    task_stop_tag = json.dumps({
        "task_status": "done"
    })
    redis_cli.ListLpush(task_id, task_stop_tag)


def Create_work_process(JobsQ, concurrency):
    """  创建多进程队列  """

    ProcessPool = []
    for num in range(concurrency):
        process_name = u"work_process_{}".format(num + 1)
        process = mp.Process(
            target=Workworkwork,
            args=(JobsQ,process_name),
            name=process_name
        )
        process.start()
        logger.info("启动 {process_name} 进程  ===>  OK.".format(
            process_name=process_name
        ))
        ProcessPool.append(process)
    return ProcessPool


def Workworkwork(Jobs, Process_name):
    """  单进程管理配合队列管理  """

    while True:
        # 队列中抓取任务
        try:
            if not Jobs.empty():
                job = Jobs.get()
                logger.info("进程 {Process_name} 从任务队列中获取到任务  ==> {task}".format(
                    Process_name=Process_name,
                    task=job
                ))
                AnsibleCli(job)
                continue

            else:
                logger.info("进程 {Process_name} 未获取到任务，退出.".format(
                    Process_name=Process_name
                ))
                break

        except KeyboardInterrupt:
            logger.info("进程 {Process_name} 接收到退出信号，关闭队列接收。".format(
                Process_name=Process_name
            ))
            break

        except:
            error_msg = "进程 {Process_name} 任务执行失败，失败原因  ==> {error}".format(
                Process_name=Process_name,
                error=traceback.format_exc()
            )
            logger.error(error_msg)
            try:
                logger.info("等待接收任务。")
            except:
                error_msg = "进程 {Process_name} 任务退还失败  ==> {error}".format(
                    Process_name=Process_name,
                    error=traceback.format_exc()
                )
                logger.error(error_msg)
                break
            continue


def AnsibleCli(parameter):
    logger.info(u"({current_step}/{task_sum})执行任务 ==> [IP:{ip} || cmd:{cmd}]".format(
        current_step=parameter.get("current_step"),
        task_sum=parameter.get("task_sum"),
        ip=parameter.get("ip"),
        cmd=parameter.get("cmd"),
        task_id=parameter.get("task_id")
    ))

    OBJ = AnsibleAPI.AnsibleCenter(
        parameter=dict(
            ip_list=[parameter.get("ip")]
        ),
        module_name='shell',
        task_id=parameter.get("task_id")
    )
    play_source = dict(
        name=u"Command Run",
        hosts=[parameter.get("ip")],
        gather_facts=u'no',
        tasks=[
            dict(
                action=dict(
                    module=u'shell',
                    args=dict(
                        _raw_params=parameter.get("cmd")
                    )
                ),
                async=config.AnsibleTimeout,   # 超时时间
                poll=3      # 轮训频率 3 秒一次
            )
        ]
    )
    OBJ.run_task(play_source)


def AnsibleGetFileCli(parameter, task_id):
    """  ansible 文件下载接口  """
    src = parameter['src']

    OBJ = AnsibleAPI.AnsibleCenter(
        parameter=parameter,
        module_name='copy',
        forks=config.MAX_TASK_NUMBER,
        task_id=task_id
    )
    play_source = dict(
        name="Get File",
        hosts=parameter['ip_list'],
        gather_facts='no',
        tasks=[
            dict(
                action=dict(
                    module='fetch',
                    args=dict(
                        dest=config.TMP_DOWNLOAD_DIR,
                        src=src
                    )
                ),
                poll=15,
                async=0
            )
        ]
    )
    OBJ.run_task(play_source)


def AnsilbeCopyCli(parameter, task_id):
    """  ansible 接口发送文件  """

    dest = parameter['dest']
    src = parameter['src']
    mode = parameter.get('mode', None)

    if mode == None:
        mode = '0644'

    if len(mode) != 4:
        mode = '0' + mode

    OBJ = AnsibleAPI.AnsibleCenter(
        parameter=parameter,
        module_name='copy',
        forks=config.MAX_TASK_NUMBER,
        task_id=task_id
    )
    play_source = dict(
        name="CopyFile",
        hosts=parameter['ip_list'],
        gather_facts='no',
        tasks=[
            dict(
                action=dict(
                    module='copy',
                    args=dict(
                        dest=dest,
                        src=src,
                        mode=mode
                    )
                ),
                poll=2
            )
        ]
    )
    OBJ.run_task(play_source)
