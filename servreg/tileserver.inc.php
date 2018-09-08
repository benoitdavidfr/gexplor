<?php
/*PhpDoc:
name:  tileserver.inc.php
title: tileserver.inc.php - classe TileServer - Serveur de tuiles
includes: [ servreg.inc.php, tilelayer.inc.php, proxydef.inc.php ]
classes:
doc: |
  Leaflet utilise un protocole simple fondé sur OSM pour définir des serveurs de tuiles.
  J'appelle ce prototocle tile et la classe TileServer permet d'interfacer visu avec ce type de serveur.
  Il est appelé avec une url de la forme : tile.php/{layer}/{z}/{x}/{y}.[png|jpg]
  Toutes les couches sont en projection EPSG:3857 et utilisent la pyramide définie par OSM.
  Pour fournir les couches exposées, j'ai défini un fichier de capacités très simple fourni lors de l'appel de tile.php.
journal: |
  31/5/2017
    Ajout du mécanisme d'accès restreint
  18/3/2017 :
    Evol de availableInWM() en availableInWmOrGeo()
  9/3/2017:
    fork pour adaptation à viuserv
  19/2/2017:
    Ajout de availableInWM()
  12/2/2017:
    Migration vers les capacités en JSON
  13/11/2016:
    Première version
*/
require_once 'servreg.inc.php';
require_once 'tilelayer.inc.php';

/*PhpDoc: classes
name:  TileServer
title: class TileServer - Classe représentant un serveur de type tile
methods:
doc: |
*/
Class TileServer implements Server {
  protected $conf; // conf issue de servers.yaml, contient 'id, 'title', 'url' & 'viuservPath'
  protected $cap; // null ou objet des capacités correspondant à l'appel de tile.php
  
  function __construct($conf) {
    $this->conf = $conf;
    $this->cap = null;
//    print_r($this); //die("ligne ".__LINE__);
  }
  
  function conf() { return $this->conf; }
  function id() { return $this->conf['id']; }
  function title() { return $this->conf['title']; }
  
  function cap() {
    if (!$this->cap) {
      require 'proxydef.inc.php';
      $url = $this->conf['url'].(isset($this->conf['auth']) ? '?auth='.$this->conf['auth'] : '');
      if (!($cap = @file_get_contents($url, false, $stream_context)))
        throw new Exception("Lecture de $url impossible");
      $this->cap = json_decode($cap);
    }
    return $this->cap;
  }
  
  function getAbstract() { return $this->cap()->abstract; }
  
  function availableInWmOrGeo() { return true; }

/*PhpDoc: methods
name:  showCapInJson
title: function showCapInJson() - génère une sortie des capacités simplifiées en JSON pour les serveurs non Ogc
doc: |
*/
  function getCap() {
    header("Content-Type: text/plain; charset=utf-8");
    die(json_encode($this->cap(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
  
// Affichage de la liste des ressources sous la forme d'un tableau HTML
  function showInHtml() {
    $url = "http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
// les infos du titre proviennent de servers.yaml
    echo "<html><head><title>server</title><meta charset='UTF-8'></head>\n",
         "<h2>Serveur de tuiles \"",$this->conf['title'],"\" (",$this->conf['id'],")</h2>\n";
    $cap = $this->cap();
//    echo "<pre>this="; print_r($this); echo "</pre>\n"; die();
//    echo "<pre>cap="; print_r($cap); echo "</pre>\n"; die();
    echo "<h3>Caractéristiques issues des capacités du serveur</h3>",
         "<table border=1>",
         "<tr><td><i>Titre :</i></td><td>",$cap->title,"</td></tr>\n",
         "<tr><td><i>Abstract&nbsp;:</i></td><td>",$cap->abstract,"</td></tr>\n",
         "<tr><td><i>URL :</i></td><td>",$this->conf['url'],"</td></tr>\n",
         "<tr><td><i>Capacités</i></td><td><a href='$url?request=getCap'>en JSON</a></td></tr>\n",
         "</table>\n";
    echo "<h3>Les couches</h3>\n",
         "<table border=1><th>Titre</th><th>Résumé</th><th>Identifiant</th><th>Format</th><th>Zooms</th>";
    foreach ($cap->layers as $layer) {
//      echo "<tr><td colspan=4>"; print_r($layer); echo "</td></tr>\n";
      $name = $layer->name;
      $tileLayer = new TileLayer($this, $layer);
      echo "<tr>",
//           "<td><a href='$url/$name?request=getCap'>",$layer->title,"</a></td>",
           "<td><a href='$url/$name'>",$layer->title,"</a></td>",
           "<td>",$tileLayer->getAbstract(),"</td>",
           "<td>",$layer->name,"</td>",
           "<td>",$layer->format,"</td>",
           '<td>',$layer->minZoom,' - ',$layer->maxZoom,'</td>',
           "</tr>\n";
    }
    echo "</table>\n";
  }
  
/*PhpDoc: methods
name:  layer
title: function layer($name) - trouve une couche identifiée par son identifiant,  retourne un objet TileLayer
*/
  function layer($name) {
    foreach ($this->cap()->layers as $layer) {
      if ($layer->name==$name) {
        return new TileLayer($this, $layer);
      }
    }
    throw new Exception("TileServer::layer(name=$name) : name unknown");
  }
  
// Renvoit l'arbre des couches avec pour chacune son nom et son titre
  function layers() {
    $layers = [];
    foreach ($this->cap()->layers as $layer)
      $layers[$layer->name] = new TileLayer($this, $layer);
    return $layers;
  }
};

// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

header('Content-Type: text/plain; charset=UTF-8');
$server = new TileServer(servers()['servers']['IGNFGP-tile-WM']);
if (0) {
  die($server->getCap());
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
  print_r($server->layer('orthos'));
}
elseif (1) {
  header('Content-Type: text/plain; charset=UTF-8');
  die($server->layer('orthos')->leafletJS());
}
die("FIN TESTS OK");