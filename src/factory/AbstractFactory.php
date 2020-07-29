<?php
/**
 * Class AbstractFactory
 * Created by PhpStorm.
 * Autor: jw
 * Time:22:22
 * @package factory
 */

namespace factory;


class AbstractFactory implements Factory
{
    /**
     * @var array
     * 保存子进程在主进程中的对象
     */
    public static $process = [];

    public static function begin($object,$conf){

    }

    public static function stdout(string $string){
        echo $string . PHP_EOL;
    }

}