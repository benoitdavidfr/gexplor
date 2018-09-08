<?php
/*PhpDoc:
name:  proxydef.inc.php
title: proxydef.inc.php - définition du proxy en fonction du lieu d'exécution + timeout
doc: |
journal: |
  1/4/2017:
    Re-affectation du timeout
    permet d'effectuer un getcap.php sur sigloire et georhonealpes depuis alwaysdata
  9/3/2017:
    Première version
*/
// Proxy au MEDDE
if (is_dir('D:/users/benoit.david'))
  $http_context_options = [
    'proxy' => 'tcp://proxy-rie.ac.i2:8080',
    'request_fulluri' => True, // indispensable pour le proxy du MEDDE, n'est mis qu'en cas d'utilisation du proxy
    'timeout' => 5*60, // 5 min.
  ];
else
  $http_context_options = [
    'timeout' => 5*60, // 5 min.
//    'timeout' => 30, // 30"
  ];
$stream_context = stream_context_create(['http'=>$http_context_options, 'https'=>$http_context_options]);
