<?php
/*PhpDoc:
name: tools.php
title: tools.php - récupère le contexte de la carte généré par mapContext et l'exploite
includes: [ '../servreg/servreg.inc.php' ]
doc: |
  Ex d'appel:
    http://localhost/gexplor/gp/tools.php?context=%7B%22baseLayers%22%3A%5B%7B%22title%22%3A%22Cartes%22%2C%22server%22%3A%22IGNFGP-tile-WM%22%2C%22lfunc%22%3A%22L.tileLayer%22%2C%22lyrname%22%3A%22cartes%22%2C%22url%22%3A%22http%3A%2F%2Figngp.geoapi.fr%2Ftile.php%2Fcartes%2F%7Bz%7D%2F%7Bx%7D%2F%7By%7D.jpg%22%2C%22options%22%3A%7B%22format%22%3A%22image%2Fjpeg%22%2C%22minZoom%22%3A0%2C%22maxZoom%22%3A18%7D%2C%22visible%22%3Atrue%7D%2C%7B%22title%22%3A%22Ortho-images%22%2C%22server%22%3A%22IGNFGP-tile-WM%22%2C%22lfunc%22%3A%22L.tileLayer%22%2C%22lyrname%22%3A%22orthos%22%2C%22url%22%3A%22http%3A%2F%2Figngp.geoapi.fr%2Ftile.php%2Forthos%2F%7Bz%7D%2F%7Bx%7D%2F%7By%7D.jpg%22%2C%22options%22%3A%7B%22format%22%3A%22image%2Fjpeg%22%2C%22minZoom%22%3A0%2C%22maxZoom%22%3A20%7D%2C%22visible%22%3Afalse%7D%2C%7B%22title%22%3A%22OSM%22%2C%22server%22%3A%22OSM%22%2C%22lfunc%22%3A%22L.tileLayer%22%2C%22lyrname%22%3A%22OSM%22%2C%22url%22%3A%22http%3A%2F%2F%7Bs%7D.tile.openstreetmap.org%2F%7Bz%7D%2F%7Bx%7D%2F%7By%7D.png%22%2C%22options%22%3A%7B%22format%22%3A%22image%2Fpng%22%2C%22minZoom%22%3A0%2C%22maxZoom%22%3A20%2C%22detectRetina%22%3Atrue%2C%22attribution%22%3A%22Map%20data%20%26copy%3B%20%3Ca%20href%3D%27http%3A%2F%2Fopenstreetmap.org%27%3EOpenStreetMap%3C%2Fa%3E%20contributors%22%7D%2C%22visible%22%3Afalse%7D%2C%7B%22title%22%3A%22Fond%20blanc%22%2C%22server%22%3A%22utilityserver%22%2C%22lfunc%22%3A%22L.tileLayer%22%2C%22lyrname%22%3A%22whiteimg%22%2C%22url%22%3A%22http%3A%2F%2Fvisu.gexplor.fr%2Futilityserver.php%2Fwhiteimg%2F%7Bz%7D%2F%7Bx%7D%2F%7By%7D.jpg%22%2C%22options%22%3A%7B%22format%22%3A%22image%2Fjpeg%22%2C%22minZoom%22%3A0%2C%22maxZoom%22%3A21%7D%2C%22visible%22%3Afalse%7D%5D%2C%22overlays%22%3A%5B%5D%2C%22center%22%3A%7B%22lat%22%3A48%2C%22lng%22%3A3%7D%2C%22zoom%22%3A8%2C%22minZoom%22%3A0%2C%22maxZoom%22%3A18%7D  Signet produit:
    url=gp.php?baseLayers=IGNFGP-tile-WM,cartes,v,IGNFGP-tile-WM,orthos,n,OSM,OSM,n,utilityserver,whiteimg,n&overlays=IGNFGP-WMS-R,ORTHOIMAGERY.ORTHO-SAT.PLEIADES.2016,v&center=48,3&zoom=8&minZoom=0&maxZoom=18

journal: |
  29/3/2017
    gestion du registre de serveurs
  19/3/2017
    renommage en tools.php
  3/3/2017
    première version
*/
if (!isset($_GET['action'])) {
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>tools</title></head><body>\n",
       "<h2>Outils associés à la carte</h2><ul>\n";
  $context = json_decode($_GET['context']);

  $dirname = dirname($_SERVER['SCRIPT_NAME']);
  if ($dirname=='/')
    $dirname = '';
  $url = "http://$_SERVER[SERVER_NAME]$dirname/gp.php?";
  $url .= "register=$context->register&";
  foreach (['baseLayers','overlays'] as $lyrkind) {
    $layers = [];
    foreach ($context->$lyrkind as $layer) {
      $layers[] = urlencode($layer->server); // la partie serveur
      $layers[] = urlencode($layer->lyrname); // la partie lyrname
      $layers[] = ($layer->visible?'v':'n'); // la partie visible
    }
    $url .= "$lyrkind=".implode(',',$layers).'&';
  }

  //echo "<pre>"; print_r($_SERVER);
  $url .= 'center='.$context->center->lat.','.$context->center->lng.'&'
        . 'zoom='.$context->zoom.'&minZoom='.$context->minZoom.'&maxZoom='.$context->maxZoom;
  $url = str_replace('&','&amp;',$url);
  echo "<li><a href='$url' target='_blank'>géo-signet correspondant à la carte</a>\n",
       "<li><a href='?action=showLegend&amp;context=",urlencode($_GET['context']),"'>affichage de la légende</a>\n",
       "<li><a href='?action=dumpContext&amp;context=",urlencode($_GET['context']),"'>Affichage du contexte (pour le déverminage)</a>\n",
       "</ul>\n";
  die();
}

if ($_GET['action']=='dumpContext') {
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>tools</title></head><body><pre>\n";
  $context = json_decode($_GET['context']);
  echo "Context=";
  print_r($context);
  die();
}

function error($message, $lineno=0) {
  die("$message dans tools.php".($lineno?" ligne $lineno":'')."\n");
}

if ($_GET['action']=='showLegend') {
  require_once '../servreg/servreg.inc.php';
  echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>tools</title></head><body>\n";
  $context = json_decode($_GET['context']);
// Initialisation des layers en paramètres
  if (!($yaml = servreg('view','../servreg/')))
    error("Erreur d'ouverture du registre des serveurs", __LINE__);
  $servers = [];
  echo "<table border=1>\n";
  foreach (['baseLayers','overlays'] as $lyrkind)
    foreach ($context->$lyrkind as $layer)
      if ($layer->visible) {
        $serverId = (string)$layer->server;
        $lyrname = (string)$layer->lyrname;
        if (!($servers[$serverId] = newServer($yaml['servers'][$serverId])))
          error("Erreur: serveur $serverId inexistant dans le registre", __LINE__);
        if (!($lyr = $servers[$serverId]->layer($lyrname)))
          error("Erreur: couche $lyrname non trouvée dans le serveur $serverId", __LINE__);
        echo "<tr><td><b>$serverId / $lyrname</b></td></tr>\n";
//        echo "<tr><td><pre>"; print_r($layer); echo "</pre></td></tr>\n";
        echo "<tr><td>",$lyr->genLegend(),"</td></tr>\n";
      }
  echo "</table>\n";
  die();
}

die("Action '$_GET[action]' inconnue");