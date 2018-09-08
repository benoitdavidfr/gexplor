<?php
/*PhpDoc:
name:  wmsserver.inc.php
title: wmsserver.inc.php - définition de la classe pour exploiter les capacités d'un serveur WMS
includes: [ ogcserver.inc.php, wmslayer.inc.php ]
classes:
doc: |
journal: |
  13/2/2017:
    modif du champ style dans l'appel WMS par styles
  11/2/2017:
    ajout de WmtsServer::getLayerDef() utilisé par gexplor/gp
  5/11/2016:
    Ajout de définition de la legende dans le fichier de configuration
  4/11/2016:
    séparation du fichier en 2 par création de WmsLayer
  30/10/2016:
    première version
*/
require_once 'ogcserver.inc.php';
require_once 'wmslayer.inc.php';

/*PhpDoc: classes
name:  WmsServer
methods:
title: class WmsServer extends OgcServer - exploite les capacités d'un serveur WMS
doc: |
*/
class WmsServer extends OgcServer {
  function wmsVersion() { return (string)$this->cap()['version']; }
  function getAbstract() { return (string)$this->cap()->Service->Abstract; }
  
// Teste si au moins une ressource est exposée en projection WM
// Si $ids==[], je m'intéresse à toutes les couches du serveur
// Sinon, seule le sous-arbre défini par $ids est testé
  function availableInWM($ids=[]) {
/*
    if (!$ids) {
      $cap = $this->cap();
//    echo "WmsServer = ",$this->conf['title'],"\n";
//    echo "WmsServer = "; print_r($this);
      if (!$cap->Capability->Layer)
        return false;
//    echo "cap->Capability->Layer = "; print_r($cap->Capability->Layer);
      $layer = new WmsLayer($this, $cap->Capability->Layer);
      return $layer->availableInWM();
    }
*/
    return $this->getLayerById($ids)->availableInWM();
  }
  
// Teste si la couche $name est exposée en projection WM
  function layerAvailableInWM($name) {
    return in_array('EPSG:3857', $this->getCrsOfLayerByName($name));
  }
  
// Fabrique la liste des CRS d'une couche identifiée par son nom
  function getCrsOfLayerByName($name, $layer=null, $tabOfCrs=[], $level=0) {
//    echo $layer->Title,"<br>\n";
    if (!$layer)
      $layer = $this->cap()->Capability->Layer;
    $lyr = new WmsLayer($this, $layer);
    $tabOfCrs = array_merge($tabOfCrs, $lyr->tabOfCrs());
    if ($layer->Name == $name)
      return array_keys($tabOfCrs);
    foreach ($layer->Layer as $sublayer) {
      if ($subListOfCrs = $this->getCrsOfLayerByName($name, $sublayer, $tabOfCrs, $level+1))
        return $subListOfCrs;
    }
    return [];
  }
  
  function contactInformation() {
    $cap = $this->cap();
    if (!$cap->Service->ContactInformation)
      return null;
    return $cap->Service->ContactInformation->ContactPersonPrimary->ContactPerson
           .' ('.$cap->Service->ContactInformation->ContactPersonPrimary->ContactOrganization.')'
           .'- Mel: '.$cap->Service->ContactInformation->ContactElectronicMailAddress;
  }
  
/*PhpDoc: methods
name:  showResources
title: function showResources() - affichage des ressources, appelée par server.php
doc: |
*/
  function showResources() {
    $cap = $this->cap();
    echo "<h2>Serveur WMS \"",$this->conf['title'],"\" ($this->id)</h2>\n";
    $getcapurl = $this->conf['url']."SERVICE=WMS&amp;REQUEST=GetCapabilities";
    echo "<table border=1>",
         "<tr><td><i>version WMS :</i></td><td>",$cap['version'],"</td></tr>\n",
         "<tr><td><i>Titre :</i></td><td>",$cap->Service->Title,"</td></tr>\n",
         "<tr><td><i>Résumé :</i></td><td>",$this->getAbstract(),"</td></tr>\n",
         (!$this->contactInformation() ? '' :
            "<tr><td><i>Contact :</i></td><td>".$this->contactInformation()."</td></tr>\n"),
         "<tr><td><i>Tarif :</i></td><td>",$cap->Service->Fees,"</td></tr>\n",
         "<tr><td><i>Conditions d'accès :</i></td><td>",$cap->Service->AccessConstraints,"</td></tr>\n",
         "<tr><td><i>Capacités serveur :</i></td>",
           "<td><a href='$getcapurl'>XML d'origine</a>, ",
           "<a href='?action=showCapInXml&amp;server=$this->id'>XML simplifié</a>",
         "</td></tr>\n",
         "<tr><td><i>Capacités suppl. :</i></td>",
           "<td><a href='?action=showConf&amp;server=$this->id'>configuration</a></td>",
         "</tr>\n",
         "</table>\n";
    $rootLayer = new WmsLayer($this, $cap->Capability->Layer);
    echo "<h3>Les couches</h3><ul>\n";
    $rootLayer->showWmsLayers();
    echo "</ul>\n";
    echo "<h3>Les couches avec leur légende</h3>\n",
         "<table border=1><th>Titre de la couche</th>\n";
    $rootLayer->showWmsLayersWithLegend();
    echo "</table>\n";
  }
  
/*PhpDoc: methods
name:  showConf
title: function showConf() - afiche les infos de configuration du serveur
*/
  function showConf() {
    echo "<pre>"; print_r($this->conf); echo "</pre>\n";
  }
  
/*PhpDoc: methods
name:  getLayerById
title: function getLayerById($ids, $layer=null, $level=0) - trouve une couche identifiée par son index
doc: |
  L'index ids qui est une liste de numéros soit sous la forme d'une chaine de caractères soit sous la forme d'une liste
  La méthode retourne un objet WmsLayer
*/
  function getLayerById($ids, $layer=null, $level=0) {
    if (!$layer)
      $layer = $this->cap()->Capability->Layer;
    if ($ids=='')
      return new WmsLayer($this, $layer);
    if (!is_array($ids))
      $ids = explode(',',$ids);
    if (count($ids)==0)
      return new WmsLayer($this, $layer);
//    echo $layer->Title,"<br>\n";
    $id = array_shift($ids);
    $no = 0;
    foreach ($layer->Layer as $sublayer) {
      if ($id==$no++)
        return $this->getLayerById($ids, $sublayer, $level+1);
    }
    throw new Exception("WmsServer::getLayerById() : ids '".implode(',',$ids)."' not found");
  }
  
// Renvoie la couche portant le nom demandé en tant qu'objet WmsLayer
  function getLayerByName($name, $layer=null, $level=0) {
//    echo $layer->Title,"<br>\n";
    if (!$layer)
      $layer = $this->cap()->Capability->Layer;
    if ($layer->Name == $name)
        return new WmsLayer($this, $layer);
    foreach ($layer->Layer as $sublayer) {
      if ($lyr = $this->getLayerByName($name, $sublayer, $level+1))
        return $lyr;
    }
    if (!$level)
      throw new Exception("WmsServer::getLayerByName() : name '$name' not found");
  }
  
// recherche la partie de la configuration portant sur la couche de nom name 
  function getLayerConf($name, $conf=null) {
    if (!$conf) {
      if (!isset($this->conf['layers']))
        return null;
      else
        $conf = $this->conf['layers'];
    }
    foreach ($conf as $layerConf) {
      if ($layerConf['name']==$name)
        return $layerConf;
      if (isset($layerConf['children']))
        if ($subLayerConf = $this->getLayerConf($name, $layerConf['children']))
          return $subLayerConf;
    }
    return null;
  }
  
/*PhpDoc: methods
name:  layer
title: function getLayerDef($name, $attribution=null, $style=null, $detectRetina=true) - Génère la définition de la layer $name sous la forme d'un array Php
doc: |
journal: |
  21/2/2017:
    Si le style n'est pas fixé à l'appel de getLayerDef() alors il n'est pas généré en cortie
    Auparavant on allait chercher un des styles de la couche
*/
  function getLayerDef($name, $attribution=null, $style=null, $detectRetina=true) {
//    $lyr = $this->getLayerByName($name)->layer();
    $def = [
      'url'=>$this->conf['url'],
      'options'=> [
        'version'=> $this->wmsVersion(),
        'layers'=> $name,
        'format'=> 'image/png',
        'transparent'=> true,
      ]
    ];
//    if (!$style)
//      $style = $lyr['style'];
    if ($style)
      $def['options']['styles'] = $style;
    if ($detectRetina)
      $def['options']['detectRetina'] = true;
    if ($attribution)
      $def['options']['attribution'] = $attribution;
    return $def;
  }
  
/*PhpDoc: methods
name:  layer
title: function genLayerDef($name, $attribution=null, $style=null, $detectRetina=true) - Affiche la définition JS de la layer $name
doc: |
  Appelée par genmapjs() défini dans /gexplor/visu/genmapjs.inc.php
  Le style est celui défini dans la carte
*/
  function genLayerDef($name, $attribution=null, $style=null, $detectRetina=true) {
    $def = $this->getLayerDef($name, $attribution, $style, $detectRetina);
    echo "new L.tileLayer.wms(\n",
         "    '$def[url]',\n",
         "    ",json_encode($def['options'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n",
         ");\n";
  }
/*
  function genLayerDef($name, $attribution, $style, $detectRetina) {
    $wmsurl = $this->conf['url'];
    $lyr = $this->getLayerByName($name)->layer();
    if (!$style)
      $style = $lyr['style'];
    $style = ($style ? "style: '$style'," : '');
    $version = $this->wmsVersion();
    echo "new L.tileLayer.wms('$wmsurl',
    { version: '$version',
      layers: '$name', $style
      format: 'image/png', transparent: true, detectRetina: $detectRetina,
      attribution: \"$attribution\"
    }
);";
  }
*/
  
// Renvoit l'arbre des couches avec pour chacune son nom et son titre et éventuellement ses enfants
  function layerTree($parentLayer=null) {
    $tree = [];
    if (!$parentLayer)
      $parentLayer = $this->cap()->Capability->Layer;
//    echo "WmsServer::layerTree(): title=",$parentLayer->Title,"\n";
    foreach ($parentLayer->Layer as $layer)
      $tree[] = [
        'name' => (string)$layer->Name,
        'title' => (string)$layer->Title,
        'children' => ($layer->Layer ? $this->layerTree($layer) : null),
      ];
    return $tree;
  }
};

// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

header('Content-Type: text/plain; charset=UTF-8');
if (0) {
  foreach([
      'CLC-WMS' => 'http://clc.developpement-durable.gouv.fr/geoserver/wms?',
      'MinEnv-georisques' => 'http://georisques.gouv.fr/services?',
    ] as $id => $url) {
    echo "$id:\n";
    $server = new WmsServer($id, $url);
    print_r($server->layerTree());
  }
}
if (0) {
  foreach([
    'gpu'=> [
      'title'=> "Géoportail de l'urbanisme",
      'class'=> "MinEnv",
      'url'=> "http://wxs-gpu.mongeoportail.ign.fr/externe/vkd1evhid6jdj5h4hkhyzjto/wms/v?",
      'protocol'=> "WMS",
    ],
  ] as $id => $serverConf) {
    $server = new WmsServer($id, $serverConf);
//    print_r($server->layerTree());
    print_r($server->getLayerByName('lowscale'));
  }
}
if (0) {
  foreach([
/*
    'IGNFGP-WMS-R'=> [
      'title'=> "GP IGN WMS raster",
      'class'=> "IGNSHOMCad",
      'url'=> 'http://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/geoportail/r/wms?',
      'protocol'=> "WMS",
    ],
*/
    'Ifremer-MNT'=> [
      'title'=> "Modèles numérique de terrain (bathymétrie)",
      'url'=> 'http://www.ifremer.fr/ifremerWS/WS/wms/MNT?',
      'protocol'=> "WMS",
    ],
  ] as $id => $serverConf) {
    $server = new WmsServer($id, $serverConf);
    echo "Serveur $id ",($server->availableInWM() ? '' : 'NON '),"disponible en WM\n";
  }
}
if (1) {
  foreach([
    'IGNFGP-WMS-R'=> [
      'serverConf'=> [
        'title'=> "GP IGN WMS raster",
        'class'=> "IGNSHOMCad",
        'url'=> 'http://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/geoportail/r/wms?',
        'protocol'=> "WMS",
      ],
      'layers'=>['CADASTRALPARCELS.PARCELS'],
    ],
    'CLC-WMS'=> [
      'serverConf'=> [
        'title'=> "Corine Land Cover (SOES)",
        'class'=> "MinEnv",
        'url'=> 'http://clc.developpement-durable.gouv.fr/geoserver/wms?',
        'protocol'=> "WMS",
      ],
      'layers'=>['clc:RCLC06','xxx'],
    ],
  ] as $id => $serverDef) {
    $server = new WmsServer($id, $serverDef['serverConf']);
    foreach ($serverDef['layers'] as $lyrName) {
      echo "getCrsOfLayerByName($id,$lyrName)="; print_r($server->getCrsOfLayerByName($lyrName));
    }
  }
}
die("FIN TESTS OK");