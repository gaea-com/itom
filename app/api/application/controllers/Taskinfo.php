<?php

use DAO\CustomGroupModel;
use DAO\OrderInfoModel;
use DAO\ServerEnvModel;
use DAO\ServerGroupModel;
use DAO\TaskInfoModel;

/**
 * OrderInfo Controller
 * 命令相关控制器
 *
 */
class TaskinfoController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    // 任务列表©
    public function indexAction()
    {
        $return     = ['status' => 200, 'error' => ''];
        $user_id    = $this->getUserId();
        $projectId  = $_GET['pid'] ?? null;
        $id         = $_GET['id'] ?? null;
        $model      = new TaskInfoModel;
        $orderModel = new OrderInfoModel;

        if ($projectId) {
            $data = $model->findAll(['project_id' => $projectId]);
        } else {
            if ($id) {
                $data = $model->findAll(['id' => $id]);
            } else {
                $data = $model->findAll();
            }
        }


        $returnData = [];
        if (! empty($data)) {
            foreach ($data as $key => $value) {
                $orderArr    = [];
                $view_status = true;
                foreach ($value['order'] as $order) {
                    $order_id   = $order['order_id'];
                    $order_data = $orderModel->findOne(['id' => $order_id]);

                    if (! empty($order_data)
                        && $order_data['run_status'] != 200
                        && $order_data['create_user'] != $user_id
                        && empty($this->rootCheck())
                    ) {
                        $view_status = false;
                    }

                    if (! empty($order_data)) {
                        $orderArr[] = [
                            'id'   => $order_id,
                            'name' => $order_data['name'],
                        ];
                    }
                }

                $userpermission = false;
                if ($value['create_user'] == $user_id || $this->rootCheck()) {
                    $userpermission = true;
                }

                if ($value['run_status'] != 200 && ! $userpermission) {
                    $view_status = false;
                }

                if ($view_status) {
                    $returnData[] = [
                        'id'                => $value['id'],
                        'description'       => $value['description'],
                        'name'              => $value['name'],
                        'update_status'     => $value['update_status'],
                        'run_status'        => $value['run_status'],
                        'userpermission'    => $userpermission,
                        'update_permission' => ($value['update_status'] == 200
                            || $userpermission) ? true : false,
                        'order_name'        => $orderArr,
                    ];
                }
            }
        }

        $return['data'] = $returnData;

        return $this->json = $return;
    }

    // 根据任务ID获取命令详细信息

    public function rootCheck()
    {
        return $this->isRoot();
    }

    // 添加任务

    public function getOrderByTaskAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $id     = $_GET['id'] ?? null;
        if (empty($id)) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $model = new TaskInfoModel;
        $data  = $model->findOne(['id' => $id]);
        if (empty($data)) {
            $return['error'] = '未发现此ID的任务';

            return $this->json = $return;
        }

        $orderArr           = [];
        $orderModel         = new OrderInfoModel;
        $customerGroupModel = new CustomGroupModel;
        $groupModel         = new ServerGroupModel;
        $dockListModel      = new ServerEnvModel;
        foreach ($data['order'] as $value) {
            $arr               = [];
            $arr['task_id']    = intval($value['task_id']);
            $arr['order_id']   = intval($value['order_id']);
            $orderData         = $orderModel->findOne(
                ['id' => $value['order_id']]
            );
            $arr['order_name'] = empty($orderData) ? '---' : $orderData['name'];
            $arr['type']       = $value['order_object']['type'];
            if ($arr['type'] != 100) {
                $arr['scope'] = $value['order_object']['scope'];
                if ($arr['scope'] != 'all') {
                    $dataArr            = $value['order_object'][$arr['scope']];
                    $arr[$arr['scope']] = [];
                    switch ($arr['scope']) {
                    case 'customerGroup':
                        foreach ($dataArr as $dataValue) {
                            $customerGroupData
                                                = $customerGroupModel->findOne(
                                                    ['id' => $dataValue]
                                                );
                            $arr[$arr['scope']][] = [
                                'name' => empty($customerGroupData) ? '---'
                                    : $customerGroupData['name'],
                                'id'   => $dataValue,
                            ];
                        }
                        break;

                    case 'Group':
                        foreach ($dataArr as $dataValue) {
                            $groupData            = $groupModel->findAllByParams(
                                ['id' => $dataValue]
                            );
                            $arr[$arr['scope']][] = [
                                'name' => empty($groupData) ? '---'
                                    : $groupData[0]['name'],
                                'id'   => $dataValue,
                            ];
                        }
                        break;

                    case 'insList':
                        foreach ($dataArr as $dataValue) {
                            if (! empty($dataValue['id'])
                                && ! empty($dataValue['type'])
                            ) {
                                $serverModel          = $model->getServerModel(
                                    $dataValue['type']
                                );
                                $serverData
                                                    = $serverModel->findAllByParams(
                                                        ['id' => $dataValue['id']]
                                                    );
                                $arr[$arr['scope']][] = [
                                    'name'  => empty($serverData) ? '---'
                                        : $serverData[0]['name'],
                                    'stype' => $dataValue['id'],
                                    'type'  => $dataValue['type'],
                                ];
                            }
                        }
                        break;

                    case 'dockList':
                        foreach ($dataArr as $dataValue) {
                            $arr[$arr['scope']][] = $dataValue;
                        }
                        break;

                    default:
                        break;
                    }
                }
            }
            $orderArr[] = [$arr['order_id'] => $arr];
        }

        $return['status'] = 200;
        $return['data']   = $orderArr;

        return $this->json = $return;
    }

    public function createTaskInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $name        = $_POST['name'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id  = $_POST['pid'] ?? null;
        $create_at   = date('Y-m-d H:i:s');
        $create_user = $this->getUserId();
        $script      = $_POST['script'] ?? null;

        if (empty($name) || empty($description) || empty($project_id)
            || empty($script)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $script = json_decode($script, true);
        if (! is_array($script)) {
            $return['error'] = 'Scope格式错误';

            return $this->json = $return;
        }

        // 格式化作用域参数
        $scopeStatus = $this->scopeFormat($script);
        if (! $scopeStatus['status']) {
            $return['error'] = $scopeStatus['error'];

            return $this->json = $return;
        }

        $paramsArr = [
            'name'          => $name,
            'description'   => $description,
            'project_id'    => $project_id,
            'order'         => $scopeStatus['data'],
            'update_status' => 100,
            'run_status'    => 200,
            'create_at'     => $create_at,
            'create_user'   => $create_user,
        ];

        $model = new TaskInfoModel;
        $data  = $model->insertOne($paramsArr);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }

        $return['status'] = 200;
        $return['data']   = ['id' => $data['id']];

        return $this->json = $return;
    }

    // 修改是否可执行和是否可编辑状态

    public function scopeFormat($script)
    {
        $return = ['status' => false, 'error' => ''];

        $returnData = [];
        foreach ($script as $orderVal) {
            switch ($orderVal['type']) {
            case 100: //本地
                $returnData[] = [
                    'order_id'     => $orderVal['order_id'],
                    'order_object' => [
                        'type'  => 100,
                        'scope' => 'Local',
                    ],
                ];

                break;

            case 200: //实例
                $scope = $orderVal['scope'];
                if ($scope == 'all') {
                    $returnData[] = [
                        'order_id'     => $orderVal['order_id'],
                        'order_object' => [
                            'type'  => 200,
                            'scope' => $orderVal['scope'],
                        ],
                    ];
                } else {
                    if ($scope == 'insList') {
                        if (empty($orderVal[$orderVal['scope']])
                            || ! is_array($orderVal[$orderVal['scope']])
                        ) {
                            $return['error'] = '命令作用域为空或错误';

                            return $return;
                        }
                        $taskModel = new TaskInfoModel;
                        foreach ($orderVal[$orderVal['scope']] as $insValue) {
                            if (empty($insValue['id'])
                                || empty($insValue['type'])
                            ) {
                                $return['error'] = '选择的实例错误';

                                return $return;
                            }
                            $serverModel = $taskModel->getServerModel(
                                $insValue['type']
                            );
                            if (empty($serverModel)) {
                                $return['error'] = '选择的ID为'.$insValue['id']
                                    .'的实例type错误';

                                return $return;
                            }
                            $insData = $serverModel->findAllByParams(
                                ['id' => $insValue['id']]
                            );
                            if (empty($insData)) {
                                $return['error'] = '选择的type为'
                                    .$insValue['type'].', ID为'
                                    .$insValue['id'].' 的实例不存在';

                                return $return;
                            }
                        }
                        $returnData[] = [
                            'order_id'     => $orderVal['order_id'],
                            'order_object' => [
                                'type'             => 200,
                                'scope'            => $orderVal['scope'],
                                $orderVal['scope'] => $orderVal[$orderVal['scope']],
                            ],
                        ];
                    } else {
                        if ($scope == 'customerGroup') {
                            if (empty($orderVal[$orderVal['scope']])
                                || ! is_array($orderVal[$orderVal['scope']])
                            ) {
                                $return['error'] = '命令作用域为空或错误';

                                return $return;
                            }
                            $customerModel = new CustomGroupModel;
                            foreach (
                                $orderVal[$orderVal['scope']] as $customerID
                            ) {
                                $customerData = $customerModel->findOne(
                                    ['id' => $customerID]
                                );
                                if (empty($customerData)) {
                                    $return['error'] = '选择的ID为'.$customerID
                                        .' 的自定义组不存在';

                                    return $return;
                                }
                            }
                            $returnData[] = [
                                'order_id'     => $orderVal['order_id'],
                                'order_object' => [
                                    'type'             => 200,
                                    'scope'            => $orderVal['scope'],
                                    $orderVal['scope'] => $orderVal[$orderVal['scope']],
                                ],
                            ];
                        } else {
                            if ($scope == 'Group') {
                                if (empty($orderVal[$orderVal['scope']])
                                    || ! is_array(
                                        $orderVal[$orderVal['scope']]
                                    )
                                ) {
                                    $return['error'] = '命令作用域为空或错误';

                                    return $return;
                                }
                                $groupModel = new ServerGroupModel;
                                foreach (
                                    $orderVal[$orderVal['scope']] as
                                    $groupID
                                ) {
                                    $groupData
                                        = $groupModel->findAllByParams(
                                            ['id' => $groupID]
                                        );
                                    if (empty($groupData)) {
                                        $return['error'] = '选择的ID为'.$groupID
                                            .' 的实例组不存在';

                                        return $return;
                                    }
                                }
                                $returnData[] = [
                                    'order_id'     => $orderVal['order_id'],
                                    'order_object' => [
                                        'type'             => 200,
                                        'scope'            => $orderVal['scope'],
                                        $orderVal['scope'] => $orderVal[$orderVal['scope']],
                                    ],
                                ];
                            } else {
                                $return['error'] = '命令Type错误';

                                return $return;
                            }
                        }
                    }
                }

                break;

            case 300: //容器
                $scope = $orderVal['scope'];
                if ($scope == 'all') {
                    $returnData[] = [
                        'order_id'     => $orderVal['order_id'],
                        'order_object' => [
                            'type'  => 300,
                            'scope' => $orderVal['scope'],
                        ],
                    ];
                } elseif ($scope == 'dockList') {
                    if (empty($orderVal[$orderVal['scope']])
                        || ! is_array($orderVal[$orderVal['scope']])
                    ) {
                        $return['error'] = '命令作用域为空或错误';

                        return $return;
                    }
                    $dockerModel = new ServerEnvModel;
                    $pDmodel = new \DAO\ProjectDockerModel();

                    foreach (
                        $orderVal[$orderVal['scope']] as $dockerVal
                    ) {
                        $dockerInfo = $pDmodel->findOne($dockerVal['id']);
                        if (!$dockerInfo) {
                            $return['error'] = '未查询到'.$dockerVal['name'].'的容器信息，容器不存在';
                        }
                        $dockerData = $dockerModel->findAll(
                            ['server_id' => $dockerInfo['instance_id'],'image_name' => $dockerInfo['image_name']]
                        );
                        if (empty($dockerData)) {
                            $return['error'] = '选择的name为'
                                .$dockerVal['name'].' 的容器环境变量配置不存在';

                            return $return;
                        }
                    }
                    $returnData[] = [
                        'order_id'     => $orderVal['order_id'],
                        'order_object' => [
                            'type'             => 300,
                            'scope'            => $orderVal['scope'],
                            $orderVal['scope'] => $orderVal[$orderVal['scope']],
                        ],
                    ];
                } elseif ($scope == 'customerGroup') {
                    if (empty($orderVal[$orderVal['scope']])
                        || ! is_array($orderVal[$orderVal['scope']])
                    ) {
                        $return['error'] = '命令作用域为空或错误';

                        return $return;
                    }
                    $customerModel = new CustomGroupModel;
                    foreach (
                        $orderVal[$orderVal['scope']] as $customerID
                    ) {
                        $customerData = $customerModel->findOne(
                            ['id' => $customerID]
                        );
                        if (empty($customerData)) {
                            $return['error'] = '选择的ID为'.$customerID
                                .' 的自定义组不存在';

                            return $return;
                        }
                    }
                    $returnData[] = [
                        'order_id'     => $orderVal['order_id'],
                        'order_object' => [
                            'type'             => 300,
                            'scope'            => $orderVal['scope'],
                            $orderVal['scope'] => $orderVal[$orderVal['scope']],
                        ],
                    ];
                } elseif ($scope == 'Group') {
                    if (empty($orderVal[$orderVal['scope']])
                        || ! is_array(
                            $orderVal[$orderVal['scope']]
                        )
                    ) {
                        $return['error'] = '命令作用域为空或错误';

                        return $return;
                    }
                    $groupModel = new ServerGroupModel;
                    foreach (
                        $orderVal[$orderVal['scope']] as
                        $groupID
                    ) {
                        $groupData
                            = $groupModel->findAllByParams(
                                ['id' => $groupID]
                            );
                        if (empty($groupData)) {
                            $return['error'] = '选择的ID为'.$groupID
                                .' 的实例组不存在';

                            return $return;
                        }
                    }
                    $returnData[] = [
                        'order_id'     => $orderVal['order_id'],
                        'order_object' => [
                            'type'             => 300,
                            'scope'            => $orderVal['scope'],
                            $orderVal['scope'] => $orderVal[$orderVal['scope']],
                        ],
                    ];
                } else {
                    $return['error'] = '命令Type错误';

                    return $return;
                }

                break;

            default:

                $return['error'] = '命令Type错误';

                return $return;
            }
        }

        $return['status'] = true;
        $return['data']   = $returnData;

        return $return;
    }

    public function updateTaskInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $id          = $_POST['id'] ?? null;
        $name        = $_POST['name'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id  = $_POST['pid'] ?? null;
        $script      = $_POST['script'] ?? null;
        $user_id     = $this->getUserId();

        if (empty($id) || empty($name) || empty($description)
            || empty($project_id)
            || empty($script)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $script = json_decode($script, true);
        if (! is_array($script)) {
            $return['error'] = 'Scope格式错误';

            return $this->json = $return;
        }

        $model    = new TaskInfoModel;
        $taskData = $model->findOne(['id' => $id]);
        if (empty($taskData)) {
            $return['error'] = '未发现此ID的任务';

            return $this->json = $return;
        }

        if ($taskData['update_status'] != 200
            && $taskData['create_user'] != $user_id
            && empty($this->rootCheck())
        ) {
            $return['error'] = '您目前没有权限执行此操作';

            return $this->json = $return;
        }

        // 格式化作用域参数
        $scopeStatus = $this->scopeFormat($script);
        if (! $scopeStatus['status']) {
            $return['error'] = $scopeStatus['error'];

            return $this->json = $return;
        }

        $paramsArr = [
            'id'          => $id,
            'name'        => $name,
            'description' => $description,
            'project_id'  => $project_id,
            'order'       => $scopeStatus['data'],
        ];

        $model = new TaskInfoModel;
        $data  = $model->updateOne($paramsArr);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }

        $return['status'] = 200;
        $return['data']   = ['id' => $data['id']];

        return $this->json = $return;
    }

    public function delTaskInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $id     = $_POST['id'] ?? null;
        if (empty($id)) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $user_id  = $this->getUserId();
        $model    = new TaskInfoModel;
        $taskData = $model->findOne(['id' => $id]);
        if (empty($taskData)) {
            $return['error'] = '未发现此ID的任务';

            return $this->json = $return;
        }

        if ($taskData['create_user'] != $user_id && empty($this->rootCheck())) {
            $return['error'] = '您目前没有权限执行此操作';

            return $this->json = $return;
        }

        $model = new TaskInfoModel;
        $data  = $model->deleteOne($id);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }
        $return['status'] = 200;

        $return['data'] = ['id' => $data['id']];

        return $this->json = $return;
    }
}
