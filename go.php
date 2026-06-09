<?php
/**
 * @Project       : Zibll子比主题 - 安全跳转中心 (重构优化版)
 * @Description   : 整合二维码识别与极简推广卡片
 */

if (
    strlen($_SERVER['REQUEST_URI']) > 384 ||
    strpos($_SERVER['REQUEST_URI'], "eval(") ||
    strpos($_SERVER['REQUEST_URI'], "base64")
) {
    @header("HTTP/1.1 414 Request-URI Too Long");
    @exit;
}

@session_start();
$t_url = !empty($_SESSION['GOLINK']) ? $_SESSION['GOLINK'] : preg_replace('/^url=(.*)$/i', '$1', $_SERVER["QUERY_STRING"]);

if (!empty($t_url)) {
    if ($t_url == base64_encode(base64_decode($t_url))) {
        $t_url = base64_decode($t_url);
    }
    $t_url = htmlspecialchars($t_url);
    preg_match('/^(http|https|thunder|qqdl|ed2k|Flashget|qbrowser):\/\//i', $t_url, $matches);
    $wiiui_title = get_bloginfo('name');
    $title = '安全中心 - ' . $wiiui_title;
    if ($matches) {
        $url = $t_url;
    } else {
        preg_match('/\./i', $t_url, $matche);
        if ($matche) {
            $url = 'http://' . $t_url;
        } else {
            $url = 'http://' . $_SERVER['HTTP_HOST'];
            $title = '参数错误...';
        }
    }
} else {
    $url = 'http://' . $_SERVER['HTTP_HOST'];
    $title = '参数缺失...';
}
$url = str_replace('&', '&', $url);

// 判断是否为网盘链接
$is_pan = false;
$pan_keywords = ['baidu.com', '139.com', '123pan', 'aliyundrive', 'lanzou', 'xunlei', 'uc.cn', 'drive.google', 'quark.cn'];
foreach ($pan_keywords as $key) {
    if (strpos($url, $key) !== false) {
        $is_pan = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="//cdn.staticfile.org/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        :root { --primary-color: #036af4; --danger-color: #ff4d4f; --bg-gray: #f7f8fa; }
        body, html { margin: 0; padding: 0; height: 100%; background: var(--bg-gray); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .flex-center { display: flex; justify-content: center; align-items: center; }
        
        .main-wrapper { min-height: 100vh; padding: 20px; box-sizing: border-box; }
        .go-card { 
            background: #fff; width: 100%; max-width: 420px; border-radius: 20px; 
            padding: 30px 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            position: relative; overflow: hidden; text-align: center;
        }

        /* Logo 优化 */
        .logo-area { height: 45px; margin-bottom: 25px; transition: transform 0.3s; }
        .logo-area img { height: 100%; width: auto; max-width: 200px; object-fit: contain; }

        /* 警告区域 */
        .warning-box {
            background: rgba(255, 77, 79, 0.05); border-radius: 12px; padding: 12px;
            color: var(--danger-color); font-size: 13px; font-weight: 500;
            margin-bottom: 20px; border: 1px solid rgba(255, 77, 79, 0.1);
        }

        /* 链接展示 */
        .url-display {
            font-size: 14px; color: #888; margin-bottom: 25px; word-break: break-all;
            padding: 10px; background: #f9f9f9; border-radius: 8px;
        }
        .url-display a { color: var(--primary-color); text-decoration: none; font-weight: 500; }

        /* 二维码增强显示 (如果是网盘) */
        .qr-section { margin-bottom: 25px; display: <?php echo $is_pan ? 'block' : 'none'; ?>; }
        .qr-container {
            width: 160px; height: 160px; margin: 0 auto 10px;
            padding: 8px; border: 1px solid #eee; border-radius: 15px; background: #fff;
        }
        .qr-container img { width: 100%; height: 100%; border-radius: 10px; }
        .qr-tip { font-size: 12px; color: #999; letter-spacing: 1px; }

        /* 推广按钮容器 */
        .promo-container {
            display: flex; gap: 12px; margin-bottom: 25px; padding-top: 15px; border-top: 1px solid #f0f0f0;
        }
        .promo-btn {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 10px 5px; background: #fff; border: 1px solid #eee;
            border-radius: 10px; text-decoration: none; color: #444; font-size: 13px;
            transition: all 0.2s; gap: 6px;
        }
        .promo-btn:hover { background: #fcfcfc; border-color: #ddd; transform: translateY(-2px); }
        .promo-btn img { width: 16px; height: 16px; }

        /* 操作按钮 */
        .action-group { display: flex; gap: 12px; }
        .btn {
            flex: 1; padding: 12px; border-radius: 12px; font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.3s; border: none; text-decoration: none;
        }
        .btn-home { background: #f0f2f5; color: #666; }
        .btn-go { background: var(--primary-color); color: #fff; box-shadow: 0 4px 12px rgba(3,106,244,0.2); }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        /* 底部版权/GID */
        .footer-info { margin-top: 20px; font-size: 11px; color: #ccc; }
    </style>
</head>
<body>

<div class="main-wrapper flex-center">
    <div class="go-card">
        <!-- Logo区 -->
        <div class="logo-area flex-center">
            <?php echo zib_get_adaptive_theme_img(_pz('logo_src'), _pz('logo_src_dark')); ?>
        </div>

        <!-- 警告区 -->
        <div class="warning-box">
            <i class="fa fa-shield"></i> 您即将离开 <?php echo $wiiui_title; ?>，注意账号安全
        </div>

        <!-- 链接展示 -->
        <div class="url-display">
            目标地址：<a href="javascript:;" onclick="location.replace('<?php echo $url; ?>')"><?php echo $url; ?></a>
        </div>

        <!-- 二维码展示 (仅网盘触发) -->
        <?php if ($is_pan): ?>
        <div class="qr-section">
            <div class="qr-container">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=<?php echo urlencode($url); ?>" alt="Scan to download">
            </div>
            <div class="qr-tip">手机扫码，极速转存</div>
        </div>
        <?php endif; ?>

        <!-- 推广功能按钮 -->
        <div class="promo-container">
            <a href="https://www.moyui.com/10627.html" class="promo-btn" target="_blank">
                <img src="https://www.moyui.com/wp-content/uploads/2026/05/20260529170702861-icons8-youtube上播放-144.png" alt="UC">
                <span>视频解压</span>
            </a>
            <a href="https://moyu.xx.kg/" class="promo-btn" target="_blank">
                <img src="https://res.oplist.org/logo/logo.svg" alt="直链">
                <span>直链下载</span>
            </a>
        </div>

        <!-- 操作区 -->
        <div class="action-group">
            <a class="btn btn-home" href="//<?php echo $_SERVER['HTTP_HOST']; ?>">返回首页</a>
            <a class="btn btn-go" onclick="location.replace('<?php echo $url; ?>')">继续访问</a>
        </div>

        <div class="footer-info">
            GID: LINK-<?php echo strtoupper(substr(md5($url), 0, 8)); ?> · 页面将在 <span id="timer">10</span>s 后自动跳转
        </div>
    </div>
</div>

<script>
    // 安全校验
    var MyHOST = new RegExp("<?php echo $_SERVER['HTTP_HOST']; ?>");
    if (!MyHOST.test(document.referrer) && document.referrer != "") {
        // 外部盗链跳转处理，可选逻辑
    }

    // 倒计时逻辑
    var timeLeft = 10;
    var timerEle = document.getElementById('timer');
    var countdown = setInterval(function(){
        timeLeft--;
        timerEle.innerText = timeLeft;
        if(timeLeft <= 0){
            clearInterval(countdown);
            // location.replace('<?php echo $url; ?>'); // 如需强制自动跳转取消注释
        }
    }, 1000);

    // 50秒自动关闭
    setTimeout(function() {
        if(window.opener) {
            window.opener = null;
            window.close();
        }
    }, 50000);
</script>

</body>
</html>