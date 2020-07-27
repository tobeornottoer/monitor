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
        $result = $this->table->get("file");
        $content = json_decode($result["content"],true);
        $str = "<tr><td width='80'>file</td><td width='100'>{$content["pid"]}</td><td width='280'>CPU占用率：{$content["cpu"]}%,MEMORY占用：" . round((int)$content["memory"]/1024,2) . "M" . ($content["last_time"] > 0 ? ',上次刷新时间:' . date("Y-m-d H:i:s",$content["last_time"]):"") . "</td><td width='180'>" .date("Y-m-d H:i:s",$result["update_time"]) . "</td></tr>";
        $refresh = $request->get["refresh"]??0;
        $html = "<html><head><meta charset='UTF-8'>";
        $html .= $refresh > 0 ? "<meta http-equiv='refresh' content='".$refresh."'>":"";
        $html .= "</head>";
        $html .= "<body><table width='900' style='text-align: left;'><tr><th width='80'>监控对象</th><th width='100'>进程PID</th><th width='280'>情况</th><th width='180'>更新时间</th></tr>". $str ."</table></body></html>";
        $response->end($html);
    }

}