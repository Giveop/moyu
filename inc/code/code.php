<?php
/**
 * ZibAut - Authorization stub
 * All authorization checks return true
 */
class ZibAut
{
    public static function __callStatic($name, $arguments)
    {
        return true;
    }

    public static function is_aut()
    {
        return true;
    }

    public static function is_local()
    {
        return false;
    }

    public static function is_active()
    {
        return true;
    }

    public static function get_aut_url()
    {
        return function_exists('home_url') ? home_url() : '127.0.0.1';
    }

    public static function is_update()
    {
        return false;
    }

    public static function get_aut_data()
    {
        return array(
            'status'  => 'active',
            'domain'  => function_exists('home_url') ? home_url() : '127.0.0.1',
            'time'    => date('Y-m-d H:i:s'),
            'version' => defined('THEME_VERSION') ? THEME_VERSION : '8.6',
        );
    }
}

function zib_save_options_filter($data)
{
    return $data;
}
add_filter('csf_save_options', 'zib_save_options_filter');
