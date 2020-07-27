<?php
/**
 * Class ${NAME}
 * Created by PhpStorm.
 * Autor: jw
 * Time:21:40
 */
/**
 * @param $class
 */
function auto_load($class){
    require_once __DIR__ . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . str_replace("\\","/",$class) . ".php";
}
spl_autoload_register("auto_load");