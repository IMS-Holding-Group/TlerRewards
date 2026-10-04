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
        $stmt = $pdo->prepare("SELECT * FROM applicants ORDER BY created_at");
        $stmt->execute();
        $applicants = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $applicants]);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'معرف غير صحيح']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT name, email FROM applicants WHERE id = ?");
            $stmt->execute([$id]);
            $applicant = $stmt->fetch();
            
            if (!$applicant) {
                echo json_encode(['success' => false, 'message' => 'المتقدم غير موجود']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM applicants WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'applicant_deleted', "حذف متقدم: {$applicant['name']} - {$applicant['email']}", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم حذف المتقدم بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $nationality = sanitizeInput($_POST['nationality'] ?? '');
        $instagram = sanitizeInput($_POST['instagram'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? '');
        
        if ($id <= 0 || empty($name) || empty($nationality) || empty($email) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة']);
            exit;
        }
        
        if ($age < 18) {
            echo json_encode(['success' => false, 'message' => 'العمر يجب أن يكون 18 سنة أو أكثر']);
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
            $checkEmail = $pdo->prepare("SELECT id FROM applicants WHERE email = ? AND id != ?");
            $checkEmail->execute([$email, $id]);
            if ($checkEmail->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً']);
                exit;
            }
            
            $checkPhone = $pdo->prepare("SELECT id FROM applicants WHERE phone = ? AND id != ?");
            $checkPhone->execute([$phone, $id]);
            if ($checkPhone->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'رقم الهاتف مسجل مسبقاً']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE applicants SET name = ?, age = ?, nationality = ?, instagram = ?, email = ?, phone = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $age, $nationality, $instagram, $email, $phone, $status, $id]);
            
            logActivity($pdo, $_SESSION['admin_id'], 'applicant_updated', "تحديث بيانات متقدم: $name - $email", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث البيانات بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    }
}
?>

