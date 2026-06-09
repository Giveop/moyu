<?php
/**
 * ZibFileAut - File verification stub
 * All file checks return true
 */
class ZibFileAut
{
    public static function __callStatic($name, $arguments)
    {
        return true;
    }

    public static function check()
    {
        return true;
    }

    public static function verify()
    {
        return true;
    }

    public static function check_file($file = '')
    {
        return true;
    }

    public static function get_file_hash($file = '')
    {
        return md5($file);
    }
}
