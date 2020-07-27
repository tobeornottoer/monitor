<?php
/**
 * Class Factory
 * Created by PhpStorm.
 * Autor: jw
 * Time:22:05
 * @package factory
 */

namespace factory;


interface Factory
{
    public static function begin($object,$conf);

}