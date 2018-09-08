<?php
/*PhpDoc:
name:  tilelayer.inc.php
title: tilelayer.inc.php - définition de la classe pour exploiter les capacités d'une couche du serveur de tuiles
classes:
doc: |
journal: |
  19/4/2017:
    correction d'un bug probablement lié au passage en Php 7
  13/11/2016:
    première version
*/
/*PhpDoc: classes
name:  TileLayer
methods:
title: class TileLayer - exploite les capacités d'une couche tuilées
doc: |
*/
class TileLayer {
  private $server; // référence vers le serveur tile
  private $cap; // capacités de la couche
  
  function title() { return (string)$this->cap->title; }
  
  function __construct($server, $cap) {
    $this->server = $server;
    $this->cap = $cap;
//    echo "<pre>"; print_r($cap); echo "</pre>\n";
  }
  
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
name:  showLayerInJson
title: function showLayerInJson() - transmet en JSON les capacités de la couche
*/
  function showLayerInJson() {
    header("Content-Type: text/plain; charset=utf-8");
    die(json_encode($this->cap, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
  
// retourne les caractéristiques de la couche sous la forme d'un tableau Php
  function layer() {
    return [
      'title'=>(string)$this->cap->title,
      'format'=>(string)$this->cap->format,
      'minZoom'=>(string)$this->cap->minZoom,
      'maxZoom'=>(string)$this->cap->maxZoom,
    ];
  }
  
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
};