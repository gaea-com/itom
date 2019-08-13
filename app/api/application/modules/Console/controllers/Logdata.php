<?php

use Yaf\Application;

/**
 * 日志导出防积压脚本控制器
 */

class LogdataController extends CliBase
{
    private $db;

    public function init()
    {
        parent::init();
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

    //备份一个月前所有
    public function indexAction()
    {
        $date = date('Y-m-d', strtotime('-30 days'));
        $sql
              = 'SELECT SUBSTR(`create_at`,1,10) AS ds FROM `order_operate_log` where `create_at`<"'
            .$date.'" GROUP BY ds';
        $sth  = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute();
        $dateArr = $sth->fetchAll(PDO::FETCH_ASSOC);
        if (! $dateArr) {
            echo 'no data export'.PHP_EOL;
            exit;
        }
        $path = '/data/order_operate_log';
        if (! is_dir($path)) {
            mkdir($path);
        }
        foreach ($dateArr as $value) {
            $this->export($path, $value['ds']);
        }
    }

    private function export($path, $start_date)
    {
        $file_path = $path.'/'.$start_date;
        if (! is_dir($file_path)) {
            mkdir($file_path);
        }
        $end_date = date('Y-m-d', strtotime($start_date.'+1 day'));
        $sql      = 'SELECT * FROM `order_operate_log` where `create_at`>="'
            .$start_date.'" AND `create_at`<"'.$end_date.'"';
        $sth      = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        $txt  = '';
        if (! $data) {
            echo 'no data export'.PHP_EOL;
            exit;
        }
        foreach ($data as $log) {
            $txt .= '101`gaea.itom`';
            unset($log['id']);
            foreach ($log as $k => $v) {
                $txt .= str_replace('`', '', $v).'`';
            }
            $txt .= PHP_EOL;
        }
        $red = file_put_contents($file_path.'/operate.log', $txt);
        if (! $red) {
            echo $start_date.' write log faild'.PHP_EOL;
        }
        $sql = 'DELETE FROM `order_operate_log` where `create_at`>="'
            .$start_date.'" AND `create_at`<"'.$end_date.'"';
        $sth = $this->db->prepare(
            $sql,
            [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]
        );
        $red = $sth->execute();
        if (! $red) {
            $error = $sth->errorInfo();
            echo $start_date.' delete faild error:'.$error[2].PHP_EOL;
        }
        echo $start_date.' SUCESS'.PHP_EOL;
    }

    //备份一个月前一天
    public function exportAction()
    {
        $start_date = date('Y-m-d', strtotime('-31 days'));
        $path       = '/data/order_operate_log';
        if (! is_dir($path)) {
            mkdir($path);
        }
        $this->export($path, $start_date);
    }
}
