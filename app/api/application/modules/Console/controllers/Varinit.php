<?php

use \DAO\VarInfoModel;
use \DAO\DockerOperateLogModel;
use \DAO\ServerOperateLogModel;
use \DAO\OrderOperateLogModel;

/**
 * Class VarinitController
 *
 * 初始化itom环境变量 此脚本应该在系统初始化的时候执行一次，写入相关数据表中
 *
 * itom环境变量是指在itom系统中可以用一些变量名称指代一些具有含义的表示，在执行任务是进行解析
 * 例如：在任务管理中使用{all_instance_count}来表示itom系统中所有实例的数量
 *      {docker_containerId} 表示选定的容器的id值
 *      {container_hostname} 表示选定的容器的hostname的值
 */
class VarinitController extends CliBase
{
    public function init()
    {
        parent::init();
    }

    public function indexAction()
    {
        $path = "var.csv";
        if (!is_file($path)) {
            echo 'file is not exit'.PHP_EOL;
            exit;
        }
        $file = fopen($path, "r");
        $model = new VarInfoModel;
        while (! feof($file)) {
            $data = fgetcsv($file);
            if (!$data) {
                continue;
            }
            $data[0] = trim($data[0]);
            $data[1] = iconv('gb2312', 'utf-8', trim($data[1]));
            $data[2] = trim($data[2]);
            $data[3] = trim($data[3]);
            $insert_data = [
                'name'        => $data[1],
                'var_name'    => $data[0],
                'var_type'    => 'String',
                'type'        => 100,
                'var_value'   => json_encode(['model'=>'ServiceVarModel','action'=>$data[2],'params'=>explode('|', $data[3])], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'description' => $data[1],
                'create_at'   => date('Y-m-d H:i:s'),
                'create_user' => 0,
            ];
            $var = $model->findOneVar(['var_name'=>$data[0]]);
            if ($var) {
                $insert_data['id'] = $var['id'];
                $res = $model->updateOne($insert_data);
            } else {
                $res = $model->insertOne($insert_data);
            }
            if ($res['status']) {
                echo $insert_data['var_name'].'：'.$insert_data['name'].PHP_EOL;
            } else {
                echo $insert_data['var_name'].'：'.json_encode($res['error']).PHP_EOL;
            }
        }
        fclose($file);
        unset($model);
    }

    public function saveLogAction()
    {
        $d_log_model = new DockerOperateLogModel;
        $o_log_model = new OrderOperateLogModel;
        $s_log_model = new ServerOperateLogModel;
        $data = $d_log_model->findAllData();
        foreach ($data as $key => $value) {
            $insert_data = $value;
            $insert_data['step_no'] = 1;
            unset($insert_data['id']);
            $res = $o_log_model->insertOneNew($insert_data);
            if (!$res['status']) {
                echo $res['error'].PHP_EOL;
                die;
            }
        }
        $data = $s_log_model->findAllData();
        foreach ($data as $key => $value) {
            $insert_data = $value;
            $insert_data['step_no'] = 1;
            unset($insert_data['id']);
            $res = $o_log_model->insertOneNew($insert_data);
            if (!$res['status']) {
                echo $res['error'].PHP_EOL;
                die;
            }
        }

        echo 'log data is save end'.PHP_EOL;
    }

    public function initPermissionAction()
    {
        $model = new \DAO\AuthPermissionsModel;
        $data = $model->findAll();
        var_dump($data);
        die;
        foreach ($data as $key => $value) {
            $model = new \DAO\AuthPermissionsModel;
            $res = $model->updateOne($value);
            // var_dump($value);die;
        }
    }
}
