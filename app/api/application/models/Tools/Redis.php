<?php
namespace Tools;

class RedisModel
{
    public $redis;

    public function __construct()
    {
        $env = getenv();
        $host   = !empty($env['REDIS_HOST']) ? $env['REDIS_HOST'] : null;
        $port   = isset($env['REDIS_PORT']) ? $env['REDIS_PORT'] : null;
        $auth   = isset($env['REDIS_AUTH']) ? $env['REDIS_AUTH'] : null ;

        if (!$host || !$port || !$auth) {
            throw new \Exception("Redis config error", 1);
        }

        $this->redis = new \Redis();
        $status      = $this->redis->pconnect($host, $port, 1);
        if (!$status) {
            throw new \Exception("Redis connect error", 1);
        }

        $login = $this->redis->auth($auth);
        if (!$login) {
            throw new \Exception("Redis auth error", 1);
        }

        $this->redis->select(0);
    }
}
