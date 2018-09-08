<?php
/*PhpDoc:
name:  edit.php
title: edit.php - gère la carte en session et effectue au fur et à mesure les modifications
includes: [ '../newserver.inc.php', '../genmapjs.inc.php' ]
functions:
doc: |
  Gère en variables de session:
  - une carte
  - la localisation
  Sans paramètre, la carte est initialisée à une valeur par défaut.
  Si url est défini alors la carte est initialisé à partir du fichier.
  A chaque appel, des modifications de la carte sont effectuées:
  - si les paramètres server et layer sont définis:
    - si la couche existe déjà dans la carte alors elle est supprimée
    - si la couche n'existe pas dans la carte alors elle est ajoutée
  - si action=move alors les nouvelles coordonnées sont enregistrées
  - si action=dump alors dump du yaml de la carte
  - si action=enregistrer alors enregistrement de la carte dans un fichier dans maps
    les URL pour consulter la carte et la modifier sont fournies
  D'autres paramètres seront ajoutés.
journal: |
  6/3/2017:
    ajout d'une exception pour OSM
  2/12/2016:
    test d'affichage des capacités des layer - pas convaincant
  17/11/2016:
    retour vers le serveur des infos de pan&zoom courants de la carte ce qui permet de réafficher la carte sans revenir
    à la localisation initiale, utilise leaflet.uGeoJSON
  1/11/2016:
    évolution
  31/10/2016:
    première version
*/
session_start();
require_once '../newserver.inc.php';
require_once '../genmapjs.inc.php';


/*PhpDoc: functions
name:  store
title: function store($map) - enregistrement d'une carte
doc: |
  La carte est enregistrée dans 2 fichiers.
  Un premier en lecture seule et un second en lecture/ecriture
  L'enregistrement en lecture/écriture contient l'uniqid qui est aussi le nom du fichier en écriture
  L'enregistrement en lecture seule est effectué dans un fichier identifié par md5(uniqid)
  il ne contient pas uniqid
  Lors de l'initialisation de l'édition, si uniqid n'existe pas alors un nouveau est généré ;
  ainsi le fichier qui ne contient pas de uniqid peut être cloné mais ne peut être modifié
*/
function store($map) {
  file_put_contents("maps/$map[uniqid].yaml", yaml_emit($map)); // sauvegarde en lecture/écriture
  $uniqid = $map['uniqid'];
  unset($map['uniqid']);
  $readOnlyId = md5($uniqid);
  unset($map['readOnlyId']);
  file_put_contents("maps/$readOnlyId.yaml", yaml_emit($map));
  echo "<html><head><title>$map[title]</title><meta charset='UTF-8'></head></body>\n";
//  echo "<pre>"; print_r($_SERVER); die();
  $visuurl = "http://$_SERVER[SERVER_NAME]".($_SERVER['SERVER_NAME']<>'visu.gexplor.fr' ? '/visu' : '');
  $mapurl = "$visuurl/edit/maps/$readOnlyId.yaml";
  echo "La carte en lecture est enregistrée sous l'URL <a href='$mapurl' target='_blank'>$mapurl</a><br>\n";
  $viewurl = "$visuurl/viewer.php?url=".urlencode("edit/maps/$readOnlyId.yaml");
  echo "Elle peut être consultée avec l'URL <a href='$viewurl' target='_blank'>$viewurl</a><br>\n";
  $editurl = "$visuurl/edit/?url=".urlencode("maps/$uniqid.yaml");
  echo "Vous pourrez recommencer à l'éditer en utilisant l'URL <a href='$editurl' target='_blank'>$editurl</a><br>\n";
}

// Initialisation de la carte, initialise la carte et retourne l'objet Php
function init($url=null) {
  $yamlSrce = file_get_contents($url ? $url : 'defaultmap.yaml');
  if (!($map = yaml_parse($yamlSrce))) {
    header('Content-Type: text/plain; charset=UTF-8');
    die();
  }
  if (!isset($map['uniqid']))
    $map['uniqid'] = uniqid('map',true);
  return $map;
}

$servers = []; // Tableau d'objets OgcServer identifiés par leur id
$serversYaml = file_get_servers('../');
foreach (array_keys($serversYaml['servers']) as $id)
  if ($id<>'OSM')
    $servers[$id] = newServer($serversYaml['servers'], $id, '../');
    
// Initialisation de la carte
if (!$_GET or isset($_GET['url'])) {
  $_SESSION['map'] = init(isset($_GET['url']) ? $_GET['url'] : null);
//  echo "<pre>map="; print_r($_SESSION['map']);
//  echo "servers="; print_r($servers);
//  echo "</pre>\n";
  $cviewurl = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]?action=move";
  genmapjs($_SESSION['map'], $servers, ['visupath'=>'../', 'cview'=>$cviewurl]);
//  echo "map="; print_r($_SESSION['map']);
  die();
}

if (!isset($_SESSION['map']))
  $_SESSION['map'] = init();

if (isset($_GET['server']) and isset($_GET['layer'])) {
/*
  if (isset($_SESSION['capabilities']) and $_SESSION['capabilities']) {
    echo "capabilities server=$_GET[server] layer=$_GET[layer]\n";
    $layer = $servers[$_GET['server']]->getLayerById($_GET['layer']);
//    echo "<pre>layer="; print_r($layer); echo "</pre>\n";
    $layer->showLayerInHtml($_GET['layer']);
    die();
  }
*/
  $deleted = false;
  foreach (['baseLayers', 'overlays'] as $kind)
    foreach ($_SESSION['map'][$kind] as $no => $layer)
      if (($layer['server']==$_GET['server']) and ($layer['layer']==$_GET['layer'])) {
        unset($_SESSION['map'][$kind][$no]);
        $deleted = true;
      }
  if ($deleted)
    foreach (['baseLayers', 'overlays'] as $kind)
      $_SESSION['map'][$kind] = array_values($_SESSION['map'][$kind]);
  else { // Si aucune couche n'a été détruite, cela signifie que la couche est à ajouter
// Avant, je désélectionne tous les overlays précédemment sélectionnés
    foreach ($_SESSION['map']['overlays'] as $no => $overlay)
      if (isset($overlay['selected']))
        unset ($_SESSION['map']['overlays'][$no]['selected']);
// J'ajoute la couche passée en paramètres et je la sélectionne
//    echo "<pre>_GET="; print_r($_GET); echo "</pre>\n";
//    echo "<pre>server="; print_r($servers[$_GET['server']]); echo "</pre>\n";
    $layer = $servers[$_GET['server']]->getLayerByName($_GET['layer']);
//    echo "<pre>layer="; print_r($layer); echo "</pre>\n";
//    echo "<pre>map="; print_r($_SESSION['map']); echo "</pre>\n";
    $kind = ($layer->baseLayer() ? 'baseLayers' : 'overlays');
//    echo "<pre>kind=$kind</pre>\n";
//    die();
    $_SESSION['map'][$kind][] = ['server' => $_GET['server'],'layer' => $_GET['layer'],'selected' => 'true'];
  }
//  echo "<pre>map="; print_r($_SESSION['map']); echo "</pre>\n";
  $cviewurl = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]?action=move";
  genmapjs($_SESSION['map'], $servers, ['visupath'=>'../', 'cview'=>$cviewurl]);
  die();
}

// Définition d'un point de vue pour la carte
if (isset($_GET['view'])) {
  if (!preg_match('!^\[([-\d.]+, [-\d.]+)\], (\d+)$!', $_GET['view'], $matches))
    die("Erreur, parametre view=\"$_GET[view]\" incorrect");
//  echo "parametre view=\"$_GET[view]\"<br>\n";
//  echo "<pre>matches="; print_r($matches); echo "</pre>\n"; // die();
  $_SESSION['map']['center'] = $matches[1];
  $_SESSION['map']['zoom'] = $matches[2];
//  echo "map=<pre>"; print_r($_SESSION['map']); echo "</pre>\n"; die();
  $cviewurl = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]?action=move";
  genmapjs($_SESSION['map'], $servers, ['visupath'=>'../', 'cview'=>$cviewurl]);
  die();
}

if (isset($_GET['action']))
  switch ($_GET['action']) {
    case 'none':
      $cviewurl = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]?action=move";
      genmapjs($_SESSION['map'], $servers, ['visupath'=>'../', 'cview'=>$cviewurl]);
      die();
    case 'move':
// Enregistrement la nouvelle position de la carte dans la variable de session map
//      $log = "\ndate: ".date('c')."\n";
      $bbox = explode(',',$_POST['bbox']);
//      $log .= "bbox=".implode(',',$bbox)."\n";
      $center = [($bbox[1]+$bbox[3])/2, ($bbox[0]+$bbox[2])/2];
//      $log .= "center=".implode(',',$center)."\n";
//      $log .= "zoom=".$_POST['zoom']."\n";
      $_SESSION['map']['center'] = implode(',',$center);
      $_SESSION['map']['zoom'] = $_POST['zoom'];
//      file_put_contents('edit.log',$log,FILE_APPEND);
// renvoie une collection vide
      die('{ "type": "FeatureCollection","features": [ ]}'."\n");
    case 'enregistrer':
      store($_SESSION['map']); die();
    case 'dump':
      header('Content-Type: text/plain; charset=UTF-8');
//      die(yaml_emit($_SESSION['map']));
      die(yaml_emit($_SESSION['map'],YAML_UTF8_ENCODING));
//      echo "_SERVER="; print_r($_SERVER);
      echo "map="; print_r($_SESSION['map']);
      die();
/*
    case 'capabilities':
      if (!isset($_SESSION['capabilities']) or !$_SESSION['capabilities']) {
        echo "mode capabilities ON\n";
        $_SESSION['capabilities'] = true;
      } else {
        echo "mode capabilities OFF\n";
        unset($_SESSION['capabilities']);
      }
      die();
*/
    default:
      die("action \"$_GET[action]\" inconnue");
  }
  
echo "<h1>edit.php</h1>\n";
echo "<pre>_GET="; print_r($_GET);
echo "map="; print_r($_SESSION['map']);
echo "</pre>\n";
echo "</body></html>\n";
