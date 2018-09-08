<?php
/*PhpDoc:
name: extract.php
title: extract.php - extraction des services WMS Inspire
doc: |
  1ère étape: élimination des services non WMS
    et classement des services WMS par serveur (nom de domaine)
  La 2ème étape consiste à insérer les serveurs dans servers.yaml
journal: |
  5/3/2017:
    première version
*/
$harvest = 'inspire20170226';

//header('Content-type: text/plain; charset="utf-8"');
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>extract</title></head><body><pre>\n";
require_once '../../../phplib/openmysql.inc.php';
//require_once '../../../phplib/yaml.inc.php';
require_once '../../../spyc/spyc.inc.php';

$mysql_params = [
  'localhost' => [
    'server' => 'mysql-bdavid.alwaysdata.net',
    'user' => 'bdavid',
    'passwd'=> 'dsbune44',
    'database' => 'bdavid_geocat3dev',
  ],
  'covadis.docinspire.eu' => [
    'server' => 'mysql-bdavid.alwaysdata.net',
    'user' => 'bdavid',
    'passwd'=> 'dsbune44',
    'database' => 'bdavid_geocat3dev',
  ],
];
$mysqli = openMySQL($mysql_params);

$sql = <<<EOT
select recordid, title.val title, attributedTo.val attributedTo, mdlocator.svar0 locvar, mdlocator.sval0 locval, mdlocator.val url
from harvestrecord
  join mdelement mdtype using(recordid)
  join mdelement title using(recordid)
  join mdelement mdlocator using(recordid)
  join mdelement attributedTo using(recordid)
where harvest='$harvest'
  and mdtype.var='type' and mdtype.val='service'
  and title.var='title'
  and mdlocator.var='locator'
  and attributedTo.var='attributedTo'
EOT;

// enregistrement des serveurs par classe
$servers = [];
// enregistrement de la miste de classes
$classes = [];

function strncmp2($str1, $str2) { return strncmp($str1, $str2, strlen($str2)); }

if (!($result = $mysqli->query($sql)))
  throw new Exception("Ligne ".__LINE__.", Req. \"$sql\" invalide: ".$mysqli->error);
while ($tuple = $result->fetch_array(MYSQLI_ASSOC)) {
  if (($tuple['locvar']=='protocol')
    and (in_array($tuple['locval'],
      [ 'WWW:LINK-1.0-http--link',
        'WWW:DOWNLOAD-1.0-http--download',
        'WWW:LINK-1.0-http--related',
        'OGC:WFS-2.0.0-http-get-capabilities',
        'OGC:WFS-1.0.0-http-get-capabilities',
        'WWW:LINK-1.0-http--atom',
        'OGC:WCS-2.0.1-http-get-capabilities',
        'WWW:DOWNLOAD-1.0-ftp--download',
        'OGC:CSW',
        'OGC:WFS',
        'WWW:LINK-1.0-http--rss',
        'FILE:GEO',
        'OGC:WMTS-1.0.0-http-get-capabilities',
      ])))
    continue;
  $title = $tuple['title'];
//  echo "$title\n";

  $attributedTo = $tuple['attributedTo'];
//  echo "$attributedTo\n";
  
  $class = 'unknown';
  if (!$tuple['url'])
    continue;
  elseif (strncmp2(
        $tuple['url'],
        'http://mapserveur.application.developpement-durable.gouv.fr/map/mapserv?map=/opt/data/carto/cartelie/')==0)
    $class = 'Cartélie';
  elseif (strncmp2(
        $tuple['url'],
        'http://ogc.geo-ide.developpement-durable.gouv.fr/cartes/mapserv?map=/opt/data/carto/geoide-catalogue/')==0)
    $class = 'geoide-catalogue';
  elseif (strncmp2($tuple['url'],'http://ogc.geo-ide.application.i2/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://ws.carmen.developpement-durable.gouv.fr/WMS/')==0)
    $class = 'carmen';
  elseif (strncmp2($tuple['url'],'http://ws.carmencarto.fr/WMS/')==0)
    $class = 'carmencarto';
  elseif (strncmp2($tuple['url'],'http://carmenwsdata.brgm-rec.fr/WMS/')==0)
    $class = 'carmen-rec';
  elseif (strncmp2($tuple['url'],'http://services.sandre.eaufrance.fr/')==0)
    $class = 'sandre';
  elseif (strncmp2($tuple['url'],'services.data.shom.fr/INSPIRE/wms/r?')==0)
    $class = 'shom';
  elseif (strncmp2($tuple['url'],'services.data.shom.fr/INSPIRE/wfs?')==0)
    continue;
  elseif (strncmp2($tuple['url'],'services.data.shom.fr/wmts?')==0)
    continue;
  elseif (strcmp($tuple['url'],'http://geoservices.brgm.fr/risques?')==0)
    $class = 'brgm';
  elseif (strcmp($tuple['url'],'http://geoservices.brgm.fr/geologie?')==0)
    $class = 'brgm';
  elseif (strcmp($tuple['url'],'http://geoservices.brgm.fr/rgf_demo_vfr?')==0)
    $class = 'brgm';
  elseif (strcmp($tuple['url'],'http://geoservices.brgm.fr/odmgm?')==0)
    $class = 'brgm';
  elseif (strcmp($tuple['url'],'http://geoservices.brgm.fr/geol1M/fr/download/capabilities.xml')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://www.ifremer.fr/services/wms')==0)
    $class = 'ifremer';
  elseif (strncmp2($tuple['url'],'http://www.ifremer.fr/services/wfs')==0)
    continue;

  elseif (strncmp2($tuple['url'],'https://www.ppige-npdc.fr/geoserver/wms?')==0)
    $class = 'ppige-npdc';
  elseif (strncmp2($tuple['url'],'https://www.pigma.org/geoserver/')==0)
    $class = 'pigma';
  elseif (strncmp2($tuple['url'],'http://ids.pigma.org/geoserver/')==0)
    $class = 'pigma';
  elseif (strncmp2($tuple['url'],'http://carto.nordpasdecalais.fr/wms?')==0)
    $class = 'nordpasdecalais';
  elseif (strncmp2($tuple['url'],'http://geobretagne.fr/geoserver/')==0)
    $class = 'geobretagne';
  elseif (strncmp2($tuple['url'],'http://www.geopicardie.fr/geoserver/')==0)
    $class = 'geopicardie';
  elseif (strncmp2($tuple['url'],'http://carto.mipygeo.fr/cgi-bin/mapserv?')==0)
    $class = 'mipygeo';
  elseif (strncmp2($tuple['url'],'http://wms.siglr.org/')==0)
    $class = 'siglr';
  elseif (strncmp2($tuple['url'],'http://geoservice.siglr.org/')==0)
    $class = 'siglr';
  elseif (strncmp2($tuple['url'],'https://sig.geo-nord.fr/arcgis/services/')==0)
    $class = 'geo-nord';
  elseif (strncmp2($tuple['url'],'http://carto.geoguyane.fr/cgi-bin/mapserv?')==0)
    $class = 'geoguyane';
  elseif (strncmp2($tuple['url'],'http://carto.georhonealpes.fr/cgi-bin/mapserv?')==0)
    $class = 'georhonealpes';
  elseif (strncmp2($tuple['url'],'http://carto.sigloire.fr/cgi-bin/mapserv?')==0)
    $class = 'sigloire';
  elseif (strncmp2($tuple['url'],'http://carto.geopal.org/cgi-bin/mapserv?')==0)
    $class = 'geopal';
  elseif (strncmp2($tuple['url'],'http://geoservices.crige-paca.org/geoserver/')==0)
    $class = 'crige-paca';
  elseif (strncmp2($tuple['url'],'http://datacarto.ideobfc.fr/WMS/wms.php')==0)
    $class = 'ideobfc';
  elseif (strncmp2($tuple['url'],'http://wms.craig.fr/')==0)
    $class = 'craig';
  elseif (strncmp2($tuple['url'],'http://carto.geonormandie.fr/cgi-bin/mapserv?')==0)
    $class = 'geonormandie';
  elseif (strncmp2($tuple['url'],'https://www.geomayenne.fr/arcgis/services/')==0)
    $class = 'geomayenne';
  elseif (strncmp2($tuple['url'],'http://carto.geolimousin.fr/cgi-bin/mapserv?')==0)
    $class = 'geolimousin';
  elseif (strncmp2($tuple['url'],'http://carto.geomartinique.fr/cgi-bin/mapserv?')==0)
    $class = 'geomartinique';
  elseif (strncmp2($tuple['url'],'http://carto.territoiredebelfort.fr/arcgis/services/')==0)
    $class = 'territoiredebelfort';
  elseif (strncmp2($tuple['url'],'http://sig.airbreizh.asso.fr/geoserver/wms?')==0)
    $class = 'airbreizh';
  elseif (strncmp2($tuple['url'],'http://ws.sigogne.org/wms/')==0)
    $class = 'sigogne';
  elseif (strncmp2($tuple['url'],'http://carto.cg90.fr/arcgis/services/')==0)
    $class = 'cg90';
  elseif (strncmp2($tuple['url'],'https://carto.cg90.fr/arcgis/services/')==0)
    $class = 'cg90';
  elseif (strncmp2($tuple['url'],'http://geo.compiegnois.fr/geoserver/wms')==0)
    $class = 'compiegnois';

  elseif (strcmp($tuple['url'],'http://www.ign.fr/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://professionnels.ign.fr/')==0)
    continue;
  elseif (strcmp($tuple['url'],'http://www.geoportail.fr/')==0)
    continue;
  elseif (strcmp($tuple['url'],'http://www.siglr.org')==0)
    continue;
  elseif (strcmp($tuple['url'],'http://www.siglr.org/idg-lr/les-donnees.html')==0)
    continue;
  elseif (strcmp($tuple['url'],'http://sd1878-2.sivit.org/geoserver/wms?')==0)
    continue;

  elseif (strncmp2($tuple['url'],'http://osm.geobretagne.fr/gwc01/service/wmts?')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://ws.carmencarto.fr/ATOM/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://carmenwsdata.brgm-rec.fr/ATOM/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://ws.carmencarto.fr/WFS/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://ws.carmen.developpement-durable.gouv.fr/ATOM/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://ws.carmen.developpement-durable.gouv.fr/WFS/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://carmenwsdata.brgm-rec.fr/WFS/')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://www.geocatalogue.fr/api-public/inspire/servicesRest?')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://www.geocatalogue.fr/api-public/improved/servicesRest?')==0)
    continue;
  elseif (preg_match('!^http://[^/]+/(geonetwork|geosource|catalogue|catalog)!', $tuple['url']))
    continue;
  elseif (strncmp2($tuple['url'],'http://catalogue.sigloire.fr/PRRA/consultation.php?')==0)
    continue;
  elseif (strncmp2($tuple['url'],'http://www.geoguyane.fr/PRRA/consultation.php?')==0)
    continue;
  elseif (strncmp2($tuple['url'],'-- Localisateur du service --')==0)
    continue;
  elseif (strncmp2($tuple['url'],'-- Lien vers la ressource décrite elle-même, ')==0)
    continue;
  elseif (strncmp2($tuple['url'],'https://geoservices.meteofrance.fr/?fond=produit&id_produit=201&id_rubrique=42')==0)
    continue;
  elseif (strncmp2($tuple['url'],'https://donneespubliques.meteofrance.fr/?fond=produit&id_produit=201&id_rubrique=42')==0)
    continue;
  elseif (preg_match('!^http://services.api.isogeo.com/ows/s/[^?]+\?service=CSW&!', $tuple['url']))
    continue;

  $servers[$class][$tuple['recordid']] = [
    'class' => $class,
    'title' => $title,
    'attributedTo' => $attributedTo,
    'url' => $tuple['url'],
    $tuple['locvar'] => $tuple['locval'],
    'protocol' => 'WMS',
  ];
  if (!in_array($class, $classes) and ($class!='unknown'))
    $classes[] = $class;
}

if (!isset($_GET['class'])) {
  foreach ($classes as $class) {
    echo count($servers[$class])," <a href='?class=$class'>serveurs $class</a>\n";
    unset($servers[$class]);
  }
  echo (isset($servers['unknown']) ? count($servers['unknown']) : 0)," serveurs unknown\n";

  echo Spyc::YAMLDump($servers, false, 120, true);
}
else {
  echo Spyc::YAMLDump($servers[$_GET['class']], false, 120, true);
}