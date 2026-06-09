<?php
/**
 * Shop public functions stub
 */

if (!function_exists('zib_shop_get_product')) {
    function zib_shop_get_product($product_id = 0)
    {
        if (!$product_id) {
            return false;
        }
        return get_post($product_id);
    }
}

if (!function_exists('zib_shop_is_enabled')) {
    function zib_shop_is_enabled()
    {
        return function_exists('_pz') ? (bool) _pz('shop_s') : false;
    }
}

if (!function_exists('zib_shop_get_product_price')) {
    function zib_shop_get_product_price($product_id = 0)
    {
        $config = get_post_meta($product_id, 'product_config', true);
        return isset($config['price']) ? floatval($config['price']) : 0;
    }
}

// zib_shop_get_product_config 已在 product.php 中定义，此处不重复声明

if (!function_exists('zib_shop_get_order_type')) {
    function zib_shop_get_order_type()
    {
        return 10; // 购买商品
    }
}
