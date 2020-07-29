<?php
/**
 * Class FileFactory
 * Created by PhpStorm.
 * Autor: jw
 * Time:22:06
 * @package factory
 */

namespace factory;


class FileFactory extends AbstractFactory
{
    public static function begin($object,$table,$conf=null)
    {
        $process = new \Swoole\Process(function(\Swoole\Process $proc) use ($object,$table,$conf) {
            new $object($proc,$table,$conf);
        },false,1,true);
        $pid = $process->start();
        self::$process["file"] = $process;
        if($pid === false){
            self::stdout("file monitor is run fail");
            return false;
        }else{
            self::stdout("file monitor is running...");
            return $pid;
        }
    }

}