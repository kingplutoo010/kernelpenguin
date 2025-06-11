<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

try {
    $stmt = $pdo->prepare("SELECT id, title, content, color FROM notes WHERE user_id = ? ORDER BY id ASC LIMIT 20");
    $stmt->execute([$_SESSION['user_id']]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($notes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

