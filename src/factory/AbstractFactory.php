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
    public static function begin($object,$conf){

    }

    public static function stdout(string $string){
        echo $string . PHP_EOL;
    }

}