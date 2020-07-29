<?php
/**
 * Class Manager
 * Created by PhpStorm.
 * Autor: jw
 * Time:21:53
 */
use drive\log\FileLog;
use drive\log\AbstractLog;
use factory\CommonFactory;

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
                    if(!isset($this->handle[$key])){
                        $pid = CommonFactory::begin($this->process[$key],$this->table,$val,$key);
                    }else{
                        $pid = $this->handle[$key]::begin($this->process[$key],$this->table,$val);
                    }
                    $this->toLog(AbstractLog::LOG_NOTICE,"{$key} 子进程ID：".$pid);
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
        if(isset($this->conf["log"]) && $this->conf["log"]["app"] == "ON"){
            FileLog::write($this->conf["log"]["log_file"],$level,$message);
        }
    }

    public function execWait(){
        /** 利用定时器，将主进程改为非阻塞模式，方便进程间收发信号 */
        \Swoole\Timer::tick(86400000,function(){});
        $this->execSignal();
    }

    public function execSignal(){
        /**
         * 当子进程退出时触发
         * 避免出现僵尸进程
         */
        \Swoole\Process::signal(SIGCHLD, function ($sigo) {
            while ($ret = \Swoole\Process::wait(false)) {
                foreach($this->child_pid as $key => $pid){
                    if($ret["pid"] == $pid){
                        $this->toLog(drive\log\AbstractLog::LOG_NOTICE,$key . "监控的子进程ID：" . $ret["pid"] . "已经退出,状态码：" . $ret["code"]);
                    }
                }
            }
        });
        /**
         * 当主进程被kill时，发送结束信号给子进程
         */
        \Swoole\Process::signal(SIGTERM,function(){
            foreach($this->child_pid as $key => $pid){
                \Swoole\Process::kill($pid,SIGTERM);
            }
            exit;
        });
    }

}