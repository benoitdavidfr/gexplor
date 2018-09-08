<?php
/*PhpDoc:
name:  tileserver.inc.php
title: tileserver.inc.php - classe TileServer - Serveur de tuiles
includes: [ tilelayer.inc.php ]
classes:
doc: |
  Leaflet utilise un protocole simple fondé sur OSM pour définir des serveurs de tuiles.
  J'appelle ce prototocle tile et la classe TileServer permet d'interfacer visu avec ce type de serveur.
  Il est appelé avec une url de la forme : tile.php/{layer}/{z}/{x}/{y}.[png|jpg]
  Toutes les couches sont en projection EPSG:3857 et utilisent la pyramide définie par OSM.
  Pour fournir les couches exposées, j'ai défini un fichier de capacités très simple fourni lors de l'appel de tile.php.
journal: |
  19/2/2017:
    Ajout de availableInWM()
  12/2/2017:
    Migration vers les capacités en JSON
  13/11/2016:
    Première version
*/
require_once 'tilelayer.inc.php';
/*PhpDoc: classes
name:  TileServer
title: class TileServer - Classe représentant un serveur de type tile
methods:
doc: |
*/
Class TileServer {
  protected $id; // id dans servers.yaml
  protected $conf; // conf issue de servers.yaml, contient 'title' & 'url'
  protected $cap; // null ou objet des capacités correspondant à l'appel de tile.php
  
  function __construct($id, $conf) {
    $this->id = $id;
    $this->conf = $conf;
    $this->cap = null;
  }
  
  function id() { return $this->id; }
  
  function cap() {
    if (!$this->cap) {
      if (!($cap = @file_get_contents($this->conf['url'])))
        throw new Exception("Lecture de $this->id.xml impossible");
      $this->cap = json_decode($cap);
    }
    return $this->cap;
  }
  
  function getAbstract() { return $this->cap()->abstract; }
  
  function availableInWM() { return true; }

/*PhpDoc: methods
name:  showCapInJson
title: function showCapInJson() - transmet en JSON les capacités du serveur
doc: |
*/
  function showCapInJson() {
    header("Content-Type: text/plain; charset=utf-8");
    die(json_encode($this->cap(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
  
// Affichage de la liste des ressources sous la forme d'un tableau HTML
  function showResources() {
// les infos du titre proviennent de servers.yaml
    echo "<html><head><title>server</title><meta charset='UTF-8'></head>\n",
         "<h2>Serveur de tuiles \"",$this->conf['title'],"\" ($this->id)</h2>\n";
    $cap = $this->cap();
//    echo "<pre>this="; print_r($this); echo "</pre>\n"; die();
//    echo "<pre>cap="; print_r($cap); echo "</pre>\n"; die();
    echo "<h3>Caractéristiques issues des capacités du serveur</h3>",
         "<table border=1>",
         "<tr><td><i>Titre :</i></td><td>",$cap->title,"</td></tr>\n",
         "<tr><td><i>Abstract&nbsp;:</i></td><td>",$cap->abstract,"</td></tr>\n",
         "<tr><td><i>URL :</i></td><td>",$this->conf['url'],"</td></tr>\n",
         "<tr><td><i>Capacités</i></td><td><a href='?action=showCapInJson&amp;server=$this->id'>en JSON</a></td></tr>\n",
         "</table>\n";
    echo "<h3>Les couches</h3>\n",
         "<table border=1><th>Titre</th><th>Résumé</th><th>Identifiant</th><th>Format</th><th>Zooms</th>";
    foreach ($cap->layers as $layer) {
//      echo "<tr><td colspan=4>"; print_r($layer); echo "</td></tr>\n";
      $name = $layer->name;
      $tileLayer = new TileLayer($this, $layer);
      echo "<tr>",
           "<td><a href='?action=showLayerInJson&amp;server=$this->id&amp;layer=",urlencode($name),"'>",$layer->title,"</a></td>",
           "<td>",$tileLayer->getAbstract(),"</td>",
           "<td>",$layer->name,"</td>",
           "<td>",$layer->format,"</td>",
           '<td>',$layer->minZoom,' - ',$layer->maxZoom,'</td>',
           "</tr>\n";
    }
    echo "</table>\n";
  }
  
/*PhpDoc: methods
name:  getLayerById
title: function getLayerById($layerId) - trouve une couche identifiée par son identifiant,  retourne un objet TileLayer
*/
  function getLayerById($layerId) {
    foreach ($this->cap()->layers as $layer) {
      if ($layer->name==$layerId) {
        return new TileLayer($this, $layer);
      }
    }
    throw new Exception("TileServer::getLayerById(layerId=$layerId) : layerId unknown");
  }
  
/*PhpDoc: methods
name:  getLayerByName
title: function getLayerByName($layerId) - trouve une couche identifiée par son identifiant,  retourne un objet WmtsLayer
*/
  function getLayerByName($layerId) { return $this->getLayerById($layerId); }
  
// Génération de la définition JS de la layer $layerId
  function genLayerDef($layerId, $attribution, $style, $detectRetina) {
    $lyr = $this->getLayerById($layerId)->layer();
//    print_r($lyr);
    $fmt = ($lyr['format']=='image/jpeg' ? 'jpg' : 'png');
    $tileurl = $this->conf['url']."/$layerId/{z}/{x}/{y}.$fmt";
    echo "new L.TileLayer(
    '$tileurl',
    { format: '$lyr[format]', minZoom: $lyr[minZoom], maxZoom: $lyr[maxZoom], detectRetina: $detectRetina,
      attribution: \"$attribution\"
    }
);";
  }
  
// Renvoit l'arbre des couches avec pour chacune son nom et son titre
  function layerTree() {
    $tree = [];
    foreach ($this->cap()->layers as $layer)
      $tree[] = [
        'name' => $layer->name,
        'title' => $layer->title,
      ];
    return $tree;
  }
};
