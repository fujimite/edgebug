<?php
define('KEYS_FILE', __DIR__ . '/keys.json');

function load_keys() {
    if (file_exists(KEYS_FILE)) {
        $content = file_get_contents(KEYS_FILE);
        $data = json_decode($content, true);
        if ($data && isset($data['keys'])) {
            return $data;
        }
    }
    return ['keys' => []];
}

function save_key($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents(KEYS_FILE, $json) === false) {
        echo "ERROR: Failed to write to keys.json\n";
        exit(1);
    }
}

echo "Enter key name: ";
$name = trim(fgets(STDIN));

if (empty($name)) {
    echo "ERROR: name cannot be empty\n";
    exit(1);
}

$api_key = bin2hex(random_bytes(32));
$key_hash = hash('sha256', $api_key);

$data = load_keys();

$data['keys'][$key_hash] = [
    'name' => $name,
    'created_at' => date('Y-m-d H:i:s')
];

save_key($data);

echo "key: " . $name . " " . date('Y-m-d H:i:s') . "\n";
echo "$api_key\n";
echo KEYS_FILE . "\n";
?>