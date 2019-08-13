<?php
/**
 * Class wsserver
 * websocket server for itom also could be used for anywhere
 * needed php ext redisn and swoole
 * 如果将此websocket server作为独立平台服务，接受各种app的webosocket信息转发，需要调整redis hash过大的问题
 */

class wsserver
{
    const REDIS_USER_HASH = 'ws_user_fd';
    private $redisHost;
    private $redisPort;
    private $redisAuth;
    private $redis;
    private $wsserverPort;

    public function __construct()
    {
        //需要redis和swoole扩展
        if ( ! in_array('redis', get_loaded_extensions())) {
            throw new \Exception("The Redis Extension Of PHP Isn't Exists", 1);
        }
        if ( ! in_array('swoole', get_loaded_extensions())) {
            throw new \Exception("The swoole Extension Of PHP Isn't Exists", 1);
        }

        $this->redisHost = getenv("REDIS_HOST") ?? null;
        $this->redisPort = getenv("REDIS_PORT") ?? null;
        $this->redisAuth = getenv("REDIS_AUTH") ?? null;
        $this->wsserverPort = getenv("WS_SERVER_PORT") ?? null;

        if ( ! $this->redisHost || ! $this->redisPort || ! $this->redisAuth) {
            throw new RedisException("redis params are error", 1);
        }

        if ( ! $this->wsserverPort) {
            throw new Exception("wsserver port isn't empty", 1);
        }
        $this->redis = new Redis();
        $connection  = $this->redis->pconnect(
            $this->redisHost,
            $this->redisPort,
            1
        );

        if ( ! $connection) {
            throw new RedisException(
                "connect redis service error,please check network or redis", 1
            );
        }

        $status = $this->redis->auth($this->redisAuth);
        if ( ! $status) {
            throw new \Exception("redis auth password error");
        }
        $this->redis->select(0);
        $this->redis->delete(self::REDIS_USER_HASH);
    }

    public function wsserver()
    {
        $ws = new swoole_websocket_server("0.0.0.0", $this->wsserverPort);
        $redis = $this->redis;

        $ws->on(
            'open',
            function ($ws, $request) {
                echo $request->fd, PHP_EOL;
            }
        );

        $ws->on(
            'message',
            function ($ws, $frame) {

                if ( ! empty($frame->data)) {

                    $msg = json_decode($frame->data, true);

                    if (isset($msg['event']) && $msg['event'] == 'login') {
                        //fd是自增的，理论上uid对应的fd不会重复
                        if (isset($msg['uid'])) {
                            $this->redis->hSet(
                                self::REDIS_USER_HASH,
                                $msg['uid'],
                                $frame->fd
                            );
                            echo 'Resevie userID:'.$msg['uid'], PHP_EOL;
                        } else {
                            $ws->push(
                                $frame->fd,
                                json_encode(['status' => 400, "error" => "未提供userID"])
                            );
                        }
                    }

                    if (isset($msg['event']) && $msg['event'] == 'sendAll') {
                        //发送信息给所有人
                        foreach ($ws->connections as $fd) {
                            echo 'the sender fd:'.$fd.PHP_EOL;
                            $ws->push($fd, json_encode($msg['data']));
                        }
                    }

                    if (isset($msg['event']) && $msg['event'] == 'sendTo') {
                        //发给指定的某人
                        if (isset($msg['uid'])) {
                            $user = $this->redis->hGet(self::REDIS_USER_HASH, $msg['uid']);
                            if ($user) {
                                $ws->push($user, json_encode($msg['data']));
                                echo 'send UserID:'.$user, PHP_EOL;
                                $ws->push($user, json_encode(['status' => 200]));
                            }
                        }
                    }
                }
            }
        );

        $ws->on(
            'close',
            function ($ws, $fd) {
                echo "client-{$fd} is closed\n";
            }
        );

        $ws->start();
    }
}

$ws = new wsserver();
$ws->wsserver();



