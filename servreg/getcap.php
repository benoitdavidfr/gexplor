<?php
/*PhpDoc:
name:  getcap.php
title: getcap.php - lecture des capacités des serveurs WMTS/WMS et enregistrement dans des fichiers XML
includes: [ servreg.inc.php, proxydef.inc.php ]
doc: |
journal: |
  26/3/2017:
    utilisation de CapSummaries::addIfAbsent($server) pour accélérer quand les capacités d'un serveur ne sont pas actualisées
  25/3/2017:
    génération d'un fichier capsummaries.phpser
  11/3/2017:
    fork dans servreg
  13/2/2017:
    les serveurs de type tile sont ignorés
  13/11/2016:
    ajout du type de serveur tile
  6-7/11/2016:
    amélioration
  28/10/2016:
    première version
*/
require_once 'servreg.inc.php';
require_once 'proxydef.inc.php';
require_once 'capsummaries.inc.php';
CapSummaries::load();

header('Content-Type: text/plain; charset=UTF-8');

// définition du paramètre register en fonction du sapi
//echo "php_sapi_name=",php_sapi_name(),"\n"; die();
$register = 'default.yaml';
if (php_sapi_name()=='cli') {
//  echo "argc=$argc\nargv="; print_r($argv);
  if ($argc > 1)
    $register = $argv[1];
} else {
  header('Content-type: text/plain; charset="utf8"');
  if (isset($_GET['register']))
    $register = $_GET['register'];
}

$nberrors = 0;
$nbitems = 0;
foreach (servreg('','',$register)['servers'] as $id => $server) {
//  if (++$nbitems > 20) break;
  if (file_exists("../capabilities/$id.xml")) {
    echo "$id skipped\n";
    CapSummaries::addIfAbsent($server);
    continue;
  }
  switch($server['protocol']) {
    case 'WMS':
    case 'WMTS':
      echo "$id : "; print_r($server);
      if (!in_array(substr($server['url'],-1),['?','&']))
        die("URL \"$server[url]\" incorrecte");
      $url = $server['url']."SERVICE=$server[protocol]&request=GetCapabilities";
      break;
    case 'tile':
    case 'OSM':
      echo "$id ignored\n";
      CapSummaries::add($server);
      continue 2;
    default:
      die("Protocole \"$server[protocol]\" ignoré dans ".__FILE__." ligne ".__LINE__);
  }
  if (!($cap = @file_get_contents($url, false, $stream_context))) {
    echo ("ERREUR: Lecture de \"$url\" impossible\n");
    $nberrors++;
    continue;
  }
  if (preg_match('!^<\?xml version=.1.0. encoding=.ISO-8859-1.!', $cap)) {
    $cap = utf8_encode($cap);
    $cap = preg_replace('!^<\?xml version=.1.0. encoding=.ISO-8859-1.[^>]*>!', '<?xml version="1.0" encoding="UTF-8"?>', $cap, 1);
    echo "encodage en UTF-8\n";
  }
  $capxml = new SimpleXmlElement($cap);
//  echo "<pre>"; print_r($capxml);
  if ($capxml->Exception) {
    echo "Exception pour $url: ",$capxml->Exception,"\n";
    $nberrors++;
    continue;
  }
  file_put_contents("../capabilities/$id.xml", $cap);
  CapSummaries::add($server);
}

CapSummaries::store();
die("\n** FIN OK, $nberrors erreurs rencontrée(s) **\n");
