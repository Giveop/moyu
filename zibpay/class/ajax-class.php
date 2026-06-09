<?php
/**
 * ZibPay AJAX class stub
 */

if (!class_exists('ZibPayAjax')) {
    class ZibPayAjax
    {
        public static function __callStatic($name, $arguments)
        {
            return true;
        }

        public function __call($name, $arguments)
        {
            return true;
        }

        public static function init()
        {
            // AJAX actions are registered in individual function files
        }
    }
}
