<?php
// Temporary script to update admin password to 'pass'
require_once __DIR__ . '/config/database.php';
$hash = password_hash('pass', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$result = $stmt->execute([$hash]);
echo $result ? "Password updated to 'pass'\n" : "Update failed\n";
echo "Rows affected: " . $stmt->rowCount() . "\n";
// Clean up this file
unlink(__FILE__);
