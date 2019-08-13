<?php

use DAO\DockerImageModel;
use DAO\ProjectDockerModel;
use DAO\ProjectServerModel;

/**
 * 删除一条项目下的某个实例记录的操作控制器
 * 会将关联此实例的所有相关数据都删除
 */

class DeldataController extends Clibase
{
    public function init()
    {
        parent::init();
    }

    public function serverAction($pid, $sid, $type)
    {
        $ProjectServerModel = new ProjectServerModel;
        $data               = $ProjectServerModel->findOneByServerId(
            $pid,
            $sid,
            $type
        );
        unset($ProjectServerModel);
        if (! $data) {
            echo 'data is not found'.PHP_EOL;
            exit;
        }
        $serverModel = ProjectServerModel::getServerModel($data['type']);
        $server      = $serverModel->findOne($data['server_id']);
        $res         = $serverModel->deleteOne($data['server_id']);
        unset($serverModel);
        if (! $res['status']) {
            echo 'delete server error'.$res['error'].' :'.$data['server_id']
                .PHP_EOL;
            exit;
        }
        echo 'delete server success:'.$data['server_id'].PHP_EOL;
        $DockerImageModel = new DockerImageModel;
        $res              = $DockerImageModel->deleteAllByServerId(
            $server['internal_ip']
        );
        unset($DockerImageModel);
        if (! $res['status']) {
            echo 'delete docker_image error'.$res['error'].PHP_EOL;
            exit;
        }
        echo 'delete docker_image success'.PHP_EOL;
        $model = new ProjectServerModel;
        $res   = $model->deleteOne($data['server_id'], $data['type']);
        unset($model);
        if (! $res['status']) {
            echo 'delete project_server error'.$res['error'].PHP_EOL;
            exit;
        }
        echo 'delete project_server success'.PHP_EOL;
        $ServerEnvModel = new DAO\ServerEnvModel;
        $res            = $ServerEnvModel->destroyEnvInsByServer(
            $data['server_id'],
            $data['type']
        );
        unset($ServerEnvModel);
        if (! $res['status']) {
            echo 'delete server_env error'.$res['error'].PHP_EOL;
            exit;
        }
        echo 'delete server_env success'.PHP_EOL;
        $ProjectDockerModel = new ProjectDockerModel;
        $res                = $ProjectDockerModel->deleteAllByServerId(
            $data['server_id'],
            $data['type']
        );
        unset($ProjectDockerModel);
        if (! $res['status']) {
            echo 'delete project_docker error'.$res['error'].PHP_EOL;
            exit;
        }
        echo 'delete project_docker success'.PHP_EOL;
    }
}
