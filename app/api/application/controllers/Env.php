<?php
/**
 * EnvTemplate Controller
 * 环境变量相关控制器
 *
 */

use DAO\ServerImageEnvModel;

class EnvController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    //通过serverEnv表中的id获取env变量相关记录
    public function indexAction()
    {
        $model      = new ServerImageEnvModel;
        $envModel   = new DAO\ServerEnvModel();
        $projectId  = $_POST['project_id'] ?? null;
        $serverId   = $_POST['server_id'] ?? null;
        $imageNames = $_POST['image_name'] ?? null;
        if (! $serverId || ! $imageNames || !$projectId) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数错误'];
        }
        //检查imageName是否是实例绑定的编排模板的，如果不是则报错
        $projectServerModel = new DAO\ProjectServerModel();
        $serverInfo         = $projectServerModel->findOneByServerId(
            $projectId,
            $serverId,
            'gaea'
        );
        if (empty($serverInfo)) {
            return $this->json = ['status' => 400, 'errorMsg' => '此项目下没有此实例'];
        }


        $images = json_decode($imageNames, true);
        if (empty($images)) {
            return $this->json = ['status' => 400, 'errorMsg' => '镜像名称不能为空'];
        }

        $returnData = [];
        foreach ($images as $image) {
            $info = $envModel->findOneByServerImage($serverId, $image);
            if (!empty($info)) {
                $data = $model->findOne($serverId, $image);
                $data['name'] = $info['container_name'];
                $data['description'] = $info['container_describe'];
                if ($data) {
                    $returnData[$image] = $data;
                }
            }
        }

        return $this->json = ['status' => 200, 'data' => $returnData];
    }

    /**
     * 为container添加环境变量模板
     *
     * 1.先创建环境变量 server_image_env中写入镜像以及env对应关系
     * 2.在server_env中关联，创建容器名称并将env关联上
     *
     * @return array
     */
    public function createAction()
    {
        $serverId      = $_POST['server_id'] ?? null;
        $imageName     = $_POST['image_name'] ?? null;
        $containerName = $_POST['name'] ?? null;
        $containerDes  = $_POST['descr'] ?? '';
        $containerNum  = $_POST['num'] ?? 1;
        $projectId     = $_POST['project_id'] ?? null;

        if (! $serverId || ! $imageName || ! $containerName || ! $projectId) {
            return $this->json = ['status' => 400, 'errorMsg' => '参数错误'];
        }
        //检查imageName是否是实例绑定的编排模板的，如果不是则报错
        $projectServerModel = new DAO\ProjectServerModel();
        $serverInfo         = $projectServerModel->findOneByServerId(
            $projectId,
            $serverId,
            'gaea'
        );
        if (empty($serverInfo)) {
            return $this->json = ['status' => 400, 'errorMsg' => '此项目下没有此实例'];
        }

        $composeModel = new DAO\DockerComposeModel();
        $imageInfo    = $composeModel->findOne($serverInfo['compose_id']);
        if (empty($imageInfo)) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '服务器绑定的编排模板不存在',
            ];
        }

        if (strpos($imageName, ':') === false) {
            return $this->json = ['status' => 400 , 'errorMsg' => '请检查镜像格式，必须是镜像名称：标签'];
        }


        $envJson    = $_POST['env'] ?? null;
        $env        = json_decode($envJson, true);
        $volumeJson = $_POST['data'] ?? null;
        $volume     = json_decode($volumeJson, true);

        if (empty($env) && empty($volume)) {
            return $this->json = ['status' => 400, 'errorMsg' => '环境变量参数不能为空'];
        }

        $insertData = [];

        if (! empty($env) && is_array($env)) {
            foreach ($env as $k => $v) {
                if (empty($k) || ! isset($v)) {
                    $error = "请检查ENV参数KEY或者VALUE，参数不能为空";

                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => $error,
                    ];
                }
            }
            $insertData['env'] = $env;
        }

        if (! empty($volume) && is_array($volume)) {
            foreach ($volume as $k => $v) {
                if (empty($k) || ! isset($v)) {
                    $error = "请检查数据卷参数KEY或者VALUE，参数不能为空";

                    return $this->json = [
                        'status'   => 400,
                        'errorMsg' => $error,
                    ];
                }
            }
            $insertData['data'] = $volume;
        }
        //插入env环境变量
        $model  = new DAO\ServerImageEnvModel();
        $status = $model->insertOne($serverId, $imageName, $insertData);
        if ($status['status']) {
            //插入serverEnv表中
            $serverEnvModel = new DAO\ServerEnvModel();
            $data           = [
                ':image_name'         => $imageName,
                ':server_id'          => $serverId,
                ':container_name'     => $containerName,
                ':container_describe' => $containerDes,
                ':container_num'      => $containerNum,
            ];
            $res            = $serverEnvModel->insertOne($data);
            if ($res['status']) {
                return $this->json = ['status' => 200];
            }

            return $this->json = [
                'status'   => 400,
                'errorMsg' => '容器写入失败：'.json_encode(
                    $res['error']
                ),
            ];
        }

        return $this->json = ['status' => 400, 'errorMsg' => 'ENV写入失败'];
    }
}
