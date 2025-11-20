<?php
define('KEYS_FILE', __DIR__ . '/../../../keys.json');

function gatekeeper($key) {
    if (empty($key) || !is_string($key)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid key provided']);
        exit;
    }
    
    if (!file_exists(KEYS_FILE)) {
        http_response_code(500);
        echo json_encode(['error' => 'the gatekeeper is asleep']);
        exit;
    }
    
    $config = json_decode(file_get_contents(KEYS_FILE), true);
    if (!$config || !isset($config['keys'])) {
        http_response_code(500);
        echo json_encode(['error' => 'the gatekeeper is confused']);
        exit;
    }
    
    $kh = hash('sha256', $key);
    
    if (isset($config['keys'][$kh])) {
        return true;
    }
    
    http_response_code(401);
    echo json_encode(['error' => 'you try your key, but the gate remains closed.']);
    exit;
}
?>