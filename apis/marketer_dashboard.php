<?php
require_once 'config.php';

session_start();

if (!isset($_SESSION['marketer_logged_in']) || $_SESSION['marketer_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

$marketer_id = $_SESSION['marketer_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'stats') {
        try {
            $clientsStmt = $pdo->prepare("SELECT COUNT(*) as total_clients FROM clients WHERE marketer_id = ?");
            $clientsStmt->execute([$marketer_id]);
            $totalClients = $clientsStmt->fetch()['total_clients'];
            
            $earningsStmt = $pdo->prepare("SELECT SUM(commission_amount) as total_earnings FROM clients WHERE marketer_id = ?");
            $earningsStmt->execute([$marketer_id]);
            $totalEarnings = $earningsStmt->fetch()['total_earnings'] ?? 0;
            
            $marketerStmt = $pdo->prepare("SELECT name, start_date FROM marketers WHERE id = ?");
            $marketerStmt->execute([$marketer_id]);
            $marketerInfo = $marketerStmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_clients' => $totalClients,
                    'total_earnings' => number_format($totalEarnings, 2),
                    'marketer_name' => $marketerInfo['name'],
                    'start_date' => $marketerInfo['start_date']
                ]
            ]);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'clients') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE marketer_id = ? ORDER BY created_at DESC");
            $stmt->execute([$marketer_id]);
            $clients = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $clients]);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_client') {
        $client_name = sanitizeInput($_POST['client_name'] ?? '');
        $order_details = sanitizeInput($_POST['order_details'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $commission_percentage = floatval($_POST['commission_percentage'] ?? 0);
        $payment_status = sanitizeInput($_POST['payment_status'] ?? 'pending');
        
        if (empty($client_name) || empty($order_details) || $amount <= 0 || $commission_percentage <= 0) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة ويجب أن تكون القيم صحيحة']);
            exit;
        }
        
        $commission_amount = ($amount * $commission_percentage) / 100;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO clients (marketer_id, client_name, order_details, amount, commission_percentage, commission_amount, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$marketer_id, $client_name, $order_details, $amount, $commission_percentage, $commission_amount, $payment_status]);
            
            logActivity($pdo, null, 'client_added', "إضافة عميل جديد بواسطة المسوق {$_SESSION['marketer_name']}: $client_name", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم إضافة العميل بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'update_client') {
        $client_id = intval($_POST['client_id'] ?? 0);
        $client_name = sanitizeInput($_POST['client_name'] ?? '');
        $order_details = sanitizeInput($_POST['order_details'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $commission_percentage = floatval($_POST['commission_percentage'] ?? 0);
        $payment_status = sanitizeInput($_POST['payment_status'] ?? 'pending');
        
        if ($client_id <= 0 || empty($client_name) || empty($order_details) || $amount <= 0 || $commission_percentage <= 0) {
            echo json_encode(['success' => false, 'message' => 'جميع الحقول مطلوبة ويجب أن تكون القيم صحيحة']);
            exit;
        }
        
        $commission_amount = ($amount * $commission_percentage) / 100;
        
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND marketer_id = ?");
            $checkStmt->execute([$client_id, $marketer_id]);
            if ($checkStmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'العميل غير موجود']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE clients SET client_name = ?, order_details = ?, amount = ?, commission_percentage = ?, commission_amount = ?, payment_status = ? WHERE id = ? AND marketer_id = ?");
            $stmt->execute([$client_name, $order_details, $amount, $commission_percentage, $commission_amount, $payment_status, $client_id, $marketer_id]);
            
            logActivity($pdo, null, 'client_updated', "تحديث بيانات عميل بواسطة المسوق {$_SESSION['marketer_name']}: $client_name", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات العميل بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    } elseif ($action === 'delete_client') {
        $client_id = intval($_POST['client_id'] ?? 0);
        
        if ($client_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'معرف العميل غير صحيح']);
            exit;
        }
        
        try {
            $checkStmt = $pdo->prepare("SELECT client_name FROM clients WHERE id = ? AND marketer_id = ?");
            $checkStmt->execute([$client_id, $marketer_id]);
            $client = $checkStmt->fetch();
            
            if (!$client) {
                echo json_encode(['success' => false, 'message' => 'العميل غير موجود']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ? AND marketer_id = ?");
            $stmt->execute([$client_id, $marketer_id]);
            
            logActivity($pdo, null, 'client_deleted', "حذف عميل بواسطة المسوق {$_SESSION['marketer_name']}: {$client['client_name']}", getClientIP());
            
            echo json_encode(['success' => true, 'message' => 'تم حذف العميل بنجاح']);
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
        }
    }
}
?>

