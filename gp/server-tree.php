<?php
/*PhpDoc:
name:  server-tree.php
title: server-tree.php - génère pour le menu FancyTree l'arbre des couches d'un serveur, appelé en Ajax par gp-fancytree-js.php
includes: [ '../servreg/servreg.inc.php' ]
functions:
doc: |
  Génère l'arbre en JSON conforme aux specs de FancyTree.
  Les paramètres utilisés du script sont:
    server: code du serveur concerné
    register: registre à utiliser (optionel)
    baseLayers: liste des couches de base (optionel)
    overlays: liste des couches superposées (optionel)

  Forme de l'arbre généré:
  Feuille correspondant à une couche L.TileLayer:
  [ { 'title': titre de la couche,
      'tooltip'=> résumé,
      'data': {
        'server': code du serveur,
        'lfunc'=> 'L.tileLayer',
        'lyrname'=> nom de la couche dans le serveur,
        'url': URL de la couche à utiliser dans LL,
        "options": {objet options pour LL}
      }
    }
  ]
  Feuille correspondant à une couche WMS:
  [ { 'title': titre de la couche,
      'tooltip'=> résumé,
      'data': {
        'server': code du serveur,
        'lfunc'=> 'L.tileLayer.wms',
        'lyrname'=> nom de la couche dans le serveur,
        'url': URL du service,
        'options': {objet options pour WMS}
  options contient éventuellement un champ 'crs' avec la valeur 'L.CRS.EPSG4326' pour créer des couches WMS en EPSG:4326
      }
    }
  ]
  avec d'éventuels noeuds intermédiaires:
  { 'title'=> "titre",
    'tooltip'=> "résumé",
    'folder'=> true,
    'children'=> sous-arbre,
  };
journal: |
  24/3/2017
    ajout de la possibilité d'utiliser un registre différent
  18/3/2017
    Ajout de la possibilité pour le paramètre options d'avoir un champ 'crs' valant 'L.CRS.EPSG4326'
    pour créer des couches WMS appelées en EPSG:4326
  9-11/3/2017
    Migration vers viuserv puis servreg
  3/3/2017:
    Ajout du champ lyrname dans data des node des feuilles
    Utilisation des paramètres éventuels baseLayers et overlays pour marquer les couches correspondantes
    comme sélectionnées dans le contexte
  2/3/2017:
    Ajout systématique du champ server dans data des node des feuilles
  19-20/2/2017:
    ajout d'un test de compatibilité de chaque couche avec WM et si elle ne l'est pas affichage barré du titre
  13/2/2017:
    prise en compte de la modification du format dans les capacités JSON
  11/2/2017:
    première version
*/
require_once '../servreg/servreg.inc.php';

// Construction de la liste des lyrname du serveur courant
//header('Content-type: text/plain; charset="utf8"');
$initialContextLayers = [];
foreach (['baseLayers','overlays'] as $lyrgpe) {
  if (isset($_GET[$lyrgpe])) {
    $params = explode(',',$_GET[$lyrgpe]);
    for ($nolyr=0; $nolyr<count($params)/3; $nolyr++)
      if ($params[3*$nolyr]==$_GET['server'])
        $initialContextLayers[] = $params[3*$nolyr+1];
  }
}
//print_r($initialContextLayers); die();

function error($message) {
  header('HTTP/1.1 404 Not Found');
  header('Content-type: text/plain; charset="utf8"');
  die(json_encode(['error'=>$message],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
}

// Fabrique récursivement la structure Php/JSON correspondant à l'arbre des couches
// parentLayer est la couche parente, null pour la couche racine
// retourne l'arbre json encodée en Php
function layerTree($wmsserver, $initialContextLayers, $parentLayer=null) {
  if (!$parentLayer)
    $layers = $wmsserver->layers();
  else
    $layers = $parentLayer->children();
  foreach ($layers as $layer) {
    if (count($layer->children())==0) {
//      echo "Couche ",$layer->name(),"\n";
//      echo "availableInWmOrGeo: ",($layer->availableInWmOrGeo() ? 'oui' : 'non'),"\n";
//      $layer->showInHtml();
//      print_r($layer);
      $leafletJS = $layer->leafletJS();
// ['title'=>titre de la couche, 'lfunc'=> fonction Leaflet, 'url'=>URL d'appel de la couche, 'options'=>options]
      $elt = [
        'title'=> ($layer->availableInWmOrGeo() ? $layer->title() : '<s>'.$layer->title().'</s>'),
        'tooltip'=> $layer->getAbstract(),
        'data'=> [
          'server'=> $_GET['server'],
          'lfunc'=> $leafletJS['lfunc'],
          'lyrname'=> $layer->name(),
          'url'=> $leafletJS['url'],
          'options'=> $leafletJS['options'],
        ],
      ];
      if (in_array($layer->name(), $initialContextLayers)) {
        $elt['data']['origTitle'] = $elt['title'];
        $elt['title'] = '<b>'.$elt['title'].'</b>';
        $elt['data']['lyrid'] = $_GET['server'].'/'.$layer->name();
      }
    }
    else {
      $elt = [
        'title'=> ($layer->availableInWmOrGeo() ? $layer->title() : '<s>'.$layer->title().'</s>'),
        'tooltip'=> $layer->getAbstract(),
        'folder'=> true,
        'children'=> layerTree($wmsserver, $initialContextLayers, $layer),
      ];
    }
    $json[] = $elt;
  }
//  echo "json=",json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"\n";  
  return $json;
}

if (!isset($_GET['server']))
  error("Erreur: paramètre server non défini");
$register = (isset($_GET['register']) ? $_GET['register'] : 'default.yaml');
if (!($servreg = servreg('view','../servreg/', $register)))
  error("Erreur d'ouverture du registre des serveurs", __LINE__);
if (!isset($servreg['servers'][$_GET['server']]))
  error("Erreur: le serveur $_GET[server] n'est pas défini dans le registre des serveurs");
$server = newServer($servreg['servers'][$_GET['server']]);
//    $server->showResources(); die();
header('Content-type: text/plain; charset="utf8"');
die(json_encode(
  layerTree($server, $initialContextLayers),
  JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

