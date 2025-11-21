<?php
function gh_info($repo_name, $repo_owner) {    
    $owner = $repo_owner;
    $repo = $repo_name;
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $owner) || 
        !preg_match('/^[a-zA-Z0-9_-]+$/', $repo)) {
        return null;
    }
    
    $repo_data = gh_api_fetch("https://api.github.com/repos/$owner/$repo");
    
    if (!$repo_data) {
        return null;
    }
    
    $release_data = gh_api_fetch("https://api.github.com/repos/$owner/$repo/releases/latest");
    //if no latest, check for pre-release
    if (!$release_data) {
        $all_releases = gh_api_fetch("https://api.github.com/repos/$owner/$repo/releases");
        
        if ($all_releases && is_array($all_releases) && count($all_releases) > 0) {
            $release_data = $all_releases[0];
        }
    }
    
    return [
        'name' => $repo,
        'description' => $repo_data['description'] ?? 'No description available.',
        'url' => 'https://github.com/'.$owner.'/'.$repo,
        'release' => $release_data
    ];
}

function gh_api_fetch($url) {
    if (!preg_match('#^https://api\.github\.com/#', $url)) {
        return null;
    }
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Script');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json'
        ]);
        
        $response = curl_exec($ch);
        $http_stat = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        if ($response === false) {
            return null;
        }
        
        if ($http_stat >= 400) {
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    if (!ini_get('allow_url_fopen')) {
        return null;
    }
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: edgebug.net PHP worker',
                'Accept: application/vnd.github.v3+json'
            ],
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        if ($error) {
        }
        return null;
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $data;
}

function digest_sha($owner, $repo, $asset_id) {
    $asset_data = gh_api_fetch("https://api.github.com/repos/$owner/$repo/releases/assets/$asset_id");
    
    if (!$asset_data) {
        return null;
    }
    
    if (isset($asset_data['digest']) && !empty($asset_data['digest'])) {
        if (preg_match('/^sha256:([a-f0-9]{64})$/i', $asset_data['digest'], $matches)) {
            return strtolower($matches[1]);
        }
    }

    return null;
}

function wrap_text($text, $width) {
    $text = strip_tags($text);
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    $words = explode(' ', $text);
    $lines = [];
    $cur_line = '';
    
    foreach ($words as $word) {
        if (strlen($cur_line . ' ' . $word) <= $width) {
            $cur_line .= ($cur_line ? ' ' : '') . $word;
        } else {
            if ($cur_line) {
                $lines[] = $cur_line;
            }
            $cur_line = $word;
        }
    }
    
    if ($cur_line) {
        $lines[] = $cur_line;
    }
    
    return $lines;
}

function box_line($content, $width) {
    $len = function_exists('mb_strlen') ? 
        mb_strlen(strip_tags($content), 'UTF-8') : 
        strlen(strip_tags($content));
    $padding = $width - $len;
    return '║ ' . $content . str_repeat(' ', max(0, $padding)) . ' ║';
}

function build_ware_box($repo_name, $repo_owner, $box_w) {
    $output = '<pre class="ware-pane">';

    $repo_name = htmlspecialchars($repo_name, ENT_QUOTES, 'UTF-8');
    
    $info = gh_info($repo_name, $repo_owner);
    
    if (!$info) {
        return "<!-- error: Could not fetch data for $repo_owner,$repo_name -->\n";
    }
    
    $title_str = " {$info['name']} ";
    $title_len = function_exists('mb_strlen') ? 
        mb_strlen($title_str, 'UTF-8') : 
        strlen($title_str);
    $w_count = $box_w - $title_len;
    $w_left = intval($w_count / 2);
    $w_right = $w_count - $w_left;
    
    //title
    $output .= '╔' . str_repeat('═', $w_left)
    . '-' . htmlspecialchars($title_str, ENT_QUOTES, 'UTF-8') . '-'
    . str_repeat('═', $w_right) . '╗' . "\n";
    
    //description
    $desc_lines = wrap_text($info['description'], $box_w);
    foreach ($desc_lines as $line) {
        $output .= box_line($line, $box_w) . "\n";
    }
    
    //downloads
    if ($info['release']) {
        $release = $info['release'];
        $version = htmlspecialchars($release['tag_name'], ENT_QUOTES, 'UTF-8');
        $prerelease_status = $release['prerelease'] ? ' (pre-release)' : '';
        
        $output .= box_line('', $box_w) . "\n";
        $output .= box_line("download ver $version$prerelease_status:", $box_w) . "\n";
        
        //binary downloads
        if (!empty($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (!isset($asset['name']) || !isset($asset['browser_download_url'])) {
                    continue;
                }
                
                $name = htmlspecialchars($asset['name'], ENT_QUOTES, 'UTF-8');
                $download_url = filter_var($asset['browser_download_url'], FILTER_VALIDATE_URL);
                
                if (!$download_url) {
                    continue;
                }
                
                if (!preg_match('#^https://github\.com/#', $download_url)) {
                    continue;
                }

                $escaped_url = htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8');
                $link = "<a href=\"$escaped_url\">$name</a>";
                $output .= box_line("- $link", $box_w) . "\n";

                if (isset($asset['id'])) {
                    $sha256 = digest_sha($repo_owner, $repo_name, $asset['id']);
                    if ($sha256) {
                        $output .= box_line("  sha256: $sha256", $box_w) . "\n";
                    }
                }
            }
        }
        
        //source downloads
        if (isset($release['zipball_url']) && isset($release['tarball_url'])) {
            $output .= box_line('', $box_w) . "\n";
            $output .= box_line("source code:", $box_w) . "\n";
            
            $zip_url = htmlspecialchars($release['zipball_url'], ENT_QUOTES, 'UTF-8');
            $tar_url = htmlspecialchars($release['tarball_url'], ENT_QUOTES, 'UTF-8');
            
            $output .= box_line("- <a href=\"$zip_url\">zip</a>", $box_w) . "\n";
            $output .= box_line("- <a href=\"$tar_url\">tar.gz</a>", $box_w) . "\n";
        }
    }

    //github repo link
    $output .= box_line('', $box_w) . "\n";
    $escaped_url = htmlspecialchars($info['url'], ENT_QUOTES, 'UTF-8');
    $output .= box_line("source: <a href=\"$escaped_url\">github</a>", $box_w) . "\n";
    
    //bottom border
    $output .= '╚' . str_repeat('═', $box_w + 2) . '╝' . "\n";
    $output .= '</pre>';

    return $output;
}

function generate_wares($repos, $box_w) {
    $repos_file_path = realpath($repos);
    
    $allowed_base = realpath(__DIR__ . '/..');
    if ($repos_file_path === false || 
        strpos($repos_file_path, $allowed_base) !== 0) {
        return "<!-- error: repo folder not found -->\n";
    }
    
    if (!file_exists($repos_file_path)) {
        return "<!-- error: repo list not found -->\n";
    }
    
    $output = '';
    $lines = file($repos_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        return "<!-- error: couldnt read repo file -->\n";
    }
    
    foreach ($lines as $line_n => $line) {
        $line = trim($line);
        
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        $parts = explode(',', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        
        $repo_owner = trim($parts[0]);
        $repo_name = trim($parts[1]);
        
        if (empty($repo_name) || strlen($repo_name) > 32) {
            continue;
        }
        
        $output .= build_ware_box($repo_name, $repo_owner, $box_w);
    }
    return $output;
}
?>