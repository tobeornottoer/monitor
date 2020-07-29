<?php
/**
 * Class AbstractLog
 * Created by PhpStorm.
 * Autor: jw
 * Time:22:54
 * @package drive\log
 */

namespace drive\log;


class AbstractLog implements LogInterface
{
    const LOG_NOTICE = "[NOTICE]:";
    const LOG_WARNNING = "[WARNNING]:";
    const LOG_ERROR = "[ERROR]:";

    /**
     * @param string $log_file
     * @param string $error_level
     * @param $log
     */
    public static function write(string $log_file,string $error_level,string $log)
    {
        // TODO: Implement write() method.
    }

}