<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'affiliate_marketing_trsi';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8 COLLATE utf8_general_ci");
    echo "Database created successfully\n";
    
    $pdo->exec("USE $dbname");
    
    $sql = file_get_contents('database_schema.sql');
    $pdo->exec($sql);
    echo "Tables created successfully\n";
    
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
    $stmt->execute(['admin', $adminPassword, 'admin']);
    echo "Admin user created/updated successfully\n";
    
    echo "Database setup completed!\n";
    echo "Admin login: username = admin, password = admin123\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

