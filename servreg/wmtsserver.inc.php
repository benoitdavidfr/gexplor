<?php
/*PhpDoc:
name:  wmtsserver.inc.php
title: wmtsserver.inc.php - classe WmtsServer - utilisation des capacités d'un serveur WMTS
includes: [ servreg.inc.php, ogcserver.inc.php, wmtslayer.inc.php ]
classes:
doc: |
journal: |
  31/3/2017 :
    modif de WmtsServer::showInHtml()
  18/3/2017 :
    Evol de availableInWM() en availableInWmOrGeo()
  9-11/3/2017:
    fork pour adaptation à servreg
  19/2/2017:
    Ajout de availableInWM()
  11/2/2017:
    ajout de WmtsServer::getLayerDef() utilisé par gexplor/gp
  8/11/2016:
    modif pour les serveurs de la NASA
  30/10/2016:
    première version
*/
require_once 'servreg.inc.php';
require_once 'ogcserver.inc.php';
require_once 'wmtslayer.inc.php';

/*PhpDoc: classes
name:  WmtsServer
title: class WmtsServer extends OgcServer - utilisation des capacités d'un serveur WMTS
methods:
doc: |
*/
class WmtsServer extends OgcServer implements Server {
  static $wmEpsgIds = ['EPSG:3857','urn:ogc:def:crs:EPSG:6.18:3:3857','urn:ogc:def:crs:EPSG::3857'];
  protected $zoomLevelNamePrefix; // normalement '' mais certains serveurs prefixent les zoomLevelName
  
  function __construct($conf) {
    $this->conf = $conf;
    $this->zoomLevelNamePrefix = '';
    foreach ($this->cap()->Contents->TileMatrixSet as $tileMatrixSet)
      if (in_array((string)$tileMatrixSet->ows_SupportedCRS, self::$wmEpsgIds)) {
//        echo "ows_SupportedCRS=",$tileMatrixSet->ows_SupportedCRS,"\n";
        foreach ($tileMatrixSet->TileMatrix as $tileMatrix)
          if (($tileMatrix->MatrixWidth==1) and ($tileMatrix->MatrixHeight==1)) {
            $ows_Identifier = (string)$tileMatrix->ows_Identifier;
//            echo "ows_Identifier='",$ows_Identifier,"'\n";
            if ($ows_Identifier<>'0')
              $this->zoomLevelNamePrefix = substr($ows_Identifier, 0, strlen($ows_Identifier)-1);
          }
      }
  }
  
  function getAbstract() { return (string)$this->cap()->ows_ServiceIdentification->ows_Abstract; }
  function zoomLevelNamePrefix() { return $this->zoomLevelNamePrefix; }
  
/*PhpDoc: methods
name:  availableInWmOrGeo
title: function availableInWmOrGeo($layer=null) - indique si le serveur expose des couches en projection WM
doc: |
  Pour un serveur WMTS, l'expose en EPSG:4326 n'est pas prise en compte car la pyramide serait différente de celle de WM
*/
  function availableInWmOrGeo() {
//    echo "Contents->TileMatrixSet"; print_r($this->cap()->Contents->TileMatrixSet);
    $tileMatrixSets = [];
    $cap = $this->cap();
    foreach ($cap->Contents->TileMatrixSet as $tileMatrixSet) {
//      echo "tileMatrixSet="; print_r($tileMatrixSet);
      $tileMatrixSets[(string)$tileMatrixSet->ows_Identifier] = [
        'crs' => (string)$tileMatrixSet->ows_SupportedCRS,
        'nbLayers' => 0,
      ];
//      echo "tileMatrixSet: id=",$tileMatrixSet->ows_Identifier,", SupportedCRS=",$tileMatrixSet->ows_SupportedCRS,"\n";
    }
//    echo "\nLayers:\n";
    foreach ($cap->Contents->Layer as $layer) {
//      echo "layer="; print_r($layer);
//      echo "layer: id=",$layer->ows_Identifier,", tileMatrixSet=",$layer->TileMatrixSetLink->TileMatrixSet,"\n";
      $tmsid = (string)$layer->TileMatrixSetLink->TileMatrixSet;
      $tileMatrixSets[$tmsid]['nbLayers']++;
    }
//    echo "\nTileMatrixSets:\n"; print_r($tileMatrixSets);
    foreach ($tileMatrixSets as $tileMatrixSet)
      if (in_array($tileMatrixSet['crs'], self::$wmEpsgIds) and ($tileMatrixSet['nbLayers']>0))
        return true;
    return false;
  }
  
// Affichage de la liste des ressources sous la forme d'un tableau HTML
  function showInHtml() {
// le titre proviennent de servers.yaml
    echo "<html><head><title>server</title><meta charset='UTF-8'></head>\n",
         "<h2>Serveur WMTS \"",$this->conf['title'],"\" (",$this->conf['id'],")</h2>\n";
    $cap = $this->cap();
    $getcapurl = $this->conf['url']."SERVICE=WMTS&amp;REQUEST=GetCapabilities";
    $individualName = (isset($cap->ows_ServiceProvider->ows_ServiceContact->ows_IndividualName) ?
              $cap->ows_ServiceProvider->ows_ServiceContact->ows_IndividualName : '');
    $providerName = (isset($cap->ows_ServiceProvider->ows_ProviderName) ? $cap->ows_ServiceProvider->ows_ProviderName : '');
    $mel = (isset($cap->ows_ServiceProvider->ows_ServiceContact->ows_ContactInfo->ows_Address->ows_ElectronicMailAddress) ? 
              $cap->ows_ServiceProvider->ows_ServiceContact->ows_ContactInfo->ows_Address->ows_ElectronicMailAddress : '');
    $url = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]$_SERVER[PATH_INFO]";
    $params = ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
    echo "<h3>Caractéristiques issues des capacités du serveur</h3>",
         "<table border=1>",
         "<tr><td><i>version WMTS :</i></td><td>",$cap['version'],"</td></tr>\n",
         "<tr><td><i>Titre :</i></td><td>",$cap->ows_ServiceIdentification->ows_Title,"</td></tr>\n",
         "<tr><td><i>Abstract :</i></td><td>",$this->getAbstract(),"</td></tr>\n",
         "<tr><td><i>Contact :</i></td><td>",
            $individualName,
            ($providerName ?" ($providerName)":''),
            ($mel ? '- Mel: '.$mel : ''),
            "</td></tr>\n",
         "<tr><td><i>Tarif :</i></td><td>",$cap->ows_ServiceIdentification->ows_Fees,"</td></tr>\n",
         "<tr><td><i>Conditions d'accès :</i></td><td>",$cap->ows_ServiceIdentification->ows_AccessConstraints,"</td></tr>\n",
         "<tr><td><i>Capacités en XML :</i></td><td>",
            "<a href='$getcapurl' target='_blank'>XML</a>",
            "<a href='$url",($params?"$params&amp;":'?'),"request=GetCap' target='_blank'>, XML simplifiées</a>",
         "</td></tr>\n",
         "<tr><td><i>Dispo. WM :</i></td><td>",($this->availableInWmOrGeo()?'oui':'non'),"</td></tr>\n",
         "</table>\n";
    echo "<h3>Les couches</h3>\n",
         "<table border=1><th>Title</th><th>Abstract</th><th>Identifier</th><th>Format</th><th>Pyr</th><th>Levels</th>";
    foreach ($cap->Contents->Layer as $layer) {
      $lyrUrl = $url.'/'.$layer->ows_Identifier;
      $levelmin = 99;
      $levelmax = -99;
      if ($layer->TileMatrixSetLink->TileMatrixSetLimits->TileMatrixLimits)
        foreach ($layer->TileMatrixSetLink->TileMatrixSetLimits->TileMatrixLimits as $TileMatrixLimit) {
          if ((int)$TileMatrixLimit->TileMatrix < $levelmin)
            $levelmin = (int)$TileMatrixLimit->TileMatrix;
          if ((int)$TileMatrixLimit->TileMatrix > $levelmax)
            $levelmax = (int)$TileMatrixLimit->TileMatrix;
        }
      $TileMatrixSet = (string)$layer->TileMatrixSetLink->TileMatrixSet;
      echo "<tr>",
           "<td><a href='$lyrUrl",($params?"$params&amp;":'?'),"request=GetCap'>",$layer->ows_Title,"</a></td>",
           "<td>",$layer->ows_Abstract,"</td>",
           "<td>",$layer->ows_Identifier,"</td>",
           "<td>",$layer->Format,"</td>",
           "<td><a href='#Pyr$TileMatrixSet'>$TileMatrixSet</a></td>",
           '<td>',($levelmin==99?'undef':"$levelmin - $levelmax"),'</td>',
           "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3><a href='$url?request=showTileMatrixSets'>Les pyramides(TileMatrixSet)</a></h3>\n",
         "<table border=1><th>Identifier</th><th>SupportedCRS</th>\n";
    foreach ($this->cap()->Contents->TileMatrixSet as $tileMatrixSet) {
      echo "<a id='Pyr",$tileMatrixSet->ows_Identifier,"'><tr>",
           "<td>",$tileMatrixSet->ows_Identifier,"</td>\n",
           "<td>",$tileMatrixSet->ows_SupportedCRS,"</td>\n",
           "</tr></a>\n";
    }
    echo "</table>\n";
  }
  
/*PhpDoc: methods
name:  layer
title: function layer($layerId) - trouve une couche identifiée par son identifiant,  retourne un objet WmtsLayer
*/
  function layer($layerId) {
    foreach ($this->cap()->Contents->Layer as $layer) {
//      echo "ows_Identifier=",$layer->ows_Identifier,", layerId=$layerId<br>\n";
      if ($layer->ows_Identifier==$layerId)
        return new WmtsLayer($this, $layer);
    }
    return null;
  }
      
/*PhpDoc: methods
name:  layers
title: function layers() - renvoie le tableau des couches indexé par leur id
*/
  function layers() {
    $layers = [];
    foreach ($this->cap()->Contents->Layer as $layer)
      $layers[(string)$layer->ows_Identifier] = new WmtsLayer($this, $layer);
    return $layers;
  }
  
/*PhpDoc: methods
name:  showTileMatrixSets
title: function showTileMatrixSets() - affiche en XML les capacités des pyramides
*/
  function showTileMatrixSets() {
    header('Content-Type: text/xml');
    echo '<TileMatrixSets>';
    foreach ($this->cap()->Contents->TileMatrixSet as $tileMatrixSet)
      echo $tileMatrixSet->asXml();
    echo '</TileMatrixSets>';
    die();
  }
};

// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

header('Content-Type: text/plain; charset=UTF-8');
$server = newServer(servreg()['servers']['IGNFGP-WMTS-WM']);
if (0) {
  header('Content-Type: text/xml');
  die($server->cap()->asXml());
}
elseif (0) {
  header('Content-Type: text/plain; charset=UTF-8');
  echo "availableInWM: ",($server->availableInWM() ? "ok": "NO"),"\n";
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
  print_r($server->layer('GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.STANDARD'));
}
elseif (1) {
  header('Content-Type: text/plain; charset=UTF-8');
  print_r($server->layer('GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.STANDARD')->leafletJS());
}
die("FIN TESTS OK");