<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2021-08-05 20:25:29
 * @LastEditTime : 2025-07-03 22:38:00
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题|论坛系统|论坛首页模板
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

function zib_bbs_posts_frontend_set_input_array($input_array, $term_id, $type)
{

    $input_array   = [];
    $input_array[] = array(
        'name' => __('标题', 'zib_language'),
        'id'   => 'post_title',
        'std'  => get_the_title($term_id),
        'type' => 'text',
    );

    $input_array[] = array(
        'name' => __('阅读数', 'zib_language'),
        'id'   => 'views',
        'std'  => get_post_meta($term_id, 'views', true),
        'type' => 'number',
    );

    return $input_array;
}
add_filter('zib_frontend_set_input_array', 'zib_bbs_posts_frontend_set_input_array', 10, 3); //添加前台设置

zib_bbs_page_template('posts');
