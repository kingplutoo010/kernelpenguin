<?php
session_start();
header('Content-Type: application/json');
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['username'], $data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$username = trim($data['username']);
$password = $data['password'];

if (strlen($username) < 3 || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Username must be 3+ chars, password 6+ chars']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $passwordHash]);

    // Create 10 default sticky notes
    $colors = [
        '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3',
        '#cfe2f3', '#d9d2e9', '#d5a6bd', '#ffd966', '#93c47d'
    ];
    $user_id = $pdo->lastInsertId();
    for ($i = 0; $i < 10; $i++) {
        $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, color, note_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, "Note " . ($i + 1), "This is note " . ($i + 1), $colors[$i % count($colors)], $i]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Signup error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
