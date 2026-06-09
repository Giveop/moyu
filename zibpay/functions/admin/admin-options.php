<?php
/**
 * ZibPay Admin Options - 后台菜单、资源加载
 * 新版 Vue SPA 商城管理后台
 */

// 添加后台菜单
function zibpay_add_settings_menu()
{
    $icon        = 'dashicons-cart';
    $member_slug = zibpay_get_member_menu_slug();

    // 主菜单
    add_menu_page(
        'zibll商城中心',
        'zibll商城中心',
        'manage_options',
        'zibpay_page',
        'zibpay_page',
        $icon,
        56
    );

    // 子菜单
    add_submenu_page('zibpay_page', 'zibll商城中心', 'zibll商城中心', 'manage_options', 'zibpay_page', 'zibpay_page');
    add_submenu_page('zibpay_page', '订单明细', '订单明细', 'manage_options', 'zibpay_order', 'zibpay_order_page_callback');

    if (_pz('shop_s')) {
        add_submenu_page('zibpay_page', '发货与物流', '发货与物流', 'manage_options', 'zibpay_shipping', 'zibpay_shipping_page');
        add_submenu_page('zibpay_page', '售后管理', '售后管理', 'manage_options', 'zibpay_after_sale', 'zibpay_after_sale_page');
    }

    add_submenu_page('zibpay_page', '卡密管理', '卡密管理', 'manage_options', 'zibpay_card', 'zibpay_card_page_callback');
    add_submenu_page('zibpay_page', '优惠码管理', '优惠码管理', 'manage_options', 'zibpay_coupon', 'zibpay_coupon_page_callback');

    add_submenu_page('zibpay_page', '提现记录', '提现记录', 'manage_options', 'zibpay_withdraw', 'zibpay_withdraw_page_callback');
    add_submenu_page('zibpay_page', '会员管理', '会员管理', 'manage_options', $member_slug);
    add_submenu_page('zibpay_page', '商品明细', '商品明细', 'manage_options', 'zibpay_product', 'zibpay_product_page_callback');

    // 旧版订单明细
    add_submenu_page('zibpay_page', '订单明细(旧版)', '订单明细(旧版)', 'manage_options', 'zibpay_old_order', 'zibpay_old_order_page_callback');
}
add_action('admin_menu', 'zibpay_add_settings_menu');

/**
 * 注册旧版后台页面 slug 兼容层
 * 说明：旧版模板/表格/描述文案里仍有大量 admin.php?page=*_page 的硬编码链接
 * 如果不注册这些隐藏页面，WordPress 会在 admin.php 阶段直接判定“您不能访问此页面”
 */
function zibpay_register_legacy_admin_pages()
{
    $legacy_pages = array(
        'zibpay_charge_card_page' => array('卡密管理', 'zibpay_card_page_callback'),
        'zibpay_coupon_page'      => array('优惠码管理', 'zibpay_coupon_page_callback'),
        'zibpay_withdraw_page'    => array('提现记录', 'zibpay_withdraw_page_callback'),
        'zibpay_product_page'     => array('商品明细', 'zibpay_product_page_callback'),
        'zibpay_balance_page'     => array('商品明细', 'zibpay_balance_page_callback'),
        'zibpay_rebate_page'      => array('返佣明细', 'zibpay_rebate_page_callback'),
        'zibpay_income_page'      => array('分成明细', 'zibpay_income_page_callback'),
        'zibpay_old_order_page'   => array('订单明细(旧版)', 'zibpay_old_order_page_callback'),
        //zibpay/page/order.php 内部硬编码 ?page=zibpay_order_page，
        //邀请码、订单 widget 等旧链接也都用这个 slug，必须注册兼容入口
        'zibpay_order_page'       => array('订单明细', 'zibpay_old_order_page_callback'),
    );

    foreach ($legacy_pages as $slug => $args) {
        add_submenu_page(null, $args[0], $args[0], 'manage_options', $slug, $args[1]);
    }
}
add_action('admin_menu', 'zibpay_register_legacy_admin_pages', 20);

function zibpay_get_member_menu_slug()
{
    $query = array('zibpay_member' => 1);

    for ($i = 1; $i <= 2; $i++) {
        if (_pz('pay_user_vip_' . $i . '_s', true)) {
            $query['vip'] = $i;
            break;
        }
    }

    return 'users.php?' . http_build_query($query);
}

function zibpay_member_menu_parent_file($parent_file)
{
    global $pagenow;

    if ('users.php' === $pagenow && isset($_GET['zibpay_member'])) {
        return 'zibpay_page';
    }

    return $parent_file;
}
add_filter('parent_file', 'zibpay_member_menu_parent_file');

function zibpay_member_menu_submenu_file($submenu_file, $parent_file)
{
    global $pagenow;

    if ('users.php' === $pagenow && isset($_GET['zibpay_member'])) {
        return zibpay_get_member_menu_slug();
    }

    return $submenu_file;
}
add_filter('submenu_file', 'zibpay_member_menu_submenu_file', 10, 2);

function zibpay_sync_settings_submenu()
{
    global $submenu;

    if (empty($submenu['zibpay_page'])) {
        return;
    }

    $member_slug = zibpay_get_member_menu_slug();
    $desired     = array(
        'zibpay_page'      => array('zibll商城中心', 'manage_options', 'zibpay_page', 'zibll商城中心'),
        'zibpay_order'     => array('订单明细', 'manage_options', 'zibpay_order', '订单明细'),
        'zibpay_card'      => array('卡密管理', 'manage_options', 'zibpay_card', '卡密管理'),
        'zibpay_coupon'    => array('优惠码管理', 'manage_options', 'zibpay_coupon', '优惠码管理'),
        'zibpay_withdraw'  => array('提现记录', 'manage_options', 'zibpay_withdraw', '提现记录'),
        $member_slug       => array('会员管理', 'manage_options', $member_slug, '会员管理'),
        'zibpay_product'   => array('商品明细', 'manage_options', 'zibpay_product', '商品明细'),
        'zibpay_old_order' => array('订单明细(旧版)', 'manage_options', 'zibpay_old_order', '订单明细(旧版)'),
    );

    if (_pz('shop_s')) {
        $desired = array_slice($desired, 0, 2, true)
            + array(
                'zibpay_shipping'   => array('发货与物流', 'manage_options', 'zibpay_shipping', '发货与物流'),
                'zibpay_after_sale' => array('售后管理', 'manage_options', 'zibpay_after_sale', '售后管理'),
            )
            + array_slice($desired, 2, null, true);
    }

    $current = array();
    foreach ($submenu['zibpay_page'] as $item) {
        if (!empty($item[2])) {
            $current[$item[2]] = $item;
        }
    }

    $submenu['zibpay_page'] = array();
    foreach ($desired as $slug => $fallback) {
        $item    = isset($current[$slug]) ? $current[$slug] : $fallback;
        $item[0] = $fallback[0];
        $item[1] = $fallback[1];
        $item[2] = $fallback[2];
        $item[3] = $fallback[3];

        $submenu['zibpay_page'][] = $item;
    }
}
add_action('admin_menu', 'zibpay_sync_settings_submenu', 999);

// =================== 页面回调 ===================

// 主页面 — Vue SPA (shop.php)
function zibpay_page()
{
    $page     = dirname(dirname(__DIR__)) . '/page/shop.php';
    $fallback = dirname(dirname(__DIR__)) . '/page/index.php';

    if (_pz('shop_s') && file_exists($page)) {
        include $page;
        return;
    }

    // 回退到旧版仪表盘
    zibpay_admin_page_start();
    echo '<div class="wrap"><h1>zibll商城中心</h1>';
    if (file_exists($fallback)) {
        include $fallback;
    }
    echo '</div>';
}

// 订单管理 — shop.php#/order
function zibpay_order_page_callback()
{
    $page = dirname(dirname(__DIR__)) . '/page/shop.php';
    if (file_exists($page)) {
        include $page;
        echo '<script>if(window.location.hash !== "#/order") window.location.hash = "#/order";</script>';
    }
}

// 发货管理 — shop.php#/shipping
function zibpay_shipping_page()
{
    $page = dirname(dirname(__DIR__)) . '/page/shop.php';
    if (file_exists($page)) {
        include $page;
        echo '<script>if(window.location.hash !== "#/shipping") window.location.hash = "#/shipping";</script>';
    }
}

// 售后管理 — shop.php#/after-sale
function zibpay_after_sale_page()
{
    $page = dirname(dirname(__DIR__)) . '/page/shop.php';
    if (file_exists($page)) {
        include $page;
        echo '<script>if(window.location.hash !== "#/after-sale") window.location.hash = "#/after-sale";</script>';
    }
}

// 旧版页面回调生成器
function _zibpay_load_old_page($filename)
{
    zibpay_admin_page_start(false);
    echo '<div class="wrap">';
    $page = dirname(dirname(__DIR__)) . '/page/' . $filename;
    if (file_exists($page)) {
        include $page;
    }
    echo '</div>';
}

function zibpay_card_page_callback() { _zibpay_load_old_page('charge-card.php'); }
function zibpay_coupon_page_callback() { _zibpay_load_old_page('coupon.php'); }
function zibpay_withdraw_page_callback() { _zibpay_load_old_page('withdraw.php'); }
function zibpay_product_page_callback() { _zibpay_load_old_page('product.php'); }
function zibpay_balance_page_callback() { zibpay_product_page_callback(); }
function zibpay_rebate_page_callback() { _zibpay_load_old_page('rebate.php'); }
function zibpay_income_page_callback() { _zibpay_load_old_page('income.php'); }
function zibpay_old_order_page_callback() { _zibpay_load_old_page('order.php'); }

// =================== 资源加载 ===================

// 基础资源加载
function zibpay_admin_page_start($load_spa = false)
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $assets_url = get_template_directory_uri() . '/zibpay/assets/';
    $assets_dir = get_template_directory() . '/zibpay/assets/';

    // CSS
    wp_enqueue_style('zibpay-page', $assets_url . 'css/pay-page.css', array(), THEME_VERSION);
    wp_add_inline_style('zibpay-page', '
        #adminmenu .dashicons,
        #adminmenu .dashicons-before:before,
        #adminmenu .wp-menu-image:before {
            width: 20px;
            height: 20px;
            font-size: 20px;
            line-height: 1;
        }
        #adminmenu .wp-menu-image img {
            width: 20px;
            height: 20px;
            object-fit: contain;
        }
    ');

    if ($load_spa && file_exists($assets_dir . 'css/element-plus.min.css')) {
        wp_enqueue_style('element-plus', $assets_url . 'css/element-plus.min.css', array(), THEME_VERSION);
    }

    // JS
    if ($load_spa && file_exists($assets_dir . 'js/echarts-c.min.js')) {
        wp_enqueue_script('echarts', $assets_url . 'js/echarts-c.min.js', array(), THEME_VERSION, true);
    }
    if ($load_spa && file_exists($assets_dir . 'js/westeros.min.js')) {
        wp_enqueue_script('echarts-westeros', $assets_url . 'js/westeros.min.js', array('echarts'), THEME_VERSION, true);
    }
    if ($load_spa && file_exists($assets_dir . 'js/vue.global.min.js')) {
        wp_enqueue_script('vue3', $assets_url . 'js/vue.global.min.js', array(), THEME_VERSION, true);
    }
    if ($load_spa && file_exists($assets_dir . 'js/vue-router.global.min.js')) {
        wp_enqueue_script('vue-router', $assets_url . 'js/vue-router.global.min.js', array('vue3'), THEME_VERSION, true);
    }
    if ($load_spa && file_exists($assets_dir . 'js/element-plus.min.js')) {
        wp_enqueue_script('element-plus', $assets_url . 'js/element-plus.min.js', array('vue3'), THEME_VERSION, true);
    }
    if ($load_spa && file_exists($assets_dir . 'js/element-plus-zh-cn.min.js')) {
        wp_enqueue_script('element-plus-zh-cn', $assets_url . 'js/element-plus-zh-cn.min.js', array('element-plus'), THEME_VERSION, true);
    }
    if ($load_spa && file_exists($assets_dir . 'js/vue-echarts.min.js')) {
        wp_enqueue_script('vue-echarts', $assets_url . 'js/vue-echarts.min.js', array('vue3', 'echarts'), THEME_VERSION, true);
    }
    if ($load_spa && file_exists($assets_dir . 'js/admin-page.js')) {
        wp_enqueue_script('zibpay-admin-page', $assets_url . 'js/admin-page.js', array('vue3', 'vue-router', 'element-plus'), THEME_VERSION, true);
    }
    if (file_exists($assets_dir . 'js/pay-page.js')) {
        wp_enqueue_script('zibpay-pay-page', $assets_url . 'js/pay-page.js', array('jquery'), THEME_VERSION, true);
    }

    // 延迟输出 Vue 数据
    if ($load_spa) {
        add_action('admin_footer', 'zibpay_output_vue_data', 1);
    }
}

// 在 admin_footer 输出 window._vue_data
function zibpay_output_vue_data()
{
    $vue_data = array(
        'ajax_url'    => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('zibpay_admin_nonce'),
        'site_url'    => home_url(),
        'admin_url'   => admin_url(),
        'assets_url'  => get_template_directory_uri() . '/zibpay/assets/',
        'theme_url'   => get_template_directory_uri(),
        'version'     => defined('THEME_VERSION') ? THEME_VERSION : '8.6',
    );

    $vue_data = apply_filters('admin_shop_page_vue_data', $vue_data);

    echo '<script>window._vue_data = ' . wp_json_encode($vue_data) . ';</script>';
}
