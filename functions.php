<?php
/*
 * @Author        : Qinver
 * @Url           : www.baidu.com
 * @Date          : 2020-09-29 13:18:36
 * @LastEditTime: 2025-09-30 20:42:48
 * @Email         : 770349780@qq.com
 * @Project       : ZIBI主题
 * @Description   : 一款极其优雅的Wordpress主题
 * @Read me       : 感谢您使用ZIBI主题，主题源码有详细的注释，支持二次开发。
 * @Remind        : 使用盗版主题会存在各种未知风险。支持正版，从我做起！
 */

require_once get_theme_file_path('/inc/inc.php');

/**
 * 如果您需要添加一些自定义的PHP代码
 * 您可以在当前目录下新建一个 func.php 的文件，然后在最顶部写上 <?php ，再写入您的php代码
 * 主题会自动判断文件进行引入
 * 使用此方式在线更新主题的时候，func.php文件的内容将不会被覆盖（手动更新仍然会覆盖）
 * 当然需要注意php的代码规范，错误代码将会引起网站严重错误！
 */
if (file_exists(get_theme_file_path('/func.php'))) {
    require_once get_theme_file_path('/func.php');
}

/**
 * Zibll SFW/NSFW 核心逻辑（分类包含版）
 * 逻辑：分类 ID 为 1, 7, 853, 860, 899 的是全年龄 (SFW)
 * 其余所有分类均为限制级 (NSFW)
 */

// 1. 列表过滤：在首页、分类、搜索页过滤内容
add_action('pre_get_posts', 'zib_custom_sfw_filter');
function zib_custom_sfw_filter($query) {
    // 仅在前端主循环生效
    if (!is_admin() && $query->is_main_query() && ($query->is_home() || $query->is_archive() || $query->is_search())) {
        
        // 定义全年龄分类 ID 数组
        $sfw_cat_ids = array(1, 7, 853, 860, 899);
        
        if (!is_user_logged_in()) {
            // 未登录用户：强制只显示全年龄分类的内容
            $query->set('category__in', $sfw_cat_ids);
        } else {
            // 已登录用户：根据 Cookie 模式决定
            $mode = isset($_COOKIE['view_mode']) ? $_COOKIE['view_mode'] : 'sfw';
            
            if ($mode == 'sfw') {
                // SFW 模式：只显示全年龄分类
                $query->set('category__in', $sfw_cat_ids);
            } elseif ($mode == 'nsfw') {
                // NSFW 模式：排除全年龄分类，显示剩下的限制级内容
                $query->set('category__not_in', $sfw_cat_ids);
            }
            // 'all' 模式：不设置过滤，显示全部内容
        }
    }
}

// 2. 详情页拦截：防止未登录用户通过链接直接访问限制级内容
add_action('template_redirect', 'zib_custom_sfw_access_control');
function zib_custom_sfw_access_control() {
    if (is_single()) {
        // 定义全年龄分类 ID 数组
        $sfw_cat_ids = array(1, 7, 853, 860, 899);
        
        // 检查当前文章是否属于上述任一全年龄分类
        $is_sfw = in_category($sfw_cat_ids);

        // 如果文章【不属于】全年龄分类，且用户【未登录】
        if (!$is_sfw && !is_user_logged_in()) {
            // 重定向到首页（也可以修改为 wp_login_url() 跳转到登录页）
            wp_redirect(home_url());
            exit;
        }
    }
}

// 3. 封装前端切换按钮 HTML
function zib_get_sfw_button_html() {
    if (!is_user_logged_in()) return '';

    $mode = isset($_COOKIE['view_mode']) ? $_COOKIE['view_mode'] : 'sfw';
    
    $names = array(
        'sfw'  => '全年龄', 
        'nsfw' => '限制级', 
        'all'  => '全部'
    );
    
    $current_text = isset($names[$mode]) ? $names[$mode] : '全年龄';

    $html = '<div class="dropdown sfw-filter-box">';
    $html .= '<a href="javascript:;" class="navbar-toggle dropdown-toggle" data-toggle="dropdown" style="width:auto; padding:0 10px; display:flex; align-items:center; height:100%;">';
    $html .= '<i class="fa fa-shield"></i><span class="sfw-text" style="margin-left:5px;">' . $current_text . '</span>';
    $html .= '</a>';
    
    $html .= '<ul class="dropdown-menu dropdown-menu-right">';
    $html .= '<li><a href="javascript:;" onclick="sfw_ajax_switch(\'sfw\', \'全年龄\')"><i class="fa fa-check-circle-o fa-fw"></i> 全年龄 (SFW)</a></li>';
    $html .= '<li><a href="javascript:;" onclick="sfw_ajax_switch(\'nsfw\', \'限制级\')"><i class="fa fa-warning fa-fw"></i> 限制级 (NSFW)</a></li>';
    $html .= '<li><a href="javascript:;" onclick="sfw_ajax_switch(\'all\', \'全部\')"><i class="fa fa-globe fa-fw"></i> 显示全部 (All)</a></li>';
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

// 未登录状态下搜索文章直接跳转到登录页（可选）
function restrict_search_to_logged_in_users() {
    // 检查是否为搜索请求且用户未登录
    if (is_search() && !is_user_logged_in()) {
        // 重定向到登录页面，登录后返回搜索页面
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
}
add_action('template_redirect', 'restrict_search_to_logged_in_users');


// 修复子比主题用户搜索权限问题
function fix_zibll_user_search_permission() {
    // 移除可能存在的权限限制钩子
    remove_action('pre_get_posts', 'zibll_restrict_user_search');
    
    // 或者添加自定义权限判断
    add_filter('zibll_search_user_capability', function($cap) {
        // 允许所有用户搜索用户（包括未登录用户）
        return 'read'; // 或者使用 false 表示无限制
    });
}
add_action('init', 'fix_zibll_user_search_permission', 20);


/**
 * 子比极致热度榜后端核心函数（SFW/NSFW 增强版）
 * 功能：处理热度榜单的 AJAX 请求，并根据分类 ID 过滤限制级内容
 */

add_action('wp_ajax_get_hot_ranking', 'ajax_zib_hot_ranking_v5');
add_action('wp_ajax_nopriv_get_hot_ranking', 'ajax_zib_hot_ranking_v5');

function ajax_zib_hot_ranking_v5() {
    global $wpdb;

    // 1. 基础参数获取
    $type     = isset($_POST['type']) ? $_POST['type'] : 'week';
    $offset   = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
    $paged    = isset($_POST['paged']) ? (int)$_POST['paged'] : 1;
    $per_page = 10;

    // 2. SFW/NSFW 核心配置
    $sfw_cat_ids = array(1, 7, 853, 860, 899); // 定义全年龄分类 ID
    
    // 确定当前用户的查看模式
    if (!is_user_logged_in()) {
        $mode = 'sfw'; // 未登录用户强制 SFW
    } else {
        $mode = isset($_COOKIE['view_mode']) ? $_COOKIE['view_mode'] : 'sfw';
    }

    // 3. 缓存键名设计 (通过 mode 隔离缓存，防止数据交叉污染)
    $cache_key = "zib_rank_v5_{$type}_off{$offset}_mode_{$mode}";
    $top_list  = get_transient($cache_key);

    if (false === $top_list) {
        // 计算时间范围
        $start = ($type == 'week') ? date('Y-m-d H:i:s', strtotime("-".($offset + 1)." week")) : date('Y-m-d H:i:s', strtotime("-".($offset + 1)." month"));
        $end   = ($type == 'week') ? date('Y-m-d H:i:s', strtotime("-$offset week")) : date('Y-m-d H:i:s', strtotime("-$offset month"));

        // SQL 查询：初步筛选出该时段内的活跃文章（取 400 条以备过滤）
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, post_title, post_date, comment_count FROM $wpdb->posts 
            WHERE post_status = 'publish' AND post_type = 'post' 
            AND post_date >= %s AND post_date <= %s 
            ORDER BY comment_count DESC LIMIT 400", $start, $end
        ));

        if (!$posts) {
            wp_send_json_error(['msg' => '暂无数据']);
        }

        $all_data = [];
        foreach ($posts as $post) {
            
            // --- 4. 核心过滤逻辑开始 ---
            // 检查该文章所属的所有分类 ID
            $post_cats = wp_get_post_categories($post->ID);
            
            // 判断是否属于“全年龄”分类（只要有一个分类在 $sfw_cat_ids 里即为全年龄）
            $is_sfw_post = false;
            foreach ($post_cats as $cat_id) {
                if (in_array($cat_id, $sfw_cat_ids)) {
                    $is_sfw_post = true;
                    break;
                }
            }

            // 根据当前模式决定是否保留该文章
            if ($mode == 'sfw') {
                if (!$is_sfw_post) continue; // 全年龄模式下，不是全年龄内容则跳过
            } elseif ($mode == 'nsfw') {
                if ($is_sfw_post) continue;  // 限制级模式下，排除掉全年龄内容
            }
            // 'all' 模式则不做任何处理，直接放行
            // --- 核心过滤逻辑结束 ---

            // 5. 获取数据指标并计算热度得分
            $views = (int)get_post_meta($post->ID, 'views', true);
            $favs  = (int)get_post_meta($post->ID, 'favorite', true);
            $coms  = (int)$post->comment_count;
            
            // 计算时间衰减系数（越老的文章分数下降越快）
            $days = max(0.5, (time() - strtotime($post->post_date)) / 86400);
            
            // 评分公式：(收藏*10 + 评论*5 + 阅读*1) / 时间系数
            $score = ($favs * 10 + $coms * 5 + $views * 1) / pow(($days + 2), 1.5);

            if ($score > 0) {
                $all_data[] = [
                    'title' => get_the_title($post->ID),
                    'link'  => get_permalink($post->ID),
                    'thumb' => zib_post_thumbnail('', '', false, $post->ID),
                    'views' => $views, 
                    'favs'  => $favs, 
                    'coms'  => $coms,
                    'score' => round($score, 1)
                ];
            }
        }

        // 6. 重新排序并保存结果
        usort($all_data, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // 只保留前 30 名
        $top_list = array_slice($all_data, 0, 30);
        
        // 设置缓存时间（建议 3600 秒即 1 小时）
        set_transient($cache_key, $top_list, 3600);
    }

    // 7. 分页处理并返回 JSON
    $total_pages  = ceil(count($top_list) / $per_page);
    $current_data = array_slice($top_list, ($paged - 1) * $per_page, $per_page);

    if (empty($current_data)) {
        wp_send_json_error(['msg' => '此模式下无更多数据']);
    } else {
        wp_send_json_success([
            'items'       => $current_data, 
            'total_pages' => $total_pages,
            'current_mode'=> $mode
        ]);
    }
}

add_filter('the_title', function($t, $id = null) {
    if (!is_admin() && in_the_loop() && get_post_type($id) == 'post') {
        if ((current_time('timestamp') - get_post_time('U', false, $id)) < 86400) {
            $svg = '<svg viewBox="0 0 1024 1024" style="width:1em;height:1em;fill:currentColor;margin-right:2px;vertical-align:-2px;"><path d="M783.5 456.3h-221L642.1 94.1c11.7-44.5-43.1-74.6-73.4-40.4L208.2 468.2c-21.3 24.1-4.2 62.4 28 62.4h221L377.6 928c-11.7 44.5 43.1 74.6 73.4 40.4l360.5-414.5c21.3-24.1 4.2-62.6-28-62.6z"></path></svg>';
            $t = '<span class="zib-new">' . $svg . '最新</span>' . $t;
        }
    }
    return $t;
}, 10, 2);


function block_qq_wechat_access() {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($ua, 'MicroMessenger') !== false || strpos($ua, 'QQ/') !== false) {
        wp_die('为了您的访问安全，请点击右上角选择“在浏览器中打开”本站。', '禁止站内访问');
    }
}
add_action('get_header', 'block_qq_wechat_access');

// 处理摸鱼办打卡 AJAX 请求
add_action('wp_ajax_moyu_checkin_final', 'moyu_checkin_final_handler');
function moyu_checkin_final_handler() {
    // 安全检查
    check_ajax_referer('moyu_nonce', '_nonce');

    $uid = get_current_user_id();
    if (!$uid) wp_send_json_error(['msg' => '请先登录']);

    $today = date('Y-m-d', current_time('timestamp'));
    
    // 检查是否已经领过
    $already = get_user_meta($uid, 'moyu_points_today', true);
    if ($already == $today) {
        wp_send_json_error(['msg' => '今天已经领过啦，不要贪心哦']);
    }

    // 计算点数
    $type = $_POST['type'];
    $points = ($type == 'fixed') ? 10 : rand(1, 20);

    // 保存到数据库
    update_user_meta($uid, 'moyu_points_today', $today);
    update_user_meta($uid, 'moyu_points_value', $points);

    // 如果是子比主题，可选：增加真实余额或积分
    // if(function_exists('zib_add_user_points')){ zib_add_user_points($uid, $points, 'moyu', '摸鱼办打卡'); }

    wp_send_json_success(['msg' => '打卡成功！']);
}