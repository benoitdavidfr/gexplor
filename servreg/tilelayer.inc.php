<?php
/*PhpDoc:
name:  tilelayer.inc.php
title: tilelayer.inc.php - définition de la classe pour exploiter les capacités d'une couche du serveur de tuiles
classes:
doc: |
journal: |
  18/3/2017 :
    Remplacement de TileLayer::availableInWM() en TileLayer::availableInWmOrGeo() et TileLayer::availableInCrs()
  9/3/2017:
    fork pour adaptation à viuserv
  13/11/2016:
    première version
*/
/*PhpDoc: classes
name:  TileLayer
methods:
title: class TileLayer - exploite les capacités d'une couche tuilées
doc: |
*/
class TileLayer implements Layer {
  private $server; // référence vers le serveur tile
  private $cap; // capacités de la couche
  
  function title() { return (string)$this->cap->title; }
  function name() { return (string)$this->cap->name; }
  function format() { return (string)$this->cap->format; }
  function minZoom() { return (string)$this->cap->minZoom; }
  function maxZoom() { return (string)$this->cap->maxZoom; }
  function availableInWmOrGeo() { return true; }
  function availableInCrs($crs) { return ($crs=='EPSG:3857'); }

  function __construct($server, $cap) {
    $this->server = $server;
    $this->cap = $cap;
  }
  
// children() - renvoie un tableau Php [id=>Layer] / id est un numéro d'ordre
// Toujours vide pour tile
  function children() { return []; }
  
/*PhpDoc: methods
name:  getAbstract
title: function getAbstract() - fabrique le résumé de la couche
*/
  function getAbstract() {
    $abstract = (isset($this->cap->abstract) ? $this->cap->abstract : '');
    if (isset($this->cap->doc_url))
      $abstract .= ($abstract ? "<br>\n" : '')
                  ."<a href='".$this->cap->doc_url."' target='documentation'>".$this->cap->doc_url."</a>";
    if (isset($this->cap->doc_urls)) {
      $abstract .= ($abstract ? "<br>\n" : '')
                  ."Définition en fonction du niveau de zoom:<br>"
                  ."<table border=1><th>minZoom</th><th>maxZoom</th><th>titre/documentation</th>";
      foreach ($this->cap->doc_urls as $doc_url)
        $abstract .=  "<tr><td align=center>$doc_url->minZoom</td><td align=center>$doc_url->maxZoom</td>"
                     ."<td><a href='$doc_url->doc_url' target='documentation'>$doc_url->title</td></tr>\n";
      $abstract .= "</table>\n";
    }
    return $abstract;
  }

/*PhpDoc: methods
name:  getAbstract
title: function getAbstractAsText() - fabrique le résumé de la couche
*/
  function getAbstractAsText() {
    $abstract = (isset($this->cap->abstract) ? $this->cap->abstract : '');
    if (isset($this->cap->doc_url))
      $abstract .= ($abstract ? "\n" : '')
                  ."Voir: ".$this->cap->doc_url;
    if (isset($this->cap->doc_urls)) {
      $abstract .= ($abstract ? "\n" : '')
                  ."Définition en fonction du niveau de zoom:\n";
      foreach ($this->cap->doc_urls as $doc_url)
        $abstract .=  "$doc_url->minZoom : $doc_url->maxZoom -> $doc_url->doc_url\n";
    }
    return $abstract;
  }
  
/*PhpDoc: methods
name:  showInHtml
title: function showInHtml() - affiche en HTML les capacités de la couche
doc: |
*/
  function showInHtml() {
    echo "<pre>";
    die(json_encode($this->cap,JSON_FORCE_OBJECT|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }

/*PhpDoc: methods
name:  showLayerInJson
title: function showLayerInJson() - transmet en JSON les capacités de la couche
*/
  function showLayerInJson() {
    header("Content-Type: text/plain; charset=utf-8");
    die(json_encode($this->cap, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
/*
// retourne les caractéristiques de la couche sous la forme d'un tableau Php
  function layer() {
    return [
      'title'=>(string)$this->cap->title,
      'format'=>(string)$this->cap->format,
      'minZoom'=>(string)$this->cap->minZoom,
      'maxZoom'=>(string)$this->cap->maxZoom,
    ];
  }
*/
/*PhpDoc: methods
name:  baseLayer
title: function baseLayer() - une couche est une couche de base ssi elle est en jpeg
*/
  function baseLayer() { return ((string)$this->cap->format=='image/jpeg'); }
  
/*PhpDoc: methods
name:  genLegend
title: function genLegend($style=null) - retourne le code HTML affichant la légende la couche
doc: |
  A ce stade, pas d'exemple de serveur tile avec légende
*/
  function genLegend($style=null) {
    return "Pas de légende";
  }
  
// Retourne la définition JS de la couche
  function leafletJS($options=[]) {
//    echo "cap="; print_r($this->cap);
    $lyr = $this->cap;
    $fmt = ($lyr->format=='image/jpeg' ? 'jpg' : 'png');
    $tileurl = $this->server->conf()['url'].'/'.$lyr->name."/{z}/{x}/{y}.$fmt";
    $options['format'] = $lyr->format;
    $options['minZoom'] = $lyr->minZoom;
    $options['maxZoom'] = $lyr->maxZoom;
    return [ 'title'=> $this->title(),
             'lfunc'=> 'L.tileLayer',
             'url'=> $tileurl,
             'options'=> $options,
           ];
  }
};