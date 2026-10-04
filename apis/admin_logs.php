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
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->prepare("SELECT l.*, u.username FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.timestamp DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        $logs = $stmt->fetchAll();
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs");
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true, 
            'data' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'limit' => $limit
            ]
        ]);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام']);
    }
}
?>

