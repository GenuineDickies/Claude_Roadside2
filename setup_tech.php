<?php
$pdo = new PDO("mysql:host=localhost;dbname=roadside_assistance", "root", "pass");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Add technician role to users enum
$pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'dispatcher', 'manager', 'technician') NOT NULL DEFAULT 'dispatcher'");
echo "Users role enum updated\n";

// Create tech1 user
$hash = password_hash("tech123", PASSWORD_DEFAULT);
$check = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'tech1'")->fetchColumn();
if ($check == 0) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['tech1', $hash, 'mike.turner@roadrunner.com', 'technician']);
    echo "Created user: tech1\n";
} else {
    $pdo->prepare("UPDATE users SET password = ? WHERE username = ?")->execute([$hash, 'tech1']);
    echo "Updated password for tech1\n";
}

// Create technician record
$check2 = $pdo->query("SELECT COUNT(*) FROM technicians WHERE email = 'mike.turner@roadrunner.com'")->fetchColumn();
if ($check2 == 0) {
    $stmt = $pdo->prepare("INSERT INTO technicians (first_name, last_name, phone, email, specialization, status, hourly_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Mike', 'Turner', '5551234567', 'mike.turner@roadrunner.com', 'General Roadside & Mobile Repair', 'available', 65.00]);
    echo "Created technician: Mike Turner\n";
} else {
    echo "Technician Mike Turner already exists\n";
}

// List users
echo "\nUsers:\n";
$rows = $pdo->query("SELECT id, username, email, role FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  {$r['id']} | {$r['username']} | {$r['email']} | {$r['role']}\n";
}

// List technicians
echo "\nTechnicians:\n";
$rows = $pdo->query("SELECT id, first_name, last_name, phone, specialization, status FROM technicians")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  {$r['id']} | {$r['first_name']} {$r['last_name']} | {$r['phone']} | {$r['specialization']} | {$r['status']}\n";
}
