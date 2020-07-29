<?php
swoole_set_process_name("monitor_master");
if(isset($argv[1]) && $argv[1] == "-d"){
    \Swoole\Process::daemon();
}
include __DIR__ . "/autoload.php";
$conf = parse_ini_file(__DIR__ . "/monitor.conf",true);
$Manager = new Manager($conf);
$Manager->start();
$Manager->execWait();