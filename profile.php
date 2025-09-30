<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$token = null;
if ($method === 'GET') {
    $token = isset($_GET['token']) ? $_GET['token'] : null;
} else {
    $token = isset($input['token']) ? $input['token'] : null;
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$sessionKey = "session:$token";
$sessionJson = $redis->get($sessionKey);
if (!$sessionJson) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Session expired']);
    exit;
}

$session = json_decode($sessionJson, true);
$user_id = (int)$session['user_id'];

if ($method === 'GET') {
    try {
        $doc = $profilesColl->findOne(['user_id' => $user_id]);
        if (!$doc) {
            echo json_encode(['ok' => false, 'error' => 'Profile not found']);
            exit;
        }
        $docArr = json_decode(json_encode($doc), true);
        echo json_encode(['ok' => true, 'profile' => $docArr]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Server error']);
    }
    exit;
}

if ($method === 'POST') {
    $display_name = isset($input['display_name']) ? $input['display_name'] : '';
    $bio = isset($input['bio']) ? $input['bio'] : '';

    try {
        $result = $profilesColl->updateOne(
            ['user_id' => $user_id],
            ['$set' => ['display_name' => $display_name, 'bio' => $bio, 'updated_at' => new MongoDB\BSON\UTCDateTime()]],
            ['upsert' => true]
        );
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Server error']);
    }
    exit;
}
