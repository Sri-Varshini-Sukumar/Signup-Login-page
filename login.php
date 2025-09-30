<?php
require_once 'config.php';
$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid credentials']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password_hash'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Invalid email/password']);
        exit;
    }

    $user_id = (int)$row['id'];

    // Generate secure session token
    $token = bin2hex(random_bytes(32));
    $sessionKey = "session:$token";

    // Save session info in Redis (value could be JSON)
    $sessionData = json_encode([
        'user_id' => $user_id,
        'created_at' => time()
    ]);

    // Set TTL e.g. 3600 seconds
    $ttl = 3600;
    $redis->set($sessionKey, $sessionData);
    $redis->expire($sessionKey, $ttl);

    // Return token and minimal user info to client
    echo json_encode(['ok' => true, 'token' => $token, 'user_id' => $user_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
