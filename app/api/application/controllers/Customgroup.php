<?php

use DAO\CustomGroupModel;
use DAO\ProjectDockerModel;
use DAO\ProjectModel;
use DAO\ProjectServerModel;

/**
 * CustomGroup Controller
 * 自定义组相关控制器
 *
 */
class CustomgroupController extends BaseController
{
    const TYPE_SERVER = 100;
    const TYPE_DOCKER = 200;

    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    // 自定义组列表
    public function indexAction()
    {
        $return      = ['status' => 200, 'error' => ''];
        $projectId   = $_GET['pid'] ?? null;
        $groupId     = $_GET['id'] ?? null;
        $type        = $_GET['type'] ?? null;
        $model       = new CustomGroupModel;
        $proModel    = new ProjectModel;
        $serverModel = new ProjectServerModel;
        $dockerModel = new ProjectDockerModel;

        if ($projectId) {
            if ($groupId) {
                $data = $model->findAll(
                    ['project_id' => $projectId, 'id' => $groupId]
                );
            } else {
                if ($type) {
                    $data = $model->findAll(
                        ['project_id' => $projectId, 'group_type' => $type]
                    );
                } else {
                    $data = $model->findAll(['project_id' => $projectId]);
                }
            }
        } else {
            $data = $model->findAll();
        }

        if (! empty($data)) {
            foreach ($data as $key => $value) {
                $proData                    = $proModel->findOne(
                    $value['project_id']
                );
                $data[$key]['project_name'] = empty($proData) ? '---'
                    : $proData['name'];

                $serverDataArr = json_decode($value['server_id'], true);
                $serverArr     = [];
                if (! empty($serverDataArr)) {
                    foreach ($serverDataArr as $serverVal) {
                        if ($value['group_type'] == self::TYPE_SERVER) {
                            $serverData = $serverModel->findAllByServerId(
                                $serverVal['server_id'],
                                $serverVal['type']
                            );
                            if (! empty($serverData)) {
                                $serverArrRow = [
                                    'server_id'   => $serverVal['server_id'],
                                    'name'        => $serverData[0]['name'],
                                    'description' => $serverData[0]['description'],
                                    'type'        => $serverVal['type'],
                                ];
                                $serverArr[]  = $serverArrRow;
                            }
                        }

                        if ($value['group_type'] == self::TYPE_DOCKER) {
                            if (! empty($serverVal['server_id'])
                                && ! empty(
                                    $serverVal['type']
                                    && ! empty($serverVal['docker_id'])
                                )
                            ) {
                                $docekrData = $dockerModel->findAllByParams(
                                    [
                                        'id'         => $serverVal['docker_id'],
                                        'cloud_type' => $serverVal['type'],
                                    ]
                                );
                                if (! empty($docekrData)) {
                                    $serverArrRow = [
                                        'description' => $docekrData[0]['description'],
                                        'docker_id'   => $docekrData[0]['id'],
                                        'docker_name' => $docekrData[0]['name'],
                                        'server_id'   => $serverVal['server_id'],
                                    ];
                                    $serverArr[]  = $serverArrRow;
                                }
                            }
                        }
                    }
                }
                $data[$key]['server']     = $serverArr;
                $data[$key]['server_num'] = count($serverDataArr);
                unset($data[$key]['server_id']);
            }
        }

        $return['data'] = $data;

        return $this->json = $return;
    }

    // 添加自定义组
    public function createAction()
    {
        $return      = ['status' => 400, 'error' => ''];
        $name        = $_POST['name'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id  = $_POST['pid'] ?? null;
        $server      = $_POST['server'] ?? null;
        $group_type  = $_POST['group_type'] ?? null;
        $create_at   = date('Y-m-d H:i:s');
        $create_user = $this->getUserId();

        if (empty($name) || empty($description) || empty($project_id)
            || empty($server)
            || empty($group_type)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        if (! in_array($group_type, [self::TYPE_SERVER, self::TYPE_DOCKER])) {
            $return['error'] = 'type类型错误';

            return $this->json = $return;
        }

        $serverArr = json_decode($server, true);
        if (! is_array($serverArr)) {
            $return['error'] = 'server格式错误';

            return $this->json = $return;
        }
        $serverModel = new ProjectServerModel;
        $serverIdArr = [];
        foreach ($serverArr as $value) {
            if ($group_type == self::TYPE_SERVER) {
                if (empty($value['server_id']) || empty($value['type'])) {
                    $return['error'] = '创建实例自定义组：实例 '.$value['server_id']
                        .' 参数错误';

                    return $this->json = $return;
                }
            }

            if ($group_type == self::TYPE_DOCKER) {
                if (empty($value['server_id']) || empty($value['type'])
                    || empty($value['docker_id'])
                ) {
                    $return['error'] = '创建容器自定义组：实例 '.$value['server_id']
                        .' 参数错误';

                    return $this->json = $return;
                }
            }
            $serverData = $serverModel->findAllByServerId(
                $value['server_id'],
                $value['type']
            );
            if (empty($serverData)) {
                $return['error'] = '未发现实例'.$value['server_id'];

                return $this->json = $return;
            }

            if (empty($value['docker_id'])) {
                $serverIdArr[] = [
                    'server_id' => $value['server_id'],
                    'type'      => $value['type'],
                ];
            } else {
                $dockerModel = new ProjectDockerModel;
                $docekrData  = $dockerModel->findAllByParams(
                    [
                        'id'          => $value['docker_id'],
                        'status'      => 200,
                        'instance_id' => $value['server_id'],
                        'cloud_type'  => $value['type'],
                    ]
                );
                if (empty($docekrData)) {
                    $return['error'] = '未发现Docker: '.$value['docker_id']
                        .'或者容器非正常状态';

                    return $this->json = $return;
                }
                $serverIdArr[] = [
                    'server_id' => $value['server_id'],
                    'type'      => $value['type'],
                    'docker_id' => $value['docker_id'],
                ];
            }
        }

        $paramsArr = [
            'name'        => $name,
            'description' => $description,
            'project_id'  => $project_id,
            'server_id'   => json_encode($serverIdArr),
            'group_type'  => $group_type,
            'create_at'   => $create_at,
            'create_user' => $create_user,
        ];

        $model = new CustomGroupModel;
        $data  = $model->insertOne($paramsArr);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }

        $return['status'] = 200;
        $return['data']   = ['id' => $data['id']];

        return $this->json = $return;
    }

    public function updateAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $id          = $_POST['id'] ?? null;
        $name        = $_POST['name'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id  = $_POST['pid'] ?? null;
        $server      = $_POST['server'] ?? null;

        if (empty($id) || empty($name) || empty($description)
            || empty($project_id)
            || empty($server)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $model      = new CustomGroupModel;
        $customData = $model->findOne(['id' => $id]);
        if (empty($customData)) {
            $return['error'] = '未发现此ID的自定义组';

            return $this->json = $return;
        }

        $serverArr = json_decode($server, true);
        if (! is_array($serverArr)) {
            $return['error'] = 'server格式错误';

            return $this->json = $return;
        }
        $serverModel = new ProjectServerModel;
        $serverIdArr = [];
        foreach ($serverArr as $value) {
            if ($customData['group_type'] == self::TYPE_SERVER) {
                if (empty($value['server_id']) || empty($value['type'])) {
                    $return['error'] = '创建实例自定义组：实例 '.$value['server_id']
                        .' 参数错误';

                    return $this->json = $return;
                }
            }

            if ($customData['group_type'] == self::TYPE_DOCKER) {
                if (empty($value['server_id']) || empty($value['type'])
                    || empty($value['docker_id'])
                ) {
                    $return['error'] = '创建容器自定义组：实例 '.$value['server_id']
                        .' 参数错误';

                    return $this->json = $return;
                }
            }
            $serverData = $serverModel->findAllByServerId(
                $value['server_id'],
                $value['type']
            );
            if (empty($serverData)) {
                $return['error'] = '未发现实例'.$value['server_name'];

                return $this->json = $return;
            }

            if (empty($value['docker_id'])) {
                $serverIdArr[] = [
                    'server_id' => $value['server_id'],
                    'type'      => $value['type'],
                ];
            } else {
                $dockerModel = new ProjectDockerModel;
                $docekrData  = $dockerModel->findAllByParams(
                    [
                        'id'          => $value['docker_id'],
                        'status'      => 200,
                        'instance_id' => $value['server_id'],
                        'cloud_type'  => $value['type'],
                    ]
                );
                if (empty($docekrData)) {
                    $return['error'] = '未发现Docker容器，请尝试同步容器列表';

                    return $this->json = $return;
                }
                $serverIdArr[] = [
                    'server_id' => $value['server_id'],
                    'type'      => $value['type'],
                    'docker_id' => $value['docker_id'],
                ];
            }
        }

        $paramsArr = [
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'project_id'  => $project_id,
            'server_id'   => json_encode($serverIdArr),
        ];

        $data = $model->updateOne($paramsArr);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }

        $return['status'] = 200;
        $return['data']   = ['id' => $data['id']];

        return $this->json = $return;
    }


    public function deleteAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $id     = $_POST['id'] ?? null;
        if (empty($id)) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $model      = new CustomGroupModel;
        $customData = $model->findOne(['id' => $id]);
        if (empty($customData)) {
            $return['error'] = '未发现此ID的自定义组';

            return $this->json = $return;
        }

        $model = new CustomGroupModel;
        $data  = $model->deleteOne($id);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }

        $return['status'] = 200;
        $return['data']   = ['id' => $data['id']];

        return $this->json = $return;
    }
}
