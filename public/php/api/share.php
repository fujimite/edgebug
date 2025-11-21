<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

define('UPLOAD_DIR', __DIR__ . '/../../share/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); //100MB
define('MAX_TEXT_SIZE', 10 * 1024 * 1024); //10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('MAX_DIR_SIZE', 1 * 1024 * 1024 * 1024); //1GB

require_once __DIR__ . '/../lib/gatekeeper.php';

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$key = null;

if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $key = $headers['X-API-Key'] ?? $headers['X-Api-Key'] ?? $headers['x-api-key'] ?? null;
}

if (!$key) {
    $key = $_SERVER['HTTP_X_API_KEY'] ?? null;
}

if (!$key) {
    $key = $_GET['api_key'] ?? null;
}

if (!$key) {
    http_response_code(401);
    echo json_encode(['error' => 'API key required']);
    exit;
}

gatekeeper($key);

//if key is ok script continues

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'this gate only accepts postal messages.']);
    exit;
}

//key:value text
if (isset($_POST['text']) && !empty($_POST['text'])) {
    $text = $_POST['text'];

    if (strlen($text) > MAX_TEXT_SIZE) {
        http_response_code(400);
        echo json_encode(['error' => 'parchment length limit exceeded (' . MAX_TEXT_SIZE . 'B)']);
        exit;
    }

    $text = str_replace("\0", '', $text);

    $filename = randomise_filename('txt');
    $filepath = UPLOAD_DIR . $filename;
    if (!check_filepath($filepath)) exit;
    
    if (file_put_contents($filepath, $text, LOCK_EX) !== false) {
        chmod($filepath, 0644);
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'url' => get_base_url() . '/share/' . $filename,
            'filepath' => '/share/' . $filename,
            'filename' => $filename,
            'size' => strlen($text),
            'mime_type' => 'text/plain'
        ]);
        check_rm_overfill();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'failed to store the parchment']);
    }
    exit;
}

//text file
if (isset($_FILES['text']) && $_FILES['text']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['text'];

    if ($file['size'] > MAX_TEXT_SIZE) {
        http_response_code(400);
        echo json_encode(['error' => 'file size limit exceeded (' . MAX_TEXT_SIZE . 'B)']);
        exit;
    }

    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($file_ext !== 'txt') {
        http_response_code(400);
        echo json_encode(['error' => 'only txt files are allowed']);
        exit;
    }

    $filename = randomise_filename('txt');
    $filepath = UPLOAD_DIR . $filename;
    if (!check_filepath($filepath)) exit;
    
    store_file($file, $filename, $filepath, 'text/plain');
    exit;
}

//image file
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];

    if ($file['size'] > MAX_FILE_SIZE) {
        http_response_code(400);
        echo json_encode(['error' => 'image size limit exceeded (' . MAX_FILE_SIZE . 'B)']);
        exit;
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    
    if (!in_array($mime_type, ALLOWED_IMAGE_TYPES)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid media type']);
        exit;
    }
    
    $img_info = @getimagesize($file['tmp_name']);
    if ($img_info === false) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid image file']);
        exit;
    }
    
    $extension = match($mime_type) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'bin'
    };
    
    $filename = randomise_filename($extension);
    $filepath = UPLOAD_DIR . $filename;
    if (!check_filepath($filepath)) exit;
    
    store_file($file, $filename, $filepath, $mime_type);
    exit;
}

//handle errors
if ((isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) || 
    (isset($_FILES['text']) && $_FILES['text']['error'] !== UPLOAD_ERR_OK)) {

    $file_error = isset($_FILES['image']) ? $_FILES['image'] : $_FILES['text'];
    
    $err_msg = match($file_error['error']) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'file too large',
        UPLOAD_ERR_PARTIAL => 'file upload incomplete',
        UPLOAD_ERR_NO_TMP_DIR => 'server configuration error',
        UPLOAD_ERR_CANT_WRITE => 'failed to write file',
        default => 'upload error'
    };
    
    http_response_code(400);
    echo json_encode(['error' => $err_msg]);
    exit;
}

//if nothing was sent
http_response_code(400);
echo json_encode(['error' => 'your message was empty.']);

function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

function randomise_filename($extension) {
    return bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;
}

function check_filepath($filepath) {
    $r_filepath = realpath(dirname($filepath)) . '/' . basename($filepath);
    
    if (strpos($r_filepath, realpath(UPLOAD_DIR)) !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid file path']);
        exit;
    }

    return true;
}

function store_file($file, $filename, $filepath, $mime_type) {
      if (move_uploaded_file($file['tmp_name'], $filepath)) {
        chmod($filepath, 0644);
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'url' => get_base_url() . '/share/' . $filename,
            'filepath' => '/share/' . $filename,
            'filename' => $filename,
            'size' => $file['size'],
            'mime_type' => $mime_type
        ]);
        check_rm_overfill();
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'failed to save file']);
    }
}

function check_rm_overfill() {
    $total_size = 0;
    $files = [];

    foreach (glob(UPLOAD_DIR . '*') as $file) {
        if (is_file($file)) {
            $total_size += filesize($file);
            $files[] = $file;
        }
    }

    if ($total_size > MAX_DIR_SIZE) {
        usort($files, function($a, $b) {
            return filectime($a) <=> filectime($b);
        });

        if (isset($files[0])) {
            unlink($files[0]);
        }
    }
}
?>