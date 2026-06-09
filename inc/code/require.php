<?php
/**
 * Require loader stub
 * MD5 file integrity checks removed
 */

$code_dir = dirname(__FILE__) . '/';
require_once $code_dir . 'code.php';
require_once $code_dir . 'aut.php';
require_once $code_dir . 'file.php';
require_once $code_dir . 'new_aut.php';
require_once $code_dir . 'action.php';
require_once $code_dir . 'update.php';

class ZibToolRequire
{
    public static function __callStatic($name, $arguments)
    {
        return true;
    }

    public static function check()
    {
        return true;
    }
}
