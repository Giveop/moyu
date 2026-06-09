<?php
/**
 * Order Rebate - Referral commission stub
 */

if (!class_exists('ZibPayOrderRebate')) {
    class ZibPayOrderRebate
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
