<?php

use DAO\OrderInfoModel;
use DAO\ProjectModel;
use DAO\UserModel;
use DAO\VarQuoteModel;

/**
 * OrderInfo Controller
 * 命令相关控制器
 *
 * @author  hu.zhou <hu.zhou@gaeamobile.com>
 * @version 1.0
 */

class OrderinfoController extends BaseController
{
    //type  100容器 200实例
    public function init()
    {
        parent::init();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    // 命令列表
    public function indexAction()
    {
        $return    = ['status' => 200, 'error' => ''];
        $user_id   = $this->getUserId();
        $projectId = $_GET['pid'] ?? null;
        $id        = $_GET['id'] ?? null;
        $type      = $_GET['type'] ?? null;
        $model     = new OrderInfoModel;

        if ($id) {
            $info = $model->findOne(['id' => $id]);
            $data[] = $info;
        } else {
            if (!$type) {
                $data      = empty($projectId)
                    ? $model->findAll()
                    : $model->findAll(
                        ['project_id' => $projectId]
                    );
            } else {
                $data = $model->findAll(['project_id' => $projectId, 'type' => $type]);
            }
        }
        $newData = [];
        if (! empty($data)) {
            foreach ($data as $key => $value) {
                //分享的设定是
                // 1.update_status 字段 200分享（可以看到可以执行） 100不分享（不可见不可执行）
                // 2.只能修改自己的命令，其他人都不能修改包括
                // 3.不分享的命令，admin看不到，只能看到分享的和自己创建的，但root账户可以看所有的，也可执行
                //admin身份
                if (!$this->rootCheck()) {
                    //非创建者
                    if ($value['create_user'] <> $user_id && $value['update_status'] == 100) {
                        continue;
                    }
                }
                $proModel = new ProjectModel;
                $proData = $proModel->findOne($value['project_id']);
                $value['project_name'] = empty($proData) ? '---' : $proData['name'];
                $value['userpermission'] = true;
                $userModel = new UserModel();
                $user      = $userModel->findOne($value['create_user']);
                if ($user) {
                    $value['user_name'] = $user['name'];
                }
                $newData[] = $value;
            }
        }
        $return['data'] = $newData;
        return $this->json = $return;
    }

    // 添加命令

    public function rootCheck()
    {
        return $this->isRoot();
    }

    public function createOrderInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $name        = $_POST['name'] ?? null;
        $type        = $_POST['type'] ?? null;
        $order       = $_POST['order'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id  = $_POST['pid'] ?? null;
        $share       = $_POST['share'] ?? 100;
        $create_at   = date('Y-m-d H:i:s');
        $create_user = $this->getUserId();

        if (empty($name) || empty($type) || empty($order) || empty($description)
            || empty($project_id)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }
        if (! in_array($type, [100, 200, 300])) {
            $return['error'] = 'type错误';

            return $this->json = $return;
        }

        if ($share == 1) {
            $updateStatus = 200;
        } else {
            $updateStatus = 100;
        }


        $paramsArr = [
            'name'          => $name,
            'type'          => $type,
            'order'         => $order,
            'description'   => $description,
            'project_id'    => $project_id,
            'create_at'     => $create_at,
            'create_user'   => $create_user,
            'update_status' => $updateStatus,
            'run_status'    => 200,
        ];

        $model = new OrderInfoModel;
        $data  = $model->insertOne($paramsArr);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }

        VarQuoteModel::orderQuote($data['id']);

        $return['status'] = 200;
        $return['data']   = ['id' => $data['id']];

        return $this->json = $return;
    }

    public function getOrderInfoByIdAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $id     = $_POST['id'] ?? null;
        if (empty($id)) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $model = new OrderInfoModel;
        $data  = $model->findOne(['id' => $id]);
        if (! empty($data)) {
            $proModel             = new ProjectModel;
            $proData              = $proModel->findOne(
                $data['project_id']
            );
            $data['project_name'] = empty($proData) ? '---' : $proData['name'];
        }

        $return['status'] = 200;
        $return['data']   = $data;

        return $this->json = $return;
    }

    // 修改是否可执行和是否可编辑状态

    public function updateOrderInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $id          = $_POST['id'] ?? null;
        $name        = $_POST['name'] ?? null;
        $type        = $_POST['type'] ?? null;
        $order       = $_POST['order'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id  = $_POST['pid'] ?? null;
        $share       = $_POST['share'] ?? 0;
        $user_id     = $this->getUserId();

        if (empty($id) || empty($name) || empty($type) || empty($order)
            || empty($description)
            || empty($project_id)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $model     = new OrderInfoModel;
        $orderData = $model->findOne(['id' => $id]);
        if (empty($orderData)) {
            $return['error'] = '未发现此ID的命令';

            return $this->json = $return;
        }
        if ($user_id != $orderData['create_user']) {
            $return['error'] = '您目前没有权限执行此操作';
            return $this->json = $return;
        }
        if ($share == 1) {
            $updateStatus = 200;
        } else {
            $updateStatus = 100;
        }

        $paramsArr = [
            'id'          => $id,
            'name'        => $name,
            'type'        => $type,
            'order'       => $order,
            'description' => $description,
            'update_status' => $updateStatus,
            'project_id'  => $project_id,
        ];

        $data = $model->updateOne($paramsArr);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }
        $return['status'] = 200;

        VarQuoteModel::orderQuote($data['id']);

        $return['data'] = ['id' => $data['id']];

        return $this->json = $return;
    }
    // 命令预览

    public function delOrderInfoAction()
    {
        $return = ['status' => 400, 'error' => ''];
        $id     = $_POST['id'] ?? null;
        if (empty($id)) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $user_id   = $this->getUserId();
        $model     = new OrderInfoModel;
        $orderData = $model->findOne(['id' => $id]);
        if (empty($orderData)) {
            $return['error'] = '未发现此ID的命令';

            return $this->json = $return;
        }

        if ($user_id != $orderData['create_user']
            && empty($this->rootCheck())
        ) {
            $return['error'] = '您目前没有权限执行此操作';

            return $this->json = $return;
        }

        $model = new OrderInfoModel;
        $data  = $model->deleteOne($id);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }
        $return['status'] = 200;

        VarQuoteModel::orderDelQuote($data['id']);

        $return['data'] = ['id' => $data['id']];

        return $this->json = $return;
    }
}
