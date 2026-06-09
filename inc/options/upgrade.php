<?php
/*
 * @Author        : Qinver
 * @Url           : zibll.com
 * @Date          : 2025-05-01 15:11:58
 * @LastEditTime : 2025-12-06 14:55:52
 * @Email         : 770349780@qq.com
 * @Project       : Zibll子比主题
 * @Description   : 一款极其优雅的Wordpress主题 | 主题更新需要执行的任务
 * @Read me       : 感谢您使用子比主题，主题源码有详细的注释，支持二次开发。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

function zib_update_theme_tasks_8_5()
{
    //推荐指数排序方式
    //获取所有的帖子，每页200个
    $size = 500;
    global $wpdb;

    $DB     = zibDb::table($wpdb->posts)->field('ID');
    $sub_DB = clone $DB;
    $sub_DB->alias('_m')
           ->join([$wpdb->postmeta, 'pm'], '_m.ID = pm.post_id')
           ->where('_m.post_type', 'forum_post')
           ->where('pm.meta_key', 'recommend_score');

    $posts = $DB->where('ID', 'NOT IN', $sub_DB)
                ->where('post_type', 'forum_post')
                ->order('ID', 'ASC')
                ->limit($size)
                ->select()->toArray();
    $total = $DB->count();

    $data_total = isset($_REQUEST['data']['total']) ? (int) $_REQUEST['data']['total'] : $total;
    $index      = isset($_REQUEST['data']['index']) ? (int) $_REQUEST['data']['index'] : 1;

    $this_count = count($posts);
    foreach ($posts as $post) {
        $add_value = zib_bbs_calculate_posts_recommend_score($post['ID']);
        update_post_meta($post['ID'], 'recommend_score', $add_value);
        $index++;
    }

    $send_data = [];
    if ($total - $this_count > 0) {
        $send_data['msg']  = '优化帖子推荐指数，当前已优化' . $this_count . '个，剩余' . ($total - $this_count) . '个帖子待处理';
        $send_data['data'] = [
            'index' => $index,
            'total' => $data_total,
        ];
        $send_data['status'] = 'continue';

        echo json_encode($send_data);
        exit;
    }

    return true;
}
zib_add_update_theme_tasks('8.4.0.4', 'zib_update_theme_tasks_8_5');

//V8.3版本需要执行的任务
function zib_update_theme_tasks_8_3()
{
    //订单数据库处理
    $sql_limit = 1000; //每次优化的用户数量
    $order_db  = ZibDB::name('zibpay_order')->where('order_price', 0)->where('order_type', '!=', 10)->where('pay_type', 'points');

    $send_data       = [];
    $order_count_all = $order_db->count();
    $orders          = $order_db->field('id,order_price,status,pay_detail')->limit($sql_limit)->order('id')->select()->toArray();

    if ($orders) {
        foreach ($orders as $order) {
            $pay_detail  = maybe_unserialize($order['pay_detail']);
            $order_price = (int) ($pay_detail['points'] ?? 0);
            if ($order_price) {
                $data = [
                    'order_price' => $order_price,
                ];
                if ($order['status'] == '1') {
                    $data['pay_price'] = $order_price;
                }

                ZibDB::name('zibpay_order')->where('id', $order['id'])->update($data);
            }
        }

        $remaining = ($order_count_all - count($orders));
        if ($remaining > 0) {
            $send_data['msg']    = '优化订单数据，当前已优化' . count($orders) . '个，剩余' . $remaining . '个订单待处理';
            $send_data['status'] = 'continue';

            echo json_encode($send_data);
            exit;
        }
    }

    return true;
}
zib_add_update_theme_tasks('8.3', 'zib_update_theme_tasks_8_3');

//8.2版本需要执行的任务
function zib_update_theme_tasks_8_2()
{
    //用户主页添加商品栏目
    $tabs_opt = _pz('author_main_tab_opt');
    if ($tabs_opt && is_array($tabs_opt) && !isset($tabs_opt['product'])) {

        $i   = 0;
        $new = [];
        foreach ($tabs_opt as $key => $value) {
            $i++;
            if ($i == 2) {
                $new['product'] = ['pc_s' => '1', 'm_s' => '1'];
            }
            $new[$key] = $value;
        }

        _spz('author_main_tab_opt', $new);
    }

    //悬浮按钮添加购物车
    $shop_btn_opt = _pz('float_btn');
    if ($shop_btn_opt && is_array($shop_btn_opt) && !isset($shop_btn_opt['shop_cart'])) {
        $i   = 0;
        $new = [];
        foreach ($shop_btn_opt as $key => $value) {
            $i++;
            if ($i == 3) {
                $new['shop_cart'] = array(
                    'pc_s'  => true,
                    'm_s'   => false,
                    'color' => array(
                        'color' => '',
                        'bg'    => '',
                    ),
                );
            }
            $new[$key] = $value;
        }
        _spz('float_btn', $new);
    }

    return true;
}
zib_add_update_theme_tasks('8.2', 'zib_update_theme_tasks_8_2');

//v7.9版本需要执行的任务
//此次主要是数据库优化任务，将多个不重要的meta信息移动到单独的key中，极大减少数据库查询压力
function zib_update_theme_tasks_7_9()
{
    global $wpdb;
    $send_data = array();

    //开始优化option数据表
    $option_meta_keys     = zib_get_option_meta_keys('option');
    $option_meta_keys_str = implode("','", $option_meta_keys);
    $option_is_need       = $wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE `option_name` IN ('$option_meta_keys_str')");

    if ($option_is_need) {
        $new_data = get_option('zib_other_options') ?: array();
        foreach ($option_is_need as $key => $value) {
            $new_data[$value->option_name] = maybe_unserialize($value->option_value);
        }

        if (update_option('zib_other_options', $new_data)) {
            //删除旧数据
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE `option_name` IN ('$option_meta_keys_str')");

            $option_msg = '优化option数据表完成，优化' . count($option_is_need) . '条数据';
            echo json_encode(array('status' => 'continue', 'msg' => $option_msg, 'new_data' => $new_data));
            exit;
        }
    }

    $option_term_is_need = $wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE `option_name`  LIKE '_taxonomy_meta_%' or `option_name` LIKE '_taxonomy_image_%'", ARRAY_A);
    if ($option_term_is_need) {
        foreach ($option_term_is_need as $value) {
            if (strstr($value['option_name'], '_taxonomy_meta_')) {
                $term_id = str_replace('_taxonomy_meta_', '', $value['option_name']);
                $term_v  = maybe_unserialize($value['option_value']);
                zib_update_term_meta($term_id, 'term_seo', $term_v);
            } elseif (strstr($value['option_name'], '_taxonomy_image_')) {
                $term_id = str_replace('_taxonomy_image_', '', $value['option_name']);
                $term_v  = maybe_unserialize($value['option_value']);
                zib_update_term_meta($term_id, 'cover_image', $term_v);
            }
        }

        $wpdb->query("DELETE FROM {$wpdb->options} WHERE `option_name`  LIKE '_taxonomy_meta_%' or `option_name` LIKE '_taxonomy_image_%'");

        $option_msg = '删除option残留数据表完成，共计删除' . count($option_term_is_need) . '条数据';
        echo json_encode(array('status' => 'continue', 'msg' => $option_msg));
        exit;
    }
    //结束优化option数据表

    // - 开始优化用户数据表
    $sql_limit                               = 1000; //每次优化的用户数量
    $user_meta_keys                          = zib_get_option_meta_keys('user_meta');
    $user_meta_keys_str                      = implode("','", $user_meta_keys);
    $user_meta_is_need_mids                  = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS user_id FROM {$wpdb->usermeta} WHERE `meta_key` IN ('$user_meta_keys_str') GROUP By user_id ORDER BY user_id ASC LIMIT 0,$sql_limit", ARRAY_A);
    $FOUND_ROWS                              = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
    $usermeta_count_all                      = !empty($_REQUEST['data']['usermeta_count_all']) ? $_REQUEST['data']['usermeta_count_all'] : (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->usermeta} WHERE 1");
    $send_data['data']['usermeta_count_all'] = $usermeta_count_all;

    if (!empty($user_meta_is_need_mids)) {
        $is_needed_user_ids = array(); //已完成的user_id
        foreach ($user_meta_is_need_mids as $user_meta_is_need_mid) {
            $_n_m_user_id = (int) $user_meta_is_need_mid['user_id'];
            $_n_m_data    = $wpdb->get_results("SELECT * FROM {$wpdb->usermeta} WHERE `meta_key` IN ('$user_meta_keys_str') AND `user_id` = $_n_m_user_id", ARRAY_A);

            $_n_m_new = get_user_meta($_n_m_user_id, 'zib_other_data', true) ?: array();
            foreach ($_n_m_data as $metarow) {
                $_n_m_new[$metarow['meta_key']] = maybe_unserialize($metarow['meta_value']);
            }

            update_user_meta($_n_m_user_id, 'zib_other_data', $_n_m_new);
            $is_needed_user_ids[] = $_n_m_user_id;
        }

        //删除已处理后的数据表
        $is_needed_user_ids_str = implode("','", $is_needed_user_ids);
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE `meta_key` IN ('$user_meta_keys_str') AND `user_id` IN ('$is_needed_user_ids_str')");

        //剩余数据量
        $is_need_remnant_count = $FOUND_ROWS - count($is_needed_user_ids);
        $send_data['msg']      = '优化user数据表，优化' . count($is_needed_user_ids) . '个用户数据' . ($is_need_remnant_count > 0 ? '，剩余' . $is_need_remnant_count . '个用户数据' : '');
        $send_data['status']   = 'continue';

        if ($is_need_remnant_count <= 0) {
            //删除陈旧user_meta数据
            $wpdb->query("DELETE FROM `$wpdb->usermeta` WHERE `meta_key` LIKE '_user_points_followed_%' OR `meta_key` LIKE '_user_integral_followed_%' OR `meta_key` = 'posts_draft'");

            $usermeta_count_need_all = (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->usermeta} WHERE 1");
            $send_data['msg'] .= '<div> >>> user_meta数据表优化完成，优化前共计' . $usermeta_count_all . '个数据，优化后剩余' . $usermeta_count_need_all . '，减少' . ($usermeta_count_all - $usermeta_count_need_all) . '条数据<div/>';
        }

        echo json_encode($send_data);
        exit;
    }
    // - 结束优化用户数据表

    // - 开始优化评论数据表
    $comment_meta_keys                          = zib_get_option_meta_keys('comment_meta');
    $comment_meta_keys_str                      = implode("','", $comment_meta_keys);
    $comment_meta_is_need                       = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS `comment_id` FROM {$wpdb->commentmeta} WHERE `meta_key` IN ('$comment_meta_keys_str') GROUP By comment_id ORDER BY comment_id ASC LIMIT 0,$sql_limit", ARRAY_A);
    $FOUND_ROWS                                 = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
    $commentmeta_count_all                      = !empty($_REQUEST['data']['commentmeta_count_all']) ? $_REQUEST['data']['commentmeta_count_all'] : (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->commentmeta} WHERE 1");
    $send_data['data']['commentmeta_count_all'] = $commentmeta_count_all;

    if (!empty($comment_meta_is_need)) {
        $is_needed_comment_ids = array(); //已完成的comment_id
        foreach ($comment_meta_is_need as $comment_meta_is_need_mid) {
            $_n_m_comment_id = (int) $comment_meta_is_need_mid['comment_id'];
            $_n_m_data       = $wpdb->get_results("SELECT * FROM {$wpdb->commentmeta} WHERE `meta_key` IN ('$comment_meta_keys_str') AND `comment_id` = $_n_m_comment_id", ARRAY_A);

            $_n_m_new = get_comment_meta($_n_m_comment_id, 'zib_other_data', true) ?: array();
            foreach ($_n_m_data as $metarow) {
                $_n_m_new[$metarow['meta_key']] = maybe_unserialize($metarow['meta_value']);
            }

            update_comment_meta($_n_m_comment_id, 'zib_other_data', $_n_m_new);
            $is_needed_comment_ids[] = $_n_m_comment_id;
        }

        //删除已处理后的数据表
        $is_needed_comment_ids_str = implode("','", $is_needed_comment_ids);
        $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE `meta_key` IN ('$comment_meta_keys_str') AND `comment_id` IN ('$is_needed_comment_ids_str')");

        //剩余数据量
        $is_need_remnant_count = $FOUND_ROWS - count($is_needed_comment_ids);
        $send_data['msg']      = '优化comment数据表，优化' . count($is_needed_comment_ids) . '个评论数据' . ($is_need_remnant_count > 0 ? '，剩余' . $is_need_remnant_count . '个评论数据' : '');
        $send_data['status']   = 'continue';

        if ($is_need_remnant_count <= 0) {
            $commentmeta_count_need_all = (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->commentmeta} WHERE 1");
            $send_data['msg'] .= '<div> >>> comment_meta数据表优化完成，优化前共计' . $commentmeta_count_all . '个数据，优化后剩余' . $commentmeta_count_need_all . '，减少' . ($commentmeta_count_all - $commentmeta_count_need_all) . '条数据<div/>';
        }
        echo json_encode($send_data);
        exit;
    }
    // - 结束优化评论数据表

    // - 开始优化文章数据表
    $post_meta_keys                          = zib_get_option_meta_keys('post_meta');
    $post_meta_keys_str                      = implode("','", $post_meta_keys);
    $post_meta_is_need                       = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` IN ('$post_meta_keys_str') GROUP By post_id ORDER BY post_id ASC LIMIT 0,$sql_limit", ARRAY_A);
    $FOUND_ROWS                              = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
    $postmeta_count_all                      = !empty($_REQUEST['data']['postmeta_count_all']) ? $_REQUEST['data']['postmeta_count_all'] : (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->postmeta} WHERE 1");
    $send_data['data']['postmeta_count_all'] = $postmeta_count_all;

    if (!empty($post_meta_is_need)) {
        $is_needed_post_ids = array(); //已完成的post_id
        foreach ($post_meta_is_need as $post_meta_is_need_mid) {
            $_n_m_post_id = (int) $post_meta_is_need_mid['post_id'];
            $_n_m_data    = $wpdb->get_results("SELECT * FROM {$wpdb->postmeta} WHERE `meta_key` IN ('$post_meta_keys_str') AND `post_id` = $_n_m_post_id", ARRAY_A);

            $_n_m_new = get_post_meta($_n_m_post_id, 'zib_other_data', true) ?: array();
            foreach ($_n_m_data as $metarow) {
                $_n_m_new[$metarow['meta_key']] = maybe_unserialize($metarow['meta_value']);
            }

            update_post_meta($_n_m_post_id, 'zib_other_data', $_n_m_new);
            $is_needed_post_ids[] = $_n_m_post_id;
        }

        //删除已处理后的数据表
        $is_needed_post_ids_str = implode("','", $is_needed_post_ids);
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE `meta_key` IN ('$post_meta_keys_str') AND `post_id` IN ('$is_needed_post_ids_str')");

        //剩余数据量
        $is_need_remnant_count = $FOUND_ROWS - count($is_needed_post_ids);
        $send_data['msg']      = '优化post数据表，优化' . count($is_needed_post_ids) . '篇文章数据' . ($is_need_remnant_count > 0 ? '，剩余' . $is_need_remnant_count . '篇文章数据' : '');
        $send_data['status']   = 'continue';

        if ($is_need_remnant_count <= 0) {
            $postmeta_count_need_all = (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->postmeta} WHERE 1");
            $send_data['msg'] .= '<div> >>> post_meta数据表优化完成，优化前共计' . $postmeta_count_all . '个数据，优化后剩余' . $postmeta_count_need_all . '，减少' . ($postmeta_count_all - $postmeta_count_need_all) . '条数据<div/>';
        }
        echo json_encode($send_data);
        exit;
    }
    // - 结束优化文章数据表

    // - 开始优化分类数据表
    $term_meta_keys                          = zib_get_option_meta_keys('term_meta');
    $term_meta_keys_str                      = implode("','", $term_meta_keys);
    $term_meta_is_need                       = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS `term_id` FROM {$wpdb->termmeta} WHERE `meta_key` IN ('$term_meta_keys_str') GROUP By term_id ORDER BY term_id ASC LIMIT 0,$sql_limit", ARRAY_A);
    $FOUND_ROWS                              = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
    $termmeta_count_all                      = !empty($_REQUEST['data']['termmeta_count_all']) ? $_REQUEST['data']['termmeta_count_all'] : (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->termmeta} WHERE 1");
    $send_data['data']['termmeta_count_all'] = $termmeta_count_all;

    if (!empty($term_meta_is_need)) {
        $is_needed_term_ids = array(); //已完成的term_id
        foreach ($term_meta_is_need as $term_meta_is_need_mid) {
            $_n_m_term_id = (int) $term_meta_is_need_mid['term_id'];
            $_n_m_data    = $wpdb->get_results("SELECT * FROM {$wpdb->termmeta} WHERE `meta_key` IN ('$term_meta_keys_str') AND `term_id` = $_n_m_term_id", ARRAY_A);

            $_n_m_new = get_term_meta($_n_m_term_id, 'zib_other_data', true) ?: array();
            foreach ($_n_m_data as $metarow) {
                $_n_m_new[$metarow['meta_key']] = maybe_unserialize($metarow['meta_value']);
            }

            update_term_meta($_n_m_term_id, 'zib_other_data', $_n_m_new);
            $is_needed_term_ids[] = $_n_m_term_id;
        }

        //删除已处理后的数据表
        $is_needed_term_ids_str = implode("','", $is_needed_term_ids);
        $wpdb->query("DELETE FROM {$wpdb->termmeta} WHERE `meta_key` IN ('$term_meta_keys_str') AND `term_id` IN ('$is_needed_term_ids_str')");

        //剩余数据量
        $is_need_remnant_count = $FOUND_ROWS - count($is_needed_term_ids);
        $send_data['msg']      = '优化term数据表，优化' . count($is_needed_term_ids) . '个分类数据' . ($is_need_remnant_count > 0 ? '，剩余' . $is_need_remnant_count . '个分类数据' : '');
        $send_data['status']   = 'continue';

        if ($is_need_remnant_count <= 0) {
            $termmeta_count_need_all = (int) $wpdb->get_var("SELECT count(*) FROM {$wpdb->termmeta} WHERE 1");
            $send_data['msg'] .= '<div> >>> term_meta数据表优化完成，优化前共计' . $termmeta_count_all . '个数据，优化后剩余' . $termmeta_count_need_all . '，减少' . ($termmeta_count_all - $termmeta_count_need_all) . '条数据<div/>';
        }
        echo json_encode($send_data);
        exit;
    }
    // - 结束优化分类数据表

    return true;
}
zib_add_update_theme_tasks('7.9.1', 'zib_update_theme_tasks_7_9');

/**
 * @description: 添加升级主题任务
 * @param {*}
 * @return {*}
 */
function zib_add_update_theme_tasks($key, $func_name)
{
    global $update_theme_tasks;
    $update_theme_tasks = is_array($update_theme_tasks) ? $update_theme_tasks : array();

    $update_theme_tasks[$key] = $func_name;
}

//ajax执行主题升级任务
function zib_ajax_update_theme_task_action()
{

    $all_tasks = zib_get_update_theme_tasks();
    $completed = zib_get_option('update_theme_tasks_completed');
    $completed = is_array($completed) ? $completed : array();

    if ($all_tasks && is_array($all_tasks)) {
        foreach ($all_tasks as $task_key => $task_value) {
            if (call_user_func($task_value)) {
                $completed[] = $task_key;
                zib_update_option('update_theme_tasks_completed', $completed);
            }
        }
    }

    //刷新全部缓存
    try {
        wp_cache_flush();
    } catch (\Exception $e) {

    }
    echo json_encode(array('status' => 'over', 'msg' => '<b calss="em12">任务执行完成！请刷新页面</b>'));
    exit;
}
add_action('wp_ajax_update_theme_task_action', 'zib_ajax_update_theme_task_action'); // 后台加载完成主题模板

//获取主题更新任务明细
function zib_get_update_theme_tasks()
{

    global $update_theme_tasks;
    $all_tasks = is_array($update_theme_tasks) ? $update_theme_tasks : array();

    //如果是第一次安装主题，不需要执行
    $version = get_option('Zibll_version');
    if (!$version) {
        //设置为已经执行过
        zib_update_option('update_theme_tasks_completed', array_keys($all_tasks));
        return false;
    }

    $___tasks = array(
        '5.5' => '', //版本号(key) : 执行的函数名称
        '5.6' => '', //版本号(key) : 执行的函数名称
    );

    $completed = zib_get_option('update_theme_tasks_completed');
    if ($completed && is_array($completed)) {
        foreach ($completed as $key => $value) {
            if (isset($all_tasks[$value])) {
                unset($all_tasks[$value]);
            }
        }
    }

    return $all_tasks;
}

/**
 * @description: 更新主题需要手动点击执行任务的页面显示
 * @param {*}
 * @return {*}
 */
function zib_update_theme_task_action_page()
{

    $all_tasks = zib_get_update_theme_tasks();
    if (empty($all_tasks)) {
        return;
    }

    $title = '执行优化任务';
    $desc  = '<div class="mb10">恭喜您！安装了zibll主题最新版，主题优化了大量的数据库逻辑，有效的提高了网站性能，目前有多个核心性能优化任务需要执行！请点击下方按钮开始优化！</div>
    <div style=" color: #e56d6d; ">
    <div class="">任务执行前请建议先备份整个数据库！以防出现未预料的意外（新搭建的网站请忽略）</div>
    <div class="mb10">任务执行后将不可以降级zibll主题，不能再使用低于V7.9以下版本的主题</div>
    </div>
            <div class="em09"><div>升级过程会根据你目前的数据量的大小，所消耗的时间不同，请耐心等待</div>
        <div>升级完成后会自动关闭此页面页面</div>
        <div>如果出现错误或者长时间未完成，可手动刷新页面</div></div>';
    $but_text = '开始优化';
    $ajax_url = admin_url('admin-ajax.php');

    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=0.0">
        <title>' . $title . '</title>
    </head>
    <style>
        body {
            background-color: #292a2d;
            color: #e5eef7;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 2;

        }

        main {
            max-width: 860px;
            margin: auto;
        }

        .box {
            margin: 20px;
            background: #343639;
            border-radius: 8px;
            padding: 20px 30px;
        }

        .box>.title{
            margin: 0;
            border-bottom: 1px solid #464444;
            padding: 10px 0;
            font-size: 26px;
        }

        .box>.desc{
            opacity: .8;
            margin: 20px 0;
        }

        .mb10{
            margin-bottom: 10px;
        }

        .em09{
            font-size: .9em;
        }

        .but{
            display: block;
            min-width: 120px;
            border-radius: 4px;
            transition: .15s;
            padding: 7px 15px;
            box-shadow: none;
            text-shadow: 0 0 0;
            line-height: 1.44;
            margin-top: 40px;
            margin-bottom: 10px;
            box-sizing: border-box;
            font-size: 16px;
            outline: none !important;
            text-align: center;
            color: #fff;
            cursor: pointer;
            border: none;
            background: linear-gradient(135deg, #59c3fb 10%, #268df7 100%);

        }

        .but:hover,
        .but:focus {
            opacity: .8;
        }

        .result-ok,
        .result-error {
            margin: 40px 0 10px;
            text-align: center;
        }

        .result-desc {
            margin-bottom: 10px;
            opacity: .6;
        }

        .c-red,
        .result-error {
            color: #fd605b;
        }

        .c-ok,
        .result-ok {
            color: #64d476;
        }

        .result-list {
            margin-bottom: 10px;
            font-size: 13px;
            color: #888;
            background: #252527;
            padding: 10px;
            border-radius: 4px;
        }

        .icon {
            width: 1em;
            height: 1em;
            fill: currentColor;
            overflow: hidden;
            font-size: 1.2em;
            display: inline-block;
            margin-right: 6px;
            vertical-align: -.2em;
        }

        .name {
            font-size: 1.5em;
            color: #ffffff;
            text-align: center;
            margin: 90px 0;
        }

        .navbar-logo {
            margin-bottom: 10px;
        }

        .navbar-logo img {
            height: 60px;
        }

        .icon-loading {
            content: "";
            width: 0.7em;
            height: 0.7em;
            display: inline-block;
            border: 0.1em solid transparent;
            border-radius: 50%;
            border-top-color: var(--this-color);
            border-bottom-color: var(--this-color);
            -webkit-animation: sql-replace-rotate 1s cubic-bezier(0.7, 0.1, 0.31, 0.9) infinite;
            animation: sql-replace-rotate 1s cubic-bezier(0.7, 0.1, 0.31, 0.9) infinite;
            margin-right: 6px;
            vertical-align: -.05em;
        }

        @-webkit-keyframes sql-replace-rotate {
            0% {
                transform: rotate(0);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes sql-replace-rotate {
            0% {
                transform: rotate(0);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .notice:empty{
            display: none;
          }

        .notice {
            border-radius: 8px;
            padding: 15px;
            font-size: 14px;
            background: #2a2a2c;
            opacity: .6;
            border: 1px solid #4c4c4c;
          }

          .notice li{
            margin-left: 15px;
          }

          .notice.error {
            color: #ff7070;
            border-color: #9c5757;
          }

          .notice.success {
            color: #25d952;
            border-color: #276737;
          }

    </style>
    <body>
    <main>
        <div class="container">
            <div class="name">
                <div class="navbar-logo"><img src="' . get_template_directory_uri() . '/img/logo_dark.png" height="50"></div>
            </div>

            <div class="box">
                <h1 class="title">' . $title . '</h1>
                <div class="desc">' . $desc . '</div>
                <div class="notice"></div>
                <button class="but c-blue ml10 flex0 but-submit">' . $but_text . '</button>
            </div>

        </div>
    </main>
    <script type="text/javascript" src="' . ZIB_TEMPLATE_DIRECTORY_URI . '/js/libs/jquery.min.js"></script>
    <script type="text/javascript">

        $("body").on("click",".but-submit",function () {
            var _this = $(this);

            if (_this.attr("disabled")) {
                return false;
            }

            if(!confirm("请确认已备份了整个数据库，且已知晓执行后不能降级zibll版本！ 请再次确认！")){
                return false;
            }

            var ajax_url = "' . $ajax_url . '";
            var data = {
                action: "update_theme_task_action",
            }
            var _text = _this.html();
            _this.attr("disabled", true).html(\'<i class="icon-loading"></i>请稍候\');

            return ajax(data),false;

            function ajax(_data) {
                $.ajax({
                    type: "POST",
                    url: ajax_url,
                    data: _data,
                    dataType: "json",
                    error: function (n) {
                        console.error("ajax_error", n);
                        notice("操作出错，错误信息已打印至浏览器控制台，请对照排查","error",0);
                        _this.attr(\'disabled\', false).html(_text);
                    },
                    success: function (n) {
                        if (n.status ==="continue") {
                            notice(n.msg);
                            if(n.data){
                                _data.data = n.data
                            }
                            ajax(_data);

                        }else if (n.status ==="over") {
                            notice(n.msg,"success");
                            _this.html("执行完成，刷新页面").attr(\'disabled\', false).removeClass("but-submit").on("click", function() {
                                window.location.reload();
                            })
                            setTimeout(function() {
                                window.location.reload();
                            }, 5500);
                        }else if (n.status ==="stop") {
                            notice(n.msg);
                            _this.attr(\'disabled\', false).html(_text);
                        }
                    },
                });
            }
        });

        function notice(msg, type, no_add) {
            var _notice = $(".notice");
            type = type || "info";
            _notice.removeClass("error info success").addClass(type);
            if (!no_add) {
                _notice.append("<li>"+msg+"</li>");
            } else {
                _notice.html(msg);
            }
        }

    </script>
</body>
</html>';

    exit;
}

//当管理员登录时候，自动主题更新的升级任务
if (is_super_admin()) {
    add_action('template_redirect', 'zib_update_theme_task_action_page', 99); // 后台加载完成主题模板
    add_action('admin_menu', 'zib_update_theme_task_action_page', 99); // 后台加载完成主题模板
}
