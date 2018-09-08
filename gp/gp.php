<?php
/*PhpDoc:
name: gp.php
title: gp.php - page de demarrage de gp
includes: [ mapcontext.js, gp-fancytree-js.php, gp-leaflet-js.php]
doc: |
  gp.php est appelé avec les paramètres du géosignet:
    baseLayers= liste des couches de base sous la forme <server>,<layer>,v|n
    overlays= liste des couches de base sous la forme <server>,<layer>,v|n
    center= <lat>,<lng>
    zoom= <zoom>
    minZoom= <minZoom>
    maxZoom= <maxZoom>
  Ces paramètres sont transmis à gp-leaflet-js.php et à gp-fancytree-js.php
  Dans gp-leaflet-js.php les couches en paramètres sont initialisées dans Leaflet au travers de mapContext
  Dans gp-fancytree-js.php les couches en paramètres sont initialisées dans le Fancytree (titre en gras, ...)
  Si les paramètres ne sont pas définis, des paramètres par défaut sont définis.
  
  Exemples d'appels avec un registre de serveurs particulier:
    gp.php?register=ifremer.yaml&baseLayers=Ifremer-surveillance_littorale,ZONES_MARINES_P,v&center=47,3&zoom=6
    gp.php?register=simplavecclass.yaml&baseLayers=
    gp.php?register=simplssclass.yaml&baseLayers=
    
journal: |
  3-4/3/2017 :
    Initialisation du contexte en fonction des paramètres du géosignet
  2/3/2017 :
    renommage en gp.php
  14/2/2017 :
    positionnement du menu des couches dans le SlideMenu
  11/2/2017 :
    création & validation du concept
*/
$version = 'version gp du 29/3/2017';
//echo "<pre>"; print_r($_SERVER);
// Définition des paramètres par défaut pour gp-leaflet-js.php et gp-fancytree-js.php
$register = (isset($_GET['register']) ? $_GET['register'] : null);
$params = 'version='.urlencode($version)
         .($register ? '&register='.urlencode($register) : '')
         .'&baseLayers='
          .(isset($_GET['baseLayers']) ? $_GET['baseLayers']
          : ($register ? '' : 'IGNFGP-tile-WM,cartes,v,IGNFGP-tile-WM,orthos,n,OSM,OSM,n,utilityserver,whiteimg,n'))
         .(isset($_GET['overlays']) ? '&overlays='.$_GET['overlays'] : '')
         .'&center='.(isset($_GET['center']) ? $_GET['center'] : '48,3')
         .'&zoom='.(isset($_GET['zoom']) ? $_GET['zoom'] : '8')
         .'&minZoom='.(isset($_GET['minZoom']) ? $_GET['minZoom'] : '0')
         .'&maxZoom='.(isset($_GET['maxZoom']) ? $_GET['maxZoom'] : '21');
//echo "params=$params\n"; die();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>gp</title>
<!-- meta nécessaire pour le mobile -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<!-- styles nécessaires pour le mobile -->
  <link rel="stylesheet" href="http://visu.gexplor.fr/viewer.css">
<!-- styles et src de Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.0/dist/leaflet.js" type="text/javascript"></script>
<!-- Include L.Control.SlideMenu -->
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="http://gexplor.fr/leaflet/L.Control.SlideMenu.css">
  <script src="http://gexplor.fr/leaflet/L.Control.SlideMenu.js" type="text/javascript"></script>
<!-- Include JQuery -->
  <script src="http://visu.gexplor.fr/lib/jquery.js" type="text/javascript"></script>
<!-- Include JQuery-UI -->
  <script src="http://visu.gexplor.fr/lib/jquery-ui.custom.js" type="text/javascript"></script>
<!-- Use Fancytree skin CSS -->
  <link href="http://visu.gexplor.fr/lib/fancytree/skin-win8/ui.fancytree.min.css" rel="stylesheet" type="text/css">
<!-- Include Fancytree -->
  <script src="http://visu.gexplor.fr/lib/fancytree/jquery.fancytree-all.min.js" type="text/javascript"></script>
</head>
<body>
  <div id="map"></div>
<!-- Include l'implem de leaflet -->
  <script src="mapcontext.js" type="text/javascript"></script>
  <script src="gp-leaflet-js.php?<?php echo $params;?>" type="text/javascript"></script>
<!-- Include l'implem de Fancytree avec les paramètres éventuels de gp.php -->
  <script src="gp-fancytree-js.php?<?php echo $params;?>" type="text/javascript"></script>
</body>
</html>