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
    public static $log_lock = null;

    public static function write(string $log_file,string $error_level,string $log)
    {
        if(self::$log_lock === null){
            self::$log_lock = new \Swoole\Lock(SWOOLE_FILELOCK,$log_file);
        }
        self::$log_lock->lock();
        $fileHandle = fopen($log_file,"a");
        fwrite($fileHandle,$error_level ." ". date("Y-m-d/H:i:s") ." ". $log . "\n");
        fclose($fileHandle);
        self::$log_lock->unlock();
        if($error_level == AbstractLog::LOG_ERROR){
            exit;
        }
    }

}