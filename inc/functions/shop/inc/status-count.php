<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Project       : Zibll子比主题
 * @Description   : 商城订单状态统计兼容函数
 */

if (!function_exists('zib_shop_get_order_meta_status_count')) {
    function zib_shop_get_order_meta_status_count($meta_key, $meta_value, $order_status = 1)
    {
        if (!class_exists('zibpay') || !method_exists('zibpay', 'order_query') || !function_exists('zib_shop_get_order_type')) {
            return 0;
        }

        $values = array_values(array_unique(array_map('intval', (array) $meta_value)));
        if (empty($values)) {
            return 0;
        }

        $query_args = array(
            'status'     => $order_status,
            'order_type' => zib_shop_get_order_type(),
            'orderby'    => 'id',
            'order'      => 'DESC',
            'paged'      => 1,
            'per_page'   => 1,
            'meta_query' => array(
                array(
                    'key'     => $meta_key,
                    'value'   => count($values) > 1 ? $values : $values[0],
                    'compare' => count($values) > 1 ? 'IN' : '=',
                ),
            ),
        );

        $db_data = zibpay::order_query($query_args);

        return (int) ($db_data['total'] ?? 0);
    }
}

if (!function_exists('zib_shop_get_shipping_status_count')) {
    function zib_shop_get_shipping_status_count($shipping_status = 0)
    {
        static $counts = array();

        $values = array_values(array_unique(array_map('intval', (array) $shipping_status)));
        $key    = implode(',', $values);

        if (isset($counts[$key])) {
            return $counts[$key];
        }

        $counts[$key] = zib_shop_get_order_meta_status_count('shipping_status', $values, 1);

        return $counts[$key];
    }
}

if (!function_exists('zib_shop_get_after_sale_status_count')) {
    function zib_shop_get_after_sale_status_count($after_sale_status = array(1, 2))
    {
        static $counts = array();

        $values = array_values(array_unique(array_map('intval', (array) $after_sale_status)));
        $key    = implode(',', $values);

        if (isset($counts[$key])) {
            return $counts[$key];
        }

        $counts[$key] = zib_shop_get_order_meta_status_count('after_sale_status', $values, array(-2, 1));

        return $counts[$key];
    }
}

//8.8 加密 public.php 被存根后丢失，此处补回快递公司列表函数
if (!function_exists('zib_shop_get_express_companies_data')) {
    function zib_shop_get_express_companies_data()
    {
        //优先读取主题设置中用户自定义的快递公司列表
        $companies = function_exists('_pz') ? _pz('shop_express_companies') : '';

        if (is_string($companies) && '' !== $companies) {
            $companies = array_filter(array_map('trim', preg_split('/[\r\n,，]+/', $companies)));
        }

        if (!is_array($companies) || empty($companies)) {
            //默认常用快递公司
            $companies = array(
                '顺丰速运', '圆通速递', '中通快递', '韵达快递', '申通快递',
                '京东物流', '邮政EMS', '德邦快递', '百世快递', '极兔速递',
            );
        }

        return array_values($companies);
    }
}
