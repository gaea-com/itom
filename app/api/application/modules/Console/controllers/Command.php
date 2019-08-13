<?php

use Amqp\RabbitWorker;
use DAO\GaeaServerModel;
use DAO\ProjectModel;
use DAO\ProjectServerModel;
use Yaf\Application;

/**
 * Class CommandController
 * 容器相关命令 控制器
 *
 * php cli.php command {action}
 */
class CommandController extends CliBase
{
    private $mq;       //rabbitmq客户端

    public function init()
    {
        parent::init();
        $this->mq = new RabbitWorker();
    }

    /**
     * Console常驻脚本——创建容器createcan
     * 用于消费createcan消息队列消息，并通过回调函数执行业务
     */
    public function createcanAction()
    {
        $this->mq->setQueueKey('createcan');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'createContain',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    /**
     * 创建容器回调脚本
     *
     * 业务流程：
     * 从mq队列中获取消息，然后通过redis获得taskID里面保存的创建详情
     * 1：更新宿主机的容器列表
     * 2：检测容器设置（宿主机上是否有镜像，编排模板和环境变量模板的设置）
     * 3：启动容器，向dockerAPI发送启动命令
     * 4：推送websocket启动结果
     *
     * @param AMQPEnvelope $message
     * @param AMQPQueue    $q
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function createContain(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request = json_decode($message->getBody(), true);
        $info    = $this->getApiRequest($request['redisKey']);
        $uid     = $request['uid'];
        $pid     = $request['pid'];
        $ischeck = $request['ischeck'];
        $task_id = $request['redisKey'];
        $tag     = 'container_create';
        echo '['.$tag.']: '.$task_id.PHP_EOL;
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '['.$tag.']: '.$task_id,
            ],
            $uid,
            $tag
        );
        if (empty($info)) {
            $this->sendWs(
                ['status' => 400, 'id' => $task_id, 'error' => '请求为空'],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $this->sendWs(
            ['status' => 200, 'id' => $task_id, 'total' => '开始更新容器列表'],
            $uid,
            $tag
        );
        //更新容器列表
        $res = $this->upContainers($pid, $uid, $info, false, $task_id);
        if ($res['status'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => $res['errorMsg'],
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '容器列表更新完成,开始检测实例容器配置',
            ],
            $uid,
            $tag
        );
        //检测各实例容器配置
        $error = [];
        foreach ($info as $key => $value) {
            $res = $this->checkCreateCan(
                $value['project_id'],
                $value['instance_id'],
                $value['compose_id'],
                $value['cloud_type']
            );
            if ($res['status'] == 400) {
                unset($info[$key]);
                $error[$key] = $res['errorMsg'];
            } else {
                $info[$key]['envArr'] = $res['envArr'];
            }
        }
        if ($error) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '配置检测不通过:'.json_encode(
                            $error,
                            JSON_UNESCAPED_UNICODE
                        ),
                    'data'   => $error,
                ],
                $uid,
                $tag
            );
            echo json_encode(
                    $error,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ).PHP_EOL;
            $q->ack($message->getDeliveryTag());

            return;
        }

        if (empty($info)) {
            $this->sendWs(
                ['status' => 400, 'id' => $task_id, 'error' => '无可启动的容器1',],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $this->sendWs(
            ['status' => 200, 'id' => $task_id, 'total' => '检测实例容器配置完成',],
            $uid,
            $tag
        );
        //启动容器
        $body          = [];
        $insertDataArr = [];
        $totalCount    = 0;
        foreach ($info as $value) {
            $body[]     = $server_body = $this->createcanformat(
                $value,
                $value['envArr'],
                $uid,
                $task_id,
                $insertDataArr
            );
            $totalCount += count($server_body);
        }
        if ( ! $totalCount) {
            $this->sendWs(
                ['status' => 400, 'id' => $task_id, 'error' => '无可启动的容器2',],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $total = '任务开始, 待启动容器个数：'.$totalCount;
        $this->sendWs(
            ['status' => 200, 'id' => $task_id, 'total' => $total,],
            $uid,
            $tag
        );
        //发送启动任务到dockerAPI
        $res = $this->getResSync($body, 'docker_contrl', 'docker_task');
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .PHP_EOL;
        if ( ! isset($res['http']) || $res['http'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => 'http connect faild or connect timeout',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $success
                              = $error = 0;
        $model                = new DAO\ProjectDockerModel;
        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        $resultArr            = [];
        $RedisModel           = new Tools\RedisModel();

        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断',
                    ],
                    $uid,
                    $tag
                );
                break;
            }
            echo '['.date('Y-m-d H:i:s').']', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status'])) {
                $resultArr[$value['ip']][] = $value;
                if ($value['status']) {
                    $newKey                      = $value['image_name'].'_'
                        .$value['hostname'];
                    $insertData
                                                 = $insertDataArr[$value['ip']][$newKey];
                    $insertData[':container_id'] = $value['id'];
                    $model->insertOne($insertData);
                    $success++;
                    $total = '任务进行中, 启动容器成功：'.($success + $error).'/'
                        .$totalCount;
                    $this->sendWs(
                        [
                            'status' => 200,
                            'id'     => $task_id,
                            'total'  => $total,
                            'count'  => 1,
                        ],
                        $uid,
                        $tag
                    );
                } else {
                    $error++;
                    $total
                        = '任务进行中, 启动容器错误：'.($success + $error).'/'.$totalCount
                        .', ip'.$value['ip'].', error: '.$value['error'];
                    $this->sendWs(
                        [
                            'status' => 400,
                            'id'     => $task_id,
                            'error'  => $total,
                            'count'  => 1,
                        ],
                        $uid,
                        $tag
                    );
                }
            }
        }
        foreach ($resultArr as $ip => $result) {
            //加入操作日志
            $OrderOperateLogModel->updateOne(
                $task_id,
                $ip,
                json_encode(
                    $result,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
        }
        $total = '批量任务ID：'.$task_id.'  总任务数：'.$totalCount.' 成功：'.$success.' 失败：'
            .($error);
        $res   = $this->sendWs(
            [
                'status'  => 200,
                'id'      => $task_id,
                'total'   => $total,
                'step_no' => 'end',
            ],
            $uid,
            $tag
        );
        $q->ack($message->getDeliveryTag());
        echo '请求结束------------------------------------------'.PHP_EOL;
    }

    /**
     * 从redis中获取要发送给dockerAPI中的任务信息
     * 获取后直接清楚原信息
     *
     * @param $id
     *
     * @return mixed|void
     * @throws Exception
     */
    private function getApiRequest($id)
    {
        $RedisModel = new Tools\RedisModel();
        $status     = $RedisModel->redis->hGet('docker_api_reqeust', $id);
        $RedisModel->redis->hDel('docker_api_reqeust', $id);
        if ($status === false) {
            return;
        }

        return json_decode($status, true);
    }

    /**
     * 推送websocket消息给前端
     *
     * @param        $msg
     * @param        $uid
     * @param string $event
     */
    private function sendWs($msg, $uid, $event = 'new_msg')
    {
        $config = getenv();
        $host   = isset($config['WS_SERVER_HOST']) ? $config['WS_SERVER_HOST']
            : 'localhost';
        $port   = isset($config['WS_SERVER_PORT']) ? $config['WS_SERVER_PORT'] : '9501';
        $model  = new Tools\WebSocketClientModel($host, $port);
        $res    = $model->connect();
        $return = [];
        if ($res) {
            $msgOne = [
                'event' => 'sendTo',
                'data'  => [
                    'event' => $event,
                    'msg'   => $msg,
                ],
                'uid'   => $uid,
            ];
            $status = $model->send(json_encode($msgOne));
            if ($status) {
                $return = [
                    'status' => true,
                    'msg'    => '推送成功',
                    'data'   => $msgOne,
                ];
            } else {
                sleep(1);
                $tmp = $model->recv(); // 如果有信息返回，则打印
                // var_dump($tmp);
                $return = [
                    'status' => false,
                    'error'  => $tmp,
                ];
            }
        } else {
            $return = [
                'status' => false,
                'error'  => '请检查websocket服务是否可用',
            ];
        }
        echo '['.date('Y-m-d H:i:s').']'.json_encode(
                $return,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
    }

    /**
     * 更新项目的容器列表
     *     将任务发送给dockerAPI，等待回调信息，然后入库
     *
     * @param      $project_id
     * @param      $uid
     * @param      $project_servers
     * @param bool $is_send
     * @param null $task_id
     *
     * @return array
     * @throws Exception
     */
    private function upContainers(
        $project_id,
        $uid,
        $project_servers,
        $is_send = false,
        $task_id = null
    ) {
        $body         = [];
        $request      = [];
        $projectModel = new DAO\ProjectModel();
        $project      = $projectModel->findOneByCache($project_id, $uid);
        if ( ! $project_id) {
            return [
                'status'   => 400,
                'errorMsg' => '项目不存在, pid: '.$project_id,
            ];
        }
        if (empty($project_servers)) {
            return [
                'status'   => 400,
                'errorMsg' => '请求不能为空',
            ];
        }
        foreach ($project_servers as $key => $ps) {
            $serverModel = DAO\ProjectServerModel::getServerModel(
                $ps['cloud_type']
            );
            $instance    = $serverModel->findOneByCache($ps['instance_id']);
            $body[]      = [
                'ip'     => $instance['internal_ip'],
                'type'   => 'container_list',
                "option" => [
                    "filter_status" => "running",
                ],
            ];
            unset($serverModel);
            if ( ! array_key_exists($instance['internal_ip'], $request)) {
                $desArr         = [];
                $ServerEnvModel = new DAO\ServerEnvModel();
                $serverEnvs     = $ServerEnvModel->findAll(
                    [
                        'server_id' => $ps['instance_id'],
                        'type'      => $ps['cloud_type'],
                    ]
                );
                foreach ($serverEnvs as $serverEnv) {
                    $newKey          = md5(
                        $serverEnv['image_name'].'_'
                        .$serverEnv['container_name']
                    );
                    $desArr[$newKey] = $serverEnv['container_describe'];
                }
                $instance['cloud_type']            = $ps['cloud_type'];
                $instance['desArr']                = $desArr;
                $request[$instance['internal_ip']] = $instance;
                unset($ServerEnvModel);
            }
        }
        $totalCount = count($body);
        $tag        = $is_send ? 'container_list' : 'container_create';
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '任务开始, 待更新实例个数：'.$totalCount,
            ],
            $uid,
            $tag
        );
        $res = $this->getResSync($body, 'docker_contrl', 'docker');
        if ( ! isset($res) || $res['http'] != 200) {
            return [
                'status'   => 400,
                'errorMsg' => 'http connect faild or connect timeout',
            ];
        }
        $RedisModel = new Tools\RedisModel();

        $errorArr             = [];
        $model                = new DAO\ProjectDockerModel;
        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        $success
                              = $error = 0;
        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断',
                    ],
                    $uid,
                    $tag
                );
                break;
            }
            echo '['.date('Y-m-d H:i:s').']', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status'])) {
                $req        = $request[$value['ip']];
                $result     = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                $insertData = [
                    'task_id'       => ! $is_send ? DockerApiModel::getId()
                        : $task_id,
                    'task_type'     => 'async',
                    'step_no'       => 1,
                    'project_id'    => $project['id'],
                    'project_name'  => $project['name'],
                    'instance_id'   => $req['id'],
                    'instance_name' => $req['name'],
                    'cloud_type'    => $req['cloud_type'],
                    'ip'            => $req['internal_ip'],
                    'uid'           => $uid,
                    'request'       => json_encode(
                        [
                            'ip'   => $req['internal_ip'],
                            'type' => 'container_list',
                        ],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'result'        => $result,
                    'operate'       => 'container_list',
                ];
                $OrderOperateLogModel->insertOneNew($insertData);
                if ($value['status']) {
                    $data   = [];
                    $desArr = [];
                    $desArr = $req['desArr'];
                    foreach ($value['container_list'] as $container) {
                        $image_name  = explode(':', $container['image'])[0];
                        $newKey      = md5(
                            $image_name.'_'.$container['hostname']
                        );
                        $description = ! empty($desArr[$newKey])
                            ? $desArr[$newKey] : '未知';
                        $data[]      = [
                            ':container_id' => $container['id'],
                            ':name'         => $container['hostname'],
                            ':status'       => ($container['container_status']
                                == 'running')
                                ? DAO\ProjectDockerModel::STATUS_OPEN
                                : DAO\ProjectDockerModel::STATUS_CLOSE,
                            ':instance_id'  => $req['id'],
                            ':project_id'   => $project_id,
                            ':image_name'   => $container['image'],
                            ':ip'           => $req['internal_ip'],
                            ':create_at'    => date('Y-m-d H:i:s'),
                            ':create_user'  => $uid,
                            ':cloud_type'   => $req['cloud_type'],
                            ':description'  => $description,
                        ];
                    }
                    $model->updateContainers($value['ip'], $data);
                    $success++;
                    $total = '任务进行中, 更新容器列表成功：'.($success + $error).'/'
                        .$totalCount;
                    $is_send ? $this->sendWs(
                        [
                            'status'  => 200,
                            'id'      => $task_id,
                            'count'   => 1,
                            'total'   => $total,
                            'step_no' => 'end',
                        ],
                        $uid,
                        $tag
                    ) : null;
                } else {
                    $errorArr[] = $value;
                    $error++;
                    $total
                        = '任务进行中, 更新容器列表失败：'.($success + $error).'/'.$totalCount
                        .', error :'.$value['error'];
                    $is_send ? $this->sendWs(
                        [
                            'status' => 400,
                            'id'     => $task_id,
                            'count'  => 1,
                            'error'  => $total,
                        ],
                        $uid,
                        $tag
                    ) : null;
                }
            }
        };
        unset($model, $OrderOperateLogModel);
        if ($errorArr) {
            return [
                'status'   => 400,
                'errorMsg' => '更新容器列表部分失败, '.json_encode(
                        $errorArr,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
            ];
        }
        if ($is_send) {
            $total = '批量任务ID：'.$task_id.'  总任务数：'.$totalCount.' 成功：'.$success
                .' 失败：'.$error;
            $res   = $this->sendWs(
                [
                    'status'  => 200,
                    'id'      => $task_id,
                    'total'   => $total,
                    'step_no' => 'end',
                ],
                $uid,
                $tag
            );
        }

        return [
            'status' => 200,
            'id'     => $task_id,
        ];
    }

    /**
     * 将任务发送给dockerAPI
     *
     * @param $body
     * @param $tag
     * @param $type
     *
     * @return array|false
     */
    private function getResSync($body, $tag, $type)
    {
        $env  = getenv();
        $host = ! empty($env['DOCKER_API_HOST']) ? $env['DOCKER_API_HOST']
            : null;
        $port = ! empty($env['DOCKER_API_PORT']) ? $env['DOCKER_API_PORT']
            : null;
        $url  = $host.':'.$port.'/'.$tag;
        $data = [
            'type' => $type,
            'data' => base64_encode(
                json_encode(
                    $body,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            ),
        ];
        $res  = Tools\FuncModel::ycurl($url, $data);

        return $res;
    }

    /**
     * 检查容器设置
     * 1.容器镜像是否存在
     * 2.compose编排模板设置
     * 3.env环境变量模板设置
     *
     * @param $project_id
     * @param $instance_id
     * @param $compose_id
     * @param $cloud_type
     * @param $envDiffArr
     *
     * @return array
     */
    private function checkCreateCan(
        $project_id,
        $instance_id,
        $compose_id,
        $cloud_type
    ) {
        $DockerComposeModel = new DAO\DockerComposeModel();
        $compose            = $DockerComposeModel->getDockerComposeById(
            $compose_id,
            $instance_id,
            $cloud_type
        );
        $serverModel        = DAO\ProjectServerModel::getServerModel(
            $cloud_type
        );
        $instance           = $serverModel->findOneByCache($instance_id);
        $msg                = '实例:'.$instance['name'];
        if ( ! $compose) {
            return ['status' => 400, 'errorMsg' => $msg.'容器编排模板不存在',];
        }
        if (empty($compose['image_name'])) {
            return ['status' => 400, 'errorMsg' => $msg.'编排模板中没有可创建的容器镜像',];
        }
        $ServerEnvModel = new DAO\ServerEnvModel();
        $res            = $ServerEnvModel->findOneByServerInsNew(
            $instance_id,
            $cloud_type,
            $instance['internal_ip'],
            $compose
        );
        if ( ! $res['status']) {
            return ['status' => 400, 'errorMsg' => $res['error'],];
        }
        $envs = $res['data'];
        foreach ($envs as $env['data']) {
            if ( ! empty($env['env']) && is_array($env['env'])) {
                foreach ($env['env'] as $k => $v) {
                    if (empty($v) && $v === '') {
                        return [
                            'status'   => 400,
                            'errorMsg' => $msg.'环境变量的值不能为空',
                        ];
                    }
                }
            }
        }

        return [
            'status' => 200,
            'envArr' => $envs,
        ];
    }

    /**
     * 将创建的容器信息写入操作日志
     *
     * @param $info
     * @param $envArr
     * @param $uid
     * @param $task_id
     * @param $insertDataArr
     *
     * @return array
     */
    private function createcanformat(
        $info,
        $envArr,
        $uid,
        $task_id,
        &$insertDataArr
    ) {
        $projectModel = new DAO\ProjectModel();
        $project      = $projectModel->findOneByCache(
            $info['project_id'],
            $uid
        );
        $serverModel  = DAO\ProjectServerModel::getServerModel(
            $info['cloud_type']
        );
        $instance     = $serverModel->findOneByCache($info['instance_id']);
        $body         = [];
        foreach ($envArr as $env) {
            $option = [
                "network_mode" => "host",
                "hostname"     => $env['container_name'],
                "image"        => $env['image_name'],
            ];
            if ( ! empty($env['data']['env'])
                && is_array(
                    $env['data']['env']
                )
            ) {
                $option['environment'] = $env['data']['env'];
            }
            if ( ! empty($env['data']['data'])
                && is_array(
                    $env['data']['data']
                )
            ) {
                foreach ($env['data']['data'] as $k => $v) {
                    if ( ! empty($v)) {
                        $option['volumes'][$v] = [
                            'bind' => $k,
                            'mode' => 'rw',
                        ];
                    }
                }
            }
            //            if ($project['status'] == ProjectModel::STATUS_GDB) {
            //                $option['privileged']     = true;
            //                $option['restart_policy'] = [
            //                    "Name"              => "on-failure",
            //                    "MaximumRetryCount" => 5,
            //                ];
            //           }
            $body[]                                           = [
                "ip"         => $instance['internal_ip'],
                "type"       => "container_create",
                'sleep_time' => $env['sleep_time'],
                "option"     => $option,
            ];
            $newKey
                                                              = $env['image_name']
                .'_'.$env['container_name'];
            $insertDataArr[$instance['internal_ip']][$newKey] = [
                ':name'        => $env['container_name'],
                ':description' => $env['container_describe'],
                ':project_id'  => $project['id'],
                ':instance_id' => $instance['id'],
                ':cloud_type'  => $info['cloud_type'],
                ':image_name'  => $env['image_name'],
                ':ip'          => $instance['internal_ip'],
                ':create_user' => $uid,
                ':create_at'   => date('Y-m-d H:i:s'),
            ];
        }
        $insertData = [
            'task_id'       => $task_id,
            'task_type'     => 'async',
            'step_no'       => 1,
            'project_id'    => $project['id'],
            'project_name'  => $project['name'],
            'instance_id'   => $instance['id'],
            'instance_name' => $instance['name'],
            'cloud_type'    => $info['cloud_type'],
            'ip'            => $instance['internal_ip'],
            'uid'           => $uid,
            'request'       => json_encode(
                $body,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'result'        => '',
            'operate'       => 'container_create',
        ];
        //加入操作日志
        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        $OrderOperateLogModel->insertOneNew($insertData);
        unset($OrderOperateLogModel, $ProjectModel, $serverModel);

        return $body;
    }

    /**
     *  Console常驻脚本--更新项目的容器列表updatecans
     * 用于消费updatecans消息队列消息，并通过回调函数执行业务
     */
    public function updatecansAction()
    {
        $this->mq->setQueueKey('updatecans');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'updateContainers',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    /**
     * 更新项目的容器列表回调脚本
     * 1.获取dockerAPI请求信息
     * 2.更新项目的容器列表
     *
     * @param AMQPEnvelope $message
     * @param AMQPQueue    $q
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function updateContainers(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request = json_decode($message->getBody(), true);
        $info    = $this->getApiRequest($request['redisKey']);
        $uid     = $request['uid'];
        $pid     = $request['pid'];
        $task_id = $request['redisKey'];
        $tag     = 'container_list';
        echo '['.$tag.']: '.$task_id.PHP_EOL;
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '['.$tag.']: '.$task_id,
            ],
            $uid,
            $tag
        );
        //更新容器列表
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '开始更新容器列表',
            ],
            $uid,
            $tag
        );
        $res = $this->upContainers($pid, $uid, $info, true, $task_id);
        if ($res['status'] != 200) {
            $this->sendWs(
                [
                    'status'  => 400,
                    'id'      => $task_id,
                    'error'   => $res['errorMsg'],
                    'step_no' => 'end',
                ],
                $uid,
                $tag
            );
        }
        $q->ack($message->getDeliveryTag());
        echo '请求结束------------------------------------------'.PHP_EOL;
    }

    /**
     * Console常驻脚本——向container中发送命令cancmd
     * 用于消费cancmd消息队列消息，并通过回调函数执行业务
     */
    public function cancmdAction()
    {
        $this->mq->setQueueKey('cancmd');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'canCmd',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    /**
     * 向container中发送命令回调函数
     * 有可能是一组命令，有顺序，支持顺序
     * TODO::阻塞问题
     *
     * @param AMQPEnvelope $message
     * @param AMQPQueue    $q
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function canCmd(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request    = json_decode($message->getBody(), true);
        $body       = $this->getApiRequest($request['redisKey']);
        $uid        = $request['uid'];
        $task_id    = $request['redisKey'];
        $totalCount = $request['totalCount'];
        $tag        = 'container_cmd';
        if (empty($body)) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '未获取到发送docker的需要执行的相关数据',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }

        echo '['.$tag.']: '.$task_id.PHP_EOL;
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '任务开始, 待执行命令容器个数：'.$totalCount,
            ],
            $uid,
            $tag
        );
        $res = $this->getResSync($body, 'docker_contrl', 'docker_task');
        if ( ! isset($res) || $res['http'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => 'http connect faild or connect timeout',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $RedisModel           = new Tools\RedisModel();
        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        $success              = 0;
        $error                = 0;
        $resultData           = [];
        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断',
                    ],
                    $uid,
                    $tag
                );
                break;
            }

            echo '['.date('Y-m-d H:i:s')
                .']', '任务ID是：', $res['task_id'], 'redis中的数据：', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            if ( ! isset($cons[1])) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '无法解析redis中的cons：'.json_encode($cons),
                    ],
                    $uid,
                    $tag
                );
                break;
            }
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status']) && isset($value['ip'])) {
                if ($value['status']) {
                    $success++;
                    $total = '任务进行中, 执行命令成功：'.($success + $error).'/'
                        .$totalCount;
                    $this->sendWs(
                        [
                            'status' => 200,
                            'id'     => $task_id,
                            'total'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        $tag
                    );
                    if ( ! empty($value['stdout'])) {
                        $value['stdout'] = str_replace(
                            "\n",
                            '<br/>',
                            mb_substr($value['stdout'], -1000)
                        );
                    }
                } else {
                    $error++;
                    if ( ! empty($value['stdout'])) {
                        $total = '任务进行中, 执行命令失败：'.($success + $error).'/'
                            .$totalCount.', error: '.$value['stdout'];
                    } else {
                        $total = '任务进行中, 执行命令失败：'.($success + $error).'/'
                            .$totalCount.', error: '.$value['error'];
                    }

                    $this->sendWs(
                        [
                            'status' => 400,
                            'id'     => $task_id,
                            'error'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        $tag
                    );
                }
                $resultData[$value['ip']][] = $value;
            }
        }
        foreach ($resultData as $ip => $result) {
            $result = json_encode(
                $result,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $OrderOperateLogModel->updateOne($task_id, $ip, $result);
        }
        unset($OrderOperateLogModel);
        $total = '批量任务ID：'.$task_id.'  总任务数：'.$totalCount.' 成功：'.$success.' 失败：'
            .($error);
        $res   = $this->sendWs(
            [
                'status'  => 200,
                'id'      => $task_id,
                'total'   => $total,
                'step_no' => 'end',
            ],
            $uid,
            $tag
        );
        $q->ack($message->getDeliveryTag());
        echo '请求结束------------------------------------------'.PHP_EOL;
    }

    /**
     * Console常驻脚本——更新镜像列表upimages docker images
     * 用于消费upimages消息队列消息，并通过回调函数执行业务
     */
    public function upimagesAction()
    {
        $this->mq->setQueueKey('upimages');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'upImages',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    /**
     * 更新镜像列表回调脚本
     * 不是编排模板中的镜像不入库
     *
     * @param AMQPEnvelope $message
     * @param AMQPQueue    $q
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function upImages(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request = json_decode($message->getBody(), true);
        $body    = $this->getApiRequest($request['redisKey']);
        $uid     = $request['uid'];
        $task_id = $request['redisKey'];
        $tag     = 'image_list';
        if (empty($body)) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '未获取到发送docker的需要执行的相关数据',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        echo '['.$tag.']: '.$task_id.PHP_EOL;
        $totalCount = count($body);
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '任务开始, 待执行命令实例个数：'.$totalCount,
            ],
            $uid,
            $tag
        );
        $res = $this->getResSync($body, 'docker_contrl', 'docker');
        echo '['.date('Y-m-d H:i:s').']'.json_encode(
                $res,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
        if ( ! isset($res) || $res['http'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => 'http connect faild or connect timeout',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }

        $RedisModel = new Tools\RedisModel();
        $success
                    = $error = 0;
        $resultData = [];
        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断',
                    ],
                    $uid,
                    $tag
                );
                break;
            }

            echo '['.date('Y-m-d H:i:s').']', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status'])) {
                if ($value['status']) {
                    $image_data = [];
                    //不是编排模板中的镜像不入库
                    $serverModel = new DAO\GaeaServerModel();
                    $serverInfo  = $serverModel->finOneByIp($value['ip']);
                    if ( ! empty($serverInfo)) {
                        $psModel           = new DAO\ProjectServerModel();
                        $projectServerInfo = $psModel->findOneBindByServerId(
                            $serverInfo['id'],
                            'gaea'
                        );
                        if ( ! empty($projectServerInfo)
                            && $projectServerInfo <> 0
                        ) {
                            $composeModel = new DAO\DockerComposeModel();
                            $composeInfo  = $composeModel->findOne(
                                $projectServerInfo['compose_id']
                            );
                            if ( ! empty($composeInfo)) {
                                $imageArr = json_decode(
                                    $composeInfo['image_name'],
                                    true
                                );
                                foreach ($value['image_list'] as $image) {
                                    $nameVsersion = $image['name_version']
                                        ? $image['name_version'][0] : '';
                                    if (in_array($nameVsersion, $imageArr)) {
                                        $image_data[] = [
                                            ':image_id'     => $image['id'],
                                            ':short_id'     => $image['short_id'],
                                            ':name_version' => $nameVsersion,
                                            ':ip'           => $value['ip'],
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    if ( ! empty($image_data)) {
                        $DockerImageModel = new DAO\DockerImageModel;
                        $DockerImageModel->updateInfo(
                            $image_data,
                            $value['ip']
                        );
                        unset($DockerImageModel);
                    }
                    unset($serverModel, $psModel, $composeModel);

                    $success++;
                    $total = '任务进行中, 执行命令实例个数：'.($success + $error).'/'
                        .$totalCount;
                    $this->sendWs(
                        [
                            'status' => 200,
                            'id'     => $task_id,
                            'total'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        $tag
                    );
                } else {
                    $error++;
                    $total = '任务进行中, 执行命令失败：'.($success + $error).'/'
                        .$totalCount.', error: '.$value['error'];
                    $this->sendWs(
                        [
                            'status' => 400,
                            'id'     => $task_id,
                            'error'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        $tag
                    );
                }
            }
        }
        $total = '批量任务ID：'.$task_id.'  总任务数：'.$totalCount.' 成功：'.$success.' 失败：'
            .($error);
        $res   = $this->sendWs(
            [
                'status'  => 200,
                'id'      => $task_id,
                'total'   => $total,
                'step_no' => 'end',
            ],
            $uid,
            $tag
        );
        $q->ack($message->getDeliveryTag());
        echo '请求结束------------------------------------------'.PHP_EOL;
    }

    /**
     * Console常驻脚本——拉取镜像到宿主机pullimages  docker pull image
     * 用于消费pullimages消息队列消息，并通过回调函数执行业务
     */
    public function pullimagesAction()
    {
        $this->mq->setQueueKey('pullimages');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'pullImages',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    /**
     * 拉取镜像到宿主机回调脚本
     *
     * @param AMQPEnvelope $message
     * @param AMQPQueue    $q
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function pullImages(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request = json_decode($message->getBody(), true);
        $info    = $this->getApiRequest($request['redisKey']);

        $uid     = $request['uid'];
        $task_id = $request['redisKey'];
        $tag     = 'image_pull';
        if (empty($info)) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '未获取到发送docker的需要执行的相关数据',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        echo '['.$tag.']: '.$task_id.PHP_EOL;
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '['.$tag.']: '.$task_id,
            ],
            $uid,
            $tag
        );
        $body = [];
        $req  = [];
        foreach ($info as $ip => $value) {
            foreach ($value['imageArr'] as $image) {
                $req[$ip][]
                    = $body[] = [
                    'ip'     => $ip,
                    'type'   => 'image_pull',
                    'option' => ['name_version' => $image],
                ];
            }
        }
        $totalCount = count($body);
        $total      = '任务开始, 待拉取镜像个数：'.$totalCount;
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => $total,
            ],
            $uid,
            $tag
        );
        $res = $this->getResSync($body, 'docker_contrl', 'docker');
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .PHP_EOL;
        if ( ! isset($res['http']) || $res['http'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => 'http connect faild or connect timeout',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $success
                    = $error = 0;
        $resultArr  = [];
        $RedisModel = new Tools\RedisModel();

        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断，可能镜像较大，下载需要时间，隔段时间请重试',
                    ],
                    $uid,
                    $tag
                );
                break;
            }

            echo '['.date('Y-m-d H:i:s').']', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status']) && $value['status'] === true) {
                $resultArr[$value['ip']][] = $value;
                if ($value['status']) {
                    //需要判断一下镜像是否属于compose中的镜像，如果不是，则不更新进去
                    $serverModel = new GaeaServerModel();
                    $serverInfo  = $serverModel->finOneByIp($value['ip']);
                    if (isset($serverInfo['id'])) {
                        $DockerImageModel = new DAO\DockerImageModel;
                        $DockerImageModel->updateOne(
                            $value['id'],
                            $value['short_id'],
                            $value['ip'],
                            $value['name']
                        );
                        unset($DockerImageModel);
                        $success++;
                        $total = '任务进行中, 拉取镜像个数：'.($success + $error).'/'
                            .$totalCount;
                        $this->sendWs(
                            [
                                'status' => 200,
                                'id'     => $task_id,
                                'count'  => 1,
                                'total'  => $total,
                            ],
                            $uid,
                            $tag
                        );
                    } else {
                    }
                } else {
                    $error++;
                    $total = '任务进行中, 拉取镜像错误：'.($success + $error).'/'
                        .$totalCount.', error: '.$value['error'];
                    $this->sendWs(
                        [
                            'status' => 400,
                            'id'     => $task_id,
                            'count'  => 1,
                            'error'  => $total,
                        ],
                        $uid,
                        $tag
                    );
                }
            }
        }
        $model                = new DAO\ProjectServerModel;
        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        foreach ($resultArr as $ip => $value) {
            $result     = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $instance   = $info[$ip]['instance'];
            $project    = $info[$ip]['project'];
            $insertData = [
                'task_id'       => $task_id,
                'task_type'     => 'async',
                'step_no'       => 1,
                'project_id'    => $project['id'],
                'project_name'  => $project['name'],
                'instance_id'   => $instance['id'],
                'instance_name' => $instance['name'],
                'cloud_type'    => $info[$ip]['cloud_type'],
                'ip'            => $instance['internal_ip'],
                'uid'           => $uid,
                'request'       => json_encode(
                    $req[$ip],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'result'        => $result,
                'operate'       => 'image_pull',
            ];
            $OrderOperateLogModel->insertOneNew($insertData);
            $model->delComposeChangeServer(
                $instance['id'],
                $info[$ip]['cloud_type']
            );
        }
        unset($model, $OrderOperateLogModel);
        $total = '批量任务ID：'.$task_id.'  总任务数：'.$totalCount.' 成功：'.$success.' 失败：'
            .($error);
        $res   = $this->sendWs(
            [
                'status'  => 200,
                'id'      => $task_id,
                'total'   => $total,
                'step_no' => 'end',
            ],
            $uid,
            $tag
        );
        $q->ack($message->getDeliveryTag());
        echo '请求结束------------------------------------------'.PHP_EOL;
    }

    /**
     * Console常驻脚本——停止容器stopcans
     * 用于消费stopcans消息队列消息，并通过回调函数执行业务
     */
    public function stopcansAction()
    {
        $this->mq->setQueueKey('stopcans');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'stopContianers',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    /**
     * 停止容器回调脚本
     *
     * @param AMQPEnvelope $message
     * @param AMQPQueue    $q
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function stopContianers(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request    = json_decode($message->getBody(), true);
        $body       = $this->getApiRequest($request['redisKey']);
        $uid        = $request['uid'];
        $task_id    = $request['redisKey'];
        $totalCount = $request['totalCount'];
        $tag        = 'container_toggle';
        if (empty($body)) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '未获取到发送docker的需要执行的相关数据',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        echo '['.$tag.']: '.$task_id.PHP_EOL;
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '任务开始, 待关闭容器个数：'.$totalCount,
            ],
            $uid,
            $tag
        );
        $res = $this->getResSync($body, 'docker_contrl', 'docker');
        if ( ! isset($res) || $res['http'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => 'http connect faild or connect timeout',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $RedisModel = new Tools\RedisModel();

        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        $success
                              = $error = 0;
        $resultData           = [];
        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断',
                    ],
                    $uid,
                    $tag
                );
                break;
            }

            echo '['.date('Y-m-d H:i:s').']', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status'])) {
                if ($value['status']) {
                    $model = new DAO\ProjectDockerModel;
                    $model->updateContainerStatus(
                        $value['id'],
                        $value['ip'],
                        400
                    );
                    unset($model);
                    $success++;
                    $total = '任务进行中, 关闭容器个数：'.($success + $error).'/'
                        .$totalCount;
                    $this->sendWs(
                        [
                            'status' => 200,
                            'id'     => $task_id,
                            'total'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        $tag
                    );
                    if ( ! empty($value['stdout'])) {
                        $value['stdout'] = str_replace(
                            "\n",
                            '<br/>',
                            $value['stdout']
                        );
                    }
                } else {
                    $error++;
                    $total = '任务进行中, 关闭容器失败：'.($success + $error).'/'
                        .$totalCount.', error: '.$value['error'];
                    $this->sendWs(
                        [
                            'status' => 400,
                            'id'     => $task_id,
                            'error'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        $tag
                    );
                }
                $resultData[$value['ip']][] = $value;
            }
        }
        foreach ($resultData as $ip => $result) {
            $result = json_encode(
                $result,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $OrderOperateLogModel->updateOne($task_id, $ip, $result);
        }
        unset($OrderOperateLogModel);
        $total = '批量任务ID：'.$task_id.'  总任务数：'.$totalCount.' 成功：'.$success.' 失败：'
            .($error);
        $res   = $this->sendWs(
            [
                'status'  => 200,
                'id'      => $task_id,
                'total'   => $total,
                'step_no' => 'end',
            ],
            $uid,
            $tag
        );
        //拉取镜像
        $q->ack($message->getDeliveryTag());
        echo '请求结束------------------------------------------'.PHP_EOL;
    }

    /**
     * Console常驻脚本——向容器中发送任务，用于支持itom系统中的任务管理中的功能 task
     * 任务可以认为是job，有一次性job，也有定时任务，模拟cronjob
     * 用于消费task消息队列消息，并通过回调函数执行业务
     */
    public function taskAction()
    {
        $this->mq->setQueueKey('task');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'execTask',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    /**
     * 向容器中发送任务回调脚本
     *
     * @param AMQPEnvelope $message
     * @param AMQPQueue    $q
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function execTask(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request    = json_decode($message->getBody(), true);
        $taskinfo   = $this->getApiRequest($request['redisKey']);
        $uid        = $request['uid'];
        $project_id = $request['project_id'];
        $task_id    = $request['redisKey'];
        $tag        = 'task';
        $totalCount = count($taskinfo['data']);
        if (empty($taskinfo)) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '未获取到发送docker的需要执行的相关数据',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        echo '['.$tag.']: '.$task_id.PHP_EOL;
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '任务开始, 待执行步数：'.$totalCount,
            ],
            $uid,
            $tag
        );
        foreach ($taskinfo['data'] as $key => $value) {
            $step_no = $key + 1;
            $res     = $this->task(
                $task_id,
                $uid,
                $project_id,
                $value,
                $step_no
            );
            if ( ! $res) {
                continue;
            }
        }
        $q->ack($message->getDeliveryTag());
    }

    /**
     * 任务执行结果业务流程
     *
     * @param $task_id
     * @param $uid
     * @param $project_id
     * @param $data
     * @param $step_no
     *
     * @return bool|void
     * @throws Exception
     */
    private function task($task_id, $uid, $project_id, $data, $step_no)
    {
        $body
             = $insertArr = [];
        $tag = 'task';
        $cmd = $data['order'];

        switch ($data['type']) {
            case 'script':
                foreach ($data['object'] as $v) {
                    $red = ParseCommandModel::parseStr(
                        $data['order'],
                        [
                            'internal_ip'   => $v['ip'],
                            'instance_name' => $v['name'],
                            'public_ip'     => $v['public_ip'],
                            'project_id'    => $project_id,
                        ]
                    );
                    if ($red['status']) {
                        $cmd = $red['data'];
                    } elseif ($red['code'] == 400) {
                        $this->sendWs(
                            [
                                'status' => 400,
                                'id'     => $task_id,
                                'error'  => '步骤'.$step_no.$red['error'],
                            ],
                            $uid,
                            'task'
                        );

                        return;
                    }
                    $body[$v['ip']]      = [
                        'cmd' => $cmd,
                        'ip'  => $v['ip'],
                    ];
                    $insertArr[$v['ip']] = [
                        'ip'         => $v['ip'],
                        'cloud_type' => $v['type'],
                    ];
                }
                $res = $this->getResSync(
                    array_values($body),
                    'docker_contrl',
                    'batch_command'
                );
                break;

            case 'docker_exec':
                foreach ($data['object'] as $v) {
                    $red = ParseCommandModel::parseStr(
                        $data['order'],
                        [
                            'docker_id'      => $v['id'],
                            'container_id'   => $v['container_id'],
                            'container_name' => $v['name'],
                            'type'           => $v['type'],
                            'project_id'     => $project_id,
                        ]
                    );

                    if ($red['status']) {
                        $cmd = $red['data'];
                    } elseif ($red['code'] == 400) {
                        $this->sendWs(
                            [
                                'status' => 400,
                                'id'     => $task_id,
                                'error'  => '步骤'.$step_no.$red['error'],
                            ],
                            $uid,
                            'task'
                        );

                        return;
                    }
                    $body[$v['ip']][]    = [
                        'ip'     => $v['ip'],
                        'type'   => 'container_cmd',
                        'option' => [
                            'id'  => $v['container_id'],
                            'cmd' => $cmd,
                        ],
                    ];
                    $insertArr[$v['ip']] = [
                        'ip'         => $v['ip'],
                        'cloud_type' => $v['type'],
                    ];
                }
                $res = $this->getResSync(
                    array_values($body),
                    'docker_contrl',
                    'docker_task'
                );
                break;

            case 'local':
                foreach ($data['object'] as $v) {
                    $red = \DAO\ParseCommandModel::parseStr(
                        $data['order'],
                        ['project_id' => $project_id]
                    );
                    if ($red['status']) {
                        $cmd            = $red['data'];
                        $body[$v['ip']] = [
                            'cmd' => $cmd,
                            'ip'  => $v['ip'],
                        ];
                        $res            = $this->getResSync(
                            array_values($body),
                            'docker_contrl',
                            'batch_command'
                        );
                    } elseif ($red['code'] == 400) {
                        $this->sendWs(
                            [
                                'status' => 400,
                                'id'     => $task_id,
                                'error'  => '步骤'.$step_no.$red['error'],
                            ],
                            $uid,
                            'task'
                        );

                        return;
                    }
                    $insertArr[$v['ip']] = [
                        'ip'         => $v['ip'],
                        'cloud_type' => $v['type'],
                    ];
                }
                break;
        }
        echo '['.date('Y-m-d H:i:s').']'.json_encode(
                $res,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
        if ( ! isset($res) || $res['http'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '步骤'.$step_no
                        .' error ,http connect faild or connect timeout',
                ],
                $uid,
                'task'
            );

            return;
        }
        $RedisModel = new Tools\RedisModel();
        $success
                    = $error = 0;
        $resultData = [];
        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断',
                    ],
                    $uid,
                    $tag
                );
                break;
            }

            echo '['.date('Y-m-d H:i:s').']', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status'])) {
                if ($value['status']) {
                    $success++;
                    if ( ! empty($value['stdout'])) {
                        $value['stdout'] = str_replace(
                            "\n",
                            '<br/>',
                            mb_substr($value['stdout'], -1000)
                        );
                    }
                } else {
                    $error++;
                }
                $resultData[$value['ip']][] = $value;
            }
        }
        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        $projectModel         = new ProjectModel;
        $project              = $projectModel->findOneByCache($project_id);
        unset($projectModel);

        foreach ($insertArr as $ip => $v) {
            $model  = ProjectServerModel::getServerModel(
                $v['cloud_type']
            );
            $server = $model->finOneByIp($ip);
            unset($model);
            if (isset($resultData[$ip])) {
                $req        = $resultData[$ip];
                $result     = json_encode(
                    $req,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                $insertData = [
                    'task_id'       => $task_id,
                    'task_type'     => 'async',
                    'step_no'       => $step_no,
                    'project_id'    => $project['id'],
                    'project_name'  => $project['name'],
                    'instance_id'   => $server['id'],
                    'instance_name' => $server['name'],
                    'cloud_type'    => $v['cloud_type'],
                    'ip'            => $server['internal_ip'],
                    'uid'           => $uid,
                    'request'       => json_encode(
                        $body[$v['ip']],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'result'        => $result,
                    'operate'       => 'task',
                ];
                $OrderOperateLogModel->insertOneNew($insertData);
            }
        }
        unset($OrderOperateLogModel);
        $total
            = '任务ID：'.$task_id.' 步骤'.$step_no.'  总任务数：'.($success + $error)
            .' 成功：'.$success.' 失败：'.($error);
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => $total,
                'count'  => 1,
                'data'   => $resultData,
            ],
            $uid,
            'task'
        );

        return $error ? false : true;
    }

    //发送实例命令
    public function cmdAction()
    {
        $this->mq->setQueueKey('cmd');
        $res = $this->mq->wokerInstance(
            [
                $this,
                'cmd',
            ]
        );
        if ( ! $res['status']) {
            echo json_encode($res).PHP_EOL;
        }
    }

    //发送实例命令回调
    public function cmd(AMQPEnvelope $message, AMQPQueue $q)
    {
        set_time_limit(0);
        $request = json_decode($message->getBody(), true);
        $body    = $this->getApiRequest($request['redisKey']);
        $uid     = $request['uid'];
        $task_id = $request['redisKey'];
        $type    = $request['type'];
        $tag     = 'cmd';
        //var_dump($request,$body);
        if (empty($body)) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => '未获取到发送到实例的需要执行的相关数据',
                ],
                $uid,
                $tag
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        echo '['.$type.']: '.$task_id.PHP_EOL;
        $totalCount = ($type == 'batch_command')
            ? count($body)
            : count(
                $body['ip_list']
            );
        $this->sendWs(
            [
                'status' => 200,
                'id'     => $task_id,
                'total'  => '任务开始, 待执行命令实例个数：'.$totalCount,
            ],
            $uid,
            'command'
        );
        $res = $this->getResSync($body, 'docker_contrl', $type);
        echo '['.date('Y-m-d H:i:s').']'.json_encode(
                $res,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
        if ( ! isset($res) || $res['http'] != 200) {
            $this->sendWs(
                [
                    'status' => 400,
                    'id'     => $task_id,
                    'error'  => 'http connect faild or connect timeout',
                ],
                $uid,
                'command'
            );
            $q->ack($message->getDeliveryTag());

            return;
        }
        $RedisModel = new Tools\RedisModel();

        $success
                    = $error = 0;
        $resultData = [];
        while (true) {
            try {
                $cons = $RedisModel->redis->brpop($res['task_id'], 900);
            } catch (Exception $e) {
                $this->sendWs(
                    [
                        'status' => 400,
                        'id'     => $task_id,
                        'error'  => '服务长时间没返回，请求中断',
                    ],
                    $uid,
                    'command'
                );
                break;
            }

            echo '['.date('Y-m-d H:i:s').']', json_encode(
                $cons,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ), PHP_EOL;
            $value = json_decode($cons[1], true);
            if (isset($value['task_status'])
                && $value['task_status'] == 'done'
            ) {
                $RedisModel->redis->del($res['task_id']);
                break;
            }
            if (isset($value['status'])) {
                if ($value['status']) {
                    $success++;
                    $total = '任务进行中, 执行命令成功：'.($success + $error).'/'
                        .$totalCount;
                    $this->sendWs(
                        [
                            'status' => 200,
                            'id'     => $task_id,
                            'total'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        'command'
                    );
                    if ( ! empty($value['stdout'])) {
                        $value['stdout'] = str_replace(
                            "\n",
                            '<br/>',
                            mb_substr($value['stdout'], -1000)
                        );
                    }
                } else {
                    $error++;
                    $total = '任务进行中, 执行命令失败：'.($success + $error).'/'
                        .$totalCount.', error: '.$value['error'];
                    $this->sendWs(
                        [
                            'status' => 400,
                            'id'     => $task_id,
                            'error'  => $total,
                            'count'  => 1,
                            'data'   => $value,
                        ],
                        $uid,
                        'command'
                    );
                }
                $resultData[$value['ip']][] = $value;
            }
        }
        $OrderOperateLogModel = new DAO\OrderOperateLogModel;
        foreach ($resultData as $ip => $result) {
            $result = json_encode(
                $result,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            $OrderOperateLogModel->updateOne($task_id, $ip, $result);
        }
        unset($OrderOperateLogModel);
        $total = '批量任务ID：'.$task_id.'  总任务数：'.$totalCount.' 成功：'.$success.' 失败：'
            .($error);
        $res   = $this->sendWs(
            [
                'status'  => 200,
                'id'      => $task_id,
                'total'   => $total,
                'step_no' => 'end',
            ],
            $uid,
            'command'
        );
        $q->ack($message->getDeliveryTag());
        echo '请求结束------------------------------------------'.PHP_EOL;
    }
}
