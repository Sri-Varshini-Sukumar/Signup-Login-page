<?php
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$display_name = isset($input['display_name']) ? trim($input['display_name']) : '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (:email, :password_hash)");
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->execute([':email' => $email, ':password_hash' => $password_hash]);
    $user_id = (int)$pdo->lastInsertId();

    $profileDoc = [
        'user_id' => $user_id,
        'display_name' => $display_name ?: '',
        'bio' => '',
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];
    $profilesColl->insertOne($profileDoc);

    echo json_encode(['ok' => true, 'user_id' => $user_id]);

} catch (PDOException $e) {
    // Duplicate email or other DB error
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Email may already be registered']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
