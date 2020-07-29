<?php
/**
 * Class CommonFactory
 * Created by PhpStorm.
 * Author: jw
 * Time:15:29
 * @package factory
 */

namespace factory;


class CommonFactory extends AbstractFactory
{
    public static function begin($object,$table,$conf=null,$key=null)
    {
        $process = new \Swoole\Process(function(\Swoole\Process $proc) use ($object,$table,$conf) {
            new $object($proc,$table,$conf);
        },false,1,true);
        $pid = $process->start();
        self::$process[$key] = $process;
        if($pid === false){
            self::stdout("{$key} monitor is run fail");
            return false;
        }else{
            self::stdout("{$key} monitor is running...");
            return $pid;
        }
    }

}