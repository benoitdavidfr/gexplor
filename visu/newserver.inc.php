<?php
/*PhpDoc:
name:  newserver.inc.php
title: newserver.inc.php - exploite le fichier Yaml pour créer un objet OgcServer
includes: [ '../../phplib/yaml.inc.php', wmsserver.inc.php, wmtsserver.inc.php, tileserver.inc.php ]
functions:
doc: |
  Définit 2 fonctions:
  - file_get_servers($visupath='') qui lit le fichier Yaml des serveurs, intègre les sous-fichiers
    et renvoie une tableau Php ayant une structure dérivée de celle du fichier Yaml des serveurs
  - newServer($servers, $serverId) qui prend la structure renvoyée par file_get_servers() ainsi qu'un identifiant de serveur
    pour renvoyer un objet d'une des classes WmtsServer, WmsServer ou TileServer

journal: |
  13/11/2016
    ajout du type de serveur tile
    suppression du serveur utility
  9/11/2016
    ajout du serveur utility
  6-7/11/2016:
    améliorations
    Adaptation pour fonctionner indifféremment sur alwaysdata, localhost ou localhost/~benoit
  3/11/2016:
    ajout des fonctions file_get_servers() et modify_classification()
  30/10/2016:
    première version
*/
require_once dirname(__FILE__).'/../../phplib/yaml.inc.php';
require_once 'wmsserver.inc.php';
require_once 'wmtsserver.inc.php';
require_once 'tileserver.inc.php';

/*PhpDoc: functions
name:  modify_classification
title: modify_classification($classif, $class, $subclassif) - modifie la classification en remplacant les enfants de la classe class par subclassif
doc: |
  Fonction récursive utilisée par file_get_servers()
*/
function modify_classification($classif, $class, $subclassif) {
//  echo "mod_class(classif, $class, subclassif)\n";
//  echo "classif="; print_r($classif);
//  echo "subclassif="; print_r($subclassif);
  foreach ($classif as $classId => $classDef)
    if ($classId == $class) {
//      echo "$classId==$class\n";
      $classif[$classId] = [ 'title'=>$classDef['title'], 'children'=>$subclassif ];
      return $classif;
    }
    elseif (isset($classDef['children']) and $classDef['children']) {
//      echo "children\n";
      $classif[$classId]['children'] = modify_classification($classDef['children'], $class, $subclassif);
    }
  return $classif;
}

/*PhpDoc: functions
name:  file_get_servers
title: function file_get_servers($visupath='', $url='servers.yaml') - lit le fichier Yaml de configuration et renvoie un tableau Php
doc: |
  Y compris:
  - Traitement récursif de l'inclusion d'un sous-fichier de serveurs
  Le paramètre visupath est le chemin relatif du répertoire courant (getcwd()) vers le répertoire racine de visu, ex: '../' 
*/
function file_get_servers($visupath='', $url='servers.yaml') {
// En cas d'exécution sur localhost, les url contenant 'http://visu.gexplor.fr/' sont remplacées par:
// 'http://localhost/~benoit/visu/' sur le Mac ou 'http://localhost/visu/' sur Vaio
//  echo "file_get_servers(url=$url, visupath=$visupath)\n";
//  echo "<pre>_SERVER="; print_r($_SERVER); echo "</pre>";
  if ((strncmp($url, 'http://visu.gexplor.fr/', 23)==0) and ($_SERVER['SERVER_NAME']=='localhost')) {
    if (strncmp($_SERVER['SCRIPT_NAME'], '/~benoit/gexplor/visu/', 22)==0)
      $url = str_replace('http://visu.gexplor.fr/','http://localhost/~benoit/gexplor/visu/',$url);
    else
      $url = str_replace('http://visu.gexplor.fr/','http://localhost/gexplor/visu/',$url);
  }
  elseif (strncmp($url, 'http://', 7)<>0)
// si l'url est un chemin relatif vers le fichier alors on rajoute visupath puis le répertoire servers
    $url = $visupath.'servers/'.$url;
//  echo "Lecture de $url\n";
  $yaml = yaml_parse(file_get_contents($url));
//  echo "<pre>"; print_r($yaml['classification']);
  $servers = [];
  foreach ($yaml['servers'] as $serverId => $server) {
// lecture d'un sous-fichier de serveurs
    if (!isset($server['protocol'])) {
//      echo "Traitement de l'inclusion de $server[url]\n";
      $url = $server['url'];
      $subyaml = file_get_servers($visupath, $url);
      $servers = array_merge($servers, $subyaml['servers']);
      $yaml['classification'] = modify_classification($yaml['classification'], $server['class'], $subyaml['classification']);
    }
    else
      $servers[$serverId] = $server;
  }
  $yaml['servers'] = $servers;
//  echo "<pre>yaml="; print_r($yaml); echo "</pre>";
  return $yaml;	
}

/*PhpDoc: functions
name:  newServer
title: function newServer($servers, $serverId) - exploite le fichier Yaml pour créer un objet OgcServer
doc: |
  Paramètres:
  - servers : liste des serveurs issue du fichier de configuration Yaml
  - serverId : identifiant d'un serveur
  - cappath : le chemin pour le répertoire capabilities
  Renvoie l'objet OgcServer
*/
function newServer($servers, $serverId, $cappath='') {
  if (!isset($servers[$serverId]))
    throw new Exception("Server $serverId unknown");
  $server = $servers[$serverId];
// [ protocol => class ]
  $classes = [
    'WMTS'=>'WmtsServer',
    'WMS'=>'WmsServer', 
    'tile'=>'TileServer', 
  ];
  if (!isset($classes[$server['protocol']]))
    throw new Exception("Protocol $server[protocol] unknown");
  return new $classes[$server['protocol']]($serverId, $server, $cappath);
}


// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

header('Content-type: text/plain; charset="utf-8"');
require_once '../../spyc/spyc.inc.php';
echo Spyc::YAMLDump(file_get_servers(), false, 100, true);
