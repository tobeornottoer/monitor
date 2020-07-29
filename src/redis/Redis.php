<?php
/**
 * Class Redis
 * Created by PhpStorm.
 * Author: jw
 * Time:22:12
 * @package redis
 */

namespace redis;


class Redis
{

    //当前进程的对象
    public $process = null;
    public $conf = null;
    // 日志文件锁
    public $log_lock = null;
    //共享内存
    public $table = null;
    public $redis = null;

    public function __construct($process,$table,$conf=null)
    {
        $this->process = $process;
        if(!is_array($conf)){
            $this->process->exit(0);
        }
        if($conf["frequency"] < 1){
            $this->process->exit(0);
        }
        swoole_set_process_name("monitor_redis");
        $this->conf = $conf;
        if(isset($conf["log_file"]) && !empty($conf["log_file"])){
            $this->log_lock = new \Swoole\Lock(SWOOLE_FILELOCK,$conf["log_file"]);
        }
        $this->table = $table;
        $this->redis = new \Redis();
        $this->connect();
        $this->run();
    }

    public function connect(){
        try {
            $this->redis->pconnect($this->conf["host"],$this->conf["port"]);
            if(isset($this->conf["password"]) && $this->conf["password"]){
                $this->redis->auth($this->conf["password"]);
            }
            return true;
        }catch (\Exception $exception){
            $this->record($exception->getMessage(),"[ERROR]");
            return false;
        }
    }

    public function run(){
        \Swoole\Timer::tick(intval($this->conf["frequency"])*1000,[$this,"execute"]);
    }

    public function execute(){
        /**
         * 是否存活
         */
        try {
            $alive = $this->redis->ping();
            if($alive === false){
                $this->connect();
                $alive = true;
            }
        }catch (\Exception $exception){
            $this->record($exception->getMessage(),"[ERROR]");
            $alive = false;
        }
        /**
         * redis客户端连接数
         * alive = true 时才执行
         * redis拒绝连接个数 refuse
         * redis 新建连接数 new_link 过多，说明过度地创建和销毁连接   短连接严重，或者连接池有问题
         * redis 进程使用内存大小
         */
        $info = [];
        if($alive){
            $redis_info = $this->redis->info();
            $info["clients"]  = $redis_info["connected_clients"]??0;
            $info["refuse"] = $redis_info["rejected_connections"]??0;
            $info["new_link"] = $redis_info["total_connections_received"]??"NAN";
            $info["memory"] = $redis_info["used_memory_rss_human"]??"NAN";
        }
        $info["alive"] = $alive;
        $content = $this->getCpuAndMemory();
        $content["content"] = $info;
        $this->table->set("redis",[
            "content" => json_encode($content),
            "update_time" => time(),
        ]);
    }

    public function getCpuAndMemory(){
        $command = "ps aux | grep monitor_redis | grep -v grep | awk '{print $2,$3,$6}'";
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
        fwrite($fileHandle,$level . " [redis] " . date("Y-m-d H:i:s") . "\n");
        fwrite($fileHandle,$log . "\n");
        fclose($fileHandle);
        $this->log_lock->unlock();

    }

}