<?php
/**
 * حماية صارمة من البوتات والفهرسة
 * منع وصول البوتات إلى صفحات الإدارة بشكل كامل
 */

// قائمة User-Agents للبوتات المشهورة (أنماط محددة فقط)
$bot_patterns = [
    // محركات البحث
    'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider', 
    'yandexbot', 'sogou', 'exabot', 'facebot', 'ia_archiver',
    
    // أدوات HTTP (أنماط محددة فقط)
    '^curl/', '^wget', '^python-requests', '^java/', '^perl', '^ruby',
    'libwww-perl', 'postman', 'insomnia',
    
    // البوتات والمشغلات التلقائية (أنماط محددة)
    '/bot', 'bot/', 'crawler', 'spider', 'scraper', 'harvest', 
    'monitor', 'scanner', 'indexer', 'aggregator'
];

/**
 * التحقق من أن الطلب من متصفح حقيقي وليس بوت
 */
function isBotRequest() {
    global $bot_patterns;
    
    $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $referer = strtolower($_SERVER['HTTP_REFERER'] ?? '');
    
    // التحقق من User-Agent
    if (empty($user_agent)) {
        return true; // User-Agent فارغ = بوت محتمل
    }
    
    // التحقق من الأنماط
    foreach ($bot_patterns as $pattern) {
        // إذا كان النمط يبدأ بـ ^، نتحقق من بداية السلسلة
        if (strpos($pattern, '^') === 0) {
            $clean_pattern = substr($pattern, 1);
            if (strpos($user_agent, $clean_pattern) === 0) {
                return true;
            }
        } else {
            // خلاف ذلك، نستخدم البحث البسيط
            if (strpos($user_agent, $pattern) !== false) {
                return true;
            }
        }
    }
    
    // التحقق من Referer فقط إذا كان من محرك بحث مباشر (وليس من نتائج البحث)
    // نكون أكثر دقة - نتحقق من أن Referer يحتوي على رابط بحث فعلي
    $search_engines = ['google.com/search', 'bing.com/search', 'yahoo.com/search', 
                       'yandex.com/search', 'baidu.com/s', 'duckduckgo.com/?q'];
    foreach ($search_engines as $engine) {
        if (strpos($referer, $engine) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * التحقق من أن الطلب من متصفح حقيقي (يتطلب JavaScript)
 */
function checkBrowserCapabilities() {
    // التحقق من وجود headers مهمة للمتصفحات الحقيقية
    // نكون أكثر تساهلاً - نتحقق من وجود Accept header على الأقل
    if (!isset($_SERVER['HTTP_ACCEPT'])) {
        return false;
    }
    
    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    
    // قبول طلبات HTML (صفحات عادية)
    if (strpos($accept, 'text/html') !== false) {
        return true;
    }
    
    // قبول طلبات AJAX/JSON (من JavaScript)
    if (strpos($accept, 'application/json') !== false || strpos($accept, '*/*') !== false) {
        return true;
    }
    
    // قبول أي طلب يحتوي على Accept header (متصفحات حقيقية عادة ترسله)
    // لكن نرفض إذا كان فارغاً أو مشبوهاً
    return !empty($accept);
}

/**
 * منع البوتات من الوصول
 * @param bool $strict إذا كان true، سيتم حجب حتى الطلبات المشبوهة قليلاً
 */
function blockBots($strict = false) {
    // التحقق من البوتات الواضحة فقط
    if (isBotRequest()) {
        // تسجيل محاولة وصول بوت
        error_log("Bot access attempt blocked: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . " from IP: " . getClientIP());
        
        // إرجاع 403 Forbidden
        http_response_code(403);
        header('X-Robots-Tag: noindex, nofollow');
        die('Access Denied');
    }
    
    // في الوضع الصارم، نتحقق أيضاً من قدرات المتصفح
    if ($strict && !checkBrowserCapabilities()) {
        error_log("Suspicious request blocked (missing browser capabilities) from IP: " . getClientIP());
        http_response_code(403);
        header('X-Robots-Tag: noindex, nofollow');
        die('Access Denied');
    }
}

/**
 * إضافة headers لمنع الفهرسة
 */
function addNoIndexHeaders() {
    header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}

/**
 * التحقق من أن الطلب من نفس الموقع (CSRF Protection)
 */
function verifySameOrigin() {
    $allowed_hosts = [
        $_SERVER['HTTP_HOST'] ?? '',
        'localhost',
        '127.0.0.1'
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    
    if (!empty($origin)) {
        $parsed = parse_url($origin);
        $host = $parsed['host'] ?? '';
        
        if (!in_array($host, $allowed_hosts)) {
            return false;
        }
    }
    
    return true;
}

// استخدام الحماية في ملفات الإدارة (يتم استدعاؤها يدوياً من الملفات الأخرى)
// لا نطبق الحماية تلقائياً هنا لتجنب المشاكل مع الطلبات الشرعية
?>

