<?php
require_once 'config.php';
require_once 'bot_protection.php';

session_start();

// إضافة headers لمنع الفهرسة
addNoIndexHeaders();

// منع البوتات (بدون وضع صارم لأن هذا طلب AJAX)
blockBots(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'اسم المستخدم وكلمة المرور مطلوبان']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ? AND role = 'admin'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_logged_in'] = true;
                
                logActivity($pdo, $user['id'], 'admin_login_success', "تسجيل دخول ناجح للأدمن: $username", getClientIP());
                
                echo json_encode(['success' => true, 'message' => 'تم تسجيل الدخول بنجاح']);
            } else {
                logActivity($pdo, null, 'admin_login_failed', "محاولة تسجيل دخول فاشلة للأدمن: $username", getClientIP());
                echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة']);
            }
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'logout') {
        if (isset($_SESSION['admin_id'])) {
            logActivity($pdo, $_SESSION['admin_id'], 'admin_logout', "تسجيل خروج الأدمن: " . $_SESSION['admin_username'], getClientIP());
        }
        
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'check_session') {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            echo json_encode(['success' => true, 'logged_in' => true, 'username' => $_SESSION['admin_username']]);
        } else {
            echo json_encode(['success' => true, 'logged_in' => false]);
        }
    }
}
?>

