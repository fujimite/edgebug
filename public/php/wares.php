<?php
require_once __DIR__ . '/lib/genware.php';

$repos = __DIR__ . '/resources/repos.txt';
$cache_file = __DIR__ . '/cache/wares_cache.html';
$cache = __DIR__ . '/cache';
$box_w = 76;
$cache_ttl = 16934400; //28 days

if (!is_dir($cache)) {
    mkdir($cache, 0750, true);
}

if (cache_valid($cache_file, $cache_ttl)) {
    comment("page served from cache");
    $wares = file_get_contents($cache_file);
} else {
    $wares = generate_wares($repos, $box_w);

    if (!empty($wares)) {
        if (file_put_contents($cache_file, $wares, LOCK_EX) !== false) {
            chmod($cache_file, 0640);
        } else {
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>edgebug.net: wares</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <meta name="description" content="edgebug.net" />
  <link rel="stylesheet" type="text/css" href="/css/edgebug.css" />
  <link rel="icon" href="/assets/favicon.png" type="image/png">
</head>
<body>
  <nav id="nav-bar" class="fix-top">
    <input id="nav-visibility-state" type="checkbox" hidden checked>
    <ul id="nav-list">
      <li id="hide-btn">
      <label for="nav-visibility-state">
        <a><span><span id="hide-btn-state"></span></span></a>
        </label>
      </li>
      <li><a href="/"><span>home</span></a></li>
      <li><a href="/wares"><span>wares</span></a></li>
      <li><a href="https://search.edgebug.net"><span>search</span></a></li>
      <li><a href="https://github.com/fujimite/edgebug"><span>git</span></a></li>
    </ul>
  </nav>
  <div id="content">
    <div class="items-center">
      <pre id="wares-header" style="margin-bottom: 3ch; margin-top: 9ch;">
                                                                  *
   █         ██                                           *           ▄█████▄
   ██         ██     █████        █ ██████        *         *     ▄███████  █  *
    ██         █    ██    ██     ████    ██           *     ▄████████████    
     █         █    █      ██    ██       █   ██████            ▀████████  * 
     █         █      ███████    █        █  ██    ██     ████     ▀█████   *
     █   ██    █    ███    ██    ██         ██   ███     ██  ███      ███▄    
     ██ ████   █    █    ████     ██       ███████       █      █       ██   
      ███  ██ ██    ██████  ██     ██      ██       ██   ██████     *        
            ███              ██     ██      ██     ██         ███           *
                                    ██       ██████   █        ██            
  ████████                          █                 ██       █             
         █████████████████████████                     ███  ███      ██████  
                                 ███████████████████     ████    █████       
      </pre>
      <?php 
        echo $wares; 
      ?>
    </div>
  </div>
</body>
</html>