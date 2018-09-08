<?php
/*PhpDoc:
name:  osmserver.inc.php
title: osmserver.inc.php - définition du serveur OSM et de sa couche
includes: [ servreg.inc.php ]
classes:
doc: |
  implémente les classes OsmServer et OsmLayer
journal: |
  21/3/2017 :
    correction d'un bug
  18/3/2017 :
    Evol de OsmServer::availableInWM() en OsmServer::availableInWmOrGeo()
    Remplacement de OsmLayer::availableInWM() en OsmLayer::availableInWmOrGeo() et OsmLayer::availableInCrs()
  9-11/3/2017:
    première version
*/
require_once 'servreg.inc.php';

/*PhpDoc: classes
name:  OsmServer
title: class OsmServer implements Server
methods:
doc: |
*/
class OsmServer implements Server {
  protected $conf; // conf issue de servers.yaml, contient 'id', 'title' & 'url'
  protected $layer;
  
  function __construct($conf) {
    $this->conf = $conf;
    $this->layer = new OsmLayer($this);
  }
  
// renvoie les capacités Ogc simplifiées comme objet SimpleXml pour les serveurs Ogc
// ou des capacités adhoc comme tableau Php pour les serveurs Tile
  function cap() { return $this->conf; }
// génère une sortie des capacités simplifiées en XML pour les serveurs Ogc ou en JSON pour les autres
  function getCap() {
    header('Content-type: text/plain; charset="utf-8"');
    die(json_encode(
          $this->conf,
          JSON_FORCE_OBJECT|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
// conf() - renvoie l'enregistrement de conf comme tableau Php + id
  function conf() { return $this->conf; }
//  - title(), abstract(), availableInWM(), showInHtml()
  function title() { return $this->conf['title']; }
  function getAbstract() { return "Serveur OSM"; }
  function availableInWmOrGeo() { return true; }
// layers() - renvoie un tableau Php [id=>Layer] / pour Wmts: id est l'identifiant, pour Wms: id est un numéro d'ordre
  function layers() { return [ $this->layer ]; }
// layer(name) - renvoie la Layer de nom name / id en Wmts, name en Wms
  function layer($name) { return $this->layer; }
  function showInHtml() {
    $url = "http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
    echo "<h2>Serveur OSM \"",$this->conf['title'],"\" (",$this->conf['id'],")</h2>\n";
    echo "<h3>Caractéristiques du serveur</h3>",
         "<table border=1>",
         "<tr><td><i>Titre :</i></td><td>",$this->conf['title'],"</td></tr>\n",
         "<tr><td><i>URL :</i></td><td>",$this->conf['url'],"</td></tr>\n",
         "<tr><td><i>Capacités</i></td><td><a href='$url?request=getCap'>en JSON</a></td></tr>\n",
         "</table>\n";
//    echo "<pre>conf="; print_r($this->conf); echo "</pre>";

    echo "<h3>Couche</h3>\n";
    $this->layer->showInHtml();
  }
};

/*PhpDoc: classes
name:  OsmLayer
title: class OsmLayer implements Layer
methods:
doc: |
*/
class OsmLayer implements Layer {
  protected $server;
  function __construct($server) { $this->server = $server; }
  function name() { return 'OSM'; }
  function title() { return "OSM"; }
  function getAbstract() { return "Couche OSM"; }
  function availableInWmOrGeo() { return true; }
  function availableInCrs($crs) { return ($crs=='EPSG:3857'); }
  function children() { return []; }
  
  function showInHtml() {
    echo $this->title();
    echo "<pre>leafletJS()="; print_r($this->leafletJS()) ; echo "</pre>\n";
  }
  
  function leafletJS($options=[]) {
    $options['format'] = 'image/png';
    $options['minZoom'] = 0;
    $options['maxZoom'] = 20;
    $options['detectRetina'] = true;
    $options['attribution'] = "Map data &copy; <a href='http://openstreetmap.org'>OpenStreetMap</a> contributors";
    return [ 'title'=> $this->title(),
             'lfunc'=> 'L.tileLayer',
             'url'=> $this->server->conf()['url'],
             'options'=> $options,
           ];
  }
  
  function genLegend($style=null) { return "Pas de légende"; }
};

// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

header('Content-Type: text/plain; charset=UTF-8');
$server = newServer(servreg()['servers']['OSM']);
if (0) {
  $server->getCap();
}
elseif (0) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "availableInWM: ",($server->availableInWM() ? "ok": "NO"),"\n";
}
elseif (0) {
  header('Content-Type: text/plain; charset=UTF-8');
  foreach ($server->layers() as $layer) {
    echo ($layer->name() ? $layer->name()." : " : ''),$layer->title(),"\n";
    foreach ($layer->children() as $subLayer)
      echo '** ',$subLayer->name()," : ",$subLayer->title(),"\n";
  }
}
elseif (0) {
  header('Content-Type: text/plain; charset=UTF-8');
  print_r($server->layer('OSM'));
}
elseif (1) {
  header('Content-Type: text/plain; charset=UTF-8');
  print_r($server->layer('OSM')->leafletJS());
}
die("FIN TESTS OK");