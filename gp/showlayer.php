<?php
/*PhpDoc:
name:  showlayer.php
title: showlayer.php - affiche une couche définie par les paramètres fixés dans gp-fancytree.js
includes: [ '../../geom2d/coordsys.inc.php' ]
functions:
doc: |
  Cet affichage facilite le déverminage car il est parfois difficile dans Leaflet de comprendre pourquoi une couche n'est 
  pas affichée.
  L'application gp permet d'appeler le présent script avec les paramètres suivants:
  - protocol: 'WMS' ou 'tile'
  - url: l'URL transmis dans Leaflet, cad pour un WMS la racine de l'URL et pour un tile le modèle d'URL
  - center: centre de la vue en coord. géo. lat lng en degrés décimaux
  - zoom: niveau de zoom
  ex:
  http://localhost/gexplor/gp/showlayer.php?server=IGNFGP-WMS-R&layer=GEOGRAPHICALGRIDSYSTEMS.ETATMAJOR40&center=48,3&zoom=8
  http://localhost/gexplor/gp/showlayer.php?protocol=tile&url=http%3A%2F%2Figngp.geoapi.fr%2Ftile.php%2Froutes%2F%7Bz%7D%2F%7Bx%7D%2F%7By%7D.png&center=48,3&zoom=8
journal: |
  28/2/2017:
    première version
*/

require_once '../../geom2d/coordsys.inc.php';

define("BASE", 20037508.3427892476320267);
$size0 = BASE * 2;
$size = $size0 / pow(2, $_GET['zoom']); // taille des tuiles en WM pour le zoom 
$ptgeo = explode(',',$_GET['center']); // centre en coord. géo. lat lng
$ptwm = CoordSys::chg('geo','WM',$ptgeo[1], $ptgeo[0]); // centre en WM
//printf("x=%.0f, y=%.0f<br>\n", $ptwm[0], $ptwm[1]);

switch (isset($_GET['protocol']) ? $_GET['protocol'] : null) {
  case 'WMS':
    $bbox = [ // boite 4 X 3
      $ptwm[0] - 2*$size, $ptwm[1] - 1.5*$size,
      $ptwm[0] + 2*$size, $ptwm[1] + 1.5*$size,
    ];
    $url = $_GET['url']
          ."service=WMS&version=1.3.0&request=GetMap"
          ."&layers=$_GET[layer]&styles="
          ."&CRS=EPSG:3857&bbox=".implode(',',$bbox)
          ."&format=image/png&transparent=true&width=1024&height=768";
//    die("<a href='$url'>$url</a>");
    header("Location: $url");
    die();
    
  case 'tile':
    $url0 = $_GET['url'];
    $i0 = floor((BASE + $ptwm[0])/$size - 2);
    $j0 = floor((BASE - $ptwm[1])/$size - 1.5);
//    echo "i0=$i0, j0=$j0<br>\n";
    echo "<table border=1>\n";
    for ($j=0; $j<3; $j++) {
      echo "<tr>";
      for ($i=0; $i<4; $i++) {
        $url = str_replace(['{z}','{x}','{y}'], [$_GET['zoom'], $i0+$i, $j0+$j], $url0);
        echo "<td><img src='$url' alt='$url'></td>\n";
      }
      echo "</tr>\n";
    }
    die();
    
  default:
    die("Erreur: protocol $_GET[protocol] non traite");
}
