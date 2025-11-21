<?php
$cache = __DIR__ . '/../cache';

if (!is_dir($cache)) {
  mkdir($cache, 0750, true);
}

function cache_serve($cache_name, $ttl, $generator_callback) {
  global $cache;
  $cache_file = $cache . '/' . $cache_name . '.cache';

  if (cache_valid($cache_file, $ttl)) {
      echo '<!-- page served from cache ' . date('Y-m-d H:i:s', filemtime($cache_file)) . ' -->' . "\n";
      return file_get_contents($cache_file);
  } else {
      $data = $generator_callback();
      if (!empty($data)) {
          if (file_put_contents($cache_file, $data, LOCK_EX) !== false) {
              chmod($cache_file, 0640);
          }
      }
      return $data;
  }
}

function cache_valid($cache_file, $max_ttl) {
    if (!file_exists($cache_file)) {
        return false;
    }
    
    $cache_age = time() - filemtime($cache_file);
    
    return $cache_age < $max_ttl;
}
?>