<?php
/*PhpDoc:
name:  wmtslayer.inc.php
title: wmtslayer.inc.php - définition de la classe pour exploiter les capacités d'une couche WMTS
classes:
doc: |
journal: |
  8/11/2016:
    modif pour les serveurs de la NASA
  4/11/2016:
    première version
*/
/*PhpDoc: classes
name:  WmtsLayer
methods:
title: class WmtsLayer - exploite les capacités d'une couche WMTS
doc: |
*/
class WmtsLayer {
  private $server; // référence vers le serveur WMS
  private $cap; // SimpleXmlElement des capacités de la couche en
  
  function title() { return (string)$this->cap->ows_Title; }
  function getAbstract() { return (string)$this->cap->ows_Abstract; }
  
  function __construct($server, $cap) {
    $this->server = $server;
    $this->cap = $cap;
  }
  
/*PhpDoc: methods
name:  showLayerXml
title: function showLayerXml() - transmet en XML les capacités de la couche
*/
  function showLayerInXml() {
    header('Content-Type: text/xml');
    echo $this->cap->asXml();
    die();
  }
  
// retourne les caractéristiques de la couche sous la forme d'un tableau Php
  function layer() {
//    echo "<pre>cap="; print_r($this->cap);
    if ($this->cap->TileMatrixSetLink->TileMatrixSetLimits->TileMatrixLimits) {
      $minZoom = 99;
      $maxZoom = -99;
      foreach ($this->cap->TileMatrixSetLink->TileMatrixSetLimits->TileMatrixLimits as $TileMatrixLimit) {
        if ((int)$TileMatrixLimit->TileMatrix < $minZoom)
          $minZoom = (int)$TileMatrixLimit->TileMatrix;
        if ((int)$TileMatrixLimit->TileMatrix > $maxZoom)
          $maxZoom = (int)$TileMatrixLimit->TileMatrix;
      }
    } else {
      $minZoom = 0;
      $maxZoom = 20;
    }
    $styleId = null;
    foreach ($this->cap->Style as $style)
      if ((string)$style['isDefault']=='true')
        $styleId = (string)$style->ows_Identifier;
    if (!$styleId)
      throw new Exception("Erreur: style par defaut non trouve");
    return [
      'title'=>(string)$this->cap->ows_Title,
      'format'=>(string)$this->cap->Format,
      'defaultStyle'=>$styleId,
      'tilematrixSet'=>(string)$this->cap->TileMatrixSetLink->TileMatrixSet,
      'minZoom'=>$minZoom,
      'maxZoom'=>$maxZoom,
    ];
  }
  
/*PhpDoc: methods
name:  baseLayer
title: function baseLayer() - une couche est une couche de base ssi elle est en jpeg
*/
  function baseLayer() { return ((string)$this->cap->Format=='image/jpeg'); }
  
/*PhpDoc: methods
name:  genLegend
title: function genLegend($style=null) - retourne le code HTML affichant la légende la couche
doc: |
  A ce stade, pas d'exemple de serveur WMTS avec légende
*/
  function genLegend($style=null) {
    return "Pas de légende";
  }
}