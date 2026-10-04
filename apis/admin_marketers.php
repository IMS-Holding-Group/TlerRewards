<?php
require_once 'config.php';
require_once 'bot_protection.php';

session_start();

// إضافة headers لمنع الفهرسة
addNoIndexHeaders();

// منع البوتات (بدون وضع صارم لأن هذا طلب AJAX)
blockBots(false);

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM marketers ORDER BY created_at DESC");
        $stmt->execute();
        $marketers = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $marketers]);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
        
        if (empty($name) || empty($email) || empty($phone) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
            exit;
        }
        
        if (!validateEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صحيح']);
            exit;
        }
        
        if (!validatePhone($phone)) {
            echo json_encode(['success' => false, 'message' => 'رقم الهاتف غير صحيح']);
            exit;
        }
        
        try {
            $checkEmail = $pdo->prepare("SELECT id FROM marketers WHERE email = ?");
            $checkEmail->execute([$email]);
            if ($checkEmail->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً']);
                exit;
            }
            
            $checkPhone = $pdo->prepare("SELECT id FROM marketers WHERE phone = ?");
            $checkPhone->execute([$phone]);
            if ($checkPhone->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'رقم الهاتف مسجل مسبقاً']);
                exit;
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO marketers (name, email, phone, password_hash, commission_rate, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $password_hash, $commission_rate, $payment_method]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'marketer_created', "إضافة مسوق جديد: $name - $email", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم إضافة المسوق بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
        
        if ($id <= 0 || empty($name) || empty($email) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
            exit;
        }
        
        if (!validateEmail($email)) {
            echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صحيح']);
            exit;
        }
        
        if (!validatePhone($phone)) {
            echo json_encode(['success' => false, 'message' => 'رقم الهاتف غير صحيح']);
            exit;
        }
        
        try {
            $checkEmail = $pdo->prepare("SELECT id FROM marketers WHERE email = ? AND id != ?");
            $checkEmail->execute([$email, $id]);
            if ($checkEmail->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً']);
                exit;
            }
            
            $checkPhone = $pdo->prepare("SELECT id FROM marketers WHERE phone = ? AND id != ?");
            $checkPhone->execute([$phone, $id]);
            if ($checkPhone->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'رقم الهاتف مسجل مسبقاً']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE marketers SET name = ?, email = ?, phone = ?, commission_rate = ?, payment_method = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $commission_rate, $payment_method, $id]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'marketer_updated', "تحديث بيانات مسوق: $name - $email", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث البيانات بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT name, email FROM marketers WHERE id = ?");
            $stmt->execute([$id]);
            $marketer = $stmt->fetch();
            
            if (!$marketer) {
                echo json_encode(['success' => false, 'message' => 'المسوق غير موجود']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM marketers WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'marketer_deleted', "حذف مسوق: {$marketer['name']} - {$marketer['email']}", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم حذف المسوق بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $is_active = intval($_POST['is_active'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT name, email FROM marketers WHERE id = ?");
            $stmt->execute([$id]);
            $marketer = $stmt->fetch();
            
            if (!$marketer) {
                echo json_encode(['success' => false, 'message' => 'المسوق غير موجود']);
                exit;
            }
            
            $start_date = $is_active ? date('Y-m-d') : null;
            
            $stmt = $pdo->prepare("UPDATE marketers SET is_active = ?, start_date = ? WHERE id = ?");
            $stmt->execute([$is_active, $start_date, $id]);
            
            $status_text = $is_active ? 'تفعيل' : 'إلغاء تفعيل';
            logActivity($pdo, $_SESSION['admin_id'], 'marketer_status_changed', "$status_text حساب مسوق: {$marketer['name']} - {$marketer['email']}", getClientIP());
            
            echo json_encode(['success' => true, 'message' => "تم $status_text الحساب بنجاح"]);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    }
}
?>

