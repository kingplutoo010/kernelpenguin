<?php
session_start();
header('Content-Type: application/json');
require 'config.php';

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sendResponse($data) {
    echo json_encode($data);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action === 'logout') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf_token)) {
        sendResponse(['error' => 'Invalid CSRF token']);
    }
    session_destroy();
    sendResponse(['success' => true]);
}

if ($action === 'get_csrf_token') {
    sendResponse(['csrf_token' => generateCsrfToken()]);
}

switch ($action) {
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf_token)) {
            sendResponse(['error' => 'Invalid CSRF token']);
        }
        if (empty($username) || empty($password)) {
            sendResponse(['error' => 'Username and password required']);
        }
        try {
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                sendResponse(['error' => 'Invalid username or password']);
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            session_regenerate_id(true);
            sendResponse(['success' => true]);
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            sendResponse(['error' => 'Database error']);
        }
        break;

    case 'signup':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf_token)) {
            sendResponse(['error' => 'Invalid CSRF token']);
        }
        if (empty($username) || empty($password)) {
            sendResponse(['error' => 'Username and password required']);
        }
        if (strlen($username) < 3 || strlen($password) < 6) {
            sendResponse(['error' => 'Username must be 3+ chars, password 6+ chars']);
        }
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                sendResponse(['error' => 'Username already taken']);
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, NOW())');
            $stmt->execute([$username, $hash]);
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            session_regenerate_id(true);

            $colors = [
                '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3',
                '#cfe2f3', '#d9d2e9', '#d5a6bd', '#ffd966', '#93c47d'
            ];
            for ($i = 0; $i < 10; $i++) {
                $stmt = $pdo->prepare("INSERT INTO notes (user_id, title, content, color, note_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$user_id, "Note " . ($i + 1), "This is note " . ($i + 1), $colors[$i % count($colors)], $i]);
            }

            sendResponse(['success' => true]);
        } catch (PDOException $e) {
            error_log("Signup error: " . $e->getMessage());
            sendResponse(['error' => 'Database error']);
        }
        break;

    case 'get_notes':
        if (empty($_SESSION['user_id'])) {
            sendResponse(['error' => 'Unauthorized']);
        }
        try {
            $stmt = $pdo->prepare('SELECT id, title, content, color, note_order FROM notes WHERE user_id = ? ORDER BY note_order');
            $stmt->execute([$_SESSION['user_id']]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(['success' => true, 'notes' => $notes]);
        } catch (PDOException $e) {
            error_log("Get notes error: " . $e->getMessage());
            sendResponse(['error' => 'Database error']);
        }
        break;

    case 'save_note':
        if (empty($_SESSION['user_id'])) {
            sendResponse(['error' => 'Unauthorized']);
        }
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($csrf_token)) {
            sendResponse(['error' => 'Invalid CSRF token']);
        }
        $content = trim($_POST['content'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $color = trim($_POST['color'] ?? '#FFFFFF');
        $index = isset($_POST['index']) ? (int)$_POST['index'] : null;
        if ($content === '') {
            sendResponse(['error' => 'Note content cannot be empty']);
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id FROM notes WHERE user_id = ? ORDER BY note_order');
            $stmt->execute([$_SESSION['user_id']]);
            $userNotes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($index !== null && isset($userNotes[$index])) {
                $noteId = $userNotes[$index];
                $stmt = $pdo->prepare('UPDATE notes SET content = ?, title = ?, color = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
                $stmt->execute([$content, $title, $color, $noteId, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare('SELECT MAX(note_order) FROM notes WHERE user_id = ?');
                $stmt->execute([$_SESSION['user_id']]);
                $maxOrder = $stmt->fetchColumn();
                $note_order = is_numeric($maxOrder) ? $maxOrder + 1 : 0;
                $stmt = $pdo->prepare('INSERT INTO notes (user_id, title, content, color, note_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
                $stmt->execute([$_SESSION['user_id'], $title, $content, $color, $note_order]);
            }
            $pdo->commit();
            sendResponse(['success' => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Note save error: " . $e->getMessage());
            sendResponse(['error' => 'Failed to save note']);
        }
        break;

    default:
        sendResponse(['error' => 'Invalid action']);
}
?>