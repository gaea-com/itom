<?php

use DAO\ProjectModel;

class ProjectController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    //项目列表
    public function indexAction()
    {
        $id    =  $_GET['id'] ?? null;
        $return = ['status' => 200, 'data' => ''];
        $model  = new ProjectModel;
        if ($this->isRoot()) {
            if ($id) {
                $info = $model->findOne($id);
                if ($info) {
                    $return['data'] = [$info];
                }
                return $this->json = $return;
            }
            $list   = $model->findAll();
            $return = ['status' => 200, 'data' => $list];
        } else {
            $uid = $this->getUserId();
            $accModel = new DAO\AccreditModel();
            $projectIdArr = $accModel->getUserProject($uid);

            if (!empty($id)) {
                if (in_array($id, $projectIdArr)) {
                    $info = $model->findOne($id);
                    if ($info) {
                        $return['data'] = [$info];
                    }
                    return $this->json = $return;
                }
                return $this->json = ['status' => 400 , 'errorMsg' => '无访问权限'];
            }
            if ($projectIdArr) {
                $list   = $model->findAll($projectIdArr);
            } else {
                $list = [];
            }

            $return = ['status' => 200, 'data' => $list];
        }
        return $this->json = $return;
    }

    public function createAction()
    {
        $return = $this->insertUpdate();

        return $this->json = $return;
    }

    private function insertUpdate($id = null)
    {
        $input  = file_get_contents('php://input');
        $params = [];
        parse_str($input, $params);

        $data                       = [];
        $data[':name']              = $params['name'] ?? null;
        $data[':project_descption'] = $params['description'] ?? null;
        if (! $data[':name']) {
            return ['status' => 400, 'errorMsg' => '参数不能为空'];
        }

        if (strlen($data[':name']) > 100) {
            return [
                'status'   => 400,
                'error'    => 100037,
                'errorMsg' => '名称不能超过100个字符或20个汉字',
            ];
        }
        if ($data[':project_descption']
            && strlen($data[':project_descption']) > 200
        ) {
            return [
                'status'   => 400,
                'errorMsg' => '描述不能超过200个字符或50个汉字',
            ];
        }

        $model = new ProjectModel();
        if ($id) {
            unset($data[':name']);
            $res = $model->updateOne($id, $data);
            if ($res['status']) {
                return [
                    'status' => 200,
                    'data'   => ['status' => $res['status']],
                ];
            }

            return [
                'status'   => 400,
                'errorMsg' => $res['error'],
            ];
        } else {
            $find = $model->findOneByVarName($data[':name']);
            if (! empty($find)) {
                return [
                    'status'   => 400,
                    'error'    => 100081,
                    'errorMsg' => '此识别名称已被使用，请更换',
                ];
            }
        }
        $data[':create_at']   = date('Y-m-d H:i:s');
        $data[':create_user'] = $this->getUserId();
        $res                  = $model->InsertOne($data);
        if ($res['status']) {
            return ['status' => 200, 'data' => ['id' => $res['id']]];
        }

        return [
            'status'   => 400,
            'errorMsg' => $res['error'],
        ];
    }

    //编辑创建公共方法

    public function updateAction()
    {
        $id = $this->getRequest()->getParam('id', null);
        if (! $id || ! is_numeric($id)) {
            return ['status' => 400, 'errorMsg' => '参数不能为空'];
        }
        $return = $this->insertUpdate($id);

        return $this->json = $return;
    }

    //删除项目

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id', null);
        if (! $id || ! is_numeric($id)) {
            return $this->json = [
                'status'   => 400,
                'errorMsg' => '参数不能为空',
            ];
        }
        $model = new ProjectModel;
        $res   = $model->deleteOne($id);
        if ($res['status']) {
            return $this->json = [
                'status' => 200,
                'data'   => ['status' => $res['status']],
            ];
        }

        return $this->json = [
            'status'   => 400,
            'errorMsg' => $res['error'],
        ];
    }
}
