<?php
/*PhpDoc:
name:  server-tree.php
title: server-tree.php - génère pour le menu FancyTree l'arbre des couches d'un serveur
includes: [ '../newserver.inc.php' ]
functions:
doc: |
  Génère l'arbre en JSON conformément aux specs de FancyTree
journal: |
  21/11/2016:
    première version
*/
require_once '../newserver.inc.php';

// Affiche récursivement une couche
function display_layer($serverId, $layer) {
  if (!isset($layer['children'])) {
    if ($layer['name']=='ERROR')
      return ['title'=> "Serveur en erreur : $layer[title]"];
    else
      return ['title'=>$layer['title'], 'href'=>"edit.php?server=$serverId&layer=$layer[name]", 'target'=>'content'];
//    echo "<br><pre>layer="; print_r($layer); echo "</pre>";
  }
  else {
    $children = [];
    foreach ($layer['children'] as $child)
      $children[] = display_layer($serverId, $child);
    return ['title'=>$layer['title'], 'folder'=>true, 'children'=>$children];
  }
}

if (!isset($_GET['server'])) {
  header("Content-Type: text/plain; charset=utf-8");
  die(json_encode(['title'=>"ERROR"],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

$yaml = file_get_servers('../');
$server = newServer($yaml['servers'], $_GET['server'], '../');
try {
  $layerTree = $server->layerTree();
}
catch (Exception $e) {
  header("Content-Type: text/plain; charset=utf-8");
  die(json_encode(['title'=>"ERROR: ".$e->getMessage()],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}
$layers = [];
foreach ($layerTree as $layer)
  $layers[] = display_layer($_GET['server'], $layer);
header("Content-Type: text/plain; charset=utf-8");
die(json_encode($layers,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));