<?php
/*PhpDoc:
name:  gpu.php
title: gpu.php - exploite gpu.yaml pour générer le serveur GpU et la carte GpU
includes: [ '../../../phplib/yaml.inc.php' ]
doc: |
  gpu.yaml contient la structure de la légende du GpU y compris les références vers les images de la légende
  Il contient aussi la liste des catégories de servitudes recopiées depuis le code du GpU le 5/11/2016
  gpu.php permet :
  - d'afficher la légende afin de vérifier la cohérence du fichier Yaml
  - de constater des erreurs d'organisation dans le GpU
  - de générer le "serveur GpU" ainsi que la carte GpU

journal: |
  5/11/2016:
    première version
*/
require_once dirname(__FILE__).'/../../../phplib/yaml.inc.php';

function showlegend($legend, &$sup) {
  echo "<ul>\n";
  foreach ($legend as $lib => $leg)
    if (isset($leg['codes']) or isset($leg['image'])) {
      $imageurl = "http://www.geoportail-urbanisme.gouv.fr/bundles/igngpu/images/map_legend/$leg[image]?v2.0.30";
      echo "<li>$lib",(isset($leg['codes']) ? " ($leg[codes])" : ''),"<br>\n",
           "<img src='$imageurl' alt='$imageurl'>\n";
      if (isset($leg['codes'])) {
        echo "<ul>\n";
        foreach (explode(', ',$leg['codes']) as $code) {
          echo "<li>$code : $sup[$code]\n";
          unset($sup[$code]);
        }
        echo "</ul>\n";
      }
    } else {
      echo "<li>$lib\n";
      if ($leg)
        showlegend($leg, $sup);
    }
  echo "</ul>\n";
}

function flatten($legend) {
  $flatten=[];
  foreach ($legend as $lib => $leg) {
// echo "lib=$lib; leg="; print_r($leg);
    $entry = ['title'=>$lib];
    foreach (['image','layer','codes'] as $field)
      if (isset($leg[$field]))
        $entry[$field] = $leg[$field];
    $flatten[] = $entry;
    if (!isset($leg['codes']) and !isset($leg['image']) and $leg)
    $flatten = array_merge($flatten, flatten($leg));
  }
  return $flatten;
}

$yaml = yaml_parse(file_get_contents('gpu.yaml'));
// header('Content-Type: text/plain; charset=UTF-8'); print_r(yaml_parse($yaml)); die();
switch (isset($_GET['action']) ? $_GET['action'] : null) {
  case null:
    echo "<html><head><title>gpulegend</title><meta charset='UTF-8'></head><body>\n";
    echo "Actions proposées:<ul>\n",
         "<li><a href='?action=showLegend'>Affichage de la légende et vérification de la prise en compte de toutes les catégories de SUP</a>\n",
         "<li><a href='?action=showSUP'>Affichage de la liste des catégories de SUP</a>\n",
         "<li><a href='?action=genServer'>Génèration du serveur GpU</a>\n",
         "<li><a href='?action=genMap'>Génèration de la carte GpU</a>\n",
         "<li><a href='?action=showYaml'>Affichage du texte Yaml de configuration</a>\n",
         "</ul>\n";
    die();
    
// Affichage de la légende et vérification de la prise en compte de toutes les catégories de SUP
  case 'showLegend':
    echo "<html><head><title>gpulegend</title><meta charset='UTF-8'></head><body>\n";
    echo "<h2>Légende du GpU</h2>\n";
    showlegend($yaml['legend'], $yaml['SUP']);
    echo "<h3>Catégories de SUP absentes de la légende</h3>\n";
    echo "<table border=1>\n";
    foreach ($yaml['SUP'] as $code => $label)
      echo "<tr><td>$code</td><td>$label</td></tr>\n";
    die();
    
// Affichage de la liste des SUP
  case 'showSUP':
    echo "<html><head><title>gpulegend</title><meta charset='UTF-8'></head><body>\n";
    echo "<h2>Liste des catégories de SUP du GpU</h2>\n";
    echo "<table border=1>\n";
    foreach ($yaml['SUP'] as $code => $label)
      echo "<tr><td>$code</td><td>$label</td></tr>\n";
    die();
    
// Génération du serveur
  case 'genServer':
    header('Content-Type: text/plain; charset=UTF-8');
//    print_r(flatten($yaml['legend'])); die();
    echo <<<EOT
phpDoc:
  name: gpu.php?action=genServer
  title: Définition du serveur GpU
  journal: |
    5/11/2016
      première version
      Test de la définition d'une légende absente du serveur
classification:
servers:
  gpu:
    title: Géoportail de l'urbanisme
    class: MinEnv
    url: http://wxs-gpu.mongeoportail.ign.fr/externe/vkd1evhid6jdj5h4hkhyzjto/wms/v?
    protocol: WMS
    layers:

EOT;
    foreach (flatten($yaml['legend']) as $entry) {
      if (!isset($entry['image']))
        continue;
      $layer = (isset($entry['layer']) ? $entry['layer'] : basename($entry['image'], '.png'));
      $codes = (isset($entry['codes']) ? " (SUP $entry[codes])" : '');
      $url = "http://www.geoportail-urbanisme.gouv.fr/bundles/igngpu/images/map_legend/$entry[image]?v2.0.30";
      echo "      - name: $layer\n",
           "        title: $entry[title]$codes\n",
           "        styles:\n",
           "          - legend:\n",
           "              url: $url\n";
    }
    die();
    
// Génération de la carte
  case 'genMap':
    header('Content-Type: text/plain; charset=UTF-8');
    echo <<<EOT
title: carte GpU
phpDoc:
  name: gpu.php?action=genMap
  title: carte des couches du GpU
  doc: |
  journal: |
    5/11/2016:
      amélioration
    2/11/2016:
      première version
center: 48, 3
zoom: 8
detectRetina: true
baseLayers:
  - server: IGNFGP-WMTS-WM
    layer: GEOGRAPHICALGRIDSYSTEMS.PLANIGN
    selected: true
  - title: Cartes IGN classiques
    server: IGNFGP-WMTS-WM
    layer: GEOGRAPHICALGRIDSYSTEMS.MAPS
  - title: Cartes IGN Express
    server: IGNFGP-WMTS-WM
    layer: GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.STANDARD
  - title: Ortho-images
    server: IGNFGP-WMTS-WM
    layer: ORTHOIMAGERY.ORTHOPHOTOS
overlays:

EOT;
    foreach (flatten($yaml['legend']) as $entry) {
      if (!isset($entry['image']))
        continue;
      $layer = (isset($entry['layer']) ? $entry['layer'] : basename($entry['image'], '.png'));
      echo "  - server: gpu\n",
           "    layer: $layer\n";
    }
    die();
    
// Affichage du texte Yaml de configuration
  case 'showYaml':
    header('Content-Type: text/plain; charset=UTF-8');
    echo file_get_contents('gpu.yaml');
}
