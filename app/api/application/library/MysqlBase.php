<?php

class MysqlBase
{
    protected $db;

    public function __construct()
    {
        //链接数据库
        $env    = getenv();
        $host   = ! empty($env['DB_HOST']) ? $env['DB_HOST'] : null;
        $db     = ! empty($env['DB_NAME']) ? $env['DB_NAME'] : null;
        $user   = ! empty($env['DB_USER']) ? $env['DB_USER'] : null;
        $passwd = ! empty($env['DB_PASSWD']) ? $env['DB_PASSWD'] : null;
        $dsn    = 'mysql:dbname='.$db.';host='.$host;
        try {
            $this->db = new \PDO($dsn, $user, $passwd);
        } catch (PDOException $e) {
            echo 'Connection failed: '.$e->getMessage();
        }
    }

    public function __destruct()
    {
        $this->db = null;
    }
}