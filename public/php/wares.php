<?php
require_once __DIR__ . '/lib/genware.php';
require_once __DIR__ . '/lib/cache.php';

$repos = __DIR__ . '/resources/repos.txt';
$cache_name = 'wares';
$box_w = 76;
$cache_ttl = 16934400; //28 days

$wares = cache_serve($cache_name, $cache_ttl, function() use ($repos, $box_w) {
    return generate_wares($repos, $box_w);
});

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
    <input id="nav-visibility-state" type="checkbox" hidden>
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
      <li id="info-btn">
        <input id="info-box-state" type="checkbox" hidden checked>
        <label for="info-box-state">
          <a><span>info</span></a>
        </label>
        <div id="info-box">
          <label for="info-box-state" id="close-btn">×</label>
          <pre> you are on the: 
  downloads page</pre>
        </div>
      </li>
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