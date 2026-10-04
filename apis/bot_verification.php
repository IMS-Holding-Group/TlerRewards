<?php
/**
 * التحقق من أن المتصفح حقيقي وليس بوت
 */
require_once 'config.php';
require_once 'bot_protection.php';

session_start();

// إضافة headers لمنع الفهرسة
addNoIndexHeaders();

// منع البوتات (بدون وضع صارم لأن هذا طلب AJAX)
blockBots(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['token']) || empty($data['token'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token مطلوب']);
        exit;
    }
    
    // التحقق من البيانات المرسلة
    $token = sanitizeInput($data['token'] ?? '');
    $user_agent = sanitizeInput($data['user_agent'] ?? '');
    $screen_resolution = sanitizeInput($data['screen_resolution'] ?? '');
    $timezone = sanitizeInput($data['timezone'] ?? '');
    $language = sanitizeInput($data['language'] ?? '');
    $platform = sanitizeInput($data['platform'] ?? '');
    
    // التحقق من أن البيانات موجودة ومنطقية
    if (empty($user_agent) || empty($screen_resolution) || empty($timezone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
        exit;
    }
    
    // حفظ token في session للتحقق لاحقاً
    $_SESSION['browser_token'] = $token;
    $_SESSION['browser_verified'] = true;
    $_SESSION['browser_info'] = [
        'user_agent' => $user_agent,
        'screen_resolution' => $screen_resolution,
        'timezone' => $timezone,
        'language' => $language,
        'platform' => $platform,
        'verified_at' => date('Y-m-d H:i:s')
    ];
    
    // تسجيل التحقق
    logActivity($pdo, $_SESSION['admin_id'] ?? null, 'browser_verification', 
        "تحقق من المتصفح: $user_agent", getClientIP());
    
    echo json_encode([
        'success' => true, 
        'message' => 'تم التحقق بنجاح',
        'token' => $token
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

