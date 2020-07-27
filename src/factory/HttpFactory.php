<?php
/**
 * Class HttpFactory
 * Created by PhpStorm.
 * Author: jw
 * Time:18:22
 * @package factory
 */

namespace factory;


class HttpFactory extends AbstractFactory
{
    public static function begin($object,$table,$conf=null)
    {
        $process = new \Swoole\Process(function(\Swoole\Process $proc) use ($object,$table,$conf) {
            new $object($proc,$table,$conf);
        },false,2,true);
        $pid = $process->start();
        if($pid === false){
            self::stdout("http_server is run fail");
            return false;
        }else{
            self::stdout("http_server is running...");
            return $pid;
        }
    }
}