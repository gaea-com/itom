<?php

use DAO\TaskInfoModel;
use DAO\TimedTaskModel;

/**
 * TimedTask Controller
 * 定时任务相关控制器
 *
 */

class TimedtaskController extends BaseController
{
    public function init()
    {
        parent::init();
    }

    //100是单次  200是多次
    public function __destruct()
    {
        parent::__destruct();
    }

    // 自定义组列表
    public function indexAction()
    {
        $return    = ['status' => 200, 'error' => ''];
        $user_id   = $this->getUserId();
        $projectId = $_GET['pid'] ?? null;
        $id        = $_GET['id'] ?? null;
        $model     = new TimedTaskModel;
        $taskModel = new TaskInfoModel;

        if ($projectId) {
            $data = $model->findAll(['project_id' => $projectId]);
        } else {
            if ($id) {
                $data = $model->findAll(['id' => $id]);
            } else {
                $data = $model->findAll();
            }
        }

        if (! empty($data)) {
            foreach ($data as $key => $value) {
                $taskData                = $taskModel->findOne(
                    ['id' => $value['task_id']]
                );
                $data[$key]['task_name'] = empty($taskData) ? '---'
                    : $taskData['name'];
            }
        }

        $return['data'] = $data;

        return $this->json = $return;
    }

    // 添加定时任务
    public function createAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $name        = $_POST['name'] ?? null;
        $description = $_POST['description'] ?? null;
        $project_id  = $_POST['pid'] ?? null;
        $task_id     = $_POST['task_id'] ?? null;
        $type        = $_POST['type'] ?? null;
        $condition   = $_POST['condition'] ?? null;
        $create_at   = date('Y-m-d H:i:s');
        $create_user = $this->getUserId();

        if (empty($name) || empty($description) || empty($project_id)
            || empty($task_id)
            || empty($type)
            || empty($condition)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $taskModel = new TaskInfoModel;
        $taskData  = $taskModel->findOne(['id' => $task_id]);
        if (empty($taskData)) {
            $return['error'] = '未发现此ID的任务';

            return $this->json = $return;
        }

        if (! in_array($type, [100, 200])) {
            $return['error'] = 'type类型错误';

            return $this->json = $return;
        }

        $paramsArr = [
            'name'          => $name,
            'description'   => $description,
            'project_id'    => $project_id,
            'task_id'       => $task_id,
            'type'          => $type,
            'run_condition' => $condition,
            'create_at'     => $create_at,
            'create_user'   => $create_user,
        ];

        $model = new TimedTaskModel;
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
        $task_id     = $_POST['task_id'] ?? null;
        $type        = $_POST['type'] ?? null;
        $condition   = $_POST['condition'] ?? null;

        if (empty($id) || empty($name) || empty($description)
            || empty($project_id)
            || empty($task_id)
            || empty($type)
            || empty($condition)
        ) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $taskModel = new TaskInfoModel;
        $taskData  = $taskModel->findOne(['id' => $task_id]);
        if (empty($taskData)) {
            $return['error'] = '未发现选择的任务';

            return $this->json = $return;
        }

        if (! in_array($type, [100, 200])) {
            $return['error'] = 'type类型错误';

            return $this->json = $return;
        }

        $paramsArr = [
            'id'            => $id,
            'name'          => $name,
            'description'   => $description,
            'project_id'    => $project_id,
            'task_id'       => $task_id,
            'type'          => $type,
            'run_condition' => $condition,
        ];

        $model         = new TimedTaskModel;
        $timedTaskData = $model->findOne(['id' => $id]);
        if (empty($timedTaskData)) {
            $return['error'] = '未发现要编辑的定时任务';

            return $this->json = $return;
        }

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

        $model      = new TimedTaskModel;
        $customData = $model->findOne(['id' => $id]);
        if (empty($customData)) {
            $return['error'] = '未发现此ID的自定义组';

            return $this->json = $return;
        }

        $model = new TimedTaskModel;
        $data  = $model->deleteOne($id);
        if (! $data['status']) {
            $return['error'] = $data['error'];

            return $this->json = $return;
        }

        $return['status'] = 200;
        $return['data']   = ['id' => $data['id']];

        return $this->json = $return;
    }

    public function crontabCheckAction()
    {
        $return = ['status' => 400, 'error' => ''];

        $crontab = $_POST['crontab'] ?? null;
        if (empty($crontab)) {
            $return['error'] = '参数不能为空';

            return $this->json = $return;
        }

        $return['data']   = self::format_crontab($crontab);
        $return['status'] = 200;

        return $this->json = $return;
    }

    public static function format_crontab($str_cron)
    {
        //格式检查
        $str_cron = trim($str_cron);
        $reg      = '#^((\*(/\d+)?|((\d+(-\d+)?)(?3)?)(,(?4))*))( (?2)){4}$#';
        if (! preg_match($reg, $str_cron)) {
            return false;

            return '格式错误';
        }

        try {
            //分别解析分、时、日、月、周
            $arr_cron    = array();
            $parts       = explode(' ', $str_cron);
            $arr_cron[0] = self::parse_cron_part($parts[0], 0, 59);//分
            $arr_cron[1] = self::parse_cron_part($parts[1], 0, 59);//时
            $arr_cron[2] = self::parse_cron_part($parts[2], 1, 31);//日
            $arr_cron[3] = self::parse_cron_part($parts[3], 1, 12);//月
            $arr_cron[4] = self::parse_cron_part($parts[4], 0, 6);//周（0周日）
        } catch (Exception $e) {
            return false;

            return $e->getMessage();
        }

        return true;

        return $arr_cron;
    }

    protected static function parse_cron_part($part, $f_min, $f_max)
    {
        $list = array();

        //处理"," -- 列表
        if (false !== strpos($part, ',')) {
            $arr = explode(',', $part);
            foreach ($arr as $v) {
                $tmp  = self::parse_cron_part($v, $f_min, $f_max);
                $list = array_merge($list, $tmp);
            }

            return $list;
        }

        //处理"/" -- 间隔
        $tmp  = explode('/', $part);
        $part = $tmp[0];
        $step = isset($tmp[1]) ? $tmp[1] : 1;

        //处理"-" -- 范围
        if (false !== strpos($part, '-')) {
            list($min, $max) = explode('-', $part);
            if ($min > $max) {
                throw new Exception('使用"-"设置范围时，左不能大于右');
            }
        } elseif ('*' == $part) {
            $min = $f_min;
            $max = $f_max;
        } else {//数字
            $min = $max = $part;
        }

        //空数组表示可以任意值
        if ($min == $f_min && $max == $f_max && $step == 1) {
            return $list;
        }

        //越界判断
        if ($min < $f_min || $max > $f_max) {
            throw new Exception('数值越界。应该：分0-59，时0-59，日1-31，月1-12，周0-6');
        }

        return $max - $min > $step ? range($min, $max, $step)
            : array((int)$min);
    }
}
