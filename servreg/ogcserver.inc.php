<?php
/*PhpDoc:
name:  ogcserver.inc.php
title: ogcserver.inc.php - classe OgcServer parente des différents types de serveur OGC
classes:
doc: |
  class Server:
  - cap() - renvoie les capacités Ogc simplifiées comme objet SimpleXml pour les serveurs Ogc
                 ou des capacités adhoc comme tableau Php pour les serveurs Tile
  - conf() - renvoie l'enregistrement de conf comme tableau Php + id
  - title(), abstract(), availableInWM(), showInHtml()
  - layers() - renvoie un tableau Php [id=>Layer] / pour Wmts: id est l'identifiant, pour Wms: id est un numéro d'ordre
  - layer(name) - renvoie la Layer de nom name / id en Wmts, name en Wms
  class Layer:
  - title(), name(), abstract(), availableInWM(), showInXml()
  - children() - renvoie un tableau Php [id=>Layer] / [] pour Wmts, pour Wms: id est un numéro d'ordre
  leafletJS(options) - renvoie le code JS pour inclure al couche dans une carte Leaflet
journal: |
  7/3/2017:
    fork dans viuserv
  24/2/2017:
    ajout d'une remplacement d'un éventuel espace de nom wms
  4/11/2016:
    amélioration
  30/10/2016:
    première version
*/
/*PhpDoc: classes
name:  OgcServer
title: class OgcServer - classe parente des différents types de serveur OGC
methods:
doc: |
  Les espaces de noms des capacités sont supprimés pour simplifier l'utilisation des capacités avec SimpleXml
  ows est remplacé par un préfixe ows_
*/
class OgcServer {
  protected $conf; // conf issue de servers.yaml, contient 'id', 'title' & 'url'
  protected $cap; // null ou objet SimpleXmlElement des capacités simplifiées
  
  function __construct($conf) {
    $this->conf = $conf;
    $this->cap = null;
  }
    
  function conf() { return $this->conf; }
// retourne s'il existe le contenu du registre pour une couche
  function getLayerConf($lyrName) {
    if (!isset($this->conf['layers']))
      return null;
    foreach ($this->conf['layers'] as $layer)
      if (!$layer)
        throw new Exception("Dans OgcServer::getLayerConf($lyrName) layer null");
      elseif ($layer['name'] == $lyrName)
        return $layer;
    return null;
  }
  function title() { return $this->conf['title']; }
  
  function cap() {
    if (!$this->cap) {
      $filepath = $this->conf['servregPath'].'../capabilities/'.$this->conf['id'].'.xml';
      if (!($cap = @file_get_contents($filepath)))
        throw new Exception("Erreur: Lecture de $filepath impossible");
      if ((strncmp($cap,'<?xml ',6)<>0) and (strncmp($cap,'<WMS_Capabilities ',18)<>0))
        throw new Exception("Erreur: le fichier $filepath des capacités du serveur n'est pas un fichier XML");
      $cap = str_replace(
                ['<ows:','</ows:','<wms:','</wms:','<ns2:','</ns2:',' xlink:'],
                ['<ows_','</ows_','<','</','<','</',' xlink_'],
                $cap);
      $this->cap = new SimpleXmlElement($cap);
    }
    return $this->cap;
  }
  
// génère une sortie des capacités simplifiées en XML pour les serveurs Ogc ou en JSON pour les autres
  function getCap() {
    header('Content-Type: text/xml');
    die($this->cap()->asXml());
  }
};
