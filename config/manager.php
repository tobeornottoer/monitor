<?php
/**
 * Class ${NAME}
 * Created by PhpStorm.
 * Author: jw
 * Time:22:10
 */
return [
    "handle" => [
        "file" => factory\FileFactory::class,
        "http" => factory\HttpFactory::class,
    ],

    "process" => [
        "file" => file\FileMonitor::class,
        "http" => http\Http::class,
        "mysql" => mysql\Mysql::class,
        "redis" => redis\Redis::class
    ],

];