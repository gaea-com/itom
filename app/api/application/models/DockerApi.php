<?php
/**
 * Docker API 控制相关model
 * 将接口相关命令放入rabbitmq
 * 通信格式json
 */
use Amqp\RabbitSender;

class DockerApiModel
{
    private $user; //userID
    const ID_FORMAT = '%04X%04X%04X%04X';
    //发送给Python接口的命令ID，唯一随机数
    public static function getId()
    {
        $get_rand = static function () {
            return mt_rand(0, 65535);
        };

        return sprintf(
            self::ID_FORMAT,
            $get_rand(),
            $get_rand(),
            $get_rand(),
            $get_rand()
        );
    }

    public function __construct($uid)
    {
        $this->user = $uid;
    }

    //实例队列
    public function pushInstanceMq(array $data, $queueName = 'instance_create')
    {
        $data['cmd_id'] = self::getId();
        $msg            = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $status = $this->sendMqWorker($msg, $queueName);
        if ($status['status']) {
            return ['status' => 200, 'cmd' => $data['cmd_id']];
        }
        return $status;
    }

    //发送命令钱，需要先存入redis
    private function credentials($msg)
    {
        $RedisModel       = new Tools\RedisModel();
        $msg['user']      = $this->user;
        $msg['create_at'] = time();
        $hashKey          = 'docker_api_cmdlist';
        $status           = $RedisModel->redis->hSet($hashKey, $msg['id'], json_encode($msg));
        if ($status === false) {
            return false;
        }
        return true;
    }
    //清除凭证
    private function cleanCredentials($id)
    {
        $RedisModel  = new Tools\RedisModel();
        $hashKey     = 'docker_api_cmdlist';
        $credentials = $RedisModel->redis->hGet($hashKey, $id);
        if ($credentials) {
            $status = $RedisModel->redis->hDel($hashKey, $id);
            if ($status === false) {
                return false;
            }
        }
        return true;
    }

    private function sendMqWorker($msg, $queueName = null)
    {
        $model = new RabbitSender();
        if ($queueName) {
            $model->setQueueKey($queueName);
        }
        return $model->sender($msg);
    }
}
