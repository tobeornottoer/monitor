<?php
/**
 * Class Mysql
 * Created by PhpStorm.
 * Author: jw
 * Time:19:59
 * @package mysql
 */

namespace mysql;


class Mysql
{
    //当前进程的对象
    public $process = null;
    public $conf = null;
    // 日志文件锁
    public $log_lock = null;
    //共享内存
    public $table = null;
    public $pdo = null;

    public function __construct($process,$table,$conf=null)
    {
        \Swoole\Runtime::enableCoroutine($flags = SWOOLE_HOOK_ALL);
        $this->process = $process;
        if(!is_array($conf)){
            $this->process->exit(0);
        }
        if($conf["frequency"] < 1){
            $this->process->exit(0);
        }
        swoole_set_process_name("monitor_mysql");
        $this->conf = $conf;
        if(isset($conf["log_file"]) && !empty($conf["log_file"])){
            $this->log_lock = new \Swoole\Lock(SWOOLE_FILELOCK,$conf["log_file"]);
        }
        $this->table = $table;
        $this->connect();
        $this->run();
    }

    public function connect(){
        try {
            $dsn = "mysql:host={$this->conf["host"]};port={$this->conf["port"]};dbname={$this->conf["database"]}";
            $this->pdo = new \PDO($dsn,$this->conf["user"],$this->conf["passwd"],array(\PDO::ATTR_PERSISTENT => true));
        }catch (\Exception $exception){
            $this->record($exception->getMessage(),"[ERROR]");
        }
    }

    public function run(){
        \Swoole\Timer::tick(intval($this->conf["frequency"])*1000,[$this,"execute"]);
    }

    public function execute(){
        $result = [];
        try {
            $handle = $this->pdo->query("show status like 'thread%'");
            if($handle){
                $data = $handle->fetchAll(\PDO::FETCH_ASSOC);
                foreach($data as $k => $val){
                    $result[$val["Variable_name"]] = $val["Value"];
                }
            }
            $handle = $this->pdo->query("show variables like '%max_connections%'");
            if($handle){
                $data = $handle->fetchAll(\PDO::FETCH_ASSOC);
                foreach($data as $k => $val){
                    $result[$val["Variable_name"]] = $val["Value"];
                }
            }
        }catch (\Exception $exception){
            $this->record($exception->getMessage(),"[ERROR]");
        }
        $content = $this->getCpuAndMemory();
        $content["content"] = $result;
        $this->table->set("mysql",[
            "content" => json_encode($content),
            "update_time" => time(),
        ]);
    }

    public function getCpuAndMemory(){
        $command = "ps aux | grep mysqld | grep -v mysqld_safe | grep -v grep | awk '{print $2,$3,$6}'";
        $result = \Swoole\Coroutine\System::exec($command);
        if($result === false){
            return [
                "pid" => "NAN",
                "cpu" => "NAN",
                "memory" => "NAN",
                "ret" => false
            ];
        }else{
            $result = explode(" ",$result["output"]);
            return [
                "pid" => $result[0],
                "cpu" => $result[1],
                "memory" => trim($result[2],"\n"),
                "ret" => true
            ];
        }
    }

    public function record($log,$level){
        if($this->log_lock === null || $this->log_lock === false) return ;
        if(!file_exists($this->conf["log_file"])){
            mkdir($this->conf["log_file"],0777,true);
        }
        $this->log_lock->lock();
        $fileHandle = fopen($this->conf["log_file"],"a");
        fwrite($fileHandle,$level . " [mysql] " . date("Y-m-d H:i:s") . "\n");
        fwrite($fileHandle,$log . "\n");
        fclose($fileHandle);
        $this->log_lock->unlock();
    }

}