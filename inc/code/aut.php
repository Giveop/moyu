<?php
/**
 * ZibCodeAut - Authorization check stub
 */
class ZibCodeAut
{
    public static function __callStatic($name, $arguments)
    {
        return true;
    }

    public static function check()
    {
        return true;
    }

    public static function is_aut()
    {
        return true;
    }

    public static function get_status()
    {
        return 'active';
    }
}
