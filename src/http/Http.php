<?php
/**
 * Class Http
 * Created by PhpStorm.
 * Author: jw
 * Time:18:08
 * @package src\http
 */

namespace http;


class Http
{
    //当前进程的对象
    public $process = null;
    public $conf = null;
    //共享内存
    public $table = null;
    public $server = null;

    public function __construct($process,$table,$conf=null)
    {
        $this->process = $process;
        swoole_set_process_name("monitor_http_server");
        $this->conf = include $conf["conf"];
        $this->table = $table;
        $this->run();
    }

    public function run(){
        $this->server =  new \Swoole\Coroutine\Http\Server($this->conf["host"],$this->conf["port"],false,false);
        $this->server->handle("/",function($request,$response){
            $this->index($request,$response);
        });
        $this->server->start();
    }

    public function index($request,$response){
        $file = $this->table->get("file");
        $redis = $this->table->get("redis");
        $html = "file:" . var_export($file,true) . "<br>" . "redis:" . var_export($redis,true);
        $response->end($html);
    }

}