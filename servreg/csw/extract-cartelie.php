<?php
/*PhpDoc:
name: extract-cartelie.php
title: extract-cartelie.php - extraction des services WMS Cartélie d'une moisson de Géo-IDE
includes:
  - '../../../phplib/openmysql.inc.php'
  - '../../../spyc/spyc2.inc.php'
  - '../../../spyc/ypath.inc.php'
  - '../../../georef/admin/minenv.yaml'
doc: |
  génère les services WMS Cartélie en les affectant aux services définis dans le registre des services déconcentrés
    cartelie-org-mapname:
      title: title
      class: org normalisé
      url: url
      protocol: WMS

journal: |
  26/3/2017:
    première version
*/
$harvest = 'adelie20170226';

header('Content-type: text/plain; charset="utf-8"');
require_once '../../../phplib/openmysql.inc.php';
require_once '../../../spyc/spyc2.inc.php';
require_once '../../../spyc/ypath.inc.php';

if (!($minenv = spycLoad('../../../georef/admin/minenv.yaml')))
  die("Erreur de lecture de minenv.yaml");

$mysql_params = [
  'localhost' => [
    'server' => 'mysql-bdavid.alwaysdata.net',
    'user' => 'bdavid',
    'passwd'=> 'dsbune44',
    'database' => 'bdavid_geocat3dev',
  ],
];
$mysqli = openMySQL($mysql_params);

$sql = <<<EOT
select title.val title, mdlocator.val url
from harvestrecord
  join mdelement mdtype using(recordid)
  join mdelement title using(recordid)
  join mdelement mdlocator using(recordid)
  join mdelement distributionFormat using(recordid)
where harvest='$harvest'
  and mdtype.var='type' and mdtype.val='service'
  and title.var='title'
  and mdlocator.var='locator'
  and distributionFormat.var='distributionFormat' and distributionFormat.val='OGC:WMS'
EOT;

$yaml = [
    'phpDoc'=> [
      'name'=> 'cartelie.yaml',
      'title'=> "cartelie.yaml - Liste de serveurs WMS Cartélie",
      'doc'=> "Extraction automatique du ".date(DATE_RFC2822)." sur la moisson $harvest\n"
             ."La classification est produite par servetat.php",
    ],
    'classification'=>[],
    'servers'=>[],
];

// Cas particuliers hors Mininitère
// Les clés sont les noms trouvés dans Cartélie, les valeurs sont les noms définis dans la classification des serveurs
$divers = ['CEREMA'=>'CEREMA','PN_France'=>'AFB'];

$nbitems=0;
if (!($result = $mysqli->query($sql)))
  throw new Exception("Ligne ".__LINE__.", Req. \"$sql\" invalide: ".$mysqli->error);
while ($tuple = $result->fetch_array(MYSQLI_ASSOC)) {
//  if (++$nbitems > 10) break;
  if (!preg_match('!^(http://mapserveur.application.developpement-durable.gouv.fr/map/mapserv\?'
                  .'map=/opt/data/carto/cartelie/prod/([^/]+)/([^&]+)&)service=WMS&request=GetCapabilities$!',
                  $tuple['url'], $matches))
    die("Don't match url=$tuple[url]");
//  print_r($matches);
  $url = $matches[1];
  $org = $matches[2];
  $org2 = str_replace('_',' ',$org);
  $mapname = substr($matches[3], 0, strlen($matches[3])-8);
//  echo "org=$org, mapname=$mapname, url=$url\n";
  $title = str_replace('service de consultation (WMS) des données de : ','',$tuple['title']);
  if (preg_match('!^(DDT)M?_(\d.)$!', $org, $matches))
    $stdorg = $matches[1].$matches[2];
  elseif ($stdorg = ypath($minenv, "//$/title=$org2")) {}
  elseif ($stdorg = ypath($minenv, "//$/altLabels-)$org2")) {}
  elseif (isset($divers[$org]))
    $stdorg = $divers[$org]; // Cas particuliers hors Mininitère
  else
    die("Dont'match org=$org");
  $yaml['servers']["cartelie-$org-$mapname"] = [
    'title'=>$title,
    'class'=>$stdorg,
    'url'=>$url,
    'protocol'=>'WMS',
  ];
}

ksort($yaml['servers']);
echo Spyc::YAMLDump($yaml, false, 100, true);
