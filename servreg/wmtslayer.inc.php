<?php
/*PhpDoc:
name:  wmtslayer.inc.php
title: wmtslayer.inc.php - définition de la classe pour exploiter les capacités d'une couche WMTS
classes:
doc: |
journal: |
  31/3/2017 :
    exploitation dans WmtsLayer::leafletJS() de WmtsServer::$zoomLevelNamePrefix initialisé dans WmtsServer::__construct()
  18/3/2017 :
    Remplacement de WmtsLayer::availableInWM() en WmtsLayer::availableInWmOrGeo() et WmtsLayer::availableInCrs()
  11/3/2017:
    Adaptation pour servreg
    ajout du style
  9/3/2017:
    fork pour adaptation à viuserv
  8/11/2016:
    modif pour les serveurs de la NASA
  4/11/2016:
    première version
*/
/*PhpDoc: classes
name:  WmtsLayer
methods:
title: class WmtsLayer implements Layer - exploite les capacités d'une couche WMTS
doc: |
*/
class WmtsLayer implements Layer {
  private $server; // référence vers le serveur WMS
  private $cap; // SimpleXmlElement des capacités de la couche en
  
  function cap() { return $this->cap; }
  function name() { return (string)$this->cap->ows_Identifier; }
  function title() { return (string)$this->cap->ows_Title; }
  function getAbstract() { return (string)$this->cap->ows_Abstract; }
  
  function __construct($server, $cap) {
    $this->server = $server;
    $this->cap = $cap;
  }
    
/*PhpDoc: methods
name:  availableInWM
title: function availableInWM() - La couche est-elle disponible en WM ?
doc: |
*/
  function availableInWM() {
    $wmEpsgIds = ['EPSG:3857','urn:ogc:def:crs:EPSG:6.18:3:3857','urn:ogc:def:crs:EPSG::3857'];
//    echo "Contents->TileMatrixSet"; print_r($this->cap()->Contents->TileMatrixSet);
    $tmsid = (string)$this->cap->TileMatrixSetLink->TileMatrixSet;
    foreach ($this->server->cap()->Contents->TileMatrixSet as $tileMatrixSet)
      if ((string)$tileMatrixSet->ows_Identifier==$tmsid)
        return in_array((string)$tileMatrixSet->ows_SupportedCRS, $wmEpsgIds);
    return false;
  }
  
/*PhpDoc: methods
name:  availableInWmOrGeo
title: function availableInWmOrGeo() - La couche est-elle disponible en WM ?
doc: |
  Pour un serveur WMTS, seule la disponibilité en WM compte car une exposition en EPSG:4326 ne seait pas
  dans une pyramide compatible avec la pyramide WM
*/
  function availableInWmOrGeo() { return $this->availableInWM(); }

/*PhpDoc: methods
name:  availableInCrs
title: function availableInCrs($crs) - La couche est-elle disponible dans le CRS ?
doc: |
  Pour un serveur WMTS, seule la disponibilité en WM compte car une exposition dans un autre CRS ne serait pas
  dans une pyramide compatible avec la pyramide WM
*/
  function availableInCrs($crs) { return (($crs=='EPSG:3857') and $this->availableInWM()); }

// children() - renvoie un tableau Php [id=>Layer] / id est un numéro d'ordre
// Toujours vide pour WMTS
  function children() { return []; }
  
  function defaultStyle() {
    $styleId = null;
    foreach ($this->cap->Style as $style)
      if ((string)$style['isDefault']=='true')
        $styleId = (string)$style->ows_Identifier;
    if (!$styleId)
      throw new Exception("Erreur: style par defaut non trouve");
    return $styleId;
  }
    
// renvoie un tableau Php adapté à la génération d'une commande JS pour insérer la couche dans Leaflet
// ['title'=>titre de la couche, 'lclass'=> classe Leaflet, 'url'=>URL d'appel de la couche, 'options'=>options]
  function leafletJS($options=[]) {
    if ($this->cap->TileMatrixSetLink->TileMatrixSetLimits->TileMatrixLimits) {
      $minZoom = 99;
      $maxZoom = -99;
      foreach ($this->cap->TileMatrixSetLink->TileMatrixSetLimits->TileMatrixLimits as $TileMatrixLimit) {
        if ((int)$TileMatrixLimit->TileMatrix < $minZoom)
          $minZoom = (int)$TileMatrixLimit->TileMatrix;
        if ((int)$TileMatrixLimit->TileMatrix > $maxZoom)
          $maxZoom = (int)$TileMatrixLimit->TileMatrix;
      }
      $options['minZoom'] = $minZoom;
      $options['maxZoom'] = $maxZoom;
    } else {
      $options['minZoom'] = 0;
      $options['maxZoom'] = 20;
    }
    $zoomLevelNamePrefix = $this->server->zoomLevelNamePrefix();
    $style = $this->defaultStyle();
    $wmtsurl = $this->server->conf()['url'].'service=WMTS&version=1.0.0&request=GetTile'
               .'&tilematrixSet='.$this->cap->TileMatrixSetLink->TileMatrixSet.'&height=256&width=256'
               ."&tilematrix=$zoomLevelNamePrefix{z}&tilecol={x}&tilerow={y}"
               .'&layer='.$this->name().'&format='.$this->cap->Format."&style=".$style;
    $options['format'] = (string)$this->cap->Format;
    return [ 'title'=> $this->title(),
             'lfunc'=> 'L.tileLayer',
             'url'=> $wmtsurl,
             'options'=> $options,
           ];
  }
    
/*PhpDoc: methods
name:  baseLayer
title: function baseLayer() - une couche est une couche de base ssi son format est jpeg
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