<?php
/*PhpDoc:
name:  wmsserver.inc.php
title: wmsserver.inc.php - définition de la classe pour exploiter les capacités d'un serveur WMS
includes: [ servreg.inc.php, ogcserver.inc.php, wmslayer.inc.php ]
classes:
doc: |
  implémente la classe correspondant à un serveur WMS en version 1.3.0 ou 1.1.1
journal: |
  18-19/3/2017 :
    Evol de availableInWM() en availableInWmOrGeo()
    Les CRS acceptés pour définir des coord. géo. sont: 'EPSG:4326', 'EPSG:4171' et 'EPSG:4258'
    Prise en compte des serveurs en version 1.1.1
  7-11/3/2017:
    fork pour adaptation à servreg
    écriture partielle
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
require_once 'servreg.inc.php';
require_once 'ogcserver.inc.php';
require_once 'wmslayer.inc.php';

/*PhpDoc: classes
name:  WmsServer
methods:
title: class WmsServer extends OgcServer - exploite les capacités d'un serveur WMS
doc: |
*/
class WmsServer extends OgcServer implements Server {
  function wmsVersion() { return (string)$this->cap()['version']; }
  function getAbstract() { return (string)$this->cap()->Service->Abstract; }
  
  function contactInformation() {
    $cap = $this->cap();
    if (!$cap->Service->ContactInformation)
      return null;
    return $cap->Service->ContactInformation->ContactPersonPrimary->ContactPerson
           .' ('.$cap->Service->ContactInformation->ContactPersonPrimary->ContactOrganization.')'
           .'- Mel: '.$cap->Service->ContactInformation->ContactElectronicMailAddress;
  }
  
/*PhpDoc: methods
name:  availableInWmOrGeo
title: function availableInWmOrGeo($layer=null) - Une couche au moins du serveur est-elle disponible en WM ou en géo ?
doc: |
  On considère géo comme EPSG:4326, EPSG:4171 ou EPSG:4258
  Appel récursif. sur les objet SimpleXml layer
  Si layer==null test à la racine et appel pour chaque sous-couche
*/
  function availableInWmOrGeo($layer=null) {
    $wmOrGeoCrsIds = ['EPSG:3857','EPSG:4326','EPSG:4171','EPSG:4258'];
    if (!$layer)
      $layer = $this->cap()->Capability->Layer;
//    echo "appel de availableInWmOrGeo() sur layer ",$layer->Title,"\n";
// Test dans la liste des CRS de la racine
    if ($this->wmsVersion()=='1.3.0') {
      if ($layer->CRS)
        foreach ($layer->CRS as $crs)
          if (in_array((string)$crs, $wmOrGeoCrsIds))
            return true;
    } else {
      if ($layer->SRS)
        foreach ($layer->SRS as $crs)
          if (in_array((string)$crs, $wmOrGeoCrsIds))
            return true;
    }
// Sinon test de chaque sous-couche
    if ($layer->Layer)
      foreach ($layer->Layer as $sublayer)
        if ($this->availableInWmOrGeo($sublayer))
          return true;
    return false;
  }
  
/*PhpDoc: methods
name:  layers
title: function layers() - renvoie un tableau Php [Layer]
doc: |
  La liste des couches ne comprend pas la couche racine
*/
  function layers() {
    $layers = [];
    $inherits = ['CRS'=>[]];
    if ($this->wmsVersion()=='1.3.0') {
      if ($this->cap()->Capability->Layer->CRS)
        foreach ($this->cap()->Capability->Layer->CRS as $crs)
          $inherits['CRS'][] = (string)$crs;
    } else {
      if ($this->cap()->Capability->Layer->SRS)
        foreach ($this->cap()->Capability->Layer->SRS as $crs)
          $inherits['CRS'][] = (string)$crs;
    }
    if ($this->cap()->Capability->Layer->Layer)
      foreach ($this->cap()->Capability->Layer->Layer as $layer)
        $layers[] = new WmsLayer($this, $layer, $inherits);
    return $layers;
  }
  
/*PhpDoc: methods
name:  layer
title: function layer($name, $layer=null, $inherits=[]) - renvoie la Layer de nom name ou null
doc: |
  L'appel normal s'effectue avec uniquement le paramètre $name
  Les autres paramètres sont exclusivement utilisés dans l'appel récursif:
  - $layer est un objet SimpleXml layer
  - $inherits est transmet le paramètre hérité entre couches CRS
*/
  function layer($name, $layer=null, $inherits=[]) {
//    echo "WmsServer::layer(name=$name, layer, ",
//          "inherits[CRS]=",(isset($inherits['CRS']) ? implode($inherits['CRS']) : ''),"<br>\n";
    if (!$layer)
      $layer = $this->cap()->Capability->Layer;
    if ($layer->Name==$name)
      return new WmsLayer($this, $layer, $inherits);
    if (!isset($inherits['CRS']))
      $inherits['CRS'] = [];
    if ($this->wmsVersion()=='1.3.0') {
      foreach ($layer->CRS as $crs)
        if (!in_array((string)$crs, $inherits['CRS']))
          $inherits['CRS'][] = (string)$crs;
    } else {
      foreach ($layer->SRS as $crs)
        if (!in_array((string)$crs, $inherits['CRS']))
          $inherits['CRS'][] = (string)$crs;
    }
    foreach ($layer->Layer as $sublayer)
      if ($result = $this->layer($name, $sublayer, $inherits))
        return $result;
    return null;
  }
  
/*PhpDoc: methods
name:  showInHtml
title: function showInHtml() - affichage d'une fiche Html de présentation du serveur
doc: |
*/
  function showInHtml() {
    $cap = $this->cap();
    echo "<h2>Serveur WMS \"",$this->conf['title'],"\" (",$this->conf['id'],")</h2>\n";
    $url = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]$_SERVER[PATH_INFO]";
    $params = ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
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
           "<td><a href='$getcapurl' target='_blank'>XML d'origine</a>, ",
           "<a href='$url",($params?"$params&amp;":'?'),"request=GetCap'>XML simplifié</a>",
         "</td></tr>\n",
         "<tr><td><i>Capacités suppl. :</i></td>",
           "<td><a href='$url",($params?"$params&amp;":'?'),"request=showConf'>configuration</a></td>",
         "</tr>\n",
         "<tr><td><i>Dispo. WM/géo :</i></td>",
           "<td>",($this->availableInWmOrGeo()?'oui':'non'),"</td>",
         "</tr>\n",
         "</table>\n";
    $rootLayer = new WmsLayer($this, $cap->Capability->Layer, []);
    echo "<h3>Les couches</h3><ul>\n";
    $rootLayer->showWmsLayers();
    echo "</ul>\n";
    echo "<h3>Les couches avec leur légende</h3>\n",
         "<table border=1><th>Titre de la couche</th>\n";
    $rootLayer->showWmsLayersWithLegend();
    echo "</table>\n";
  } 
};


// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

$serverId = 'IGNFGP-WMS-R';
$serverId = 'CLC-WMS';
$server = newServer(servreg()['servers']['CLC-WMS']);
if (0) {
  header('Content-Type: text/xml');
  echo $server->cap()->asXml();
}
elseif (0) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo ($server->availableInWM() ? "ok": "NO"),"\n";
}
elseif (0) {
  header('Content-Type: text/plain; charset=UTF-8');
  foreach ($server->layers() as $wmsLayer) {
    echo ($wmsLayer->name() ? $wmsLayer->name()." : " : ''),$wmsLayer->title(),"\n";
    foreach ($wmsLayer->children() as $wmsSubLayer)
      echo '** ',$wmsSubLayer->name()," : ",$wmsSubLayer->title(),"\n";
  }
}
elseif (0) {
  header('Content-Type: text/plain; charset=UTF-8');
  print_r($server->layer('clc:RCLC06'));
}
elseif (1) {
  header('Content-Type: text/plain; charset=UTF-8');
  print_r($server->layer('clc:RCLC06')->leafletJS());
}
die("FIN TESTS OK");