<?php
/**
 * 实例相关的异步队列回调控制器
 * 实例（容器所在的宿主机）
 * 回调方式：websocket
 *
 * TODO::整合入口以及服用的方法
 */

use Amqp\RabbitWorker;
use Yaf\Application;

class InstancemqworkerController extends CliBase
{
    private $mq;       //rabbitmq客户端

    public function init()
    {
        parent::init();
        $this->mq = new RabbitWorker();
    }

    //复制实例组 脚本入口
    public function copyAction()
    {
        $this->mq->setQueueKey('instance_copy');
        $res = $this->mq->wokerInstance([$this, 'copyInstance']);
        if (! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    //复制实例组 回调函数
    public function copyInstance(AMQPEnvelope $message, AMQPQueue $q)
    {
        $info = json_decode($message->getBody(), true);
        if ($info) {
            try {
                //先创建实例组
                $sGroupModel  = new DAO\ServerGroupModel();
                $pServerModel = new DAO\ProjectServerModel();
                $sendMsg[]    = '任务ID：'.$info['cmd_id'].'已执行。';
                //查询被拷贝的实例组是否存在
                $copyGroup = $sGroupModel->findOne($info['copy_group_id']);
                if (! $copyGroup) {
                    $msg       = '被拷贝的实例组不存在,操作不再执行';
                    $sendMsg[] = $msg;
                    if ($sendMsg) {
                        $this->senderMsgByWs(
                            999,
                            $sendMsg,
                            $info['user_id'],
                            'instance_copy'
                        );
                    }
                    $q->ack($message->getDeliveryTag());

                    return;
                }
                //实例组类型是不可复制类型
                if ($copyGroup['type'] == 100) {
                    $msg       = '被拷贝的实例组是不可复制类型,操作不再执行';
                    $sendMsg[] = $msg;
                    if ($sendMsg) {
                        $this->senderMsgByWs(
                            999,
                            $sendMsg,
                            $info['user_id'],
                            'instance_copy'
                        );
                    }
                    $q->ack($message->getDeliveryTag());

                    return;
                }
                //查询被拷贝的实例组下是否有导入的实例；
                $res = $pServerModel->findOneByGroupId(
                    $info['copy_group_id'],
                    100
                );
                if ($res) {
                    $msg       = '被拷贝的实例组中存在导入的实例,操作不再执行';
                    $sendMsg[] = $msg;
                    if ($sendMsg) {
                        $this->senderMsgByWs(
                            400,
                            $sendMsg,
                            $info['user_id'],
                            'instance_copy'
                        );
                    }

                    $q->ack($message->getDeliveryTag());

                    return;
                }
                //创建新的实例组
                $data[':name']       = $info['group_name'];
                $data[':type']       = 200;
                $data[':project_id'] = $copyGroup['project_id'];
                $data['create_at']   = date('Y-m-d H:i:s');
                $data['create_user'] = $info['user_id'];
                $insertId            = $sGroupModel->InsertOne($data);
                if ($insertId['status']) {
                    //查询要被复制的实例组中的实例
                    $instanceArr = $pServerModel->findAllByGroupId(
                        $info['copy_group_id']
                    );
                    if (! empty($instanceArr)) {
                        $all = $success = $fail = 0;
                        foreach ($instanceArr as $instance) {
                            if (in_array(
                                $instance['server_id'],
                                $info['instance_ids'][$instance['type']]
                            )
                            ) {
                                //判断实例名和备注是否存在
                                if (! array_key_exists(
                                    $instance['server_id'],
                                    $info['instance_names'][$instance['type']]
                                )
                                ) {
                                    $msg       = '此实例ID:'.$instance['server_id']
                                        .'没有查找到对应的新实例名称，因此不会被复制，其他跳过复制此实例';
                                    $sendMsg[] = $msg;
                                    if ($sendMsg) {
                                        $this->senderMsgByWs(
                                            400,
                                            $sendMsg,
                                            $info['user_id'],
                                            'instance_copy'
                                        );
                                    }
                                    $fail += 1;
                                    continue;
                                }
                                if (! array_key_exists(
                                    $instance['server_id'],
                                    $info['instance_desp'][$instance['type']]
                                )
                                ) {
                                    $msg       = '此实例ID:'.$instance['server_id']
                                        .'没有查找到对应的新实例备注，因此不会被复制，其他跳过复制此实例';
                                    $sendMsg[] = $msg;
                                    if ($sendMsg) {
                                        $this->senderMsgByWs(
                                            400,
                                            $sendMsg,
                                            $info['user_id'],
                                            'instance_copy'
                                        );
                                    }
                                    $fail += 1;
                                    continue;
                                }
                                $all       += 1;
                                $insertMsg = [];
                            } else {
                                $msg       = '此实例ID:'.$instance['server_id']
                                    .'虽然在实例组中，但是不在提交的任务中，因此不会被复制，只会复制提交的实例';
                                $sendMsg[] = $msg;
                                if ($sendMsg) {
                                    $this->senderMsgByWs(
                                        200,
                                        $sendMsg,
                                        $info['user_id'],
                                        'instance_copy'
                                    );
                                }
                                $searchArr     = [
                                    'task_id'       => ':task_id',
                                    'instance_name' => ':instance_name',
                                ];
                                $updateDataLog = [
                                    ':task_id'       => $info['cmd_id'],
                                    ':instance_name' => $instance['name'],
                                    //':instance_id'   => $serverId['id'],
                                ];
                                $this->updateLog(
                                    $searchArr,
                                    $updateDataLog,
                                    $insertMsg
                                );
                            }
                        }
                        $msg       = '总计应复制实例：'.$all.'个 实际成功复制：'.$success
                            .'个 复制失败：'
                            .$fail.'个';
                        $sendMsg[] = $msg;
                        if ($sendMsg) {
                            $this->senderMsgByWs(
                                999,
                                $sendMsg,
                                $info['user_id'],
                                'instance_copy'
                            );
                        }
                    } else {
                        $msg       = '被拷贝的实例组下不存在实例，已经创建完成实例组';
                        $sendMsg[] = $msg;
                        if ($sendMsg) {
                            $this->senderMsgByWs(
                                200,
                                $sendMsg,
                                $info['user_id'],
                                'instance_copy'
                            );
                        }
                    }
                } else {
                    $msg       = '新实例组写入失败：'.$insertId['error'];
                    $sendMsg[] = $msg;
                    if ($sendMsg) {
                        $this->senderMsgByWs(
                            400,
                            $sendMsg,
                            $info['user_id'],
                            'instance_copy'
                        );
                    }
                }
            } catch (Exception $e) {
                $msg       = '暴异常了，请查看：'.$e->getMessage();
                $sendMsg[] = $msg;
                if ($sendMsg) {
                    $this->senderMsgByWs(
                        400,
                        $sendMsg,
                        $info['user_id'],
                        'instance_copy'
                    );
                }
            }
            unset($sGroupModel, $pServerModel, $tpModel, $model);
        }
        $q->ack($message->getDeliveryTag());
        echo " Received: ", $message->getBody(), PHP_EOL;
        echo " Done", PHP_EOL;
    }

    private function senderMsgByWs(
        $status,
        $msg,
        $user_id,
        $event = 'instance_create',
        $total = null,
        $step  = null
    ) {
        $config = getenv();
        $host   = isset($config['WS_SERVER_HOST']) ? $config['WS_SERVER_HOST']
            : 'localhost';
        $port   = isset($config['WS_SERVER_PORT']) ? $config['WS_SERVER_PORT'] : '9501';
        $model  = new Tools\WebSocketClientModel($host, $port);
        $res    = $model->connect();
        if ($res) {
            $msgOne = [
                'event' => 'sendTo',
                'data'  => [
                    'event'  => $event,
                    'status' => $status,
                    'msg'    => [
                        'msg'    => $msg,
                        'status' => $status,
                        'total'  => $total,
                    ],
                ],
                'uid'   => $user_id,
            ]; //推送指定的人
            if ($step) {
                $msgOne['data']['msg']['step_no'] = 'end';
            }
            $status = $model->send(json_encode($msgOne));
            if ($status) {
                echo json_encode(
                    $msgOne,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ), PHP_EOL;
            }
        } else {
            echo json_encode(
                [
                    'status' => false,
                    'error'  => '请检查websocket服务是否可用',
                ],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
    }

    //删除实例  回调函数

    /**
     * 更新操作日志
     *
     * @param array  $where      pdo的where绑定语句
     *                           task_id=:task_id
     * @param array  $whereValue :task_id=1 ,':project_id' => 1
     * @param [type] $result     [description]
     *
     * @return [type]             [description]
     */
    private function updateLog(array $where, array $whereValue, $result)
    {
        $whereValue[':result'] = json_encode(
            $result,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $model                 = new DAO\OrderOperateLogModel;
        $res                   = $model->updateOneNew($where, $whereValue);
        var_dump($res);
        if (! $res['status']) {
            return ['status' => false, 'error' => $res['error']];
        }

        return ['status' => true];
    }


    //导入实例 脚本入口
    public function includeAction()
    {
        $this->mq->setQueueKey('instance_include');
        $res = $this->mq->wokerInstance([$this, 'includeInstance']);
        if (! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    public function includeInstance(AMQPEnvelope $message, AMQPQueue $q)
    {
        $info = json_decode($message->getBody(), true);

        if ($info) {
            $errMsg    = [];
            $data      = [];
            $event     = 'instance_include';
            $success   = 0;
            $error     = 0;
            $sendMsg = '任务ID：'.$info['cmd_id'].'已执行。';
            echo $sendMsg,PHP_EOL;
            $this->senderMsgByWs(
                200,
                $sendMsg,
                $info['user_id'],
                $event
            );
            echo '-----------------------开始批量导入实例-----------------------'
                .PHP_EOL;
            foreach ($info['data'] as $data) {
                $id         = trim($data['instance']);
                $cloud_type = $data['cloud_type'];
                if ($data['cloud_type'] == 'gaea') {
                    $res = $this->includeGaeaInstance(
                        $data,
                        $info['project_id'],
                        $info['user_id']
                    );
                    echo json_encode(
                        $res,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ).PHP_EOL;
                    if ($res['status'] == 200) {
                        $success += 1;
                        $sendMsg = $data['id'].'导入成功';
                        $this->senderMsgByWs(
                            200,
                            $sendMsg,
                            $info['user_id'],
                            $event
                        );
                    } else {
                        $error    += 1;
                        $sendMsg = $data['id'].'导入失败：'.$res['errorMsg'];
                        $this->senderMsgByWs(
                            400,
                            $sendMsg,
                            $info['user_id'],
                            $event,
                            null
                        );
                    }
                }
            }


            $total     = '批量任务ID：'.$info['cmd_id'].'  总实例·任务数：'.($success
                        + $error).' 成功：'.$success.' 失败：'.$error;
            $sendMsg = $total;
            $this->senderMsgByWs(
                200,
                $sendMsg,
                $info['user_id'],
                $event,
                $total,
                1
            );

            $projectModel = new DAO\ProjectModel();
            $project      = $projectModel->findOne(
                $info['project_id']
            );
            if ($project) {
                $instance = ['id' => 0, 'name' => '', 'internal_ip' => ''];
                $res      = $this->insertOperateLog(
                    $info['cmd_id'],
                    $project,
                    $instance,
                    $info['user_id'],
                    $info['data'],
                    $push_data,
                    '批量导入实例',
                    $cloud_type
                );
                if (! $res) {
                    var_dump($res);
                }
            }
            unset($projectModel);
            $q->ack($message->getDeliveryTag());
            echo " Received: ", $message->getBody(), PHP_EOL;
            echo " Done", PHP_EOL;
        }
    }
    //导入gaea服务器
    private function includeGaeaInstance($serverInfo, $pid, $uid)
    {
        //入库
        $create_time                 = date('Y-m-d H:i:s');
        $serverData                  = $pjServerData = [];
        $serverData[':name']         = $serverInfo['instance'];
        $serverData[':status']       = 200; //运行状态
        $serverData[':public_ip']    = $serverInfo['public_ip'];
        $serverData[':internal_ip']  = $serverInfo['internal_ip'];
        $serverData[':create_time']  = $create_time;
        $serverData[':include_type'] = 100;
        $serverData[':cpu']          = $serverInfo['cpu'];
        $serverData[':ram']          = $serverInfo['ram'];
        $serverData[':cds']         = $serverInfo['cds'];
        $serverModel                 = new DAO\GaeaServerModel();
        $serverId                    = $serverModel->insertOne($serverData);
        unset($serverModel);
        sleep(1);
        if (isset($serverId['id'])) {
            // var_dump($serverData);
            $pjServerData[':project_id']   = $pid;
            $pjServerData[':server_id']    = $serverId['id'];
            $pjServerData[':type']         = $serverInfo['cloud_type'];
            $pjServerData[':create_at']    = $create_time;
            $pjServerData[':name']         = $serverInfo['instance'];
            $pjServerData[':description']  = $serverInfo['description'];
            $pjServerData[':create_user']  = $uid;
            $pjServerData[':run_status']   = 'Running';
            $pjServerData[':status']
                                           = DAO\ProjectServerModel::STATUS_SUCCESS;
            $pjServerData[':group_id']     = 0;
            $pjServerData[':compose_id']   = 0;
            $pjServerData[':template_id']  = 0;
            $pjServerData[':include_type'] = 100;
            $pjServerModel                 = new DAO\ProjectServerModel();
            $res                           = $pjServerModel->InsertOne(
                $pjServerData
            );
            unset($pjServerModel);
            if (isset($res['id'])) {
                return [
                    'status' => 200,
                    'data'   => 'server_name---'.$serverInfo['instance']
                        .' 导入成功',
                ];
            } else {
                $msg = 'server_name---'.$serverInfo['instance']
                    .'对应关系写入失败（project_server）,请将错误信息发送给开发人员:'.$res['error'];

                return ['status' => 400, 'errorMsg' => $msg];
            }
        } else {
            $errMsg[]
                = $msg = 'server_name---'.$serverInfo['instance']
                .'实例详情写入失败（gaea_server）,请将错误信息发送给开发人员:'.$serverId['error'];

            return ['status' => 400, 'errorMsg' => $msg];
        }
    }

    //加入操作日志
    private function insertOperateLog(
        $task_id,
        $project,
        $instance,
        $uid,
        $body,
        $result,
        $operate,
        $cloud_type
    ) {
        $data  = [
            ':task_id'       => $task_id,
            ':task_type'     => 'async',
            ':step_no'       => 1,
            ':project_id'    => $project['id'],
            ':project_name'  => $project['name'],
            ':instance_id'   => $instance['id'],
            ':instance_name' => $instance['name'],
            ':cloud_type'    => $cloud_type,
            ':ip'            => $instance['internal_ip'],
            ':uid'           => $uid,
            ':request'       => json_encode(
                $body,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            ':result'        => json_encode(
                $result,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            ':operate'       => $operate,
        ];
        $model = new DAO\OrderOperateLogModel;
        $res   = $model->insertOne($data);
        if (! $res['status']) {
            return $this->json = ['status' => false, 'error' => $res['error']];
        }

        return $this->json = ['status' => true];
    }
}
