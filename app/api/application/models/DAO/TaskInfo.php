<?php

namespace DAO;

class TaskInfoModel extends \MysqlBase
{
    public function findOne($paramsArr=null)
    {
        $sql = 'SELECT * FROM `task_info` WHERE id<>0  ';
        $arr = [];
        if (!empty($paramsArr)) {
            foreach ($paramsArr as $key => $value) {
                $sql .= ' AND '.$key.'=:'.$key.' ';
                $arr[':'.$key] = $value;
            }
        }
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetch(\PDO::FETCH_ASSOC);

        $taskOrderModel = new \DAO\TaskOrderModel;
        $returnData = [];
        if (!empty($red)) {
            $red['order'] = $taskOrderModel->findAll(['task_id' => $red['id']]);
        }

        return $red;
    }


    // 根据ID获得任务详细配置，用于任务执行使用
    public function findOneById($id, $order_sort = null)
    {
        $return = ['status' => false, 'error' => ''];

        $sql = 'SELECT * FROM `task_info` WHERE id='.$id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute();
        $red = $sth->fetch(\PDO::FETCH_ASSOC);
        
        $taskOrderModel = new \DAO\TaskOrderModel;
        $orderModel = new \DAO\OrderInfoModel;

        $returnData = [];
        if (!empty($red)) {
            $orderArr = $taskOrderModel->findAll(['task_id' => $red['id']]);
            if (!empty($orderArr)) {
                $start_num = empty($order_sort) ? 1 : intval($order_sort);
                $new_num = 0;
                foreach ($orderArr as $order) {
                    // 从第几步开始
                    $new_num++;
                    if ($new_num < $start_num) {
                        continue;
                    }
                    $orderData = $orderModel->findOne(['id' => $order['order_id']]);
                    if (empty($orderData)) {
                        $return['error'] = '未发现此任务中ID为'.$order['order_id'].'的命令';
                        return $return;
                    }

                    switch ($order['order_object']['type']) {
                    case '100':
                        $arr = $this->taskParamsFormatLocal($order, $orderData, $red['project_id']);
                        break;

                    case '200':
                        $arr = $this->taskParamsFormatRemoteIns($order, $orderData, $red['project_id']);
                        break;

                    case '300':
                        $arr = $this->taskParamsFormatRemoteDocker($order, $orderData, $red['project_id']);
                        break;
                        
                    default:
                        $return['error'] = '此任务下的命令: '.$orderData['name'].' type错误';
                        return $return;
                    }

                    if (empty($arr['status'])) {
                        $return['error'] = $arr['error'];
                        return $return;
                    }

                    $returnData[] = $arr['data'];
                }
            } else {
                $return['error'] = '未发现此任务的命令';
                return $return;
            }
        } else {
            $return['error'] = '未发现此ID的任务';
            return $return;
        }

        $return['status'] = true;
        $return['data'] = $returnData;
        return $return;
    }

    // 处理类型为本地的命令的作用域
    public function taskParamsFormatLocal($order, $orderData, $project_id)
    {
        $return = ['status' => true, 'error' => ''];
        $arr = [
            'type' => 'script',
            'order' => $orderData['order'],
            'object' => [
                [
                    'ip' => 'localhost',
                    'type' => 'local',
                ],
            ],
        ];
        return $return;
    }

    // 处理类型为远程实例的命令的作用域
    public function taskParamsFormatRemoteIns($order, $orderData, $project_id)
    {
        $return = ['status' => false, 'error' => ''];
        $returnArr = [
            'type' => 'script',
            'order' => $orderData['order'],
            'object' => [],
        ];

        $serverArr = [];
        switch ($order['order_object']['scope']) {
        case 'all':
            $projectServerModel = new ProjectServerModel;
            $projectServerData = $projectServerModel->findAllByParams(['project_id' => $project_id, 'status' => ProjectServerModel::STATUS_SUCCESS]);
            if (!empty($projectServerData)) {
                foreach ($projectServerData as $value) {
                    $serverArr[] = [
                        'type' => $value['type'],
                        'server_id' => $value['server_id'],
                    ];
                }
            }
            break;

        case 'customerGroup':
            $customeModel = new \DAO\CustomGroupModel;
            foreach ($order['order_object']['customerGroup'] as $customeGroupId) {
                $customeData = $customeModel->findAll(['id' => $customeGroupId]);
                if (!empty($customeData)) {
                    $serverArr = json_decode($customeData[0]['server_id'], true);
                } else {
                    $return['error'] = '未发现ID为'.$orderData['id'].'的命令中的ID为'.$customeGroupId.'的自定义组';
                    return $return;
                }
            }
            break;

        case 'Group':
            $projectServerModel = new ProjectServerModel;
            foreach ($order['order_object']['Group'] as $groupId) {
                $projectServerData = $projectServerModel->findAllByParams(['project_id' => $project_id, 'group_id'=>$groupId, 'status' => ProjectServerModel::STATUS_SUCCESS]);
                if (!empty($projectServerData)) {
                    foreach ($projectServerData as $value) {
                        $serverArr[] = [
                            'type' => $value['type'],
                            'server_id' => $value['server_id'],
                        ];
                    }
                }
            }
            break;

        case 'insList':
            foreach ($order['order_object']['insList'] as $insListValue) {
                $serverArr[] = [
                    'type' => $insListValue['type'],
                    'server_id' => $insListValue['id'],
                ];
            }
            break;
                            
        default:
            $return['error'] = 'ID为'.$orderData['id'].'的命令中的作用域类型错误';
            return $return;
        }

        if (empty($serverArr)) {
            $return['error'] = 'ID为'.$orderData['id'].'的命令中未发现可以执行的实例';
            return $return;
        }

        foreach ($serverArr as $serverValue) {
            $serverModel = \DAO\ProjectServerModel::getServerModel($serverValue['type']);
            if (empty($serverModel)) {
                $return['error'] = 'ID为'.$serverValue['server_id'].'的实例type错误';
                return $return;
            }
            $data = $serverModel->findAllByParams(['id' => $serverValue['server_id'], 'status' => 'success']);
            if (!empty($data)) {
                $returnArr['object'][] = [
                    'ip' => $data[0]['internal_ip'],
                    'type' => $serverValue['type'],
                    'public_ip' => $data[0]['public_ip'],
                    'name' => $data['0']['name'],
                ];
            } else {
                $return['error'] = '未发现ID为'.$serverValue['server_id'].', type为'.$serverValue['type'].' 的实例';
                return $return;
            }
        }

        $return['data'] = $returnArr;
        $return['status'] = true;
        return $return;
    }

    // 处理类型为远程容器的命令的作用域
    public function taskParamsFormatRemoteDocker($order, $orderData, $project_id)
    {
        $return = ['status' => false, 'error' => ''];
        $returnArr = [
            'type' => 'docker_exec',
            'order' => $orderData['order'],
            'object' => [],
        ];

        $serverArr = [];
        switch ($order['order_object']['scope']) {
        case 'all':
            $projectServerModel = new ProjectServerModel;
            $projectServerData = $projectServerModel->findAllByParams(['project_id' => $project_id, 'status' => ProjectServerModel::STATUS_SUCCESS]);
            if (!empty($projectServerData)) {
                foreach ($projectServerData as $value) {
                    $serverArr[] = [
                        'type' => $value['type'],
                        'server_id' => $value['server_id'],
                    ];
                }
            }
            break;

        case 'customerGroup':
            $customeModel = new \DAO\CustomGroupModel;
            foreach ($order['order_object']['customerGroup'] as $customeGroupId) {
                $customeData = $customeModel->findAll(['id' => $customeGroupId]);
                if (!empty($customeData)) {
                    if ($customeData[0]['group_type'] == 100) {
                        $serverArr = json_decode($customeData[0]['server_id'], true);
                    } elseif ($customeData[0]['group_type'] == 200) {
                        $serverArr = 'type_docker';
                        $dockerArr = json_decode($customeData[0]['server_id'], true);
                        $dockerIdsArr = [];
                        foreach ($dockerArr as $value) {
                            if (!empty($value['docker_id'])) {
                                $dockerIdsArr[] = $value['docker_id'];
                            }
                        }
                    }
                } else {
                    $return['error'] = '未发现ID为'.$orderData['id'].'的命令中的ID为'.$customeGroupId.'的自定义组';
                    return $return;
                }
            }
            break;

        case 'Group':
            $projectServerModel = new ProjectServerModel;
            foreach ($order['order_object']['Group'] as $groupId) {
                $projectServerData = $projectServerModel->findAllByParams(['project_id' => $project_id, 'group_id'=>$groupId, 'status' => ProjectServerModel::STATUS_SUCCESS]);
                if (!empty($projectServerData)) {
                    foreach ($projectServerData as $value) {
                        $serverArr[] = [
                            'type' => $value['type'],
                            'server_id' => $value['server_id'],
                        ];
                    }
                }
            }
            break;

        case 'dockList':
            $serverArr = 'type_docker';
            $dockerIdArr = $order['order_object']['dockList'];
            $dockerNameArr = $dockerIdsArr = [];
            foreach ($dockerIdArr as $dockerIdArrValue) {
                $dockerNameArr[] = $dockerIdArrValue['name'];
                $dockerIdsArr[] = $dockerIdArrValue['id'];
            }
            break;
                            
        default:
            $return['error'] = 'ID为'.$orderData['id'].'的命令中的作用域类型错误';
            return $return;
        }

        $dockerModel = new \DAO\ProjectDockerModel;
        $serverEnvModel = new \DAO\ServerEnvModel;
        $dockerParamsArr = [];
        if ($serverArr == 'type_docker') {
            if (!empty($dockerIdsArr)) {
                foreach ($dockerIdsArr as $dockerId) {
                    $arr = $dockerModel->findAllByParams(['id' => $dockerId,'status' => 200]);
                    if (empty($arr)) {
                        $return['error'] = 'ID为'.$orderData['id'].'的命令中 id为'.$dockerId.'的容器未启动或配置不存在';
                        return $return;
                    }
                    $dockerParamsArr[] = [
                        'server_id' => $arr[0]['instance_id'],
                        'type' => $arr[0]['cloud_type'],
                        'image_name' => $arr[0]['image_name'],
                        'container_name' => $arr[0]['name'],
                    ];
                }
            }
        } else {
            if (empty($serverArr)) {
                $return['error'] = 'ID为'.$orderData['id'].'的命令中未发现可以执行的容器1';
                return $return;
            }
            foreach ($serverArr as $serverValue) {
                $arr = $serverEnvModel->findAll(['server_id' => $serverValue['server_id']]);
                if (!empty($arr)) {
                    foreach ($arr as $arrValue) {
                        $dockerParamsArr[] = $arrValue;
                    }
                }
            }
        }

        if (empty($dockerParamsArr)) {
            $return['error'] = 'ID为'.$orderData['id'].'的命令中未发现可以执行的容器2';
            return $return;
        }

        foreach ($dockerParamsArr as $dockerParamsValue) {
            $params = [
                'instance_id' => $dockerParamsValue['server_id'],
                'cloud_type' => 'gaea',
                'image_name' => $dockerParamsValue['image_name'],
                'name' => $dockerParamsValue['container_name'],
                'status' => \DAO\ProjectDockerModel::STATUS_OPEN,
            ];
            $dockerData = $dockerModel->findAllByParams($params);
            if (empty($dockerData)) {
                $return['error'] = 'GAEA下ID为'.$dockerParamsValue['server_id'].'的实例中image为'.$dockerParamsValue['image_name'].',name为'.$dockerParamsValue['container_name'].'的容器目前未启动，无法执行任务';
                return $return;
            }
            $returnArr['object'][] = [
                'id' => $dockerData[0]['id'],
                'ip' => $dockerData[0]['ip'],
                'name' => $dockerData[0]['name'],
                'container_id' => $dockerData[0]['container_id'],
                'type' => $dockerData[0]['cloud_type'],
            ];
        }

        $return['data'] = $returnArr;
        $return['status'] = true;
        return $return;
    }

    public function getServerModel($type)
    {
        $model = \DAO\ProjectServerModel::getServerModel($type);
        return $model;
    }


    public function findAll($paramsArr=null)
    {
        $sql = 'SELECT * FROM `task_info` ';
        $arr = [];
        if (!empty($paramsArr)) {
            $sql .= ' WHERE ';
            $strArr = [];
            foreach ($paramsArr as $key => $value) {
                $strArr[] = $key.'=:'.$key.' ';
                $arr[':'.$key] = $value;
            }
            $sql .= ' '.implode(' and ', $strArr);
        }

        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $sth->execute($arr);
        $red = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $taskOrderModel = new \DAO\TaskOrderModel;
        $returnData = [];
        if (!empty($red)) {
            foreach ($red as $value) {
                $value['order'] = $taskOrderModel->findAll(['task_id' => $value['id']]);
                $returnData[] = $value;
            }
        }

        return $returnData;
    }

    public function insertOne($paramsArr=null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }
        if (empty($paramsArr['order']) || !is_array($paramsArr['order'])) {
            $return['error'] = '命令列表错误';
            return $return;
        }
        $orderArr = $paramsArr['order'];
        array_values($orderArr);
        unset($paramsArr['order']);
        // 事务
        $this->db->beginTransaction();
        
        $sql = 'INSERT INTO `task_info` (`'.implode('`,`', array_keys($paramsArr)).'`) VALUES (\''.implode('\',\'', $paramsArr).'\') ';
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error[2];
            $this->db->rollBack();
            return $return;
        }
        $task_id = $this->db->lastInsertId();

        $taskOrderModel = new \DAO\TaskOrderModel;
        $delStatus = $taskOrderModel->deleteAll(['task_id' => $task_id]);
        if (!$delStatus['status']) {
            $return['error'] = $delStatus['status'];
            $this->db->rollBack();
            return $return;
        }
        foreach ($orderArr as $key => $value) {
            $arr = $value;
            $arr['task_id'] = $task_id;
            $arr['order_sort'] = $key+1;
            $status = $taskOrderModel->insertOne($arr);
            if (!$status['status']) {
                $return['error'] = $status['error'];
                $this->db->rollBack();
                return $return;
            }
        }

        $this->db->commit();
        $return['status'] = true;
        $return['id'] = $task_id;
        return $return;
    }

    public function updateOne($paramsArr=null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }

        if (empty($paramsArr['id'])) {
            $return['error'] = 'ID为空';
            return $return;
        }
        if (empty($paramsArr['order']) || !is_array($paramsArr['order'])) {
            $return['error'] = '命令列表错误';
            return $return;
        }
        $id = $paramsArr['id'];
        unset($paramsArr['id']);
        $orderArr = $paramsArr['order'];
        array_values($orderArr);
        unset($paramsArr['order']);
        
        // 事务
        $this->db->beginTransaction();

        $sql = 'UPDATE `task_info` set  ';
        $arr = [];
        foreach ($paramsArr as $key => $value) {
            $arr[] = '`'.$key.'`=\''.$value.'\'';
        }
        $sql .= implode(' , ', $arr);
        $sql .= ' WHERE id='.$id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error['2'];
            $this->db->rollBack();
            return $return;
        }

        $task_id = $id;

        $taskOrderModel = new \DAO\TaskOrderModel;
        $delStatus = $taskOrderModel->deleteAll(['task_id' => $task_id]);
        if (!$delStatus['status']) {
            $return['error'] = $delStatus['status'];
            $this->db->rollBack();
            return $return;
        }
        foreach ($orderArr as $key => $value) {
            $arr = $value;
            $arr['task_id'] = $task_id;
            $arr['order_sort'] = $key+1;
            $status = $taskOrderModel->insertOne($arr);
            if (!$status['status']) {
                $return['error'] = $status['error'];
                $this->db->rollBack();
                return $return;
            }
        }

        $this->db->commit();
        $return['status'] = true;
        $return['id'] = $id;
        return $return;
    }

    public function update($paramsArr=null)
    {
        $return = ['status' => false, 'error' => ''];
        if (empty($paramsArr)) {
            $return['error'] = '参数为空';
            return $return;
        }

        if (empty($paramsArr['id'])) {
            $return['error'] = 'ID为空';
            return $return;
        }

        $id = $paramsArr['id'];
        unset($paramsArr['id']);

        $sql = 'UPDATE `task_info` set ';
        $arr = [];
        foreach ($paramsArr as $key => $value) {
            $arr[] = '`'.$key.'`=\''.$value.'\'';
        }
        $sql .= implode(' , ', $arr);
        $sql .= ' WHERE id='.$id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();

        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error['2'];
            return $return;
        }
        $return['status'] = true;
        $return['id'] = $id;
        return $return;
    }

    public function deleteOne($id)
    {
        $return = ['status' => false, 'error' => ''];

        // 事务
        $this->db->beginTransaction();

        $sql = 'DELETE FROM task_info WHERE id='.$id;
        $sth = $this->db->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY]);
        $serStatus = $sth->execute();
        if (!$serStatus) {
            $error = $sth->errorInfo();
            $return['error'] = $error['2'];
            $this->db->rollBack();
            return $return;
        }

        $taskOrderModel = new \DAO\TaskOrderModel;
        $delStatus = $taskOrderModel->deleteAll(['task_id' => $id]);
        if (!$delStatus['status']) {
            $return['error'] = $delStatus['status'];
            $this->db->rollBack();
            return $return;
        }

        $this->db->commit();

        $return['status'] = true;
        $return['id'] = $id;
        return $return;
    }
}
