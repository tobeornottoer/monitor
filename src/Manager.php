<?php
/**
 * Class Manager
 * Created by PhpStorm.
 * Autor: jw
 * Time:21:53
 */
use drive\log\FileLog;
use drive\log\AbstractLog;

class Manager
{
    private $conf = null;
    private $handle = null;
    public $child_pid = [];
    private $process = null;
    public $table = null;

    public function __construct($conf)
    {
        $this->conf = $conf;
        $handle = include dirname(__DIR__) . "/config/manager.php";
        $this->handle = $handle["handle"] ?? null;
        $this->process = $handle["process"] ?? null;
        $this->table = new \Swoole\Table(1024);
        $this->table->column('content', Swoole\Table::TYPE_STRING, 1024);
        $this->table->column('update_time', Swoole\Table::TYPE_STRING,11);
        $this->table->create();
    }

    public function start(){
        if(is_array($this->conf) && is_array($this->handle)){
            foreach($this->conf as $key => $val){
                if($key == "log" || $key == "common") continue;
                if(strtoupper($val['app']) === 'ON'){
                    if(isset($this->conf["log"]) && $this->conf["log"]["app"] == "ON"
                        && (isset($val["log_file"]) || empty($val["log_file"]))){
                        $val["log_file"] = $this->conf["log"]["log_file"];
                    }
                    if(isset($this->conf["common"]) && !empty($this->conf["common"])){
                        $val["common"] = $this->conf["common"];
                    }else{
                        $val["common"] = [];
                    }
                    $pid = $this->handle[$key]::begin($this->process[$key],$this->table,$val);
                    $this->toLog(AbstractLog::LOG_NOTICE,"子进程ID：".$pid);
                    if($pid){
                        $this->child_pid[$key] = $pid;
                    }
                }
            }
        }else{
            $this->toLog(AbstractLog::LOG_ERROR,"请检查monitor.conf以及config/manger.php文件是否已经做了相应的配置");
        }
    }

    public function toLog($level,$message){
        FileLog::write($level,$message);
    }

    /**
     * 阻塞等待所有的子进程退出
     * 避免出现僵尸进程
     */
    public function execWait(){
        $process_nums = count($this->child_pid);
        for($i=0;$i<$process_nums;$i++){
            $ret = \Swoole\Process::wait();
            foreach($this->child_pid as $key => $pid){
                if($ret["pid"] == $pid){
                    $this->toLog(drive\log\AbstractLog::LOG_NOTICE,$key . "监控的子进程ID：" . $ret["pid"] . "已经退出,状态码：" . $ret["code"]);
                }
            }
        }
    }

}