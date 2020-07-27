<?php
/**
 * Class FileMonitor
 * Created by PhpStorm.
 * Autor: jw
 * Time:21:46
 * @package file/
 */

namespace file;


class FileMonitor
{
    //当前进程的对象
    public $process = null;
    public $conf = null;
    public $content = null;
    public $change = false;
    public $new_file = false;
    public $change_log = 0;
    // 日志文件锁
    public $log_lock = null;
    //共享内存
    public $table = null;

    public function __construct($process,$table,$conf=null)
    {
        $this->process = $process;
        if(!is_array($conf)){
            $this->process->exit(0);
        }
        if($conf["frequency"] < 1){
            $this->process->exit(0);
        }
        swoole_set_process_name("monitor_file");
        $this->conf = $conf;
        $this->content = [];
        foreach($this->conf["path"] as $value){
            $this->initScan($value);
        }
        if(isset($conf["log_file"]) && !empty($conf["log_file"])){
            $this->log_lock = new \Swoole\Lock(SWOOLE_FILELOCK,$conf["log_file"]);
        }
        $this->table = $table;
        $this->run();
    }

    public function initScan($path){
        if(is_dir($path)){
            $pathArr = scandir($path);
            if(is_array($pathArr)){
                foreach($pathArr as $value){
                    if($value !== "." && $value !== ".."){
                        if(is_dir(rtrim($path,"/") . "/" . $value)){
                            $this->initScan(rtrim($path,"/") . "/" . $value);
                        }else{
                            $this->content[][rtrim($path,"/") . "/" . $value] = md5_file(rtrim($path,"/") . "/" . $value);
                        }
                    }
                }
            }
        }else{
            $this->content[][$path] = md5_file($path);
        }
    }

    public function run(){
        \Swoole\Timer::tick(intval($this->conf["frequency"])*1000,[$this,"execute"]);
    }

    public function runScan($path,&$i){
        if($this->new_file){
            return ;
        }
        if(is_dir($path)){
            $pathArr = scandir($path);
            if(is_array($pathArr)){
                foreach($pathArr as $value){
                    if($value !== "." && $value !== ".."){
                        if(is_dir(rtrim($path,"/") . "/" . $value)){
                            $this->runScan(rtrim($path,"/") . "/" . $value,$i);
                        }else{
                            $this->compare(rtrim($path,"/") . "/" . $value,$i);
                        }
                    }
                }
            }
        }else{
            $this->compare($path,$i);
        }
    }

    public function execute(){
        $this->change = false;
        $this->new_file = false;
        $i=0;
        foreach($this->conf["path"] as $path) {
            $this->runScan($path, $i);
        }
        if($this->change){
            if($this->new_file) {
                $this->content = [];
                foreach ($this->conf["path"] as $value) {
                    $this->initScan($value);
                }
            }
            $this->change_log = time();
            $this->command();
        }
        $this->watchSelf();
    }

    /**
     * @param $file
     * @param $num
     */
    public function compare($file,&$num){
        $md5_file = md5_file($file);
        if($this->change === false){
            $res = isset($this->content[$num][$file]) ? $this->content[$num][$file] : "-1";
            if(strcmp($md5_file,$res) !== 0){
                if($res == "-1"){
                    $this->new_file = true;
                }
                $this->content[$num][$file] = $md5_file;
                $this->change = true;
            }
        }
        $num++;
    }

    public function command(){
        if(!empty($this->conf["command"])){
            $result = \Swoole\Coroutine\System::exec($this->conf["command"]);
            if($result === false){
                $this->record("file command is run fail","[ERROR]");
            }else{
                $this->record($result["output"],"[NORMAL]");
            }
        }
    }

    public function getCpuAndMemory(){
        $command = "ps aux | grep monitor_file | grep -v grep | awk '{print $2,$3,$6}'";
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
                "memory" => $result[2],
                "ret" => true
            ];
        }
    }

    public function watchSelf(){
        $system_info = $this->getCpuAndMemory();
        $system_info["last_time"] = $this->change_log;
        $this->table->set("file",[
            "content" => json_encode($system_info),
            "update_time" => time(),
        ]);
    }

    public function record($log,$level){
        if($this->log_lock === null || $this->log_lock === false) return ;
        if(!file_exists($this->conf["log_file"])){
            mkdir($this->conf["log_file"],0777,true);
        }
        $this->log_lock->lock();
        $fileHandle = fopen($this->conf["log_file"],"a");
        fwrite($fileHandle,$level . " [file] " . date("Y-m-d H:i:s") . "\n");
        fwrite($fileHandle,$log . "\n");
        fclose($fileHandle);
        $this->log_lock->unlock();
    }


}