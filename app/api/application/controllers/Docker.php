<?php
/**
 * Docker Controller
 * 实例/docker相关控制器
 *
 */

use DAO\CustomGroupModel;
use DAO\DockerComposeModel;
use DAO\DockerImageModel;
use DAO\HubImageModel;
use DAO\OrderOperateLogModel;
use DAO\ProjectDockerModel;
use DAO\ProjectModel;
use DAO\ProjectServerModel;
use DAO\ProjectSpaceModel;
use DAO\TaskInfoModel;
use Yaf\Application;

class DockerController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    //获取服务器上的镜像列表

    public function getServerImageListAction()
    {
        $serverId  = $_POST['server_id'] ?? null;
        $type      = $_POST['type'] ?? null;
        $projectId = $_POST['project_id'] ?? null;
        if (empty($serverId)) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $model      = new DAO\GaeaServerModel();
        $serverInfo = $model->findOne($serverId);
        if (empty($serverInfo)) {
            return $this->json = ['status' => 400, 'errorMsg' => '服务器不存在'];
        }
        $imageModel = new DockerImageModel();
        $imagesInfo = $imageModel->findByIp($serverInfo['internal_ip']);
        //只返回获取编排模板中绑定的镜像名称的镜像
        if ($type == 'env' && $projectId) {
            $psModel = new DAO\ProjectServerModel();
            $info    = $psModel->findOneByServerId(
                $projectId,
                $serverId,
                'gaea'
            );
            if (! $info) {
                return $this->json = [
                    'status'   => 400,
                    'errorMsg' => '未查询到项目下服务器信息',
                ];
            }
            $composeModel = new DAO\DockerComposeModel();
            $composeInfo  = $composeModel->findOne($info['compose_id']);
            if (! $composeInfo) {
                return $this->json = [
                    'status'   => 400,
                    'errorMsg' => '未查询到服务器关联的编排模板',
                ];
            }
            $imageArr = json_decode($composeInfo['image_name']);
            $data     = [];
            foreach ($imagesInfo as $image) {
                //判断是不是公共仓库的 docker.io/library/
                $public = 'docker.io/library/';
                if (strpos($image['name_version'], $public) !== false) {
                    $nameArr = explode('/', $image['name_version']);
                    if (in_array(end($nameArr), $imageArr)) {
                        $data[] = $image;
                    }
                } else {
                    $data[] = $image;
                }
            }
            return $this->json = ['status' => 200, 'data' => $data];
        }

        return $this->json = ['status' => 200, 'data' => $imagesInfo];
    }

    //获取指定服务器的镜像列表并更新到本地
    public function updateImageListAction()
    {
        $project_id = $_POST['pid'] ?? null;
        if (! $project_id) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $projectModel = new ProjectModel;
        $project      = $projectModel->findOne($project_id);
        if (! $project) {
            return $this->json = ['status' => 400, 'errorMsg' => '项目不存在'];
        }
        $ProjectServerModel = new ProjectServerModel;
        $data               = $ProjectServerModel->findAllByParams(
            ['project_id' => $project_id,'status' => $ProjectServerModel::STATUS_SUCCESS]
        );
        if (! $data) {
            return $this->json = ['status' => 400, 'errorMsg' => '没有可更新的实例'];
        }
        $body = [];
        foreach ($data as $key => $value) {
            $serverModel = ProjectServerModel::getServerModel($value['type']);
            $server      = $serverModel->findOneByCache($value['server_id']);
            if (!empty($server)) {
                $body[]      = [
                    'ip'   => $server['internal_ip'],
                    'type' => 'image_list',
                ];
            }
        }
        if (!empty($body)) {
            $task_id = DockerApiModel::getId();
            $uid     = $this->getUserId();
            $data    = ['uid' => $uid, 'redisKey' => $task_id];
            $status  = $this->getResAsync('upimages', $data, $body, $task_id);
            if ($status['status'] == 400) {
                return $status;
            }

            return $this->json = ['status' => 200, 'task_id' => $task_id];
        }
        return $this->json = ['status' => 400, 'errorMsg' => '没有可更新的实例'];
    }

    //批量执行拉取镜像

    public function getResAsync($action, $data, $redis_data, $task_id)
    {
        $res     = $this->credentials($task_id, $redis_data);
        $mqModel = new DockerApiModel($data['uid']);
        $status  = $mqModel->pushInstanceMq($data, $action);
        if ($status['status'] != 200) {
            $status['errorMsg'] = '程序异常：'.$status['error'];
            $status['status']   = 400;
        }

        return $status;
    }

    //拉取镜像条件检测

    private function credentials($id, $msg)
    {
        $RedisModel = new Tools\RedisModel();
        $hashKey    = 'docker_api_reqeust';
        $status     = $RedisModel->redis->hSet(
            $hashKey,
            $id,
            json_encode($msg)
        );
        if ($status === false) {
            return false;
        }

        return true;
    }

    //容器列表查询

    public function batchPullImageAction()
    {
        $ischeck = $_POST['ischeck'] ?? null;
        $request = $_POST['request'] ?? null;
        if (! $request) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $request = json_decode($request, true);
        if (! is_array($request)) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数格式错误'];
        }
        $error      = [];
        $redis_data = [];
        foreach ($request as $key => $value) {
            $project_id  = $value['project_id'] ?? null;
            $instance_id = $value['instance_id'] ?? null;
            $cloud_type  = $value['cloud_type'] ?? null;
            $res         = $this->checkPullImage(
                $project_id,
                $instance_id,
                $cloud_type
            );
            if ($res['status'] == 200) {
                unset($res['status']);
                $ip              = $res['instance']['internal_ip'];
                $redis_data[$ip] = $res;
            } else {
                $error[$key] = $res['errorMsg'];
            }
        }
        if ($ischeck && $error) {
            return $this->json = ['status' => 300, 'errorMsg' => $error];
        }
        $task_id = DockerApiModel::getId();
        $uid     = $this->getUserId();
        $data    = [
            'uid'      => $uid,
            'ischeck'  => $ischeck,
            'redisKey' => $task_id,
        ];
        $status  = $this->getResAsync(
            'pullimages',
            $data,
            $redis_data,
            $task_id
        );
        if ($status['status'] == 400) {
            return $this->json = $status;
        }

        return $this->json = ['status' => 200, 'task_id' => $task_id];
    }

    //批量创建容器

    private function checkPullImage($project_id, $instance_id, $cloud_type)
    {
        if (! $project_id || ! $instance_id) {
            return ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $serverModel = ProjectServerModel::getServerModel($cloud_type);
        $server      = $serverModel->findOneByCache($instance_id);
        if (! $server) {
            return ['status' => 400, 'errorMsg' => '实例不存在'];
        }
        $ProjectModel = new ProjectModel;
        $project      = $ProjectModel->findOneByCache($project_id);
        if (! $project) {
            return ['status' => 400, 'errorMsg' => '项目不存在'];
        }
        $ProjectServerModel = new ProjectServerModel;
        $project_server     = $ProjectServerModel->findOneByServerId(
            $project_id,
            $instance_id,
            $cloud_type
        );
        if (! $project_server) {
            return ['status' => 400, 'errorMsg' => '没有绑定的compose'];
        }
        $DockerComposeModel = new DockerComposeModel;
        $compose            = $DockerComposeModel->findOne(
            $project_server['compose_id']
        );
        if (! $compose) {
            return ['status' => 400, 'errorMsg' => '编排模板不存在'];
        }
        $imageArr = json_decode($compose['image_name'], true);
        if (empty($imageArr)) {
            return ['status' => 400, 'errorMsg' => '无任何镜像可拉取'];
        }

        return [
            'status'     => 200,
            'instance'   => $server,
            'project'    => $project,
            'imageArr'   => $imageArr,
            'cloud_type' => $cloud_type,
        ];
    }

    //获取已停止的容器列表

    public function containerAction()
    {
        $project_id         = $_POST['project_id'] ?? null;
        $instance_id        = $_POST['instance_id'] ?? null;
        $cloud_type         = $_POST['type'] ?? null;
        $ProjectDockerModel = new ProjectDockerModel;
        $where              = ['status' => 200];
        if ($project_id) {
            $where['project_id'] = $project_id;
        }
        $compose_id = null;
        if ($instance_id) {
            $where['instance_id'] = $instance_id;
            $ProjectServerModel   = new ProjectServerModel;
            $ps                   = $ProjectServerModel->findOneBindByServerId(
                $instance_id,
                $cloud_type
            );
            if ($ps) {
                $compose_id = $ps['compose_id'];
            }
        }
        if ($cloud_type) {
            $where['cloud_type'] = $cloud_type;
        }
        $data = $ProjectDockerModel->findAllByParams($where);

        $projectModel = new ProjectModel;
        foreach ($data as $key => $value) {
            $serverModel                 = ProjectServerModel::getServerModel(
                $value['cloud_type']
            );
            $server                      = $serverModel->findOneByCache(
                $value['instance_id']
            );
            $data[$key]['instance_name'] = $server['name'];
            //TODO::修改获取image_name的方式
            $data[$key]['image_name']   = str_replace(
                'harbor.gaeamobile-inc.net/',
                '',
                $value['image_name']
            );
            $project                    = $projectModel->findOneByCache(
                $value['project_id']
            );
            $data[$key]['project_name'] = $project['name'];
        }
        $dateSort = [];
        foreach ($data as $key => $value) {
            $dateSort[] = $value['create_at'];
        }
        array_multisort($dateSort, SORT_DESC, $data);

        return $this->json = [
            'status'     => 200,
            'data'       => $data,
            'compose_id' => $compose_id,
        ];
    }

    //停止容器

    public function batchCreateContainerAction()
    {
        $ischeck = $_POST['ischeck'] ?? null;
        $request = $_POST['request'] ?? null;
        $pid     = $_POST['pid'] ?? null;
        if (! $request) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $request = json_decode($request, true);
        if (! is_array($request)) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数格式错误'];
        }
        if (count($request) > 500) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '操作的实例不能超过500个',
            ];
        }
        $task_id = DockerApiModel::getId();
        $uid     = $this->getUserId();
        $data    = [
            'uid'      => $uid,
            'pid'      => $pid,
            'ischeck'  => $ischeck,
            'redisKey' => $task_id,
        ];
        $status  = $this->getResAsync('createcan', $data, $request, $task_id);
        if ($status['status'] == 400) {
            return $this->json = $status;
        }

        return $this->json = ['status' => 200, 'task_id' => $task_id];
    }

    //获取指定实例上已停止的容器列表

    public function stopcansAction()
    {
        $ip = $_POST['ip'] ?? null;
        if (! $ip) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $data   = [
            [
                'ip'     => $ip,
                'type'   => 'container_list',
                "option" => [
                    "filter_status" => "exited",
                ],

            ],
        ];
        $post   = [
            'type' => 'docker',
            'data' => base64_encode(json_encode($data)),
        ];
        $env  = getenv();
        $host = ! empty($env['DOCKER_API_HOST']) ? $env['DOCKER_API_HOST']
            : null;
        $port = ! empty($env['DOCKER_API_PORT']) ? $env['DOCKER_API_PORT']
            : null;
        $url    = $host.':'.$port.'/docker_sync_api';
        $res    = Tools\FuncModel::ycurl($url, $post);
        if (! isset($res['http']) || $res['http'] != 200) {
            return $this->json = ['status' => 400, 'errorMsg' => '获取容器列表失败'];
        }

        $container_data = $res[0] ?? null;
        if (! $container_data || empty($container_data['status'])) {
            return $this->json = ['status' => 400, 'errorMsg' => '获取容器列表失败'];
        }
        if ($container_data['container_list']) {
            $dateSort = [];
            foreach ($container_data['container_list'] as $key => $value) {
                $dateSort[] = $value['FinishedAt'];
            }
            array_multisort(
                $dateSort,
                SORT_DESC,
                $container_data['container_list']
            );
        }

        return $this->json = [
            'status' => 200,
            'data'   => $container_data['container_list'],
        ];
    }

    //按实例批量关闭容器

    public function stopContainerAction()
    {
        return $this->json = $this->containerOperate('container_stop');
    }

    //同步容器列表到本地

    private function containerOperate($type = null)
    {
        if (! $type) {
            return ['status' => 400, 'errorMsg' => '非法请求'];
        }
        $id = $_POST['id'] ?? null;
        if (! $id) {
            return ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $idArr = json_decode($id, true);
        if (! $idArr || ! is_array($idArr)) {
            return ['status' => 400, 'errorMsg' => '参数格式错误'];
        }
        $ProjectDockerModel = new ProjectDockerModel;
        foreach ($idArr as $docker_id) {
            $docker = $ProjectDockerModel->findOne($docker_id);
            if ($docker) {
                $dockers[] = $docker;
            }
        }
        if (empty($dockers)) {
            return $this->json = ['status' => 400, 'errorMsg' => '操作的容器不存在'];
        }
        $bodyArr    = [];
        $request    = [];
        $totalCount = 0;
        foreach ($dockers as $docker) {
            if ($docker['status'] == 400) {
                continue;
            }
            $newKey                  = $docker['cloud_type'].'_'
                .$docker['instance_id'];
            $serverModel             = ProjectServerModel::getServerModel(
                $docker['cloud_type']
            );
            $instance                = $serverModel->findOneByCache(
                $docker['instance_id']
            );
            $docker['instance_name'] = ! empty($instance['name'])
                ? $instance['name'] : '';
            $projectModel            = new ProjectModel;
            $project                 = $projectModel->findOneByCache(
                $docker['project_id']
            );
            $docker['project_name']  = $project['name'];
            if (! array_key_exists($newKey, $request)) {
                $request[$newKey] = $docker;
            }
            $request[$newKey]['body'][] = $bodyArr[] = [
                'ip'     => $docker['ip'],
                'type'   => $type,
                'option' => ['id' => $docker['container_id']],
            ];
            $totalCount++;
        }
        $task_id = DockerApiModel::getId();
        $uid     = $this->getUserId();
        $data    = [
            'uid'        => $uid,
            'redisKey'   => $task_id,
            'totalCount' => $totalCount,
        ];
        $status  = $this->getResAsync('stopcans', $data, $bodyArr, $task_id);
        if ($status['status'] == 400) {
            return $this->json = $status;
        }
        foreach ($request as $docker) {
            $OrderOperateLogModel = new OrderOperateLogModel;
            $insertData           = [
                'task_id'       => $task_id,
                'task_type'     => 'async',
                'step_no'       => 1,
                'project_id'    => $docker['project_id'],
                'project_name'  => $docker['project_name'],
                'instance_id'   => $docker['instance_id'],
                'instance_name' => $docker['instance_name'],
                'cloud_type'    => $docker['cloud_type'],
                'ip'            => $docker['ip'],
                'uid'           => $uid,
                'request'       => json_encode(
                    $docker['body'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'result'        => '',
                'operate'       => $type,
            ];
            //加入操作日志
            $OrderOperateLogModel->insertOneNew($insertData);
        }

        return $this->json = ['status' => 200, 'task_id' => $task_id];
    }

    //删除容器

    public function containerListAction()
    {
        $project_id  = $_POST['project_id'] ?? null;
        $instance_id = $_POST['instance_id'] ?? null;
        $cloud_type  = $_POST['cloud_type'] ?? 'gaea';
        if (! $project_id) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $where = ['project_id' => $project_id];
        if ($cloud_type) {
            $where['type'] = $cloud_type;
        }
        if ($instance_id) {
            $where['server_id'] = $instance_id;
        }
        $ProjectServerModel = new ProjectServerModel;
        $project_servers    = $ProjectServerModel->findAllServerByParams(
            $where
        );
        if (! $project_servers) {
            return $this->json = ['status' => 400, 'errorMsg' => '实例不存在'];
        }
        foreach ($project_servers as $key => $value) {
            $project_servers[$key]['instance_id'] = $value['server_id'];
            $project_servers[$key]['cloud_type']  = $value['type'];
        }
        $uid     = $this->getUserId();
        $task_id = DockerApiModel::getId();
        $data    = [
            'uid'      => $uid,
            'pid'      => $project_id,
            'redisKey' => $task_id,
        ];
        $status  = $this->getResAsync(
            'updatecans',
            $data,
            $project_servers,
            $task_id
        );
        if ($status['status'] == 400) {
            return $this->json = $status;
        }

        return $this->json = ['status' => 200, 'task_id' => $task_id];
    }

    //向docker中发命令

    public function delContainerAction()
    {
        $id = $_POST['id'] ?? null;
        if (! $id) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $idArr = json_decode($id, true);
        if (! is_array($idArr)) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数格式错误'];
        }
        $model = new ProjectDockerModel;
        $res   = $model->updateAllByIds(
            $idArr,
            ['status' => ProjectDockerModel::STATUS_CLOSE]
        );
        if (! $res['status']) {
            return $this->json = ['status' => 400, 'errorMsg' => $res['error']];
        }

        return $this->json = ['status' => 200];
    }

    //发送远程命令

    public function execAction()
    {
        $id       = $_POST['id'] ?? null;
        $cmd      = $_POST['cmd'] ?? null;
        $type     = $_POST['type'] ?? 'container_cmd';
        $group_id = $_POST['group_id'] ?? null;
        if ((! $id && ! $group_id) || ! $cmd) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $dockers = [];
        if ($group_id) {
            $CustomGroupModel = new CustomGroupModel;
            $group            = $CustomGroupModel->findOne(
                ['id' => $group_id, 'group_type' => 200]
            );
            $arr              = json_decode($group['server_id'], true);
            $dockerModel      = new ProjectDockerModel;
            if ($arr) {
                foreach ($arr as $key => $value) {
                    $docker = $dockerModel->findAllByParams(
                        [
                            'project_id'  => $group['project_id'],
                            'instance_id' => $value['server_id'],
                            'cloud_type'  => $value['type'],
                            'id'          => $value['docker_id'],
                            'status'      => 200,
                        ]
                    );
                    if ($docker) {
                        $dockers[] = $docker[0];
                        unset($arr[$key]);
                    }
                }
                if ($arr) {
                    $errorMsg = '有未启动的容器: ';
                    foreach ($arr as $key => $value) {
                        $errorMsg .= $value['docker_id'].' ';
                    }

                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => $errorMsg,
                    ];
                }
            } else {
                return $this->json = [
                    'status'   => 400,
                    'errorMsg' => '自定义容器组中无启动的容器',
                ];
            }
        } else {
            $idArr = json_decode($id, true);
            if (! $idArr || ! is_array($idArr)) {
                return $this->json = ['status' => 400, 'errorMsg' => '参数格式错误'];
            }
            $ProjectDockerModel = new ProjectDockerModel;
            foreach ($idArr as $docker_id) {
                $docker = $ProjectDockerModel->findOne($docker_id);
                if ($docker) {
                    $dockers[] = $docker;
                }
            }
        }
        if (! $dockers) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '请选中需要发命令的docker',
            ];
        }
        $body        = [];
        $request     = [];
        $instanceArr = [];
        $projectArr  = [];
        $totalCount  = 0;
        foreach ($dockers as $docker) {
            $newKey = $docker['cloud_type'].'_'.$docker['instance_id'];
            if (! isset($instanceArr[$newKey])) {
                $serverModel          = ProjectServerModel::getServerModel(
                    $docker['cloud_type']
                );
                $instance             = $serverModel->findOne(
                    $docker['instance_id']
                );
                $instanceArr[$newKey] = $instance;
            } else {
                $instance = $instanceArr[$newKey];
            }
            $docker['instance_name'] = $instance['name'];
            if (! isset($projectArr[$docker['project_id']])) {
                $projectModel = new ProjectModel;
                $project      = $projectModel->findOne($docker['project_id']);
            } else {
                $project = $projectArr[$docker['project_id']];
            }
            $docker['project_name'] = $project['name'];

            $new_cmd = $cmd;
            if ($type == 'script') {
                try {
                    $red = ParseCommandModel::parseStr(
                        $new_cmd,
                        [
                            'docker_id'      => $docker['id'],
                            'container_id'   => $docker['container_id'],
                            'container_name' => $docker['name'],
                            'type'           => $docker['cloud_type'],
                            'project_id'     => $docker['project_id'],
                        ]
                    );
                    if ($red['status']) {
                        $new_cmd = $red['data'];
                    } elseif ($red['code'] == 400) {
                        return $this->json = [
                            'status'   => 400,
                            'errorMsg' => $red['error'],
                        ];
                    }
                } catch (Exception $e) {
                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => $e->getMessage(),
                    ];
                }
            }
            $body[$newKey][] = [
                'ip'     => $docker['ip'],
                'type'   => 'container_cmd',
                'option' => [
                    'id'  => $docker['container_id'],
                    'cmd' => $new_cmd,
                ],
            ];
            if (! array_key_exists($newKey, $request)) {
                $docker['cmd']    = $new_cmd;
                $request[$newKey] = $docker;
            }
            $totalCount++;
        }
        $uid     = $this->getUserId();
        $task_id = DockerApiModel::getId();
        $data    = [
            'uid'        => $uid,
            'redisKey'   => $task_id,
            'totalCount' => $totalCount,
        ];
        $status  = $this->getResAsync(
            'cancmd',
            $data,
            array_values($body),
            $task_id
        );
        if ($status['status'] == 400) {
            return $this->json = $status;
        }
        foreach ($request as $newKey => $docker) {
            $project = [
                'id'   => $docker['project_id'],
                'name' => $docker['project_name'],
            ];
            $server  = [
                'id'          => $docker['instance_id'],
                'name'        => $docker['instance_name'],
                'cloud_type'  => $docker['cloud_type'],
                'internal_ip' => $docker['ip'],
            ];
            $this->insertOperateLog(
                $task_id,
                $project,
                $server,
                $uid,
                $body[$newKey],
                'container_cmd',
                1
            );
        }

        return $this->json = ['status' => 200, 'task_id' => $task_id];
    }

    //发送本地命令

    private function insertOperateLog(
        $task_id,
        $project,
        $instance,
        $uid,
        $body,
        $operate,
        $step_no
    ) {
        $data  = [
            ':task_id'       => $task_id,
            ':task_type'     => 'async',
            ':step_no'       => $step_no,
            ':project_id'    => $project['id'],
            ':project_name'  => $project['name'],
            ':instance_id'   => $instance['id'],
            ':instance_name' => $instance['name'],
            ':cloud_type'    => $instance['cloud_type'],
            ':ip'            => $instance['internal_ip'],
            ':uid'           => $uid,
            ':request'       => json_encode(
                $body,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            ':result'        => '',
            ':operate'       => $operate,
        ];
        $model = new OrderOperateLogModel;
        $res   = $model->insertOne($data);
        if (! $res['status']) {
            return $this->json = ['status' => false, 'error' => '操作日志入库失败'];
        }

        return $this->json = ['status' => true];
    }

    //发送远程文件

    public function cmdAction()
    {
        $request  = $_POST['request'] ?? null;
        $cmd      = $_POST['cmd'] ?? null;
        $type     = $_POST['type'] ?? 'command';
        $pid      = $_POST['pid'] ?? null;
        $group_id = $_POST['group_id'] ?? null;
        if ((! $request && ! $group_id) || ! $cmd || ! $pid) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $requestArr = [];
        if ($group_id) {
            $CustomGroupModel = new CustomGroupModel;
            $group            = $CustomGroupModel->findOne(
                ['id' => $group_id, 'group_type' => 100]
            );
            $arr              = json_decode($group['server_id'], true);
            $dockerModel      = new ProjectDockerModel;
            if ($arr) {
                foreach ($arr as $key => $value) {
                    $requestArr[] = [
                        'id'         => $value['server_id'],
                        'cloud_type' => $value['type'],
                    ];
                }
            }
            if (! $requestArr) {
                return $this->json = [
                    'status'   => 400,
                    'errorMsg' => '自定义组中无任何实例: '.$group_id,
                ];
            }
        } else {
            $requestArr = json_decode($request, true);
            if (! $requestArr || ! is_array($requestArr)) {
                return $this->json = ['status' => 400, 'errorMsg' => '参数格式错误'];
            }
        }
        $ProjectModel = new ProjectModel;
        $project      = $ProjectModel->findOne($pid);
        if (! $project) {
            return $this->json = ['status' => 400, 'errorMsg' => '项目不存在'];
        }
        $requests = $body = [];
        foreach ($requestArr as $key => $value) {
            $model   = ProjectServerModel::getServerModel($value['cloud_type']);
            $server  = $model->findOneByCache($value['id']);
            $new_cmd = $cmd;
            if ($type == 'script') {
                try {
                    $red = ParseCommandModel::parseStr(
                        $new_cmd,
                        [
                            'internal_ip'   => $server['internal_ip'],
                            'instance_name' => $server['name'],
                            'public_ip'     => $server['public_ip'],
                            'project_id'    => $pid,
                        ]
                    );
                    if ($red['status']) {
                        $new_cmd = $red['data'];
                    } elseif ($red['code'] == 400) {
                        return $this->json = [
                            'status'   => 400,
                            'errorMsg' => $red['error'],
                        ];
                    }
                } catch (Exception $e) {
                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => $e->getMessage(),
                    ];
                }
            }
            $server['cloud_type'] = $value['cloud_type'];
            $body[]               = [
                'cmd' => $new_cmd,
                'ip'  => $server['internal_ip'],
            ];
            $newKey               = $server['internal_ip'];
            if (! array_key_exists($newKey, $requests)) {
                $server['cmd']     = $new_cmd;
                $requests[$newKey] = $server;
            }
        }
        $uid     = $this->getUserId();
        $task_id = DockerApiModel::getId();
        $data    = [
            'uid'      => $uid,
            'redisKey' => $task_id,
            'type'     => 'batch_command',
        ];
        $res     = $this->getResAsync('cmd', $data, $body, $task_id);
        if ($res['status'] == 400) {
            return $this->json = $res;
        }
        foreach ($requests as $key => $server) {
            $body = ['cmd' => $server['cmd'], 'ip' => $server['internal_ip']];
            $this->insertOperateLog(
                $task_id,
                $project,
                $server,
                $uid,
                $body,
                $type,
                1
            );
        }

        return $this->json = ['status' => 200, 'id' => $task_id];
    }

    //提交执行任务请求

    public function taskAction()
    {
        $project_id = $_POST['project_id'] ?? null;
        $task_id    = $_POST['task_id'] ?? null;
        $order_sort = $_POST['order_sort'] ?? null;
        if (! $project_id || ! $task_id || ! $order_sort) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $TaskInfoModel = new TaskInfoModel;
        $info          = $TaskInfoModel->findOneById($task_id, $order_sort);
        if (! $info['status']) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => $info['error'],
            ];
        }
        $uid     = $this->getUserId();
        $task_id = DockerApiModel::getId();
        $data    = [
            'uid'        => $uid,
            'redisKey'   => $task_id,
            'project_id' => $project_id,
        ];
        $status  = $this->getResAsync('task', $data, $info, $task_id);
        if ($status['status'] == 400) {
            return $this->json = $status;
        }

        return $this->json = ['status' => 200, 'id' => $task_id];
    }

    //加入操作日志

    public function operateLogAction()
    {
        $type        = $_POST['type'] ?? 'instance';
        $instance_id = $_POST['instance_id'] ?? null;
        $project_id  = $_POST['id'] ?? null;
        $cloud_type  = $_POST['cloud_type'] ?? 'gaea';
        $uid         = !empty($_POST['uid']) ? $POST['uid'] : null;
        $page        = $_POST['page'] ?? 1;
        $count       = $_POST['count'] ?? 10;
        $taskId      = $_POST['task_id'] ?? null;
        $start_date  = ! empty($_POST['start_date']) ? date(
            'Y-m-d H:i:s',
            $_POST['start_date']/1000
        ) : null;
        $end_date    = ! empty($_POST['end_date']) ? date(
            'Y-m-d H:i:s',
            $_POST['end_date']/1000
        ) : null;
        $model       = new OrderOperateLogModel();
        if ($taskId) {
            $data = $model->findAllByParams(['task_id' => $taskId]);
            foreach ($data as $key => $value) {
                $data[$key]['create_at'] = strtotime(
                    $value['create_at']
                ) * 1000;
                $data[$key]['end_at']    = strtotime(
                    $value['end_at']
                ) * 1000;
                if ($value['result']) {
                    $data[$key]['result'] = json_decode(
                        $value['result'],
                        true
                    );
                }
                if ($value['request']) {
                    $data[$key]['request'] = json_decode(
                        $value['request'],
                        true
                    );
                }
            }

            return $this->json = ['status' => 200, 'data' => $data];
        } else {
            if ($project_id) {
                $ProjectModel = new ProjectModel;
                $project      = $ProjectModel->findOne($project_id);
                if (! $project) {
                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => '项目不存在',
                    ];
                }
            }
            if ($instance_id && $cloud_type) {
                $serverModel = ProjectServerModel::getServerModel($cloud_type);
                $server      = $serverModel->findOne($instance_id);
                if (! $server) {
                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => '实例不存在',
                    ];
                }
            }
            $isRoot = $this->isRoot($uid);
            $data = $model->findAll(
                $uid,
                $project_id,
                $instance_id,
                $type,
                $page,
                $count,
                $start_date,
                $end_date,
                $isRoot
            );

            foreach ($data['pageData'] as $key => $value) {
                $data['pageData'][$key]['create_at'] = strtotime(
                    $value['create_at']
                ) * 1000;
                $data['pageData'][$key]['end_at']    = strtotime(
                    $value['end_at']
                ) * 1000;
                if ($value['result']) {
                    $data['pageData'][$key]['result'] = json_decode(
                        $value['result'],
                        true
                    );
                }
                if ($value['request']) {
                    $data['pageData'][$key]['request'] = json_decode(
                        $value['request'],
                        true
                    );
                }
            }

            return $this->json = ['status' => 200, 'data' => $data];
        }
    }

    //停止容器

    private function stopContainersAction()
    {
        $request = $_POST['request'] ?? null;
        $pid     = $_POST['pid'] ?? null;
        if (! $request || ! $pid) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $requestArr = json_decode($request, true);
        if (! $requestArr || ! is_array($requestArr)) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数不正确'];
        }
        $projectModel = new ProjectModel;
        $project      = $projectModel->findOne($pid);
        if (! $project) {
            return $this->json = ['status' => 400, 'errorMsg' => '操作的项目不存在'];
        }
        $bodyArr            = [];
        $request            = [];
        $totalCount         = 0;
        $ProjectDockerModel = new ProjectDockerModel;
        foreach ($requestArr as $value) {
            $dockerArr = $ProjectDockerModel->findAllOpen(
                $pid,
                $value['server_id'],
                $value['type']
            );
            if (! $dockerArr) {
                continue;
            }
            foreach ($dockerArr as $docker) {
                $newKey                  = $docker['cloud_type'].'_'
                    .$docker['instance_id'];
                $serverModel             = ProjectServerModel::getServerModel(
                    $docker['cloud_type']
                );
                $instance                = $serverModel->findOneByCache(
                    $docker['instance_id']
                );
                $docker['instance_name'] = ! empty($instance['name'])
                    ? $instance['name'] : '';
                $docker['project_name']  = $project['name'];
                if (! array_key_exists($newKey, $request)) {
                    $request[$newKey] = $docker;
                }
                $request[$newKey]['body'][] = $bodyArr[] = [
                    'ip'     => $docker['ip'],
                    'type'   => 'container_stop',
                    'option' => ['id' => $docker['container_id']],
                ];
                $totalCount++;
            }
        }
        if (! $totalCount) {
            return $this->json = ['status' => 400, 'errorMsg' => '无任何需要关闭的容器'];
        }
        $task_id = DockerApiModel::getId();
        $uid     = $this->getUserId();
        $data    = [
            'uid'        => $uid,
            'redisKey'   => $task_id,
            'totalCount' => $totalCount,
        ];
        $status  = $this->getResAsync('stopcans', $data, $bodyArr, $task_id);
        if ($status['status'] == 400) {
            return $this->json = $status;
        }
        foreach ($request as $docker) {
            $OrderOperateLogModel = new OrderOperateLogModel;
            $insertData           = [
                'task_id'       => $task_id,
                'task_type'     => 'async',
                'step_no'       => 1,
                'project_id'    => $docker['project_id'],
                'project_name'  => $docker['project_name'],
                'instance_id'   => $docker['instance_id'],
                'instance_name' => $docker['instance_name'],
                'cloud_type'    => $docker['cloud_type'],
                'ip'            => $docker['ip'],
                'uid'           => $uid,
                'request'       => json_encode(
                    $docker['body'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'result'        => '',
                'operate'       => 'container_stop',
            ];
            //加入操作日志
            $OrderOperateLogModel->insertOneNew($insertData);
        }

        return $this->json = ['status' => 200, 'task_id' => $task_id];
    }
}
