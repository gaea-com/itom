<?php

use \DAO\VarInfoModel;
use \DAO\OrderOperateLogModel;
use \DAO\BccServerModel;
use \DAO\AliServerModel;
use \DAO\TtServerModel;
use \DAO\ProjectModel;

/**
 * Class TaskController
 * 定时任务脚本控制器
 */
class TaskController extends Yaf\Controller_Abstract
{
    public function init()
    {
        $method = $this->getRequest()
            ->getMethod();
        if ($method != 'CLI') {
            throw new \Exception("非法请求", 10000);
        }
        Yaf\Dispatcher::getInstance()->disableView();
    }

    public function indexAction()
    {
        $RedisModel = new \Tools\RedisModel();
        $data       = $RedisModel->redis->hgetall('timed_task_redis_key');
        if (!$data) {
            echo date('Y-m-d H:i:s').' no any time task' . PHP_EOL;
            exit;
        }
        foreach ($data as $key => $value) {
            $value = json_decode($value, true);
            if ($value['type'] == 100) {
                $date = date('Y-m-d H:i', $value['run_condition']/1000);
                $now  = date('Y-m-d H:i', time());
                $res  = ($now == $date);
                var_dump($date, $now, $res);
            } else {
                $res = self::checkAction(time(), $value['run_condition']);
            }
            if ($res) {
                $TaskInfoModel = new \DAO\TaskInfoModel;
                $info          = $TaskInfoModel->findOneById($value['task_id']);
                if (!$info['status']) {
                    echo $info['error'] . PHP_EOL;
                    continue;
                }
                $task_id = \DockerApiModel::getId();
                $data    = ['uid'        => $value['create_user'],
                            'redisKey'   => $task_id,
                            'project_id' => $value['project_id']];
                $status  = $this->getResAsync('task', $data, $info, $task_id);
                if ($status['status'] == 400) {
                    echo 'push task error, errorMsg: ' . $status['errorMsg'] . PHP_EOL;
                    continue;
                }
                echo 'push task success date:' . date('Y-m-d H:i') . PHP_EOL;
            }
        }
    }

    //格式化任务数据
    private function formateTaskData($data, $project_id)
    {
        $body   = [];
        $return = ['status' => false,
                   'error'  => ''];
        foreach ($data as $key => $value) {
            $step_no = $key + 1;
            if ($value['type'] == 'script') {
                $body[$key]['type'] = 'script';
                $cmd                = $value['order'];
                foreach ($value['object'] as $v) {
                    $red = ParseCommandModel::parseStr(
                        $value['order'],
                        ['internal_ip'   => $v['ip'],
                                                                         'instance_name' => $v['name'],
                                                                         'public_ip'     => $v['public_ip'],
                        'project_id'    => $project_id]
                    );
                    if ($red['status']) {
                        $cmd = $red['data'];
                    } elseif ($red['code'] == 400) {
                        $return['error'] = $red['error'];
                        return $return;
                    }
                    $body[$key]['data'][] = ['cmd'  => $cmd,
                                             'ip'   => $v['ip'],
                                             'type' => $v['type']];
                }
            } elseif ($value['type'] == 'docker_exec') {
                $body[$key]['type'] = 'docker_exec';
                $cmd                = $value['order'];
                foreach ($value['object'] as $v) {
                    $red = ParseCommandModel::parseStr(
                        $value['order'],
                        ['docker_id'      => $v['id'],
                                                                         'container_id'   => $v['container_id'],
                                                                         'container_name' => $v['name'],
                                                                         'type'           => $v['type'],
                        'project_id'     => $project_id]
                    );
                    if ($red['status']) {
                        $cmd = $red['data'];
                    } elseif ($red['code'] == 400) {
                        $return['error'] = $red['error'];
                        return $return;
                    }
                    $body[$key]['data'][] = ['cmd'          => $cmd,
                                             'ip'           => $v['ip'],
                                             'container_id' => $v['container_id'],
                                             'type'         => $v['type']];
                }
            } elseif ($value['type'] == 'local') {
                $body[$key]['type'] = 'script';
                $cmd                = $value['order'];
                foreach ($value['object'] as $v) {
                    $red = ParseCommandModel::parseStr($value['order'], ['project_id' => $project_id]);
                    if ($red['status']) {
                        $cmd                  = $red['data'];
                        $body[$key]['data'][] = ['cmd'  => $cmd,
                                                 'ip'   => $v['ip'],
                                                 'type' => $v['type']];
                    } elseif ($red['code'] == 400) {
                        $return['error'] = $red['error'];
                        return $return;
                    }
                }
            }
        }

        $return['status'] = true;
        $return['data']   = $body;
        return $return;
    }

    /**
     * 检查某时间($time)是否符合某个corntab时间计划($str_cron)
     *
     * @param int    $time     时间戳
     * @param string $str_cron corntab的时间计划，如，"30 2 * * 1-5"
     *
     * @return bool/string 出错返回string（错误信息）
     */
    public static function checkAction($time, $str_cron)
    {
        // $time = strtotime('2017-08-07 20:21:30');
        // $str_cron = "21 20 * * *";
        $format_time = self::format_timestamp($time);
        $format_cron = self::format_crontab($str_cron);
        if (!is_array($format_cron)) {
            return $format_cron;
        }
        return self::format_check($format_time, $format_cron);
    }

    /**
     * 使用格式化的数据检查某时间($format_time)是否符合某个corntab时间计划($format_cron)
     *
     * @param array $format_time self::format_timestamp()格式化时间戳得到
     * @param array $format_cron self::format_crontab()格式化的时间计划
     *
     * @return bool
     */
    public static function format_check(array $format_time, array $format_cron)
    {
        return (!$format_cron[0] || in_array($format_time[0], $format_cron[0]))
            && (!$format_cron[1] || in_array($format_time[1], $format_cron[1]))
            && (!$format_cron[2] || in_array($format_time[2], $format_cron[2]))
            && (!$format_cron[3] || in_array($format_time[3], $format_cron[3]))
            && (!$format_cron[4] || in_array($format_time[4], $format_cron[4]));
    }

    /**
     * 格式化时间戳，以便比较
     *
     * @param int $time 时间戳
     *
     * @return array
     */
    public static function format_timestamp($time)
    {
        return explode('-', date('i-G-j-n-w', $time));
    }


    public static function format_crontab($str_cron)
    {
        //格式检查
        $str_cron = trim($str_cron);
        $reg      = '#^((\*(/\d+)?|((\d+(-\d+)?)(?3)?)(,(?4))*))( (?2)){4}$#';
        if (!preg_match($reg, $str_cron)) {
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
            return $e->getMessage();
        }

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
            list(
                $min,
                $max
                )
                = explode('-', $part);
            if ($min > $max) {
                throw new Exception('使用"-"设置范围时，左不能大于右');
            }
        } elseif ('*' == $part) {
            $min = $f_min;
            $max = $f_max;
        } else {//数字
            $min
                =
            $max = $part;
        }

        //空数组表示可以任意值
        if ($min == $f_min && $max == $f_max && $step == 1) {
            return $list;
        }

        //越界判断
        if ($min < $f_min || $max > $f_max) {
            throw new Exception('数值越界。应该：分0-59，时0-59，日1-31，月1-12，周0-6');
        }

        return $max - $min > $step ? range($min, $max, $step) : array((int)$min);
    }

    //将docker api的请求信息需要先存入redis
    private function credentials($id, $msg)
    {
        $RedisModel = new Tools\RedisModel();
        $hashKey    = 'docker_api_reqeust';
        $status     = $RedisModel->redis->hSet($hashKey, $id, json_encode($msg));
        if ($status === false) {
            return false;
        }
        return true;
    }

    //docker请求
    public function getResAsync($action, $data, $redis_data, $task_id)
    {
        $res     = $this->credentials($task_id, $redis_data);
        $mqModel = new DockerApiModel($data['uid']);
        $status  = $mqModel->pushInstanceMq($data, $action);
        if ($status['status'] != 200) {
            $status['errorMsg'] = '程序异常：' . $status['error'];
            $status['status']   = 400;
        }
        return $status;
    }
}
