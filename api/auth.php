<?php
require_once __DIR__ . '/config.php';

$input = readJsonInput();
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $fullName = trim($input['full_name'] ?? $input['fullName'] ?? '');
        $userId = trim($input['user_id'] ?? $input['userId'] ?? '');

        if (!$email || !$password || !$fullName || !$userId) {
            sendJson(['success' => false, 'error' => 'Please fill all fields'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJson(['success' => false, 'error' => 'Please enter a valid email address'], 400);
        }

        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR user_id = ?');
        $stmt->bind_param('ss', $email, $userId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            sendJson(['success' => false, 'error' => 'User already exists'], 409);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare('INSERT INTO users (full_name, email, user_id, password_hash, created_at) VALUES (?, ?, ?, ?, NOW())');
        $insertStmt->bind_param('ssss', $fullName, $email, $userId, $passwordHash);
        $insertStmt->execute();

        $newUserId = $insertStmt->insert_id;
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;

        sendJson([
            'success' => true,
            'user' => [
                'id' => (int) $newUserId,
                'full_name' => $fullName,
                'email' => $email,
                'user_id' => $userId
            ]
        ]);
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $identifier = trim($input['email'] ?? $input['user_id'] ?? $input['userId'] ?? '');
        $password = $input['password'] ?? '';

        if (!$identifier || !$password) {
            sendJson(['success' => false, 'error' => 'Enter credentials'], 400);
        }

        $stmt = $conn->prepare('SELECT id, full_name, email, user_id, password_hash FROM users WHERE email = ? OR user_id = ? LIMIT 1');
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            sendJson(['success' => false, 'error' => 'Invalid credentials'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];

        sendJson([
            'success' => true,
            'user' => [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'user_id' => $user['user_id']
            ]
        ]);
        break;

    case 'logout':
        session_destroy();
        sendJson(['success' => true]);
        break;

    case 'me':
        $userId = getCurrentUserId($conn);
        if (!$userId) {
            sendJson(['user' => null]);
        }

        $stmt = $conn->prepare('SELECT id, full_name, email, user_id FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        sendJson([
            'user' => $user ? [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'user_id' => $user['user_id']
            ] : null
        ]);
        break;

    case 'reset':
        $email = trim($input['email'] ?? '');
        if (!$email) {
            sendJson(['success' => false, 'error' => 'Please enter your email'], 400);
        }
        sendJson(['success' => true, 'message' => 'Password reset is ready for your MySQL-backed server configuration.']);
        break;

    default:
        sendJson(['success' => false, 'error' => 'Unsupported action'], 400);
}
