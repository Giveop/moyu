<?php

// 子比主题适配 Resend 免插件代码
add_action('phpmailer_init', 'zibll_resend_smtp_config');
function zibll_resend_smtp_config($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.resend.com';            // Resend SMTP地址
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = 465;                          // 端口
    $phpmailer->Username   = 'resend';                     // 强制用户名，绕过界面邮箱格式限制
    $phpmailer->Password   = 're_AEqkQUAj_2UtS6ou2NiTMjwG4TTpywNB3';               // 这里填入你的 Resend API Key
    $phpmailer->SMTPSecure = 'ssl';                        // 加密方式
    $phpmailer->From       = 'moyu@ruyi.dpdns.org';    // 必须是你Resend验证过的发件域名
    $phpmailer->FromName   = '摸鱼茶话会';                // 发件人显示名称
}

/**
 * 子比主题：指定标签文章自动扣除积分 (极简限制版)
 * 针对标签ID：842, 848
 */
add_action('template_redirect', 'zib_custom_deduct_points_by_tags', 30);
function zib_custom_deduct_points_by_tags() {
    // 1. 仅对单篇文章生效
    if (!is_singular('post')) return;

    $target_tags = array(842, 848); // 目标标签ID
    $post_id = get_the_ID();

    // 2. 检查文章是否属于指定标签
    if (has_tag($target_tags, $post_id)) {
        
        $user_id = get_current_user_id();
        
        // 管理员及作者免扣分（如果未登录 $user_id 为 0，不会触发此处）
        if ($user_id && (current_user_can('manage_options') || $user_id == get_post_field('post_author', $post_id))) {
            return;
        }

        // 3. 检查是否已经扣过费
        $paid_list = $user_id ? get_user_meta($user_id, 'zib_tag_paid_post_ids', true) : array();
        if (!is_array($paid_list)) $paid_list = array();

        if (!in_array($post_id, $paid_list)) {
            $cost_points = 2; // 扣除2积分
            
            // 获取用户积分（未登录则为0）
            $user_points = $user_id ? (int)get_user_meta($user_id, 'points', true) : 0;

            if ($user_points < $cost_points) {
                // --- 积分不足：直接展示无按钮提示页 ---
                zib_custom_no_button_notice($cost_points);
                exit;
            } else {
                // 积分足够：执行扣费
                $desc = '阅读文章《' . get_the_title($post_id) . '》自动扣积分';
                
                if (function_exists('zib_update_user_points')) {
                    zib_update_user_points($user_id, -$cost_points, 'pay', $desc);
                } else {
                    update_user_meta($user_id, 'points', ($user_points - $cost_points));
                }

                // 记录已支付状态
                $paid_list[] = $post_id;
                update_user_meta($user_id, 'zib_tag_paid_post_ids', $paid_list);
            }
        }
    }
}

/**
 * 无按钮提示页面
 */
function zib_custom_no_button_notice($cost) {
    get_header(); 
    ?>
    <div class="container" style="padding: 100px 20px;">
        <div class="box-body" style="max-width: 450px; margin: 0 auto; text-align: center; border-radius: 12px; background: var(--main-bg-color); border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); padding: 60px 20px;">
            <i class="fa fa-lock" style="font-size: 60px; color: #ff4d4f; margin-bottom: 25px; display: block;"></i>
            <h3 style="font-weight: bold; margin-bottom: 15px;">权限受限</h3>
            <p style="color: #666; font-size: 16px; margin: 0;">阅读本文需要扣除 <b><?php echo $cost; ?></b> 积分</p>
            <p style="color: #999; font-size: 13px; margin-top: 10px;">您的积分余额不足，请获取积分后再试</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "积分不足",
                    text: "阅读本文需扣除 <?php echo $cost; ?> 积分",
                    type: "error",
                    showConfirmButton: false, // 去掉确认按钮
                    allowOutsideClick: false, // 禁止点击背景关闭
                    allowEscapeKey: false    // 禁止按Esc关闭
                });
            }
        };
    </script>
    <?php
    get_footer();
}


add_filter('author_header_drop_lists', 'custom_zib_author_privacy_toggle_btn', 20, 2);
function custom_zib_author_privacy_toggle_btn($lists, $author_id) {
    if (get_current_user_id() !== $author_id) {
        return $lists;
    }
    $is_private = get_user_meta($author_id, 'profile_is_private', true);
    $text = $is_private ? '设为公开' : '设为私密';
    $icon = $is_private ? '<i class="fa fa-eye"></i>' : '<i class="fa fa-eye-slash"></i>';
    $lists .= '<li><a href="javascript:;" id="toggle-profile-privacy" data-user-id="' . esc_attr($author_id) . '">' . $icon . ' <span class="privacy-text">' . $text . '</span></a></li>';

    return $lists;
}

add_action('wp_ajax_toggle_profile_privacy', 'custom_zib_handle_privacy_toggle');
function custom_zib_handle_privacy_toggle() {
    if (!is_user_logged_in()) {
        wp_send_json_error('请先登录');
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $current_user_id = get_current_user_id();
    if ($user_id !== $current_user_id) {
        wp_send_json_error('无权操作');
    }

    $is_private = get_user_meta($user_id, 'profile_is_private', true);
    
    if ($is_private) {
        delete_user_meta($user_id, 'profile_is_private'); // 设为公开
        wp_send_json_success(array(
            'text' => '设为私密', 
            'icon' => '<i class="fa fa-eye-slash"></i>',
            'msg'  => '您的主页已设为公开展示'
        ));
    } else {
        update_user_meta($user_id, 'profile_is_private', '1'); // 设为私密
        wp_send_json_success(array(
            'text' => '设为公开', 
            'icon' => '<i class="fa fa-eye"></i>',
            'msg'  => '您的主页已设为私密状态'
        ));
    }
}

add_action('wp_footer', 'custom_zib_privacy_toggle_js');
function custom_zib_privacy_toggle_js() {
    if (!is_author()) return; 
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('body').on('click', '#toggle-profile-privacy', function() {
            var btn = $(this);
            var user_id = btn.data('user-id');
            var textSpan = btn.find('.privacy-text');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'toggle_profile_privacy',
                    user_id: user_id
                },
                success: function(res) {
                    if (res.success) {
                        textSpan.text(res.data.text);
                        btn.find('i').replaceWith(res.data.icon);
                        if (typeof notyf !== 'undefined') {
                            notyf.success(res.data.msg);
                        } else if (typeof layer !== 'undefined') {
                            layer.msg(res.data.msg);
                        } else {
                            alert(res.data.msg);
                        }
                    } else {
                        alert(res.data);
                    }
                },
                error: function() {
                    alert('网络请求失败，请稍后重试');
                }
            });
        });
    });
    </script>
    <?php
}

add_action('template_redirect', 'custom_zib_enforce_profile_privacy', 1);
function custom_zib_enforce_profile_privacy() {
    if (is_author()) {
        $author_id = get_queried_object_id();
        $is_private = get_user_meta($author_id, 'profile_is_private', true);
    }
}


add_filter('pre_comment_user_ip', function(){
    return '127.0.0.1';
});
if (isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}