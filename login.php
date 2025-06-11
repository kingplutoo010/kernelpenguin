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

try {
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect username or password']);
    }
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
