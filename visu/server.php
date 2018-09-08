<?php
/*PhpDoc:
name:  server.php
title: server.php - consultation de la liste des serveurs et leurs capacités
includes: [ newserver.inc.php ]
doc: |
journal: |
  5/11/2016:
    Ajout de définition de la legende dans le fichier de configuration
  2/11/2016:
    remplacement du theme par class
  29/10/2016:
    améliorations
  28/10/2016:
    première version
*/
require_once 'newserver.inc.php';

// Affichage récursif de la classification et des serveurs rattachés
function display_tree($tree, &$servers, $level=0) {
//  echo "<pre>tree="; print_r($tree); echo "</pre>\n";
  foreach ($tree as $classId => $class) {
    echo "<li><b>",htmlspecialchars($class['title'], ENT_COMPAT|ENT_HTML401,'UTF-8'),"</b><ul>\n";
    foreach ($servers as $id => $server)
      if ($server['class']==$classId) {
        echo "<li><a href='?action=showResources&amp;server=$id'>$server[title] ($server[protocol])</a>\n";
        unset($servers[$id]);
      }
    if (isset($class['children']) and $class['children'])
      display_tree($class['children'], $servers, $level+1);
    echo "</ul>\n";
  }
// Liste des serveurs qui n'ont pas été affichés précédemment
  if (!$level and $servers) {
    echo "<li><b>Autres</b><ul>\n";
    foreach ($servers as $id => $server)
      echo "<li><a href='?action=showResources&amp;server=$id'>$server[title] ($server[protocol])</a>\n";
    echo "</ul>\n";
  }
}

$yaml = file_get_servers();
  
// Affichage de la liste des serveurs
if (!isset($_GET['server'])) {
  echo "<html><head><title>server</title><meta charset='UTF-8'></head><body>\n";
  echo "<h2>Liste des serveurs</h2>\n",
       "Les serveurs sont organisés selon une classification hiérarchique indiquée en gras.</p>\n";
  display_tree($yaml['classification'], $yaml['servers']);
  die();
}

$server = newServer($yaml['servers'], $_GET['server']);
//  echo "<pre>"; print_r($server); echo "</pre>\n";
 
switch ($_GET['action']) {
// Affichage des couches ou de la configuration du serveur
  case 'showResources':
  case 'showConf':
    echo "<html><head><title>server</title><meta charset='UTF-8'></head><body>\n";
//  echo "<pre>"; print_r($yaml['servers']); echo "</pre>\n";
    $server->$_GET['action']();
    die();
    
// Affichage des Pyramides d'un serveur WMTS
  case 'showTileMatrixSets':
    $server->showTileMatrixSets();
    die();
    
// Affichage des capacités simplifiés du serveur en XML
  case 'showCapInXml':
    $server->showCapInXml();
    die();
    
// Affichage des capacités simplifiés du serveur en JSON
  case 'showCapInJson':
    $server->showCapInJson();
    die();
    
// Affichage HTML des capacités ou de la configuration de la couche
  case 'showLayerInHtml':
  case 'showLayerConf':
    echo "<html><head><title>server</title><meta charset='UTF-8'></head><body>\n";
    $layer = $server->getLayerById($_GET['layer']);
    $layer->$_GET['action']($_GET['layer']);
    die();
    
// Affichage XML des capacités simplifiées de la couche
  case 'showLayerInXml':
    $layer = $server->getLayerById($_GET['layer']);
    $layer->showLayerInXml($_GET['layer']);
    die();
    
// Affichage JSON des capacités simplifiées de la couche
  case 'showLayerInJson':
    $layer = $server->getLayerById($_GET['layer']);
    $layer->showLayerInJson($_GET['layer']);
    die();
}
echo "Erreur: Action '$_GET[action]' non reconnue<br>\n";
