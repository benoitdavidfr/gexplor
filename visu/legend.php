<?php
/*PhpDoc:
name:  legend.php
title: legend.php - affichage de la légende d'une carte
includes: [ '../../phplib/yaml.inc.php', newserver.inc.php ]
doc: |
  Prend en paramètre l'URL d'un fichier texte contenant la description Yaml d'une carte
  Si le paramètres est absent, génère un formulaire permettant de donner l'URL
journal: |
  6/3/2017:
    ajout d'une exception pour OSM
  6/11/2016:
    amélioration
  5/11/2016:
    première version
*/
require_once '../../phplib/yaml.inc.php';
require_once 'newserver.inc.php';

$servers = []; // Tableau d'objets OgcServer identifiés par leur id
$serversYaml = file_get_servers();
foreach (array_keys($serversYaml['servers']) as $id)
  if ($id<>'OSM')
    $servers[$id] = newServer($serversYaml['servers'], $id);
  
if (!isset($_GET['url'])) {
  echo <<<EOT
<html><head><title>legend</title><meta charset='UTF-8'></head><body>
<table border=1><form><tr>
<td>url</td>
<td><input type='text' size=120 name='url' value=""/></td>
<td><center><input type='submit' value='Envoi'></center></td>
</tr></form></table>
</body></html>
EOT;
  die();
}

$url = $_GET['url'];
if ((strncmp($url, 'http://visu.gexplor.fr/', 23)==0) and ($_SERVER['SERVER_NAME']=='localhost'))
  $url = str_replace('http://visu.gexplor.fr/', 'http://localhost/gexplor/visu/', $url);
if (!($yamlSrce = file_get_contents($url)))
  die("Lecture de <a href='$url' target='_blank'>$url</a> impossible");
if (!($map = yaml_parse($yamlSrce))) {
  header('Content-Type: text/plain; charset=UTF-8');
  die($yamlSrce);
}

echo "<html><head><meta charset='UTF-8'><title>legende $map[title]</title></head><body>\n",
     "<h2>Légende de la carte \"$map[title]\"</h2>\n",
     "<table border=1>\n";
foreach(['baseLayers','overlays'] as $layerType)
  foreach ($map[$layerType] as $layerDef) {
    $layer = $servers[$layerDef['server']]->getLayerByName($layerDef['layer']);
// le titre de la couche est celui donné dans la carte ou en cas d'absence le titre fourni dans les capacités
    $title = (isset($layerDef['title']) ? $layerDef['title'] : $layer->title());
    echo "<tr><td><b>$title</b></td></tr>\n";
//    echo "<tr><td><pre>"; print_r($layerDef); echo "</pre></td></tr>\n";
    echo "<tr><td>",$layer->genLegend(isset($layerDef['style']) ? $layerDef['style'] : null),"</td></tr>\n";
  }
echo "</table>\n";

?>