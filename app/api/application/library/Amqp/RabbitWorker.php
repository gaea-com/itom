<?php

namespace Amqp;

class RabbitWorker
{
    private $queue = 'common';
    private $cnn
                   = [
            'host'          => 'localhost',
            'login'         => 'admin',
            'password'      => 'admin',
            'route'         => 'common',
            'port'          => 5672,
            'exchange_name' => 'itom_task',
        ];

    public function __construct()
    {
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
        echo '-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-', PHP_EOL;
        echo '请求执行开始时间：'.date('Y-m-d H:i:s'), PHP_EOL;
        echo '-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-', PHP_EOL;
    }

    public function setConfig($host, $account, $password, $port, $route)
    {
        if ($host) {
            $this->cnn['host'] = $host;
        }
        if ($account) {
            $this->cnn['login'] = $account;
        }
        if ($password) {
            $this->cnn['password'] = $password;
        }
        if ($route) {
            $this->cnn['route'] = $route;
        }
        if ($port) {
            $this->cnn['port'] = $port;
        }
    }

    public function __destruct()
    {
        echo '-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-', PHP_EOL;
        echo '执行结束时间：'.date('Y-m-d H:i:s'), PHP_EOL;
        echo '-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-', PHP_EOL;
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

    public function woker($callback_func = null)
    {
        //Establish connection AMQP
        try {
            $connection = new \AMQPConnection();
            $connection->setHost($this->cnn['host']);
            $connection->setLogin($this->cnn['login']);
            $connection->setPassword($this->cnn['password']);
            $connection->setPort($this->cnn['port']);
            $connection->setVhost('/');
            $connection->connect();
            //var_dump($this->queue, $this->cnn);
            //Create and declare channel
            $channel  = new \AMQPChannel($connection);
            $exchange = new \AMQPExchange($channel);
            $exchange->setName($this->cnn['exchange_name']);
            $exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
            $exchange->setFlags(AMQP_DURABLE); //持久化
            if ( ! $callback_func) {
                $callback_func = function (
                    \AMQPEnvelope $message,
                    \AMQPQueue $q
                ) use (&$max_jobs) {
                    echo 1, PHP_EOL;
                    echo " [x] Received: ", $message->getBody(), PHP_EOL;
                    sleep(sleep(substr_count($message->getBody(), '.')));
                    echo " [X] Done", PHP_EOL;
                    $q->ack($message->getDeliveryTag());
                };
            }
            $return = ['status' => false, 'error' => ''];

            $queue = new \AMQPQueue($channel);
            $queue->setName($this->queue);
            $queue->setFlags(AMQP_DURABLE);
            $queue->declareQueue();
            $queue->bind($this->cnn['exchange_name'], $this->cnn['route']);
            echo ' [*] Waiting for logs. To exit press CTRL+C', PHP_EOL;
            $queue->consume($callback_func);
            $return['status'] = true;
        } catch (\AMQPQueueException $ex) {
            // print_r($ex);
            $return['error'] = $ex->getMessage();
        } catch (\Exception $ex) {
            // print_r($ex);
            $return['error'] = $ex->getMessage();
        }
        $connection->disconnect();

        return $return;
    }

    public function wokerInstance($callback_func = null)
    {
        //Establish connection AMQP
        $connection = new \AMQPConnection();
        $connection->setHost($this->cnn['host']);
        $connection->setLogin($this->cnn['login']);
        $connection->setPassword($this->cnn['password']);
        $connection->setPort($this->cnn['port']);
        $connection->setVhost('/');
        $connection->connect();
        //Create and declare channel
        $channel = new \AMQPChannel($connection);
        //$exchange = new \AMQPExchange($channel);
        //$exchange->setName($this->cnn['exchange_name']);
        //$exchange->setType(AMQP_EX_TYPE_DIRECT); //direct类型
        //$exchange->setFlags(AMQP_DURABLE); //持久化
        if ( ! $callback_func) {
            $callback_func = function (
                \AMQPEnvelope $message,
                \AMQPQueue $q
            ) use (&$max_jobs) {
                echo 1, PHP_EOL;
                echo " [x] Received: ", $message->getBody(), PHP_EOL;
                sleep(sleep(substr_count($message->getBody(), '.')));
                echo " [X] Done", PHP_EOL;
                $q->ack($message->getDeliveryTag());
            };
        }
        $return = ['status' => false, 'error' => ''];
        try {
            $queue = new \AMQPQueue($channel);
            $queue->setName($this->queue);
            $queue->setFlags(AMQP_DURABLE);
            //$queue->bind($this->cnn['exchange_name'], $this->cnn['route']);
            $queue->declareQueue();
            echo ' [*] Waiting for logs. To exit press CTRL+C', PHP_EOL;
            $queue->consume($callback_func);
            $return['status'] = true;
        } catch (\AMQPQueueException $ex) {
            // print_r($ex);
            $return['error'] = $ex->getMessage();
        } catch (\Exception $ex) {
            // print_r($ex);
            $return['error'] = $ex->getMessage();
        }
        $connection->disconnect();

        return $return;
    }
}
