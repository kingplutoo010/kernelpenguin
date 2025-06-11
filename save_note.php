<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$title = trim($data['title'] ?? '');
$content = trim($data['content'] ?? '');
$color = trim($data['color'] ?? '#fef68a');
$id = $data['id'] ?? null;

try {
    if ($id) {
        $stmt = $pdo->prepare("UPDATE notes SET title = ?, content = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $color, $id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, color) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $content, $color]);
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}