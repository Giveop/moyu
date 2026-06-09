<?php
/**
 * Order Income - Author revenue sharing stub
 */

if (!class_exists('ZibPayOrderIncome')) {
    class ZibPayOrderIncome
    {
        public static function __callStatic($name, $arguments)
        {
            return true;
        }

        public function __call($name, $arguments)
        {
            return true;
        }
    }
}
