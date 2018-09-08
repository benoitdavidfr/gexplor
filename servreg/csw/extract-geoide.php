<?php
/*PhpDoc:
name: extract-geoide.php
title: extract-geoide.php - extraction des services WMS d'une moisson de Géo-IDE
includes:
  - '../../../phplib/openmysql.inc.php'
  - '../../../spyc/spyc2.inc.php'
  - '../../../spyc/ypath.inc.php'
  - '../../../georef/admin/dreal.yaml'
  - '../../../georef/admin/deal.yaml'
  - '../../../georef/admin/dirm.yaml'
  - '../../../georef/admin/dmom.yaml'
  - '../../../georef/admin/dir.yaml'
  - '../../../georef/admin/minenv.yaml'
  - '../../../georef/admin/draaf.yaml'
  - '../../../georef/admin/daaf.yaml'
doc: |
  A REVOIR à la suite de restructuration du référentiel administratif
  génère les services WMS d'une moisson géo-IDE en les affectant aux services définis dans le registre des services déconcentrés
journal: |
  27/3/2017:
    déplacement de adminreg dans /georef/admin et de ypath dans spyc
  25-26/3/2017:
    utilisation de adminreg
  20/3/2017:
    améliorations
  5/3/2017:
    première version
*/
$harvest = 'geoide20170226';

header('Content-type: text/plain; charset="utf-8"');
require_once '../../../phplib/openmysql.inc.php';
require_once '../../../spyc/spyc2.inc.php';
require_once '../../../spyc/ypath.inc.php';

if (!($minenv = spycLoad('../../../georef/admin//minenv.yaml')))
  die("Erreur de lecture de minenv.yaml");
if (!($minagri = spycLoad('../../../georef/admin//minagri.yaml')))
  die("Erreur de lecture de minagri.yaml");


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
select recordid, title.val title, attributedTo.val attributedTo, mdlocator.val url
from harvestrecord
  join mdelement mdtype using(recordid)
  join mdelement title using(recordid)
  join mdelement mdlocator using(recordid)
  join mdelement attributedTo using(recordid)
where harvest='$harvest'
  and mdtype.var='type' and mdtype.val='service'
  and title.var='title'
  and mdlocator.var='locator' and mdlocator.sval0='OGC:WMS-1.3.0-http-get-capabilities'
  and attributedTo.var='attributedTo'
EOT;

$yaml = [
    'phpDoc'=> [
      'name'=> 'serv-geoide.yaml',
      'title'=> "serv-geoide.yaml - Liste de serveurs WMS Géo-IDE",
      'doc'=> "Extraction automatique du ".date(DATE_RFC2822)." sur la moisson $harvest",
    ],
    'classification'=>[],
    'servers'=>[],
];

function strncmp2($str1, $str2) { return strncmp($str1, $str2, strlen($str2)); }

if (!($result = $mysqli->query($sql)))
  throw new Exception("Ligne ".__LINE__.", Req. \"$sql\" invalide: ".$mysqli->error);
while ($tuple = $result->fetch_array(MYSQLI_ASSOC)) {
  if (strncmp2($tuple['url'], 'http://ogc.geo-ide.developpement-durable.gouv.fr/'))
    continue;
//  if (!$first++) print_r($tuple);
  $title = $tuple['title'];
  $title= str_replace('Service de visualisation cartographique (WMS) des ','',$title);
  $title= str_replace('jeux de données ','JdD ',$title);
  $title= str_replace('lots de données ','LdD ',$title);

// DREAL
  $title= str_replace("DREAL Champ.Ard. (Direction Régionale de l'Environnement, de l'Aménagement et du Logement de Champagne-Ardenne)","DREAL Champagne-Ardenne",$title);
  $title= str_replace("DREAL Fr.Comté (Direction Régionale de l'Environnement, de l'Aménagement et du Logement Franche-Comté)","DREAL Franche-Comté",$title);
  $title= str_replace("DREAL H.Normandie (Direction Régionale de l'Environnement, de l'Aménagement et du Logement Haute-Normandie)","DREAL Haute-Normandie",$title);
  $title= str_replace("DREAL Lang.Rous. (Direction Régionale de l'Environnement, de l'Aménagement et du Logement Languedoc-Roussillon)","DREAL Languedoc-Roussillon",$title);
  $title= str_replace(" (Direction Régionale de l'Environnement, de l'Aménagement et du Logement Bretagne)",'',$title);
  $title= str_replace(" (Direction Régionale de l'Environnement, de l'Aménagement et du Logement Lorraine)",'',$title);
  $title= str_replace(" (Direction Régionale de l'Environnement, de l'Aménagement et du Logement Aquitaine)",'',$title);
  $title= str_replace(" (Direction Régionale et Interdépartementale de l'Equipement et de l'Aménagement d'Île-de-France)",'',$title);
  $title= str_replace(" (Direction Régionale de l'Environnement, de l'Aménagement et du Logement Alsace)",'',$title);
  $title= str_replace("DREAL N-PdC (Direction Régionale de l'Environnement de l'Aménagement et du Logement du Nord - Pas de Calais)","DREAL Nord - Pas de Calais",$title);
  
// D(R)AAF
  $title= str_replace("(Direction Régionale de l'Alimentation, de l'Agriculture et de la Forêt d'Aquitaine)",'',$title);
  $title= str_replace(" (Direction Régionale de l'Alimentation, de l'Agriculture et de la Forêt de Haute-Normandie)",'',$title);
  $title= str_replace(" (Direction Régionale de l'Alimentation, de l'Agriculture et de la Forêt de Franche-Comté)",'',$title);
  $title= str_replace("DRIAAF ILE DE FRANCE (Direction régionale et interdépartementale de l'alimentation, de l'agriculture et de la forêt d'Ile-de-France)","DRIAAF Ile-de-France",$title);
  $title= str_replace("DRAAF-ALSACE (Direction Régionale de l'Alimentation, de l'Agriculture et de la Forêt d'ALSACE)","DRAAF Alsace",$title);
  $title= str_replace("DRAAF-BOURGOGNE (DRAAF Bourgogne(Dijon))",'DRAAF Bourgogne',$title);
  $title= str_replace("Direction de l'Alimentation, de l'Agriculture et de la Forêt de la ",'',$title);
  $title= str_replace(" (Direction Régionale de l’Alimentation de l’Agriculture et de la Forêt Midi-Pyrénées)",'',$title);
  $title= str_replace(" (Direction Régionale de l'Alimentation, de l'Agriculture et de la Forêt de Corse",'',$title);
  $title= str_replace(" (Direction régionale de l'alimentation, de l'agriculture et de la forêt de Lorraine)",'',$title);
  $title= str_replace("  (Direction Régionale de l'Alimentation, de l'Agriculture et de la Forêt de Picardie)",'',$title);
  
// DDT
  $title= str_replace(" (Direction Départementale des territoires des Hautes-Alpes",' (Hautes-Alpes)',$title);
  $title= str_replace(" (Direction Départementale des Territoires de la Haute-Loire)de Haute-Loire",' (Haute-Loire)',$title);
  
// générique
// DDT
  $title= str_replace("Direction Départementale des Territoires de l'",'',$title);
  $title= str_replace("Direction Départementale des Territoires du ",'',$title);
  $title= str_replace("Direction Départementale des Territoires de la ",'',$title);
  $title= str_replace("Direction départementale des Territoires de la ",'',$title);
  $title= str_replace("Direction Départementale des Territoires de ",'',$title);
  $title= str_replace("Direction départementale des territoires  d'",'',$title);
  $title= str_replace("Direction Départementale des Territoires d'",'',$title);
  $title= str_replace("Direction Départementale des Territoires des ",'',$title);
// DDTM
  $title= str_replace("Direction Départementale des Territoires et de la Mer d'",'',$title);
  $title= str_replace("Direction Départementale des Territoires et de la Mer du ",'',$title);
  $title= str_replace("Direction départementale des territoires et de la Mer du ",'',$title);
  $title= str_replace("Direction départementale des territoires et de la mer du ",'',$title);
  $title= str_replace("Direction Départementale des Territoires et de la Mer de la ",'',$title);
  $title= str_replace("Direction Départementale des Territoires et de la Mer de l'",'',$title);
  $title= str_replace("Direction Départementale des Territoires et de la Mer de ",'',$title);
  $title= str_replace("Direction Départementale des Territoires et de la Mer des ",'',$title);
  $title= str_replace("Direction Départementale des territoires et de la Mer des ",'',$title);
  
// DIRM
  $title= str_replace("Direction InterRégionale Mer ",'',$title);

//  echo "$tuple[title]\n->";
//  echo "$title\n";

  echo "\n-> attributedTo=$tuple[attributedTo]\n";
  $attributedTo = null;
  if (preg_match('!^(DDT)M? (\d.)$!', $tuple['attributedTo'], $matches))
    $attributedTo = $matches[1].$matches[2];
  elseif ($tuple['attributedTo'] == '') {}
  elseif ($attributedTo = ypath($minenv, "//$/title=$tuple[attributedTo]")) {}
  elseif ($attributedTo = ypath($minagri, "//$/title=$tuple[attributedTo]")) {}
  elseif ($attributedTo = ypath($minenv, "//$/altLabels-)$tuple[attributedTo]")) {}
  elseif ($attributedTo = ypath($minenv, "///children/$/title=$tuple[attributedTo]")) {}
  elseif ($attributedTo = ypath($minenv, "///children/$/altLabels-)$tuple[attributedTo]")) {}
  elseif ($attributedTo = ypath($minagri, "//$/altLabels-)$tuple[attributedTo]")) {}
  elseif ($tuple['attributedTo']=="DTAM Saint-Pierre-et-Miquelon")
    $attributedTo = 'DtamSpm';
  else
    die("Erreur attributedTo=\"$tuple[attributedTo]\" non trouvé");
/*
  $attributedTo= str_replace("DRAAF-OCCITANIE (Direction Régionale de l'Alimentation, de l'Agriculture et de la Forêt Occitanie)","DRAAF Occitanie",$attributedTo);
  $attributedTo= str_replace("DRAAF-OCCITANIE (Direction Régionale de l’Alimentation de l’Agriculture et de la Forêt Occitanie)","DRAAF Occitanie",$attributedTo);
  $attributedTo= str_replace("DIRM NAMO (Direction interrégionale de la mer Nord Atlantique - Manche Ouest)","DIRM NAMO (Nord Atlantique - Manche Ouest)",$attributedTo);
  $attributedTo= str_replace("DREAL Grand Est (Direction Régionale de l'Environnement, de l'Aménagement et du Logement)","DREAL Grand Est",$attributedTo);
*/
  echo "<- attributedTo=$attributedTo\n";
  
  if (!preg_match('!^http://ogc.geo-ide.developpement-durable.gouv.fr/cartes/mapserv\?map=/opt/data/carto/geoide-catalogue/([^/]*)/([^.]*).www.map&service=WMS&request=GetCapabilities$!', $tuple['url'], $matches))
    die("url $tuple[url] don't match");
  $servId = "geoide-$matches[1]-$matches[2]";
  $yaml['servers'][$servId] = [
    'title' => $title,
    'class' => $attributedTo,
    'url' => str_replace('request=GetCapabilities','', $tuple['url']),
    'protocol' => 'WMS',
  ];
//  $yaml['classification'][$attributedTo] = ['title'=>$attributedTo];
}
ksort($yaml['servers']);
ksort($yaml['classification']);
echo Spyc::YAMLDump($yaml, false, 100, true);
