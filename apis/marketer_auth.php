<?php
require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $login = sanitizeInput($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($login) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'اسم المستخدم وكلمة المرور مطلوبان']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, phone, password_hash, is_active FROM marketers WHERE (email = ? OR phone = ?) AND is_active = 1");
            $stmt->execute([$login, $login]);
            $marketer = $stmt->fetch();
            
            if ($marketer && password_verify($password, $marketer['password_hash'])) {
                $_SESSION['marketer_id'] = $marketer['id'];
                $_SESSION['marketer_name'] = $marketer['name'];
                $_SESSION['marketer_email'] = $marketer['email'];
                $_SESSION['marketer_logged_in'] = true;
                
                logActivity($pdo, null, 'marketer_login_success', "تسجيل دخول ناجح للمسوق: {$marketer['name']} - {$marketer['email']}", getClientIP());
                
                echo json_encode(['success' => true, 'message' => 'تم تسجيل الدخول بنجاح']);
            } else {
                logActivity($pdo, null, 'marketer_login_failed', "محاولة تسجيل دخول فاشلة للمسوق: $login", getClientIP());
                echo json_encode(['success' => false, 'message' => 'بيانات الدخول غير صحيحة أو الحساب غير مفعل']);
            }
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'logout') {
        if (isset($_SESSION['marketer_id'])) {
            logActivity($pdo, null, 'marketer_logout', "تسجيل خروج المسوق: " . $_SESSION['marketer_name'], getClientIP());
        }
        
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'check_session') {
        if (isset($_SESSION['marketer_logged_in']) && $_SESSION['marketer_logged_in'] === true) {
            echo json_encode([
                'success' => true, 
                'logged_in' => true, 
                'marketer' => [
                    'id' => $_SESSION['marketer_id'],
                    'name' => $_SESSION['marketer_name'],
                    'email' => $_SESSION['marketer_email']
                ]
            ]);
        } else {
            echo json_encode(['success' => true, 'logged_in' => false]);
        }
    }
}
?>

