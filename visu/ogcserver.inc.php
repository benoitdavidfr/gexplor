<?php
/*PhpDoc:
name:  ogcserver.inc.php
title: ogcserver.inc.php - classe OgcServer parente des différents types de serveur OGC
classes:
doc: |
journal: |
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
  protected $id; // id dans servers.yaml
  protected $conf; // conf issue de servers.yaml, contient 'title' & 'url'
  protected $cappath; // chemin relatif vers le répertoire cache des capacités
  protected $cap; // null ou objet SimpleXmlElement des capacités simplifiées
  
  function __construct($id, $conf, $cappath='') {
    $this->id = $id;
    $this->conf = $conf;
    $this->cappath = $cappath;
    $this->cap = null;
  }
  
  function id() { return $this->id; }
  
  function cap() {
    if (!$this->cap) {
      $filepath = $this->cappath."capabilities/$this->id.xml";
      if (!($cap = @file_get_contents($filepath)))
        throw new Exception("Lecture de $filepath impossible");
      $cap = str_replace(
                ['<ows:','</ows:','<wms:','</wms:','<ns2:','</ns2:',' xlink:'],
                ['<ows_','</ows_','<','</','<','</',' xlink_'],
                $cap);
      $this->cap = new SimpleXmlElement($cap);
    }
    return $this->cap;
  }

/*PhpDoc: methods
name:  showCapInXml
title: function showCapInXml() - transmet en XML les capacités simplifiées du serveur
doc: |
*/
  function showCapInXml() {
    header('Content-Type: text/xml');
    die($this->cap()->asXml());
  }
};
