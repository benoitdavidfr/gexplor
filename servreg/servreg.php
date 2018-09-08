<?php
/*PhpDoc:
name: servreg.php
title: servreg.php - IHM Html et API d'accès en mode web au registre
includes: [ servreg.inc.php, '../../geom2d/coordsys.inc.php' ]
doc: |
  servreg.php constitue (i) l'IHM d'accès au registre des serveurs et (ii) une API d'accès en mode web
  - servreg.php
    - en mode Html génère l'affichage de la liste des serveurs organisée selon le classement
    - en mode API (ou avec format=json) génère le flux JSON des serveurs + actions
  - servreg.php/{server}
    - en mode Html génère l'affichage de la fiche du serveur
    - en mode API (ou avec format=json) génère une description JSON du serveur + actions + layers
    - request
      - GetCapabilities|GetCap : affiche les capacités en XML pour un serveur Ogc et en JSON sinon
  - servreg.php/{server}/{layer}
    - en mode Html génère l'affichage de la fiche de la couche
    - en mode API (ou avec format=json) génère une description JSON de la couche + serveur + actions 
    - request
      - GetCapabilities|GetCap : affiche les capacités de la couche en XML pour un serveur Ogc et en JSON sinon
  - servreg.php/{server}/{layer}/{z}/{x}/{y}.[png|jpg]
    essaie de générer à partir du serveur l'image correspondant au format tile
    Mise en oeuvre pour les serveurs WMS exposant leur couche en EPSG:4326 comme Carmen
    ex: http://localhost/gexplor/servreg/servreg.php/Carmen8-nature/ZNIEFF_continentales_de_type_II/8/132/89.jpg
    Leaflet sait afficher dans une carte WM des couches WMS en EPSG:4326, cette fonctionalité n'est donc pas primordiale

journal: |
  28/3/2017 :
    ajout de la possibilité de choisir le registre en le passant en paramètre
  17/3/2017 :
    ajout de servreg.php/{server}/{layer}/{z}/{x}/{y}.[png|jpg]
  10/3/2017 :
    chgt de nom -> servreg
  7-8/3/2017 :
    fork dans viuserv
    écriture partielle
  5/11/2016:
    Ajout de définition de la legende dans le fichier de configuration
  2/11/2016:
    remplacement du theme par class
  29/10/2016:
    améliorations
  28/10/2016:
    première version
*/

require_once 'servreg.inc.php';

//echo "<pre>_SERVER="; print_r($_SERVER); die();
//echo "url = http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]"; die();

// Affichage récursif de la classification et des serveurs rattachés
function show_classification($tree, &$servers, $level=0) {
//  echo "<pre>tree="; print_r($tree); echo "</pre>\n";
//  $url = "http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
  $url = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]".(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
  $params = ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
//  echo "url=$url<br>\n";
//  echo "<pre>_SERVER="; print_r($_SERVER); die();
  foreach ($tree as $classId => $class) {
    echo "<li><b>",htmlspecialchars($class['title'], ENT_COMPAT|ENT_HTML401,'UTF-8'),"</b><ul>\n";
    foreach ($servers as $serverid => $server)
      if (isset($server['class']) and ($server['class']==$classId)) {
        echo "<li><a href='$url/$serverid$params'>$server[title] ($server[protocol])</a>\n";
        unset($servers[$serverid]);
      }
    if (isset($class['children']) and $class['children'])
      show_classification($class['children'], $servers, $level+1);
    echo "</ul>\n";
  }
// Liste des serveurs qui n'ont pas été affichés précédemment
  if (!$level and $servers) {
    echo "<li><b>Autres</b><ul>\n";
    foreach ($servers as $serverid => $server)
      echo "<li><a href='$url/$serverid'>$server[title] ($server[protocol])</a>\n";
    echo "</ul>\n";
  }
}

// comptage du nombre de couches
function countNbreLayers($layer=null) {
  if (!$layer) {
    header('Content-type: text/plain; charset="utf-8"');
    $totalNbreLayers = 0;
    $nbServers = 0;
    foreach (servreg()['servers'] as $serverDef) {
//      if (++$nbServers > 10) break; //die("Fin ligne ".__LINE__);
      $nbreLayers = 0;
      try {
        if (!($server = newServer($serverDef)))
          die("Server $serverDef[id] non reconnu");
        foreach ($server->layers() as $layer)
          $nbreLayers += countNbreLayers($layer);
        echo "$serverDef[id]: $nbreLayers\n";
        $totalNbreLayers += $nbreLayers;
      } catch (Exception $e) {
        echo $e->getMessage(),"\n";
      }
    }
    die("totalNbreLayers=$totalNbreLayers");
  }
  else {
    if (!($children = $layer->children()))
      return 1;
    $nbreLayers = 0;
    foreach ($children as $sublayer)
      $nbreLayers += countNbreLayers($sublayer);
    return $nbreLayers;
  }
}

$register = (isset($_GET['register']) ? $_GET['register'] : 'default.yaml');

// sans paramètre server ou layer
if (!isset($_SERVER['PATH_INFO']) or !$_SERVER['PATH_INFO'] or ($_SERVER['PATH_INFO']=='/')) {
// mode API : renvoie la liste des serveurs en JSON
  if (!isset($_SERVER['HTTP_ACCEPT']) or !preg_match('!text/html!',$_SERVER['HTTP_ACCEPT'])
    or (isset($_GET['format']) and ($_GET['format']=='json'))) {
      header('Content-type: text/plain; charset="utf-8"');
      die(json_encode(
          servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register),
          JSON_FORCE_OBJECT|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
// mode HTML : affichage de la liste des serveurs
  else
    switch (isset($_GET['request']) ? $_GET['request'] : null) {
      case null:
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>server</title></head><body>\n";
        echo "<h2>Liste des serveurs</h2>\n",
             "Les serveurs sont organisés selon une classification hiérarchique indiquée en gras.</p>\n";
        $yaml = servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register);
        show_classification($yaml['classification'], $yaml['servers']);
        die();
      case 'countNbreLayers':
        countNbreLayers();
    }
}

/* avec paramètre server
  - servreg.php/{server}
    - en mode Html génère l'affichage de la fiche du serveur
    - en mode API (ou avec format=json) génère une description JSON du serveur + actions + layers
    - request
      - GetCapabilities|GetCap : affiche les capacités simplifiées en XML pour un serveur Ogc et en JSON sinon
      - showConf : affiche la configuration en JSON
*/
elseif (preg_match('!^/([^/]*)$!', $_SERVER['PATH_INFO'], $matches)) {
  $serverId = $matches[1];
// mode API : renvoie la liste des serveurs en JSON
  if (!isset($_SERVER['HTTP_ACCEPT']) or !preg_match('!text/html!',$_SERVER['HTTP_ACCEPT'])
    or (isset($_GET['format']) and ($_GET['format']=='json'))) {
      $server = servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register)['servers'][$serverId];
      die("A FAIRE");
  }
// mode HTML : affichage de la liste des serveurs
  else {
    switch(isset($_GET['request']) ? strtolower($_GET['request']) : null) {
      case null:
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>server $serverId</title></head><body>\n";
        try {
          if (!($server = newServer(servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register)['servers'][$serverId])))
            die("Server $serverId non reconnu");
          $server->showInHtml();
        } catch(Exception $e) {
          die("Sur le serveur $serverId: ".$e->getMessage());
        }
        die();
      case 'showconf':
        header('Content-type: text/plain; charset="utf-8"');
        die(json_encode(
              servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register)['servers'][$serverId],
              JSON_FORCE_OBJECT|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
      case 'getcap':
        die(newServer(servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register)['servers'][$serverId])->getCap());
      default:
// par défaut j'utilise le paramètre REQUEST comme méthode sur la classe Server
        die(newServer(servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register)['servers'][$serverId])->$_GET['request']());
    }
  }
}
  
/* servreg.php/{server}/{layer}
    - request
      - GetCapabilities|GetCap : affiche les capacités de la couche en XML pour un serveur Ogc et en JSON sinon
*/
elseif (preg_match('!^/([^/]*)/([^/]*)$!', $_SERVER['PATH_INFO'], $matches)) {
  $serverId = $matches[1];
  $lyrName = $matches[2];
// en mode API (ou avec format=json) génère une description JSON de la couche + serveur + actions 
  if (!isset($_SERVER['HTTP_ACCEPT']) or !preg_match('!text/html!',$_SERVER['HTTP_ACCEPT'])
    or (isset($_GET['format']) and ($_GET['format']=='json'))) {
      die("A FAIRE");
  }
// en mode Html génère l'affichage de la fiche de la couche
  else {
    if (!($server = newServer(servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register)['servers'][$serverId])))
      die("Server $serverId non reconnu");
    if (!($layer = $server->layer($lyrName)))
      die("Layer \"$lyrName\" inconnue");
    if (!isset($_GET['request'])) {
      echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>server $serverId</title></head><body>\n";
      $layer->showInHtml();
    } elseif (in_array(strtolower($_GET['request']),['getcapabilities','getcap'])) {
      header('Content-Type: text/xml');
      die($layer->cap()->asXml());
    }
  }
}

/* servreg.php/{server}/{layer}/{z}/{x}/{y}.[png|jpg]
   génère l'image correspondant au format tile
*/
elseif (!preg_match('!^/([^/]*)/([^/]*)/(\d+)/(\d+)/(\d+)\.(png|jpg)$!', $_SERVER['PATH_INFO'], $matches)) {
  header('Content-type: text/plain; charset="utf-8"');
  die("Erreur: Paramètres non reconnus");
}

// servreg.php/{server}/{layer}/{z}/{x}/{y}.[png|jpg]
// http://localhost/gexplor/servreg/servreg.php/Carmen8-nature/ZNIEFF_continentales_de_type_II/11/1021/703.png
//echo "matches ligne ",__LINE__,"<br>\n";
//print_r($matches);

$serverId = $matches[1];
$lyrName = $matches[2];
$z = $matches[3];
$x = $matches[4];
$y = $matches[5];
$format = ($matches[6]=='png' ? 'image/png' : 'image/jpeg');

if (!($server = newServer(servreg(/*$serviceType=*/'', /*$servregPath=*/'', $register)['servers'][$serverId])))
  die("Server $serverId non reconnu");
if (!($layer = $server->layer($lyrName)))
  die("Layer $lyrName inconnue");
//->showInHtml();

// calcul du BBox à partir de (z,x,y)
function bbox($z, $ix, $iy) {
  $base = 20037508.3427892476320267;
  $size0 = $base * 2;
  $x0 = - $base;
  $y0 =   $base;
  $size = $size0 / pow(2, $z);
  return [
    $x0 + $size * $ix,
    $y0 - $size * ($iy+1),
    $x0 + $size * ($ix+1),
    $y0 - $size * $iy,
  ];
}

$bbox = bbox($z, $x, $y);

require_once '../../geom2d/coordsys.inc.php';
$ptmin = CoordSys::chg('WM','geo', $bbox[0], $bbox[1]);
$ptmax = CoordSys::chg('WM','geo', $bbox[2], $bbox[3]);
//print_r($ptmin); print_r($ptmax);
$bbox = [$ptmin[1], $ptmin[0], $ptmax[1], $ptmax[0]];

$conf = $server->conf();
//echo "<pre>conf="; print_r($conf); echo "</pre>\n";

switch($conf['protocol']) {
  case 'WMS':
    $url = $conf['url']
          .'service=WMS&version=1.3.0&request=GetMap'
          ."&layers=$lyrName&format=".urlencode($format)."&styles="
          .($format=='image/png' ? '&transparent=true' : '')
          .'&crs='.urlencode('EPSG:4326').'&bbox='.implode(',',$bbox)
          .'&height=256&width=256';
//    die("url=<a href='$url'>$url</a>\n");
    header("Location: $url");
    die();
    
  default:
    die("Erreur protocole $conf[protocol] non traite ligne ".__LINE__);
}