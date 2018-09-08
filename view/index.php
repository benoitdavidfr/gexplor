<?php
/*PhpDoc:
name: index.php
title: index.php - interface de présentation des couches pour Google
includes: [ ../servreg/servreg.inc.php ]
doc: |
  Dans un premier temps, on se limite aux couches de SigLoire (http://gexplor.fr/servreg/servreg.php/sigloire)
  exemples:
    http://view.gexplor.fr/
      Liste des serveurs
    http://view.gexplor.fr/sigloire
      Liste des couches du serveur
    http://view.gexplor.fr/sigloire/l_zonesactioncomplementaire_085
      Affichage de la fiche de la couche
journal: |
  1/4/2017 :
    première version
*/

require_once '../servreg/servreg.inc.php';

function showLayers($server, $layer=null) {
  $children = ($layer ? $layer->children() : $server->layers());
  echo "<ul>\n";
  foreach ($children as $layerId=>$layer) {
    echo "<li>$layerId -> ",$layer->title(),$layer->name(),"\n";
    showLayers($server, $layer);
  }
  echo "</ul>\n";
}

// sans paramètre server ou layer
if (!isset($_SERVER['PATH_INFO']) or !$_SERVER['PATH_INFO'] or ($_SERVER['PATH_INFO']=='/')) {
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>servers</title></head><body>\n";
  echo "<h2>Liste des serveurs</h2>\n";
  $yaml = servreg(/*$serviceType=*/'view', /*$servregPath=*/'../servreg/');
  $server = $yaml['servers']['sigloire'];
  echo "<a href='$_SERVER[SCRIPT_NAME]/sigloire'>$server[title]</a>\n";
//  echo "<pre>_SERVER="; print_r($_SERVER);
  die();
}

/* avec paramètre server
  - index.php/{server}
    - génère l'affichage de la fiche du serveur
*/
elseif (preg_match('!^/([^/]*)$!', $_SERVER['PATH_INFO'], $matches)) {
  $serverId = $matches[1];
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>server $serverId</title></head><body>\n";
  try {
    if (!($server = newServer(servreg(/*$serviceType=*/'', /*$servregPath=*/'../servreg/')['servers'][$serverId])))
      die("Server $serverId non reconnu");
//    $server->showInHtml();
    showLayers($server);
  } catch(Exception $e) {
    die("Sur le serveur $serverId: ".$e->getMessage());
  }
  die();
}

/* index.php/{server}/{layer}
*/
elseif (preg_match('!^/([^/]*)/([^/]*)$!', $_SERVER['PATH_INFO'], $matches)) {
  $serverId = $matches[1];
  $lyrName = $matches[2];
  if (!($server = newServer(servreg(/*$serviceType=*/'', /*$servregPath=*/'../servreg/')['servers'][$serverId])))
    die("Server $serverId non reconnu");
  if (!($layer = $server->layer($lyrName)))
    die("Layer \"$lyrName\" inconnue");
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>server $serverId</title></head><body>\n";
//  $layer->showInHtml();
  echo "<pre>_SERVER="; print_r($_SERVER);
}

die("OK ligne ".__LINE__);