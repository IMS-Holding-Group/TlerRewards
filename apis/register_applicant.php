<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $name = sanitizeInput($_POST['name'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $nationality = sanitizeInput($_POST['nationality'] ?? '');
    $instagram = sanitizeInput($_POST['instagram'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');

    if (empty($name) || empty($nationality) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة باستثناء انستقرام']);
        exit;
    }

    if ($age < 13) {
        echo json_encode(['success' => false, 'message' => 'العمر يجب أن يكون 13 سنة أو أكثر']);
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

    $checkEmail = $pdo->prepare("SELECT id FROM applicants WHERE email = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقا']);
        exit;
    }

    $checkPhone = $pdo->prepare("SELECT id FROM applicants WHERE phone = ?");
    $checkPhone->execute([$phone]);
    if ($checkPhone->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'رقم الهاتف مسجل مسبقا']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO applicants (name, age, nationality, instagram, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $age, $nationality, $instagram, $email, $phone]);

    logActivity($pdo, null, 'applicant_registration', "تسجيل متقدم جديد : $name - $email", getClientIP());

    echo json_encode(['success' => true, 'message' => 'تم إرسال طلبك بنجاح، سيتم التواصل معك قريبا']);

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
}
?>