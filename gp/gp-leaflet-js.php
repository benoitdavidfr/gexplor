<?php
/*PhpDoc:
name: gp-leaflet-js.php
title: gp-leaflet-js.php - code JS généré par Php initialisant la carte Leaflet
includes: [ '../servreg/servreg.inc.php' ]
doc: |
  La carte est définie en fonction des paramètres center et zoom
  Elle contient:
  - l'affichage de d'échelle (L.control.scale)
  - un slidemenu qui contiend l'arbre Fancytree
  - les couches définies par les paramètres de l'appel
journal: |
  29/3/2017
    ajout du passage du registre a mapcontext
  26/3/2017
    optimisation en évitant d'initialiser plusieurs fois le même serveur
  24/3/2017
    ajout de la possibilité d'utiliser un registre différent
  9-11/3/2017
    Migration vers viuserv puis servreg
  7/3/2017
    Correction de bugs empêchant l'utilisation d'un géosignet
  4/3/2017
    Initialisation du contexte en fonction des paramètres du géosignet
  3/3/2017
    A REVOIR pour initialiser le contexte en fonction des paramètres du géosignet !!!!
  2/3/2017
    Ajout d'un objet mapContext pour générer un géosignet
  19/2/2016
    Utilisation d'une image fixe pour le fond blanc
  18/2/2016
    test de slide-menu plus large
*/
require_once '../servreg/servreg.inc.php';

header('Content-type: text/plain; charset="utf8"');
//echo "/* GET="; print_r($_GET); echo "*/\n";

function error($message, $lineno=0) {
  die("alert(\"$message dans gp-leaflet-js.php".($lineno?" ligne $lineno":'')."\");\n");
}

if (!isset($_GET['center']) or !isset($_GET['zoom']))
  error("Erreur: les paramètres center er zoom doivent être définis");

echo <<<EOT
// Code JS généré par gp-leaflet-js.php
// initialize the map on the "map" div with a given center and zoom
var map = L.map('map', {center: [$_GET[center]], zoom: $_GET[zoom]}); // view pour la zone
L.control.scale({position:'bottomleft', metric:true, imperial:false}).addTo(map);
L.control.slideMenu(
  "<div id='mytree'></div>&nbsp;&nbsp;L'arbre ci-dessus permet de changer de point de vue et d'ajouter / enlever des couches.<br>"
  +"&nbsp;&nbsp;<i>$_GET[version]</i>",
  {width: '500px'}
).addTo(map);

EOT;

$register = (isset($_GET['register']) ? $_GET['register'] : 'default.yaml');
if (!($yaml = servreg('view','../servreg/', $register)))
  error("Erreur d'ouverture du registre des serveurs $register", __LINE__);

$servers = [];

// Initialisation des layers en paramètres
$layers = ['baseLayers'=>[]];
foreach (['baseLayers','overlays'] as $lyrgpe) {
  if (isset($_GET[$lyrgpe]) and $_GET[$lyrgpe]) {
    $layers[$lyrgpe] = [];
    $params = explode(',',$_GET[$lyrgpe]);
    for ($nolyr=0; $nolyr<count($params)/3; $nolyr++) {
//      echo "layer="; print_r($layer);
      $serverId = $params[3*$nolyr];
      $lyrname = $params[3*$nolyr+1];
      $visible = ($params[3*$nolyr+2]=='v');
      $serv = (isset($servers[$serverId]) ? $servers[$serverId] : null);
      if (!$serv and !isset($yaml['servers'][$serverId]))
        error("Erreur: serveur $serverId non défini", __LINE__);
      try {
        if (!$serv) {
          if (!($serv = newServer($yaml['servers'][$serverId])))
            error("Erreur: serveur $serverId inexistant dans le registre", __LINE__);
          $servers[$serverId] = $serv;
        }
        if (!($lyr = $serv->layer($lyrname)))
          error("Erreur: couche $lyrname non trouvée dans le serveur $serverId", __LINE__);
        $layer = $lyr->leafletJS();
      } catch(Exception $e) {
        error("Ouverture de $serverId impossible", __LINE__);
      }
      $layer['server'] = $serverId;
      $layer['lyrname'] = $lyrname;
      $layer['visible'] = $visible;
      $layers[$lyrgpe][] = $layer;
    }
  }
}

//echo "layers[baseLayers]="; print_r($layers['baseLayers']);
echo "mapContext.init(",
      json_encode(
        $layers['baseLayers'],
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
     ",\n '$register'",
     ");\n";
if (isset($layers['overlays']))
  foreach ($layers['overlays'] as $layer)
    echo "mapContext.addOverlay(",json_encode($layer,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),");\n";
die();
?>
