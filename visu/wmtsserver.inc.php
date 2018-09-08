<?php
/*PhpDoc:
name:  wmtsserver.inc.php
title: wmtsserver.inc.php - classe WmtsServer - utilisation des capacités d'un serveur WMTS
includes: [ ogcserver.inc.php, wmtslayer.inc.php ]
classes:
doc: |
journal: |
  19/2/2017:
    Ajout de availableInWM()
  11/2/2017:
    ajout de WmtsServer::getLayerDef() utilisé par gexplor/gp
  8/11/2016:
    modif pour les serveurs de la NASA
  30/10/2016:
    première version
*/
require_once 'ogcserver.inc.php';
require_once 'wmtslayer.inc.php';

/*PhpDoc: classes
name:  WmtsServer
title: class WmtsServer extends OgcServer - utilisation des capacités d'un serveur WMTS
methods:
doc: |
*/
class WmtsServer extends OgcServer {
  function getAbstract() { return (string)$this->cap()->ows_ServiceIdentification->ows_Abstract; }
  
// indique si le serveur expose des couches en projection WM
  function availableInWM() {
    $wmEpsgIds = ['EPSG:3857','urn:ogc:def:crs:EPSG:6.18:3:3857','urn:ogc:def:crs:EPSG::3857'];
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
      if (in_array($tileMatrixSet['crs'], $wmEpsgIds) and ($tileMatrixSet['nbLayers']>0))
        return true;
    return false;
  }
  
// Affichage de la liste des ressources sous la forme d'un tableau HTML
  function showResources() {
// les infos du titre proviennent de servers.yaml
    echo "<html><head><title>server</title><meta charset='UTF-8'></head>\n",
         "<h2>Serveur WMTS \"",$this->conf['title'],"\" ($this->id)</h2>\n";
    $cap = $this->cap();
    $getcapurl = $this->conf['url']."SERVICE=WMTS&amp;REQUEST=GetCapabilities";
    $individualName = (isset($cap->ows_ServiceProvider->ows_ServiceContact->ows_IndividualName) ?
              $cap->ows_ServiceProvider->ows_ServiceContact->ows_IndividualName : '');
    $providerName = (isset($cap->ows_ServiceProvider->ows_ProviderName) ? $cap->ows_ServiceProvider->ows_ProviderName : '');
    $mel = (isset($cap->ows_ServiceProvider->ows_ServiceContact->ows_ContactInfo->ows_Address->ows_ElectronicMailAddress) ? 
              $cap->ows_ServiceProvider->ows_ServiceContact->ows_ContactInfo->ows_Address->ows_ElectronicMailAddress : '');
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
            "<a href='$getcapurl'>XML</a>",
            "<a href='?action=showCapInXml&server=$this->id'>, XML simplifiées</a>",
         "</td></tr>\n",
         "</table>\n";
    echo "<h3>Les couches</h3>\n",
         "<table border=1><th>Title</th><th>Abstract</th><th>Identifier</th><th>Format</th><th>Pyr</th><th>Levels</th>";
    foreach ($cap->Contents->Layer as $layer) {
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
           "<td><a href='?action=showLayerInXml&amp;server=$this->id&amp;layer=",urlencode($layer->ows_Identifier),"'>",$layer->ows_Title,"</a></td>",
           "<td>",$layer->ows_Abstract,"</td>",
           "<td>",$layer->ows_Identifier,"</td>",
           "<td>",$layer->Format,"</td>",
           "<td><a href='#Pyr$TileMatrixSet'>$TileMatrixSet</a></td>",
           '<td>',($levelmin==99?'undef':"$levelmin - $levelmax"),'</td>',
           "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3><a href='?action=showTileMatrixSets&amp;server=$this->id'>Les pyramides(TileMatrixSet)</a></h3>\n",
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
name:  showTileMatrixSets
title: function showTileMatrixSets() - transmet en XML les capacités des pyramides
*/
  function showTileMatrixSets() {
    header('Content-Type: text/xml');
    echo '<TileMatrixSets>';
    foreach ($this->cap()->Contents->TileMatrixSet as $tileMatrixSet)
      echo $tileMatrixSet->asXml();
    echo '</TileMatrixSets>';
    die();
  }
  
/*PhpDoc: methods
name:  getLayerById
title: function getLayerById($layerId) - trouve une couche identifiée par son identifiant,  retourne un objet WmtsLayer
*/
  function getLayerById($layerId) {
    foreach ($this->cap()->Contents->Layer as $layer) {
//      echo "ows_Identifier=",$layer->ows_Identifier,", layerId=$layerId<br>\n";
      if ($layer->ows_Identifier==$layerId) {
        return new WmtsLayer($this, $layer);
      }
    }
    return null;
  }
  
/*PhpDoc: methods
name:  getLayerByName
title: function getLayerByName($layerId) - trouve une couche identifiée par son identifiant,  retourne un objet WmtsLayer
*/
  function getLayerByName($layerId) { return $this->getLayerById($layerId); }
    
// Génère la définition de la layer $layerId sous la forme d'un array Php
  function getLayerDef($layerId, $attribution=null, $style=null, $detectRetina=true) {
    $lyr = $this->getLayerById($layerId)->layer();
    if (!$style)
      $style = $lyr['defaultStyle'];
    $wmtsurl = $this->conf['url'].'service=WMTS&version=1.0.0&request=GetTile'
               ."&tilematrixSet=$lyr[tilematrixSet]&height=256&width=256"
               .'&tilematrix={z}&tilecol={x}&tilerow={y}';
    $def = [
      'url'=>$wmtsurl."&layer=$layerId&format=$lyr[format]&style=$style",
      'options'=> [
        'format'=> $lyr['format'],
        'minZoom'=> $lyr['minZoom'],
        'maxZoom'=> $lyr['maxZoom'],
      ]
    ];
    if ($detectRetina)
      $def['options']['detectRetina'] = true;
    if ($attribution)
      $def['options']['attribution'] = $attribution;
    return $def;
  }
  
// Affiche la définition JS de la layer $layerId
  function genLayerDef($layerId, $attribution=null, $style=null, $detectRetina=true) {
    $def = $this->getLayerDef($layerId, $attribution, $style, $detectRetina);
    echo "new L.TileLayer(\n",
         "    '$def[url]',\n",
         "    ",json_encode($def['options'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n",
         ");\n";
  }
  
// Renvoit l'arbre des couches avec pour chacune son nom et son titre
  function layerTree() {
    $tree = [];
    foreach ($this->cap()->Contents->Layer as $layer)
      $tree[] = [
        'name' => (string)$layer->ows_Identifier,
        'title' => (string)$layer->ows_Title,
      ];
    return $tree;
  }
};

// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

header('Content-Type: text/plain; charset=UTF-8');
if (1)
  foreach([
    'IGNFGP-WMTS'=> [
      'title'=> "Géoportail IGN WM",
      'class'=> "IGNSHOM",
      'url'=> "http://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?",
      'protocol'=> "WMTS",
    ],
    'CLC-WMTS'=> [
      'title'=> "Corine Land Cover (SOES) - pas WM",
      'class'=> "MinEnv",
      'url'=> "http://clc.developpement-durable.gouv.fr/geoserver/gwc/service/wmts?",
      'protocol'=> "WMTS",
    ],
  ] as $id => $serverConf) {
    $server = new WmtsServer($id, $serverConf);
    echo "Serveur $id ",($server->availableInWM() ? '' : 'NON '),"disponible en WM\n";
  }
die("FIN TESTS OK");