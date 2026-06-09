<?php
/*
Template Name: 子比极致热度榜-无图优化版
*/
get_header(); ?>

<div class="container mt20 mb20">
    <div class="zib-widget rank-v5-card">
        <!-- 头部导航 -->
        <div class="v5-header">
            <div class="v5-title-group">
                <i class="fa fa-line-chart v5-main-icon"></i>
                <div class="v5-title-text">
                    <h2>内容风向标</h2>
                    <p>全站互动数据实时分析</p>
                </div>
            </div>
            <div class="v5-actions">
                <div class="v5-tabs">
                    <div class="v5-tab active" data-type="week">周榜</div>
                    <div class="v5-tab" data-type="month">月榜</div>
                </div>
                <select id="v5-offset" class="v5-select">
                    <option value="0">本期</option>
                    <option value="1">上期</option>
                    <option value="2">往期</option>
                </select>
            </div>
        </div>

        <!-- 榜单列表 -->
        <div id="v5-list-box" class="v5-list-container">
            <!-- 加载动画 -->
            <div class="v5-loading">正在计算实时排名...</div>
        </div>

        <!-- 分页 -->
        <div class="v5-footer text-center">
            <button id="v5-load-btn" class="v5-more-btn">显示更多排名</button>
        </div>
    </div>
</div>

<style>
/* 容器 */
.rank-v5-card { background: #fff; border-radius: 20px; padding: 30px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.03); }

/* 头部 */
.v5-header { display: flex; justify-content: space-between; align-items: flex-end; padding-bottom: 20px; border-bottom: 2px solid #f8f9fb; margin-bottom: 15px; flex-wrap: wrap; gap: 20px; }
.v5-title-group { display: flex; align-items: center; }
.v5-main-icon { width: 45px; height: 45px; background: #2997ff; color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px; box-shadow: 0 5px 15px rgba(41,151,255,0.3); }
.v5-title-text h2 { margin: 0; font-size: 20px; font-weight: 800; color: #333; }
.v5-title-text p { margin: 5px 0 0 0; font-size: 12px; color: #999; }

/* 筛选器 */
.v5-actions { display: flex; align-items: center; gap: 10px; }
.v5-tabs { background: #f0f2f5; padding: 4px; border-radius: 10px; display: flex; }
.v5-tab { padding: 6px 18px; cursor: pointer; border-radius: 8px; font-size: 13px; color: #777; font-weight: bold; transition: 0.3s; }
.v5-tab.active { background: #fff; color: #2997ff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.v5-select { border: 1px solid #eee; border-radius: 8px; height: 32px; font-size: 12px; }

/* 列表项 - 默认PC端 */
.v5-item { display: flex; align-items: center; padding: 18px; border-radius: 15px; margin-bottom: 8px; transition: 0.3s; border: 1px solid transparent; }
.v5-item:hover { background: #f9fbff; border-color: #eef5ff; transform: translateX(5px); }

/* 排名数字 */
.v5-rank-num { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 900; color: #ddd; font-style: italic; margin-right: 15px; flex-shrink: 0; }
.v5-item:nth-child(1) .v5-rank-num { color: #ffbc00; }
.v5-item:nth-child(2) .v5-rank-num { color: #adb5bd; }
.v5-item:nth-child(3) .v5-rank-num { color: #cd7f32; }

/* PC端缩略图 */
.v5-thumb { width: 100px; height: 65px; border-radius: 10px; overflow: hidden; margin-right: 20px; flex-shrink: 0; }
.v5-thumb img { width: 100%; height: 100%; object-fit: cover; }

/* 内容区域 */
.v5-main { flex: 1; min-width: 0; }
.v5-main h3 { margin: 0; font-size: 17px; font-weight: bold; }
.v5-main h3 a { color: #333; text-decoration: none !important; }
.v5-main h3 a:hover { color: #2997ff; }

/* 元数据 */
.v5-meta { margin-top: 10px; display: flex; align-items: center; gap: 15px; font-size: 12px; color: #99a2aa; }
.v5-fav-info { color: #ff4d4f; font-weight: bold; }
.v5-score-box { margin-left: auto; background: #f4f6f9; padding: 2px 10px; border-radius: 6px; font-size: 11px; color: #ccc; }

/* 更多按钮 */
.v5-more-btn { background: #2997ff; color: #fff; border: none; padding: 12px 45px; border-radius: 30px; font-weight: bold; margin-top: 20px; cursor: pointer; transition: 0.3s; }
.v5-more-btn:hover { background: #007bff; transform: scale(1.05); }

/* ----------------- 移动端适配 ----------------- */
@media (max-width: 768px) {
    .rank-v5-card { padding: 15px; }
    .v5-header { flex-direction: column; align-items: center; text-align: center; }
    .v5-thumb { display: none !important; }
    .v5-item { padding: 12px 5px; border-bottom: 1px solid #f5f5f5; border-radius: 0; }
    .v5-item:hover { transform: none; background: none; }
    .v5-rank-num { width: 25px; margin-right: 12px; font-size: 16px; }
    .v5-main h3 { font-size: 16px; line-height: 1.5; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .v5-score-box { display: none; }
    .v5-meta { gap: 12px; }
}

.v5-loading { padding: 50px; text-align: center; color: #bbb; }

/* ----------------- 深色模式适配 ----------------- */
.dark-theme .rank-v5-card { background: #262a30; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
.dark-theme .v5-header { border-bottom-color: #333943; }
.dark-theme .v5-title-text h2 { color: #eee; }
.dark-theme .v5-title-text p { color: #888; }
.dark-theme .v5-tabs { background: #1b1e22; }
.dark-theme .v5-tab { color: #888; }
.dark-theme .v5-tab.active { background: #333943; color: #2997ff; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
.dark-theme .v5-select { background: #1b1e22; color: #ccc; border-color: #333943; }
.dark-theme .v5-item:hover { background: #2d3239; border-color: #3d444d; }
.dark-theme .v5-main h3 a { color: #ccc; }
.dark-theme .v5-main h3 a:hover { color: #2997ff; }
.dark-theme .v5-score-box { background: #333943; color: #777; }
.dark-theme .v5-rank-num { color: #444; }
.dark-theme .v5-loading { color: #666; }
@media (max-width: 768px) {
    .dark-theme .v5-item { border-bottom-color: #333943; }
}
</style>

<script>
jQuery(document).ready(function($) {
    let type = 'week', offset = 0, paged = 1;
    const ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';

    function loadRank(more = false) {
        if (!more) {
            paged = 1;
            $('#v5-list-box').html('<div class="v5-loading"><i class="fa fa-spinner fa-spin"></i> 正在排位中...</div>');
        }
        $('#v5-load-btn').text('努力加载中...').prop('disabled', true);

        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: { action: 'get_hot_ranking', type: type, offset: offset, paged: paged },
            success: function(res) {
                if (res.success) {
                    let html = '';
                    res.data.items.forEach((item, i) => {
                        let rank = ((paged - 1) * 10) + (i + 1);
                        html += `
                        <div class="v5-item">
                            <div class="v5-rank-num">${rank}</div>
                            <div class="v5-thumb">${item.thumb}</div>
                            <div class="v5-main">
                                <h3><a href="${item.link}">${item.title}</a></h3>
                                <div class="v5-meta">
                                    <span class="v5-fav-info"><i class="fa fa-star"></i> ${item.favs} 收藏</span>
                                    <span><i class="fa fa-commenting-o"></i> ${item.coms}</span>
                                    <span><i class="fa fa-eye"></i> ${item.views}</span>
                                    <div class="v5-score-box">HEAT ${item.score}</div>
                                </div>
                            </div>
                        </div>`;
                    });
                    if (more) $('#v5-list-box').append(html);
                    else $('#v5-list-box').html(html);

                    if (paged >= res.data.total_pages || paged >= 3) $('#v5-load-btn').hide();
                    else $('#v5-load-btn').show().text('显示更多排名').prop('disabled', false);
                } else {
                    if (!more) $('#v5-list-box').html('<div class="v5-loading">暂无热门内容</div>');
                    $('#v5-load-btn').hide();
                }
            }
        });
    }

    $('.v5-tab').click(function() {
        $('.v5-tab').removeClass('active');
        $(this).addClass('active');
        type = $(this).data('type');
        loadRank();
    });

    $('#v5-offset').change(function() {
        offset = $(this).val();
        loadRank();
    });

    $('#v5-load-btn').click(function() {
        paged++;
        loadRank(true);
    });

    loadRank();
});
</script>

<?php get_footer(); ?>