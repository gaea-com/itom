<?php

use DAO\DockerComposeModel;
use DAO\DockerImageModel;
use DAO\EnvTemplateModel;
use DAO\ProjectDockerModel;
use DAO\ProjectModel;
use DAO\ProjectServerModel;
use DAO\ServerEnvModel;
use DAO\ServerGroupModel;

/**
 * 服务器实例相关控制器
 *
 */
class ServerController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    //实例列表
    public function indexAction()
    {
        $name      = $_POST['name'] ?? null;
        $projectId = $_POST['pid'] ?? null;
        $groupId   = $_POST['gid'] ?? null;
        $server_id = $_POST['sid'] ?? null;
        $type      = $_POST['type'] ?? null;
        $page      = $_POST['page'] ?? null;
        $count     = $_POST['count'] ?? null;
        $status    = $_POST['status'] ?? null;
        $sortArr   = $_POST['sorting'] ?? null;

        $envModel     = new EnvTemplateModel;
        $imageDiffArr = $envModel->envServerChech();

        $envServerModel = new ServerEnvModel;

        $composeModel = new ProjectServerModel;
        $composeDiff  = $composeModel->getAllComposeChangeServer();

        $paramsArr = [];
        if (! empty($name)) {
            $paramsArr['name'] = $name;
        }
        if (! empty($projectId)) {
            $paramsArr['project_id'] = $projectId;
        }
        if (! empty($groupId) || $groupId === '0' || $groupId === 0) {
            $paramsArr['group_id'] = $groupId;
        }
        if (! empty($server_id)) {
            $paramsArr['server_id'] = $server_id;
        }
        if (! empty($type)) {
            $paramsArr['type'] = $type;
        }
        if (! empty($status) && $status == 'delete') {
            $paramsArr['status'] = ProjectServerModel::STATUS_DELETE;
        }
        if (! empty($sortArr['name'])
            && in_array(
                $sortArr['name'],
                ['asc', 'desc']
            )
        ) {
            $paramsArr['sort'] = ['name' => $sortArr['name']];
        }

        if (! empty($page) && ! empty($count)) {
            $limit               = $count;
            $offset              = $count * ($page - 1);
            $paramsArr['limit']  = $limit;
            $paramsArr['offset'] = $offset;
            $list                = $composeModel->findAllServerByParamsLimit(
                $paramsArr
            );
            $totalCount          = $list['totalCount'];
            unset($list['totalCount']);
        } else {
            $list       = $composeModel->findAllServerByParamsLimit($paramsArr);
            $totalCount = $list['totalCount'];
            unset($list['totalCount']);
        }

        foreach ($list as $key => $value) {
            if ($value['status'] == 400) {
                continue;
            }
            $proModel                   = new ProjectModel;
            $proData                    = $proModel->findOneByCache(
                $value['project_id']
            );
            $list[$key]['project_name'] = $proData['name'];
            $comModel                   = new DockerComposeModel;
            $comData                    = $comModel->findOneByCache(
                $value['compose_id']
            );
            $list[$key]['compose_name'] = $comData['name'];
            $imageArr                   = json_decode(
                $comData['image_name'],
                true
            );
            $imageArr                   = empty($imageArr) ? [] : $imageArr;
            $imageTimesArr              = json_decode(
                $comData['image_times'],
                true
            );
            $imageTimesArr              = empty($imageTimesArr) ? []
                : $imageTimesArr;

            $list[$key]['image_name'] = empty($imageArr)
                ? []
                : array_values(
                    $imageArr
                );

            if ($value['group_id'] == 0) {
                $list[$key]['group_name'] = '虚拟组';
            } else {
                $groupModel               = new ServerGroupModel;
                $groupData                = $groupModel->findOneByCache(
                    $value['group_id']
                );
                $list[$key]['group_name'] = $groupData['name'];
            }

            $docModel = new ProjectDockerModel;
            $docData  = $docModel->findAllOpen(
                $value['project_id'],
                $value['server_id'],
                $value['type']
            );
            $arr      = [];
            if (! empty($docData)) {
                foreach ($docData as $v) {
                    $arr[] = [
                        'docker_id'   => $v['id'],
                        'docker_name' => $v['name'],
                    ];
                }
            }
            $list[$key]['docker'] = $arr;

            $serverModel = ProjectServerModel::getServerModel(
                $value['type']
            );
            if (! empty($serverModel)) {
                $serverData = $serverModel->findOneByCache($value['server_id']);
                if (! empty($serverData)) {
                    foreach ($serverData as $serverKey => $serverVal) {
                        //if (!array_key_exists($serverKey, $list[$key])) {
                        if (! in_array(
                            $serverKey,
                            ['project_id', 'description']
                        )
                        ) {
                            $list[$key][$serverKey] = $serverVal;
                        }
                        //}
                    }
                } else {
                    unset($list[$key]);
                    continue;
                }
            } else {
                unset($list[$key]);
                continue;
            }

            if ($list[$key]['type'] != 'bcc') {
                if (! empty($list[$key]['memory'])) {
                    $list[$key]['memery'] = $list[$key]['memory'];
                }
                if (! empty($list[$key]['disk_info'])) {
                    $cdsArr = json_decode($list[$key]['disk_info'], true);
                    if (! empty($cdsArr['rootSize'])) {
                        $list[$key]['cds'] = $cdsArr['rootSize'];
                    }
                }
            } else {
                if (! empty($list[$key]['cds_info'])) {
                    $cdsArr = json_decode($list[$key]['cds_info'], true);
                    if (! empty($cdsArr['diskSizeInGB'])) {
                        $list[$key]['cds'] = $cdsArr['diskSizeInGB'];
                    }
                }
            }

            $list[$key]['image_status'] = $this->getImageStatus(
                $list[$key]['internal_ip'],
                $list[$key]['image_name']
            );

            $comKey = $value['type'].'_'.$value['server_id'];
            //            // 小橙框
            //            $list[$key]['image_diff']
            //                = $envServerModel->orangePointCheckByServer(
            //                $value['server_id'],
            //                $value['type'],
            //                $imageTimesArr,
            //                $imageDiffArr
            //            );
            //            // 小红点
            //            $list[$key]['env_diff'] = $envServerModel->redPointCheck(
            //                $value['server_id'],
            //                $value['type'],
            //                $list[$key]['internal_ip']
            //            );
        }

        $returnArr = ['status' => 200, 'data' => $list];
        if (! empty($totalCount)) {
            $returnArr['totalCount'] = intval($totalCount);
        }

        return $this->json = $returnArr;
    }

    private function getImageStatus($internal_ip, $composeImageArr)
    {
        $comModel = new DockerImageModel;
        $comData  = $comModel->findByIp($internal_ip);
        $imageArr = [];
        if (! empty($comData)) {
            foreach ($comData as $val) {
                $imageArr[] = $val['name_version'];
            }
            $imageArr = array_unique($imageArr);
        }

        if (! empty($composeImageArr)) {
            foreach ($composeImageArr as $imageName) {
                if (! in_array($imageName, $imageArr)) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    //删除实例数据 修改状态  与恢复相对应
    //TODO::事务
    public function deleteInstanceAction()
    {
        $id   = $_POST['id'] ?? null;
        $json = json_decode($id, true);
        if (empty($json)) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100013,
                'errorMsg' => '参数不能为空',
            ];
        }
        $ServerEnvModel     = new DAO\ServerEnvModel;
        $DockerImageModel   = new DockerImageModel;
        $ProjectDockerModel = new ProjectDockerModel;
        $error              = $errId = $susscessId = [];

        foreach ($json as $value) {
            $model = new DAO\GaeaServerModel();
            $info  = $model->findOne($value['id']);
            if (! $info) {
                $error[] = 'Gaea服务器实例不存在：'.$value['id'];
                $errId[] = ['id' => $value['id'], 'name' => ''];
                break;
            }
            //1.项目关联表
            $projectServerModel = new ProjectServerModel;
            //删除记录
            $res = $projectServerModel->releaseOne($value['id'], $value['type']);
            if (! $res['status']) {
                $error[] = '服务器ID：'.$info['id'].'项目与服务器关联操作失败：'.$res['error'];
                $errId[] = ['id' => $info['id'], 'name' => $info['name']];
                break;
            }
            //2.gaea server
            $res = $model->releaseOne($value['id']);
            if (! $res['status']) {
                $error[] = '服务器ID：'.$info['id'].'项目与服务器关联操作失败：'.$res['error'];
                $errId[] = ['id' => $info['id'], 'name' => $info['name']];
                break;
            }
            //3.删除其关联的ENV记录，不是修改状态
            $res = $ServerEnvModel->destroyEnvInsByServer(
                $value['id'],
                $value['type']
            );
            if (! $res['status']) {
                $error[] = '服务器ID：'.$info['id'].'ENV关联操作失败：'.$res['error'];
                $errId[] = ['id' => $info['id'], 'name' => $info['name']];
                break;
            }
            //4.删除宿主机上的docker镜像记录，不是该状态，实际镜像仍然存在在宿主机上
            $res = $DockerImageModel->deleteAllByServerId($value['ip']);
            if (! $res['status']) {
                $error[] = '服务器ID：'.$info['id'].'镜像关联操作失败：'.$res['error'];
                $errId[] = ['id' => $info['id'], 'name' => $info['name']];
                break;
            }
            //宿主机上的container记录也删除，实际仍然存在在宿主机上
            $res = $ProjectDockerModel->deleteAllByServerId(
                $value['id'],
                $value['type']
            );
            if (! $res['status']) {
                $error[] = '服务器ID：'.$info['id'].'容器关联操作失败：'.$res['error'];
                $errId[] = ['id' => $info['id'], 'name' => $info['name']];
                break;
            }
            $susscessId = ['id' => $info['id'], 'name' => $info['name']];
        }

        if (! empty($error)) {
            return $this->json = ['status'     => 400,
                                  'error'      => 100079,
                                  'errorMsg'   => $error,
                                  'susscessID' => $susscessId,
                                  'errID'      => $errId,
            ];
        }

        return $this->json = ['status' => 200];
    }

    // 根据实例获得关联的环境变量信息
    public function getEnvBySidAction()
    {
        $server_id = $_POST['sid'] ?? null;
        $type      = $_POST['type'] ?? null;

        if (empty($server_id) || empty($type)) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100063,
                'errorMsg' => '参数错误',
            ];
        }

        $model = new DAO\ProjectServerModel();
        $list  = $model->findAllServerByParams(
            ['server_id' => $server_id, 'type' => $type]
        );

        $data = [];
        foreach ($list as $key => $value) {
            $envModel = new EnvTemplateModel;
            $envData  = $envModel->findOneInstance($value['env_id']);
            $data[]   = $envData;
        }

        return $this->json = ['status' => 200, 'data' => $data];
    }

    // 编辑实例的环境变量实例值
    public function serverEnvUpdateAction()
    {
        if (empty($_POST['env_id'])) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100064,
                'errorMsg' => 'env_id不能为空',
            ];
        }
        $env_id = $_POST['env_id'];

        if (empty($_POST['params_env'])) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100065,
                'errorMsg' => '环境变量参数不能为空',
            ];
        }
        $params_env = json_decode($_POST['params_env'], true);
        if (! is_array($params_env)) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100066,
                'errorMsg' => '环境变量参数格式错误',
            ];
        }

        if (! empty($_POST['params_data'])) {
            $params_data = json_decode($_POST['params_data'], true);
            if (! is_array($params_data)) {
                return $this->json = [
                    'status'   => 400,
                    'error'    => 100067,
                    'errorMsg' => '数据卷参数格式错误',
                ];
            }
        } else {
            $params_data = [];
        }

        $data = [
            'env_id' => $env_id,
            'params' => [
                'env'  => $params_env,
                'data' => $params_data,
            ],
        ];

        $model  = new EnvTemplateModel;
        $status = $model->updateInstance($data);

        if (! $status['status']) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100068,
                'errorMsg' => $status['error'],
            ];
        }

        return $this->json = ['status' => 200, 'data' => $status['id']];
    }
   private function characet($data){
       if( !empty($data) ){
           $fileType = mb_detect_encoding($data , array('UTF-8','GBK','LATIN1','BIG5')) ;
           if( $fileType != 'UTF-8'){
               $data = mb_convert_encoding($data ,'utf-8' , $fileType);
           }
       }
       return $data;
   }
    //导入实例
    public function instanceIncludeAction()
    {
        $project_id   = $_POST['project_id'] ?? null;
        $ProjectModel = new DAO\ProjectModel();
        $res          = $ProjectModel->findOne($project_id);
        if (! $res) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100060,
                'errorMsg' => '项目不存在',
            ];
        }
        $upload = Tools\FuncModel::uploadFile('file');
        if (! $upload['status']) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => $upload['error'],
            ];
        }
        $body = [];
        $file = fopen($upload['file_url'], "r");
        $i    = 0;
        while (! feof($file)) {
            $data = fgetcsv($file);
            if (! $data) {
                continue;
            }
            $cloud_type = trim($data[1]);
            if ($cloud_type == 'gaea') {
                //当云服务器类型为gaea时
                $len = count($data);
                if ($len <> 8) {
                    return $this->json = ['status'   => 400,
                                          'errorMsg' => '导入的数据字段不全',
                    ];
                }
                //字段分别为:实例名称、服务器类型、描述、内网ip、公网ip
                $body[$cloud_type][] = [
                    'instance'    => $this->characet(trim($data[0])),
                    'cloud_type'  => $cloud_type,
                    'description' => $this->characet(trim($data[2])),
                    'internal_ip' => trim($data[3]),
                    'public_ip'   => trim($data[4]),
                    'cpu'         => trim($data[5]),
                    'ram'         => trim($data[6]),
                    'cds'         => trim($data[7]),
                ];
            }
            $i++;
            if ($i > 1000) {
                return $this->json = [
                    'status'   => 400,
                    'error'    => 100032,
                    'errorMsg' => '一次导入的数量不能超过1000，可多次导入',
                ];
            }
        }
        fclose($file);
        @unlink($upload['file_url']);

        $uid     = $this->getUserId();
        $mqModel = new DockerApiModel($uid);
        foreach ($body as $cloud_type => $value) {
            $res = $mqModel->pushInstanceMq(
                [
                    'data'       => $value,
                    'user_id'    => $uid,
                    'project_id' => $project_id,
                ],
                'instance_include'
            );
            if (! $res['status']) {
                return $this->json = [
                    'status'   => 400,
                    'errorMsg' => '云服务商类型：'.$cloud_type.'，'.$res['error'],
                ];
            }
        }

        return $this->json = ['status' => 200, 'errorMsg' => ''];
    }

    //给导入的实例绑定实例组
    public function bindGroupAction()
    {
        $composeId   = $_POST['compose_id'] ?? null;
        $groupId     = $_POST['group_id'] ?? null;
        $sid         = $_POST['instance_id'] ?? null;
        $project_id  = $_POST['project_id'] ?? null;
        $description = $_POST['description'] ?? null;
        $cloudType   = $_POST['cloud_type'] ?? null;
        if (! $composeId || ! $groupId || ! $sid) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100031,
                'errorMsg' => '参数不存在',
            ];
        }
        $DockerComposeModel = new DAO\DockerComposeModel();
        $sGroupModel        = new DAO\ServerGroupModel();
        $compose            = $DockerComposeModel->findOne($composeId);
        if (! $compose) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100061,
                'errorMsg' => 'compose不存在或状态错误',
            ];
        }
        $group = $sGroupModel->findOne($groupId);
        if (! $group) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100061,
                'errorMsg' => '实例组不存在',
            ];
        }
        $model  = ProjectServerModel::getServerModel($cloudType);
        $server = $model->findOne($sid);
        if (! $server) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100061,
                'errorMsg' => '实例不存在',
            ];
        }
        $pServerModel = new DAO\ProjectServerModel();
        $info         = $pServerModel->findOneBindByServerId($sid, $cloudType);
        if ($info['group_id']) {
            return $this->json = [
                'status'   => 400,
                'error'    => 10062,
                'errorMsg' => '此实例已经绑定实例组ID:'.$info['group_id'],
            ];
        }
        if ($info['compose_id']) {
            return $this->json = [
                'status'   => 400,
                'error'    => 10062,
                'errorMsg' => '此实例已经绑定composeID:'.$info['compose_id'],
            ];
        }
        $ProjectModel = new DAO\ProjectModel();
        $project      = $ProjectModel->findOne($project_id);
        if (! $project) {
            return $this->json = [
                'status'   => 400,
                'error'    => 100060,
                'errorMsg' => '项目不存在',
            ];
        }
        $res = $pServerModel->bindGroup(
            $composeId,
            $groupId,
            $sid,
            $description,
            $cloudType
        );
        if (! $res['status']) {
            return $this->json = [
                'status'   => 400,
                'error'    => 999999,
                'errorMsg' => $res['error'],
            ];
        }
        $uid     = $this->getUserId();
        $task_id = DockerApiModel::getId();
        $body    = [
            'compose_id'  => $composeId,
            'group_id'    => $groupId,
            'instance_id' => $sid,
            'description' => $description,
            'cloud_type'  => $cloudType,
        ];
        $res     = $this->insertOperateLog(
            $task_id,
            $cloudType,
            $project,
            $server,
            $uid,
            $body,
            '绑定成功',
            '绑定实例'
        );

        return $this->json = ['status' => 200, 'data' => '绑定成功'];
    }

    // 设置实例的环境变量配置

    private function insertOperateLog(
        $task_id,
        $cloudtype,
        $project,
        $instance,
        $uid,
        $body,
        $result,
        $operate
    ) {
        $data  = [
            ':task_id'       => $task_id,
            ':task_type'     => 'sync',
            ':step_no'       => 1,
            ':project_id'    => $project['id'],
            ':project_name'  => $project['name'],
            ':instance_id'   => $instance['id'],
            ':instance_name' => $instance['name'],
            ':cloud_type'    => $cloudtype,
            ':ip'            => $instance['internal_ip'],
            ':uid'           => $uid,
            ':request'       => json_encode(
                $body,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            ':result'        => is_array($result) ? json_encode(
                $result,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) : $result,
            ':operate'       => $operate,
        ];
        $model = new DAO\OrderOperateLogModel();
        $res   = $model->insertOne($data);
        if (! $res['status']) {
            return $this->json = ['status' => false, 'error' => '操作日志入库失败'];
        }

        return $this->json = ['status' => true];
    }

    /**
     * 根据镜像获得环境变量
     *
     * @params sid  实例ID
     *         type 实例类型
     *         cid  编排模板id
     *         option 操作方式  pull 获得env update更新env
     *
     * @return array
     */

    public function getEnvByImageAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $sid  = $_POST['sid'] ?? null;
        $type = $_POST['type'] ?? null;
        // $image_name = $_POST['image_name'] ?? null;
        $option     = $_POST['option'] ?? null;
        $compose_id = $_POST['cid'] ?? null;

        if (empty($sid) || empty($type) || empty($compose_id)) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        if ($option == 'pull') {
            $comModel      = new DockerComposeModel;
            $comData       = $comModel->findOne($compose_id);
            $imageArr      = json_decode($comData['image_name'], true);
            $imageArr      = empty($imageArr) ? [] : array_values($imageArr);
            $imageTimesArr = json_decode($comData['image_times'], true);

            $returnData = [];
            foreach ($imageTimesArr as $image_data_arr) {
                $image_name = $image_data_arr['image_name'];

                $serverModel = ProjectServerModel::getServerModel($type);
                if (empty($serverModel)) {
                    $return['error'] = 'type错误，未发现此服务器类型';

                    return $this->json = $return;
                }
                $model      = new ServerEnvModel;
                $statusData = $model->findOneByServerImageTid(
                    $sid,
                    $type,
                    $image_name,
                    $image_data_arr['tid']
                );

                if (empty($statusData['data']['id'])) {
                    $return['error'] = '未发现镜像：'.$image_name.' 下的环境变量模板或配置';

                    return $this->json = $return;
                }

                $data     = $statusData['data'];
                $tem_diff = $statusData['tem_diff'];

                $returnRowData = [
                    'id'                 => $data['id'],
                    'image_name'         => $data['image_name'],
                    'redPoint'           => $tem_diff,
                    'params_env'         => [],
                    'params_data'        => [],
                    'container_name'     => $data['container_name'],
                    'container_describe' => $data['container_describe'],
                    'container_num'      => $image_data_arr['sid'],
                    // 'container_tid'      => $image_data_arr['tid'],
                ];
                if (! empty($data['params_env'])) {
                    foreach ($data['params_env'] as $key => $value) {
                        $returnRowData['params_env'][] = [
                            'key'   => $key,
                            'value' => $value,
                        ];
                    }
                }

                if (! empty($data['params_data'])) {
                    foreach ($data['params_data'] as $key => $value) {
                        $returnRowData['params_data'][] = [
                            'key'   => $key,
                            'value' => $value,
                        ];
                    }
                }

                $returnData[$image_data_arr['sid']] = $returnRowData;
            }

            $return['status'] = 200;
            $return['data']   = $returnData;

            return $this->json = $return;
        } else {
            if ($option == 'update') {
                if (empty($_POST['image_env'])) {
                    $return['error'] = '环境变量配置为空';

                    return $this->json = $return;
                }
                $imageEnvArr = json_decode($_POST['image_env'], true);
                if (! is_array($imageEnvArr) || empty($imageEnvArr)) {
                    $return['error'] = '环境变量配置格式错误';

                    return $this->json = $return;
                }

                $comModel = new DockerComposeModel;
                $comData  = $comModel->findOne($compose_id);
                $imageArr = json_decode($comData['image_times'], true);
                $imageArr = empty($imageArr) ? [] : array_values($imageArr);

                foreach ($imageArr as $image_data_arr) {
                    $image_name = $image_data_arr['image_name'];
                    if (empty($imageEnvArr[$image_data_arr['sid'] - 1])) {
                        $return['error'] = '未发现第'.$image_data_arr['sid']
                            .'个要启动的容器的环境变量配置';

                        return $this->json = $return;
                    }
                }

                // 清空原设置
                $model     = new ServerEnvModel;
                $delStatus = $model->destroyEnvInsByServer($sid, $type);

                // 新设置
                foreach ($imageArr as $image_data_arr) {
                    $image_name = $image_data_arr['image_name'];
                    $env        = $imageEnvArr[$image_data_arr['sid'] - 1];
                    if ($env['image_name'] != $image_name) {
                        $return['error'] = '环境变量配置排序错误';

                        return $this->json = $return;
                    }
                    $container        = empty($env['container']) ? []
                        : $env['container'];
                    $container['num'] = $image_data_arr['sid'];
                    $container['tid'] = $image_data_arr['tid'];
                    $params           = [
                        'server_id'   => $sid,
                        'image_name'  => $image_name,
                        'type'        => $type,
                        'user_id'     => $this->getUserId(),
                        'container'   => $container,
                        'params_env'  => empty($env['env']) ? [] : $env['env'],
                        'params_data' => empty($env['data']) ? []
                            : $env['data'],
                    ];

                    $status = $model->insertOne($params);

                    if (! $status['status']) {
                        $return['errorImage'][$image_data_arr['sid']]
                            = $status['error'];
                    }
                }

                $return['status'] = 200;

                return $this->json = $return;
            } else {
                $return['error'] = 'option错误';

                return $this->json = $return;
            }
        }
    }
}
