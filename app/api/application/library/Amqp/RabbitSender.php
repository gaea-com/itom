<?php

namespace Amqp;

class RabbitSender
{
    private $queue = 'Request';
    private $cnn
                   = [
            'host'          => 'localhost',
            'port'          => 5672,
            'login'         => 'admin',
            'password'      => 'admin',
            'route'         => 'common',
            'exchange_name' => 'itom_task',
                   ];

    public function __construct(){
        $env   = getenv();
        $host  = ! empty($env['MQ_HOST']) ? $env['MQ_HOST'] : null;
        $port  = ! empty($env['MQ_PORT']) ? $env['MQ_PORT'] : null;
        $login = ! empty($env['MQ_USER']) ? $env['MQ_USER'] : null;
        $pass  = ! empty($env['MQ_PASS']) ? $env['MQ_PASS'] : null;
        $route = 'common';
        if ( ! $host || ! $port || ! $login || ! $pass) {
            throw new \Exception('Rabbit MQ config is error!', 2);
        }
        $this->setConfig($host, $login, $pass, $port, $route);
    }

    public function getQueueKey()
    {
        return $this->queue;
    }

    public function setQueueKey(string $key)
    {
        $this->queue = $key;
    }

    public function getConfig()
    {
        return $this->cnn;
    }

    public function setConfig($host, $account, $password, $port, $route)
    {
        $this->cnn['host']     = $host;
        $this->cnn['login']    = $account;
        $this->cnn['password'] = $password;
        $this->cnn['port']     = $port;
        $this->cnn['route']    = $route;
    }

    public function sender($message)
    {
        try {
            //Establish connection to AMQP
            $connection = new \AMQPConnection();
            $connection->setHost($this->cnn['host']);
            $connection->setLogin($this->cnn['login']);
            $connection->setPassword($this->cnn['password']);
            $connection->setPort($this->cnn['port']);
            $connection->setVhost('/');
            $res = $connection->connect();
            //Create and declare channel
            $channel = new \AMQPChannel($connection);
            //AMQPC Exchange is the publishing mechanism
            $exchange = new \AMQPExchange($channel);
            //$exchange->setName($this->cnn['exchange_name']);
            //$exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
            //$exchange->setFlags(AMQP_DURABLE); //持久化
            $queue = new \AMQPQueue($channel);
            //$queue->bind($this->cnn['exchange_name'], $this->cnn['route']);
            $queue->setName($this->queue);
            $queue->setFlags(AMQP_DURABLE);
            $queue->declareQueue();
            $res = $exchange->publish($message, $this->queue);
            $connection->disconnect();
            if ($res) {
                return ['status' => true];
            } else {
                return ['status' => false, 'error' => '发送请求失败'];
            }
        } catch (\Exception $ex) {
            return ['status' => false, 'error' => $ex->getMessage()];
        }
    }
}
