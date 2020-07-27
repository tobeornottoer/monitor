<?php
/**
 * Class FileLog
 * Created by PhpStorm.
 * Autor: jw
 * Time:22:53
 * @package drive\log
 */

namespace drive\log;

use drive\log\AbstractLog;
class FileLog implements LogInterface
{

    public static function write(string $error_level,string $log)
    {
        // TODO: Implement write() method.
        echo $error_level ." ". date("Y-m-d/H:i:s") ." ". $log . PHP_EOL;
        if($error_level == AbstractLog::LOG_ERROR){
            exit;
        }
    }

}