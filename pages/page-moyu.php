<?php
/**
 * Template Name: 摸鱼鸭-最终完美版(适配深色模式)
 */

get_header();
$uid = get_current_user_id();
$now = current_time('timestamp'); 
$today = date('Y-m-d', $now);

// --- 1. 统计数据 ---
$total_query = new WP_User_Query(array(
    'meta_key' => 'moyu_points_today',
    'meta_value' => $today,
    'count_total' => true
));
$total_participants = $total_query->get_total();

$args = array(
    'meta_query' => array(
        'relation' => 'AND',
        array('key' => 'moyu_points_today', 'value' => $today, 'compare' => '='),
    ),
    'orderby' => 'meta_value_num',
    'meta_key' => 'moyu_points_value',
    'order' => 'DESC',
    'number' => 15 
);
$user_query = new WP_User_Query($args);
$rank_users = $user_query->get_results();

$my_rank = '未上榜';
$my_points = 0;
if ($uid) {
    $my_points_raw = get_user_meta($uid, 'moyu_points_value', true);
    $my_date = get_user_meta($uid, 'moyu_points_today', true);
    if($my_date == $today) {
        $my_points = $my_points_raw;
        foreach ($rank_users as $index => $u) {
            if ($u->ID == $uid) { $my_rank = $index + 1; break; }
        }
    }
}

// --- 2. 节假日倒计时逻辑 ---
$holidays = [
    '元旦' => '2026-01-01',
    '春节' => '2026-02-17',
    '清明节' => '2026-04-05',
    '劳动节' => '2026-05-01',
    '端午节' => '2026-06-19',
    '中秋节' => '2026-09-25',
    '国庆节' => '2026-10-01',
    '跨年' => '2027-01-01',
];

$next_holiday_name = '努力工作中';
$next_holiday_days = 0;

asort($holidays);
foreach ($holidays as $name => $date) {
    $diff = ceil((strtotime($date) - strtotime(date('Y-m-d', $now))) / 86400);
    if ($diff >= 0) {
        $next_holiday_name = $name;
        $next_holiday_days = $diff;
        break;
    }
}

$day_of_week = date('w', $now); 
$days_to_weekend = (6 - $day_of_week + 7) % 7;
?>

<style>
:root {
    --moyu-gold: #FFD700;
    --moyu-silver: #C0C0C0;
    --moyu-bronze: #CD7F32;
    --moyu-primary: #ff9f43;
    --moyu-secondary: #54a0ff;
    
    /* 亮色模式变量 */
    --moyu-bg-page: #f8f9fa;
    --moyu-bg-card: #ffffff;
    --moyu-bg-item: #f9f9f9;
    --moyu-text-main: #333333;
    --moyu-text-dim: #999999;
    --moyu-border: #eeeeee;
    --moyu-header-grad: linear-gradient(135deg, #f9e36d 0%, #ffeaa7 100%);
    --moyu-header-text: #5a4b10;
    --moyu-shadow: rgba(0,0,0,0.08);
}

/* 黑夜模式兼容：支持系统级和Zibll主题级 */
@media (prefers-color-scheme: dark) {
    :root:not(.light-theme) { 
        --moyu-bg-page: #121212;
        --moyu-bg-card: #1e1e1e;
        --moyu-bg-item: #252525;
        --moyu-text-main: #e0e0e0;
        --moyu-text-dim: #888888;
        --moyu-border: #333333;
        --moyu-header-grad: linear-gradient(135deg, #3d350d 0%, #5a4b10 100%);
        --moyu-header-text: #f9e36d;
        --moyu-shadow: rgba(0,0,0,0.3);
    }
}

/* 子比主题深色模式强制覆盖 */
.dark-theme .moyu-page {
    --moyu-bg-page: #121212;
    --moyu-bg-card: #1e1e1e;
    --moyu-bg-item: #252525;
    --moyu-text-main: #e0e0e0;
    --moyu-text-dim: #888888;
    --moyu-border: #333333;
    --moyu-header-grad: linear-gradient(135deg, #3d350d 0%, #5a4b10 100%);
    --moyu-header-text: #f9e36d;
    --moyu-shadow: rgba(0,0,0,0.3);
}

.moyu-page { background: var(--moyu-bg-page); min-height: 100vh; padding: 20px 10px; font-family: -apple-system, sans-serif; transition: background 0.3s; }
.moyu-container { max-width: 600px; margin: 0 auto; background: var(--moyu-bg-card); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px var(--moyu-shadow); transition: background 0.3s; }
.moyu-header { background: var(--moyu-header-grad); padding: 30px 20px; text-align: center; }
.moyu-header h1 { margin: 0; font-size: 24px; color: var(--moyu-header-text); font-weight: 800; }
.moyu-header p { color: var(--moyu-header-text); opacity: 0.8; margin-top: 5px; }

.moyu-status-card { margin: -20px 20px 0; background: var(--moyu-bg-card); border-radius: 15px; padding: 15px; box-shadow: 0 4px 15px var(--moyu-shadow); display: flex; justify-content: space-around; border: 1px solid var(--moyu-border); position: relative; z-index: 10; }
.status-item { text-align: center; flex: 1; }
.status-item span { display: block; font-size: 12px; color: var(--moyu-text-dim); margin-bottom: 5px; }
.status-item b { font-size: 18px; color: var(--moyu-text-main); }
.status-sep { width: 1px; height: 30px; background: var(--moyu-border); align-self: center; }

.moyu-action-box { padding: 40px 20px 30px; text-align: center; border-bottom: 8px solid var(--moyu-bg-page); }
.moyu-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.moyu-btn { border: none; padding: 15px; border-radius: 12px; cursor: pointer; font-weight: bold; transition: all 0.3s; font-size: 15px; }
.btn-orange { background: var(--moyu-primary); color: #fff; box-shadow: 0 4px 14px rgba(255,159,67,0.4); }
.btn-blue { background: var(--moyu-secondary); color: #fff; box-shadow: 0 4px 14px rgba(84,160,255,0.4); }
.moyu-btn:disabled { background: var(--moyu-border) !important; color: var(--moyu-text-dim) !important; box-shadow: none !important; cursor: not-allowed; }
.btn-full { grid-column: span 2; width: 100%; }

.rank-wrap { padding: 20px; border-bottom: 8px solid var(--moyu-bg-page);}
.rank-title-row { display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center; }
.rank-title { font-size: 18px; font-weight: bold; color: var(--moyu-text-main); }
.rank-item { display: flex; align-items: center; padding: 12px 10px; border-radius: 12px; transition: background 0.2s; }
.rank-item:hover { background: var(--moyu-bg-item); }
.rank-num { width: 35px; font-size: 18px; font-weight: 800; text-align: center; }
.rank-1 .rank-num { color: var(--moyu-gold); }
.rank-2 .rank-num { color: var(--moyu-silver); }
.rank-3 .rank-num { color: var(--moyu-bronze); }
.rank-avatar-box { margin: 0 12px; }
.rank-avatar-box img { width: 44px; height: 44px; border-radius: 50%; border: 2px solid var(--moyu-border); }
.rank-info { flex: 1; }
.rank-name { font-size: 15px; font-weight: 600; color: var(--moyu-text-main); }
.rank-value { font-size: 18px; font-weight: bold; color: var(--moyu-text-main); }

.moyu-countdown-wrap { padding: 25px 20px; background: var(--moyu-bg-card); }
.countdown-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.countdown-item { background: var(--moyu-bg-item); padding: 15px; border-radius: 12px; text-align: center; border: 1px solid var(--moyu-border); }
.countdown-item .label { font-size: 13px; color: var(--moyu-text-dim); margin-bottom: 8px; display: block; }
.countdown-item .days { font-size: 24px; font-weight: 900; color: var(--moyu-text-main); }
.countdown-item .unit { font-size: 12px; color: var(--moyu-text-dim); margin-left: 4px; }

.holiday-next { background: var(--moyu-bg-card); border-color: var(--moyu-primary); border-style: dashed; }
.holiday-next .days { color: var(--moyu-primary); }
</style>

<div class="moyu-page">
    <div class="moyu-container">
        <div class="moyu-header">
            <h1>摸鱼鸭 · 每日打卡</h1>
            <p>工作再累，别忘领鸡腿</p>
        </div>

        <div class="moyu-status-card" id="moyuStatus">
            <div class="status-item"><span>今日鸡腿</span><b><?php echo $my_points; ?></b></div>
            <div class="status-sep"></div>
            <div class="status-item"><span>我的排名</span><b><?php echo $my_rank; ?></b></div>
            <div class="status-sep"></div>
            <div class="status-item"><span>参与人数</span><b><?php echo $total_participants; ?></b></div>
        </div>

        <div class="moyu-action-box" id="moyuAction">
            <?php if (!$uid): ?>
                <button class="moyu-btn btn-orange btn-full" onclick="window.location.href='/login'">请先登录</button>
            <?php elseif ($my_points > 0): ?>
                <button class="moyu-btn btn-blue btn-full" disabled>✨ 今日已领过鸡腿，明天见 ✨</button>
            <?php else: ?>
                <div class="moyu-btns">
                    <button class="moyu-btn btn-orange" onclick="ajaxCheckin('random')">🎲 随机 (1-20)</button>
                    <button class="moyu-btn btn-blue" onclick="ajaxCheckin('fixed')">🍗 稳拿 (10个)</button>
                </div>
            <?php endif; ?>
        </div>

        <div class="rank-wrap">
            <div class="rank-title-row">
                <div class="rank-title">今日手气榜</div>
                <div style="font-size:12px;color:var(--moyu-text-dim)">Top 15</div>
            </div>
            <div id="rankListContainer">
                <?php if ($rank_users): $count = 1; foreach ($rank_users as $u): 
                    $val = get_user_meta($u->ID, 'moyu_points_value', true);
                    $num_class = ($count <= 3) ? "rank-{$count}" : "rank-other";
                ?>
                    <div class="rank-item <?php echo $num_class; ?>">
                        <div class="rank-num"><?php echo $count++; ?></div>
                        <div class="rank-avatar-box"><?php echo zib_get_avatar_box($u->ID); ?></div>
                        <div class="rank-info"><span class="rank-name"><?php echo $u->display_name; ?></span></div>
                        <div class="rank-value"><?php echo $val; ?><small style="font-size:10px;opacity:0.6">个</small></div>
                    </div>
                <?php endforeach; else: ?>
                    <div style="text-align:center;padding:30px;color:var(--moyu-text-dim);">虚位以待，快来打卡</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="moyu-countdown-wrap">
            <div class="rank-title" style="margin-bottom:15px;">摸鱼倒计时</div>
            <div class="countdown-grid">
                <div class="countdown-item">
                    <span class="label">距离周六(放假)</span>
                    <span class="days"><?php echo $days_to_weekend; ?></span>
                    <span class="unit">天</span>
                </div>
                <div class="countdown-item holiday-next">
                    <span class="label">距离【<?php echo $next_holiday_name; ?>】</span>
                    <span class="days"><?php echo $next_holiday_days; ?></span>
                    <span class="unit">天</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ajaxCheckin(type) {
    const $btns = jQuery('.moyu-btn');
    if($btns.prop('disabled')) return;

    $btns.prop('disabled', true).text('同步中...');

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'moyu_checkin_final',
        type: type,
        _nonce: '<?php echo wp_create_nonce("moyu_nonce"); ?>'
    }, function(res) {
        if(res.success) {
            window.location.reload(); 
        } else {
            alert(res.data.msg || '操作失败');
            $btns.prop('disabled', false).text('重试');
        }
    }).fail(function(){
        alert('连接服务器失败');
        $btns.prop('disabled', false).text('重试');
    });
}
</script>

<?php get_footer(); ?>