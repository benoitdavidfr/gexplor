<?php
/*PhpDoc:
name:  genmapjs.inc.php
title: genmapjs.inc.php - fonctions génèrant le code JavaScript affichant la carte
includes: [ viewer.css, 'lib/leaflet.edgebuffer.js' ]
functions:
doc: |
journal: |
  6/5/2017:
    la simplification de l'URL a introduit un bug
    pour le corriger, j'utilise dans le code JS pour viewer.css et lib l'URL internet au lieu d'un chemin relatif
  19/4/2017:
    adaptation au transfert sur gexplor
  17/11/2016:
    Remplacement des paramètres optionnels par un paramètre options
    Ajout de l'option cview qui fournit un URL à appeller à chaque déplacement de la carte
  15/11/2016:
    Ajout de la possibilité de se géolocaliser
  7/11/2016:
    Adaptation pour fonctionner indifféremment sur alwaysdata, localhost ou localhost/~benoit
  2/11/2016:
    Ajout de la possibilité d'intégrer les outils du Géoportail
  31/10/2016:
    première version
*/
/*PhpDoc: functions
name:  genmapjsFromYamlFile
title: function genmapjsFromYamlFile($url, $servers, $options=[]) - génération d'une carte Lealet partant de l'URL du Yaml
doc: |
*/
function genmapjsFromYamlFile($url, $servers, $options=[]) {
//  echo "<pre>"; print_r($_SERVER); echo "</pre>";
// En cas d'exécution sur localhost, les url contenant 'http://visu.gexplor.fr/' sont remplacées par:
// 'http://localhost/~benoit/gexplor/visu/' sur le Mac ou 'http://localhost/gexplor/visu/' sur Vaio
  if ((strncmp($url, 'http://visu.gexplor.fr/', 23)==0) and ($_SERVER['SERVER_NAME']=='localhost')) {
    if (strncmp($_SERVER['SCRIPT_NAME'],'/~benoit/gexplor/visu/', 22)==0)
      $url = str_replace('http://visu.gexplor.fr/','http://localhost/~benoit/gexplor/visu/',$url);
    else
      $url = str_replace('http://visu.gexplor.fr/','http://localhost/gexplor/visu/',$url);
  }
  if (!($yamlSrce = file_get_contents($url)))
    die("Lecture de <a href='$url' target='_blank'>$url</a> impossible");
  genmapjsFromYaml($yamlSrce, $servers, $options);
}

/*PhpDoc: functions
name:  genmapjsFromYaml
title: function genmapjsFromYaml($yamlSrce, $servers, $options=[]) - génération d'une carte Lealet partant du source Yaml
doc: |
*/
function genmapjsFromYaml($yamlSrce, $servers, $options=[]) {
  if (!($map = spycLoad($yamlSrce))) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo $yamlSrce;
    die();
  }
//  echo "<pre>"; print_r($map); echo "</pre>";
  genmapjs($map, $servers, $options);
}

/*PhpDoc: functions
name:  genmapjs
title: function genmapjs($map, $servers, $options) - génération d'une carte Lealet
doc: |
  $map contient la définition de la carte, c'est un tableau Php correspondant à la définition Yaml
  $servers est la liste des serveurs définis
  $options est un tableau d'options possibles ; les possibilités sont:
  - 'mapstyle' : un style injecté dans dans le <div>, défaut: 'height: 100%; width: 100%'
  - 'cview' : l'URL d'un script appelé à chaque déplacement de la carte
*/
function genmapjs($map, $servers, $options) {
  $mapstyle = (isset($options['mapstyle']) ? $options['mapstyle'] : 'height: 100%; width: 100%');
  $cview = (isset($options['cview']) ? $options['cview'] : null);
//  echo "<pre>"; print_r($map);
  foreach (['title','center','zoom','baseLayers','overlays'] as $field)
    if (!isset($map[$field]))
      throw new Exception("Erreur: $field non defini dans la carte");
  $geoportalControls = []; // Liste des contrôles du Géoportail demandés
  $tools = (isset($map['tools']) ? $map['tools'] : []);
  foreach ($tools as $tool)
    if (in_array($tool, ['L.geoportalControl.SearchEngine','L.geoportalControl.ReverseGeocode','L.geoportalControl.Route','L.geoportalControl.Isocurve','L.geoportalControl.MousePosition']))
      $geoportalControls[] = $tool;
  foreach (['baseLayers','overlays'] as $layerType)
    foreach ($map[$layerType] as $no => $layer)
      foreach (['server','layer'] as $field)
        if (!isset($layer[$field]))
          throw new Exception("Erreur: ${layerType}[$no].$field non defini");
    
  $leafletVersion = '1.0';
  if ($geoportalControls)
    $leafletVersion = '0.7.0'; // avec l'extension Géoportail, il faut utiliser Leaflet 0.7.0
  
  echo <<<EOT
<html>
  <head>
    <title>$map[title]</title>
    <meta charset="UTF-8">
<!-- meta nécessaire pour le mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<!-- styles nécessaires pour le mobile -->
    <link rel="stylesheet" href="http://visu.gexplor.fr/viewer.css">
<!-- styles et src de Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@$leafletVersion/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@$leafletVersion/dist/leaflet.js"></script>
<!-- Include the edgebuffer plugin -->
    <script src="http://visu.gexplor.fr/lib/leaflet.edgebuffer.js"></script>\n
EOT;
  if ($cview)
    echo <<<EOT
<!-- Include the uGeoJSON plugin -->
    <script src="http://visu.gexplor.fr/lib/leaflet.uGeoJSON.js"></script>\n
EOT;
  if ($geoportalControls)
    echo <<<EOT
<!-- Extension Géoportail pour Leaflet -->
  <script src="http://visu.gexplor.fr/lib/GpPluginLeaflet/GpPluginLeaflet.js"></script>
  <link rel="stylesheet" href="http://visu.gexplor.fr/lib/GpPluginLeaflet/GpPluginLeaflet.css" />\n
EOT;
  echo <<<EOT
  </head>
  <body>
    <div id="map" style="$mapstyle"></div>
    <script>\n
EOT;
  if ($geoportalControls)
    echo "function go() {\n"; // 
  echo "var map = L.map('map').setView([$map[center]], $map[zoom]); // view pour la zone\n";
  if (isset($map['locate']) and ($map['locate']=='true'))
    echo "map.locate({setView: true, maxZoom: 16});\n";
  echo "L.control.scale({position:'bottomleft', metric:true, imperial:false}).addTo(map);\n";

  foreach (['baseLayers'=>'base','overlays'=>'overlay'] as $layerType => $var)
    foreach ($map[$layerType] as $no => $layer) {
      if (!isset($servers[$layer['server']]))
        throw new Exception("Erreur: Serveur '$layer[server]' utilisé dans la carte sans être défini dans la liste des serveurs");
      echo "\nvar $var$no = ",
           $servers[$layer['server']]->genLayerDef(
              $layer['layer'],
              (isset($layer['attribution']) ? $layer['attribution'] : ''),
              (isset($layer['style']) ? $layer['style'] : null),
              ((isset($map['detectRetina']) and ($map['detectRetina']=='true')) ? 'true' : 'false'));
    }
    
  $selected=0;
  foreach ($map['baseLayers'] as $no => $layer)
    if (isset($layer['selected']) and ($layer['selected']=='true'))
      $selected = $no;
  echo "\nmap.addLayer(base$selected);\n";
  foreach ($map['overlays'] as $no => $layer)
    if (isset($layer['selected']) and ($layer['selected']=='true'))
      echo "\nmap.addLayer(overlay$no);\n";
// si l'option cview est définie alors ajout d'une couche uGeoJSONLayer pour transmettre les coordonnées courantes
  if ($cview)
    echo "L.uGeoJSONLayer({endpoint: '$cview', usebbox: true}).addTo(map);\n";
  $titles = ['baseLayers'=>[], 'overlays'=>[]];
  foreach (['baseLayers'=>'base','overlays'=>'overlay'] as $layerType => $var)
    foreach ($map[$layerType] as $no => $layer) {
      if (isset($layer['title'])) // si le titre de la couche est défini dans la carte je l'utilise
        $title = $layer['title'];
      else { // sinon j'utilise le titre défini dans les capacités du serveur
        $lyr = $servers[$layer['server']]->getLayerByName($layer['layer'])->layer();
        $title = $lyr['title'];
      }
      $titles[$layerType][] = "\"$title\" : $var$no";
    }
  echo "
<!-- ajout de l'outil de sélection de couche -->
L.control.layers({
  ",implode(",\n  ",$titles['baseLayers']),"
}, {
  ",implode(",\n  ",$titles['overlays']),"
}).addTo(map);
";
  if ($geoportalControls) {
    foreach ($geoportalControls as $geoportalControl)
      echo "  map.addControl($geoportalControl({}));\n";
    echo <<<EOT
}
Gp.Services.getConfig({
  apiKey: '2hl0xk4s8pz482s81o4nrilt',
  onSuccess: go
});\n
EOT;
  }
  echo "\n      </script>\n    </body>\n</html>\n";
}
