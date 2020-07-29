<?php
/**
 * Class LogInterface
 * Created by PhpStorm.
 * Autor: jw
 * Time:22:51
 * @package drive\log
 */

namespace drive\log;


interface LogInterface
{
    public static function write(string $log_file,string $error_level,string $log);
}