<?php

use DAO\DockerImageModel;
use DAO\ProjectDockerModel;
use DAO\ProjectServerModel;
use DAO\ServerEnvModel;

/**
 * 各云实例的增删改查
 */
class CloudController extends BaseController
{
    private $config = [];
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
        $Millisecond = 0;

        $name      = $_GET['filter']['name'] ?? null; //项目name
        $projectId = $_GET['pid'] ?? null; //项目ID
        $groupId   = $_GET['gid'] ?? null; //实例组ID
        //$server_id = $_GET['sid'] ?? null; //实例ID
        $server_id = $this->getRequest()->getParam('id', null);
        $type      = $_GET['type'] ?? null; //云类型
        $page      = $_GET['page'] ?? null; //分页
        $count     = $_GET['count'] ?? null; //每页显示数量
        $status    = $_GET['status'] ?? null; //实例状态
        $sortArr   = $_GET['sorting'] ?? null;

        $composeModel = new \DAO\ProjectServerModel;
        $paramsArr = [];
        if (!empty($name)) {
            $paramsArr['name'] = $name;
        }
        if (!empty($projectId)) {
            $paramsArr['project_id'] = $projectId;
        }
        if (!empty($groupId)) {
            $paramsArr['group_id']   = explode('-', $groupId);
            //$paramsArr['group_id'][] = 0;
        }

        if (!empty($server_id)) {
            $paramsArr['server_id'] = $server_id;
        }
        if (!empty($type)) {
            $paramsArr['type'] = $type;
        }
        if (!empty($status) && $status == 'delete') {
            $paramsArr['status'] = \DAO\ProjectServerModel::STATUS_DELETE;
        }
        if (!empty($sortArr['name']) && in_array($sortArr['name'], ['asc', 'desc'])) {
            $paramsArr['sort'] = ['name' => $sortArr['name']];
        }

        if (!empty($page) && !empty($count)) {
            $limit               = $count;
            $offset              = $count * ($page - 1);
            $paramsArr['limit']  = $limit;
            $paramsArr['offset'] = $offset;
            $list                = $composeModel->findAllServerByParamsLimit($paramsArr);
            $totalCount          = $list['totalCount'];
            unset($list['totalCount']);
        } else {
            $list       = $composeModel->findAllServerByParamsLimit($paramsArr);
            $totalCount = $list['totalCount'];
            unset($list['totalCount']);
        }

        foreach ($list as $key => $value) {
            $proModel                   = new \DAO\ProjectModel;
            $proData                    = $proModel->findOneByCache($value['project_id']);
            $list[$key]['project_name'] = $proData['name'];
            $comModel                   = new \DAO\DockerComposeModel;
            $comData                    = $comModel->findOneByCache($value['compose_id']);
            $list[$key]['compose_name'] = $comData['name'];
            $imageArr                   = json_decode($comData['image_name'], true);
            $imageArr                   = empty($imageArr) ? [] : $imageArr;
            $imageTimesArr              = json_decode($comData['image_times'], true);
            $imageTimesArr              = empty($imageTimesArr) ? [] : $imageTimesArr;

            $list[$key]['image_name'] = empty($imageArr) ? [] : array_values($imageArr);

            if ($value['group_id'] == 0) {
                $list[$key]['group_name'] = '虚拟组';
            } else {
                $groupModel               = new \DAO\ServerGroupModel;
                $groupData                = $groupModel->findOneByCache($value['group_id']);
                $list[$key]['group_name'] = $groupData['name'];
            }

            $docModel = new \DAO\ProjectDockerModel;
            $docData  = $docModel->findAllOpen($value['project_id'], $value['server_id'], $value['type']);
            $arr      = [];
            if (!empty($docData)) {
                foreach ($docData as $v) {
                    $arr[] = [
                        'docker_id'   => $v['id'],
                        'docker_name' => $v['name'],
                    ];
                }
            }
            $list[$key]['docker'] = $arr;

            $serverModel = \DAO\ProjectServerModel::getServerModel($value['type']);
            if (!empty($serverModel)) {
                $serverData = $serverModel->findOneByCache($value['server_id']);
                if (!empty($serverData)) {
                    foreach ($serverData as $serverKey => $serverVal) {
                        //if (!array_key_exists($serverKey, $list[$key])) {
                        if (!in_array($serverKey, ['project_id', 'description'])) {
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


            $list[$key]['image_status'] = $this->getImageStatus($list[$key]['internal_ip'], $list[$key]['image_name']);
        }

        $returnArr = ['status' => 200, 'data' => $list];
        if (!empty($totalCount)) {
            $returnArr['totalCount'] = intval($totalCount);
        }
        return $this->json = $returnArr;
    }
    private function getImageStatus($internal_ip, $composeImageArr)
    {
        $comModel = new \DAO\DockerImageModel;
        $comData  = $comModel->findByIp($internal_ip);
        $imageArr = [];
        if (!empty($comData)) {
            foreach ($comData as $val) {
                $imageArr[] = $val['name_version'];
            }
            $imageArr = array_unique($imageArr);
        }

        if (!empty($composeImageArr)) {
            foreach ($composeImageArr as $imageName) {
                if (!in_array($imageName, $imageArr)) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }


    //删除实例（释放实例） 删除记录
    public function deleteAction()
    {
        $ids = $this->getRequest()->getParam('id', null);
        if (empty($ids)) {
            header('HTTP/1.1 403 Forbidden');
        }
        $idArr = array_filter(explode('_', $ids));
        if (empty($idArr)) {
            return $this->json = ['status' => 400, 'error' => 100064, 'errorMsg' => '实例ID不能为空'];
        }

        $input  = file_get_contents('php://input');
        $params = [];
        parse_str($input, $params);
        $json = json_decode($params['id'], true);
        if (empty($json)) {
            return $this->json = ['status' => 400, 'error' => 100013, 'errorMsg' => '参数不能为空'];
        }

        $error = $errId = $susscessId = [];
        foreach ($json as $server) {
            if (isset($server['id']) && isset($server['pid']) &&  isset($server['type']) && $server['type'] == 'gaea') {
                $model = new DAO\GaeaServerModel();
                $info  = $model->findOne($server['id']);
                if (!$info) {
                    $error[] = 'Gaea服务器实例不存在:' . $server['id'];
                    $errId[] = ['id'=>$server['id'] , 'name'=>''];
                    break;
                }
                $ProjectServerModel = new ProjectServerModel;
                $data               = $ProjectServerModel->findOneByServerId(
                    $server['pid'],
                    $server['id'],
                    $server['type']
                );
                if (! $data) {
                    $error[] = '项目关联服务器记录不存在！项目ID：'.$server['pid'].' 服务器ID：'.$server['id'].' 服务器名称：'.$info['name'];
                    $errId[] = ['id' => $info['id'], 'name' => $info['naem']];
                    break;
                }
                //数据库  TODO::事务
                //1.删除ENV
                $ServerEnvModel = new DAO\ServerEnvModel;
                $res            = $ServerEnvModel->destroyEnvInsByServer(
                    $server['id'],
                    $server['type']
                );
                if (!$res['status']) {
                    $error[] = '服务器ID：'.$server['id'].' 服务器名称：'.$info['name'].' ENV相关错误：'.$res['error'];
                    $errId[] = ['id' => $info['id'], 'name' => $info['naem']];
                    break;
                }
                unset($ServerEnvModel);

                //2.删除image list
                $DockerImageModel = new DockerImageModel;
                $res              = $DockerImageModel->deleteAllByServerId(
                    $data['internal_ip']
                );
                unset($DockerImageModel);
                if (!$res['status']) {
                    $error[] = '服务器ID：'.$server['id'].' 服务器名称：'.$info['name'].' 镜像列表相关错误：'.$res['error'];
                    $errId[] = ['id' => $info['id'], 'name' => $info['naem']];
                    break;
                }
                //3.删除container list
                $ProjectDockerModel = new ProjectDockerModel;
                $res                = $ProjectDockerModel->deleteAllByServerId(
                    $server['id'],
                    $server['type']
                );
                unset($ProjectDockerModel);
                if (!$res['status']) {
                    $error[] = '服务器ID：'.$server['id'].' 服务器名称：'.$info['name'].' 容器列表相关错误：'.$res['error'];
                    $errId[] = ['id' => $info['id'], 'name' => $info['naem']];
                    break;
                }
                //4.删除项目关联列表 projecserver list 真删
                $res   = $ProjectServerModel->releaseOne($server['id'], $server['type']);
                unset($model);
                if (!$res['status']) {
                    $error[] = '服务器ID：'.$server['id'].' 服务器名称：'.$info['name'].' 项目关联列表相关错误：'.$res['error'];
                    $errId[] = ['id' => $info['id'], 'name' => $info['naem']];
                    break;
                }
                //5.删除gaea server list 真删
                $res         = $model->releaseOne($server['id']);
                unset($serverModel);
                if (!$res['status']) {
                    $error[] = '服务器ID：'.$server['id'].' 服务器名称：'.$info['name'].' GaeaServer列表相关错误：'.$res['error'];
                    $errId[] = ['id' => $info['id'], 'name' => $info['naem']];
                    break;
                }
                $susscessId[] = ['id' => $info['id'], 'name' => $info['naem']];
            } else {
                $error[] = '服务器ID或者类型参数错误';
                break;
            }
        }
        if (!empty($error)) {
            return $this->json = ['status' => 400, 'error' => 100079, 'errorMsg' => $error,'susscessID'=>$susscessId,'errID'=>$errId];
        }
        return $this->json = ['status' => 200];
    }
}
