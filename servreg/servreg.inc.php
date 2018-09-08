<?php
/*PhpDoc:
name: servreg.inc.php
title: servreg.inc.php - API Php d'accès au registre des serveurs
includes:
  - '../../spyc/spyc2.inc.php'
  - wmsserver.inc.php
  - wmtsserver.inc.php
  - tileserver.inc.php
  - osmserver.inc.php
functions:
classes:
doc: |
  Ce fichier définit:
  - les 2 interfaces Server et Layer que doivent respecter les serveurs et les couches
  - 2 fonctions:
    - servreg() qui renvoie le tableau des serveurs définis dans le registre
    - newServer() qui renvoie un objet Server à partir d'une définition d'un serveur dans le registre
  Exemple d'utilisation:
    echo newServer(servreg()['servers']['IGNFGP-WMS-R'])->cap()->asXml();
journal: |
  27/3/2017 :
    essai d'amélioration du temps de réponse en bufferisant le register par défaut dans servreg.phpser
  18/3/2017 :
    Evol de Server::availableInWM() en Server::availableInWmOrGeo()
    Remplacement de Layer::availableInWM() en Layer::availableInWmOrGeo() et Layer::availableInCrs()
  13/3/2017 :
    Prise en compte de la modification du schema des fichiers serveurs
  10-11/3/2017 :
    Passage à servreg, rajout du paramètre serviceType pour filtrer sur le type de service
  7-8/3/2017 :
    première version
    écriture partielle
*/
require_once dirname(__FILE__).'/../../spyc/spyc2.inc.php';
require_once 'wmsserver.inc.php';
require_once 'wmtsserver.inc.php';
require_once 'tileserver.inc.php';
require_once 'osmserver.inc.php';


/*PhpDoc: classes
name:  interface Server
title: interface Server
doc: |
  Interface pour un serveur comme un serveur WMS
  interface Server {
    public function cap(); // renvoie pour les serveurs Ogc les capacités Ogc simplifiées comme objet SimpleXml
                           // et pour les autres des capacités adhoc comme tableau Php
    public function getCap(); // affiche les capacités simplifiées en XML pour les serveurs Ogc ou en JSON pour les autres
    public function conf(); // renvoie l'enregistrement de conf comme tableau Php + id
    public function title(); // titre pour l'IHM
    public function getAbstract(); // résumé
    public function availableInWmOrGeo(); // Une resource au moins est-elle disponible en WM ou en EPSG:4326
    public function showInHtml(); // fiche de présentation du service en HTML
    public function layers(); // renvoie un tableau Php [id=>Layer] / id: pour Wmts: Identifier, pour Wms: numéro d'ordre
    public function layer($name); // renvoie la Layer de nom name, i.e. Identifier pour Wmts, Name pour Wms
  }
*/
interface Server {
  public function cap(); // renvoie pour les serveurs Ogc les capacités Ogc simplifiées comme objet SimpleXml
                         // et pour les autres des capacités adhoc comme tableau Php
  public function getCap(); // affiche les capacités simplifiées en XML pour les serveurs Ogc ou en JSON pour les autres
  public function conf(); // renvoie l'enregistrement de conf comme tableau Php + id
  public function title(); // titre pour l'IHM
  public function getAbstract(); // résumé
  public function availableInWmOrGeo(); // Une resource au moins est-elle disponible en WM ou en EPSG:4326
  public function showInHtml(); // fiche de présentation du service en HTML
  public function layers(); // renvoie un tableau Php [id=>Layer] / id: pour Wmts: Identifier, pour Wms: numéro d'ordre
  public function layer($name); // renvoie la Layer de nom name, i.e. Identifier pour Wmts, Name pour Wms
}

/*PhpDoc: classes
name:  interface Layer
title: interface Layer
doc: |
  Interface pour une couche d'un serveur comme une couche WMS
  interface Layer {
    public function name(); // identifiant interne de la couche, i.e. Identifier pour Wmts, Name pour Wms
    public function title(); // titre pour l'IHM
    public function getAbstract(); // résumé
    public function availableInWmOrGeo(); // la couche ou une de ses sous-couches est-elle disponible en WM ou en EPSG:4326 ?
    public function availableInCrs($crs); // la couche est-elle disponible pour le CRS
    public function children(); // renvoie les couches filles comme tableau Php [id=>Layer] (Wmts'Identifier, Wms'#)
  // renvoie un tableau Php adapté à la génération d'une commande JS pour insérer la couche dans Leaflet
  // ['title'=>titre de la couche, 'lfunc'=> fonction Leaflet, 'url'=>URL d'appel de la couche, 'options'=>options]
  // lfunc vaut 'L.tileLayer' ou 'L.tileLayer.wms'
    public function leafletJS($options=[]);
  }
*/
interface Layer {
  public function name(); // identifiant interne de la couche, i.e. Identifier pour Wmts, Name pour Wms
  public function title(); // titre pour l'IHM
  public function getAbstract(); // résumé
  public function availableInWmOrGeo(); // la couche ou une de ses sous-couches est-elle disponible en WM ou en EPSG:4326 ?
  public function availableInCrs($crs); // la couche est-elle disponible pour le CRS
  public function children(); // renvoie les couches filles comme tableau Php [id=>Layer] (Wmts'Identifier, Wms'#)
// renvoie un tableau Php adapté à la génération d'une commande JS pour insérer la couche dans Leaflet
// ['title'=>titre de la couche, 'lfunc'=> fonction Leaflet, 'url'=>URL d'appel de la couche, 'options'=>options]
// lfunc vaut 'L.tileLayer' ou 'L.tileLayer.wms'
  public function leafletJS($options=[]);
  public function genLegend($style=null);
}

/*PhpDoc: functions
name:  merge_classification
title: function merge_classification($classif1, $classif2) - fusionne les 2 classifications
doc: |
  Fonction récursive utilisée par servreg() et par modify_classification()
  classif0 et classif1 sont 2 classifications cad:
    [ KEY=>CLASS ] / CLASS ::= ['title'=> TEXT, 'url'?=>TEXT, 'children'?=> [KEY=>CLASS]]
  La logique de la fusion est la suivante:
  - pour les champs mono-valués
    s'il est défini dans classif1 alors
      on prend cette valeur
    sinon s'il est défini dans classif2 alors
      on prend cette valeur
    fin_si
  - pour les champs multi-valués: appel récursif de merge_classification() sur le champ des 2 classif
*/
function merge_classification($classif1, $classif2) {
  foreach ($classif1 as $classId => $classDef1)
    if (!isset($classif2[$classId]))
      $classif[$classId] = $classDef1;
    else {
// Si la classe existe dans les 2 classifications alors je garde le titre de la première et je fusionne les enfants
      $classDef2 = $classif2[$classId];
// Pour les champs élémentaires, s'il est défini dans 1 alors on prend 1, sinon s'il est défini dans 2 alors on prend 2, sinon rien
      foreach (['title','abstract','url'] as $field)
        if (isset($classDef1[$field]))
          $classif[$classId][$field] = $classDef1[$field];
        elseif (isset($classDef2[$field]))
          $classif[$classId][$field] = $classDef2[$field];
// Pour les enfants, s'ils sont définis des 2 côtés alors merge récursif, sinon si uniquement dans l'un des 2 on le prend, sinon rien
      if (isset($classDef1['children']) and isset($classDef2['children']))
        $classif[$classId]['children'] = merge_classification($classDef1['children'], $classDef2['children']);
      elseif (isset($classDef1['children']))
        $classif[$classId]['children'] = $classDef1['children'];
      elseif (isset($classDef2['children']))
        $classif[$classId]['children'] = $classDef2['children'];
      unset($classif2[$classId]);
    }
  foreach ($classif2 as $classId => $classDef2)
    $classif[$classId] = $classDef2;
  return $classif;
}

/*PhpDoc: functions
name:  modify_classification
title: function modify_classification($classif, $class, $subclassif) - modifie la classification en ajoutant subclassif aux enfants de la classe class
doc: |
  Fonction récursive utilisée par servreg()
  classif et subclassif sont des classification cad:
    [ KEY=>CLASS ] / CLASS ::= ['title'=> TEXT, 'url'?=>TEXT, 'children'?=> [KEY=>CLASS]]
  class est soit une classe (cad une clé) de classif
  La classiffication classif est modifiée pour fusionner subclassif avec les enfants de la classe class
*/
function modify_classification($classif, $class, $subclassif) {
//  echo "<pre>modify_classification(classif, class=$class, subclassif)\n";
//  echo "classif="; print_r($classif);
//  echo "subclassif="; print_r($subclassif);
//  echo "</pre>\n";
  foreach ($classif as $classId => $classDef)
    if ($classId == $class) {
//      echo "$classId==$class\n";
      $children = [];
      if (isset($classDef['children']) and $classDef['children'] and $subclassif)
        $children =  merge_classification($classDef['children'], $subclassif);
      elseif (isset($classDef['children']) and $classDef['children'])
        $children =  $classDef['children'];
      elseif ($subclassif)
        $children =  $subclassif;
      $classif[$classId] = [
        'title'=>$classDef['title'],
        'children'=> $children,
      ];
      return $classif;
    }
    elseif (isset($classDef['children']) and $classDef['children']) {
//      echo "children\n";
      $classif[$classId]['children'] = modify_classification($classDef['children'], $class, $subclassif);
    }
  return $classif;
}
// Test unitaire de modify_classification
function test_modify_classification() {
  $classif = <<<EOT
a:
  title: a
  children:
    ab:
      title: ab
      url: urlab
      children:
        abc:
          title: abc
        abd:
          title: abd
    ac:
      title: ac
    ae:
      title: ae
b:
  title: b
EOT;
  $subclassif = <<<EOT
ab:
  title: ab
EOT;
  header('Content-type: text/plain; charset="utf-8"');
  echo Spyc::YAMLDump(modify_classification(Spyc::YAMLLoadString($classif), 'a', Spyc::YAMLLoadString($subclassif))),"\n";
  die("Fin du test unitaire de modify_classification() ligne ".__LINE__);
}
// test_modify_classification();

// Test unitaire de merge_classification
function test_merge_classification() {
  $classif1 = <<<EOT
a:
  children:
    ab:
      url: urlab
    ac:
      title: ac
      children:
        ace:
          title: ace
b:
  title: b
EOT;
  $classif2 = <<<EOT
a:
  title: a
  url: urla
  children:
    ab:
      title: ab
    ac:
      title: ac
      url: urlac
      children:
        acd:
          title: acd
c:
  title: c
EOT;
  header('Content-type: text/plain; charset="utf-8"');
  echo Spyc::YAMLDump(merge_classification(Spyc::YAMLLoadString($classif1), Spyc::YAMLLoadString($classif2))),"\n";
  die("Fin du test unitaire de merge_classification() ligne ".__LINE__);
}
//test_merge_classification();

if (!function_exists('strcmpstart')) {
  function strcmpstart($str1, $str2) {
    if (!is_string($str1))
      throw new Exception("str1 not string");
    if (!is_string($str2))
      throw new Exception("str2 not string");
    return strncmp($str1, $str2, strlen($str2));
  }
}

/*PhpDoc: functions
name:  servreg
title: function servreg($serviceType='', $servregPath='', $url='default.yaml') - renvoie le registre sous la forme d'un tableau Php
doc: |
  renvoie le tableau des serveurs: ['phpDoc'=>phpDoc, 'classification'=>[id=>CLASS], 'servers'=>[id=>SERVER]] où:
  - phpDoc ::= ['name'=>name, 'title'=>title, 'doc'=>doc, 'journal'=>journal ]
  - CLASS ::= ['id'=>id, 'title'=>title, 'url'=>url, 'children'=>[id=>CLASS]]
  - SERVER ::= ['id'=>id, 'title'=>title, 'class'=>classid?, 'url'=>url, 'protocol'=>protocol, 'layers'=>[LAYER]? ]
  Les paramètres de la fonction:
  - $serviceType définit le type de service recherché dans le registre, il peut valoir: (valeurs définies par le règlement)
    - 'discovery' : Service de recherche
    - 'view' : Service de consultation
    - 'download' : Service de téléchargement
    - 'transformation' : Service de transformation (non implémenté)
    - 'invoke' : Service d’appel de services de données géographiques (non implémenté)
    - 'other' : Autre service (non implémenté)
    - '' : tous
  - $servregPath est le chemin vers le répertoire servreg, on copiera derrière url précédé de 'servers/', ex: '../'
  - $url est le chemin vers le fichier, utilisé principalement pour l'appel récursif
    c'est généralement le nom du fichier yaml sans répertoire, mais cela peut aussi être un document http
  Y compris:
  - Traitement récursif de l'inclusion d'un sous-fichier de serveurs
  servregPath est recopié dans chaque server afin de ne pas avoir à le fournir dans newServer()
*/
function servreg($serviceType='', $servregPath='', $url='default.yaml') {
// si le registre est celui par défaut et si servreg.phpser existe alors lecture et envoie
  if (($url=='default.yaml') and is_file($servregPath.'servreg.phpser')) {
    $yaml = unserialize(file_get_contents($servregPath.'servreg.phpser'));
    foreach ($yaml['servers'] as $servId => $server)
      $yaml['servers'][$servId]['servregPath'] = $servregPath;
    return $yaml;
  }
// associe à chaque type de service les protocoles correspondants
  $protocols = [
    'discovery'=>[],
    'view'=>['WMS','WMTS','tile','OSM'],
    'download'=>[],
  ];
  if ($serviceType and !isset($protocols[$serviceType]))
    throw new Exception("servreg: serviceType $serviceType inconnu");
// En cas d'exécution sur localhost, les url contenant 'http://servreg.gexplor.fr/' sont remplacées par:
// 'http://localhost/~benoit/gexplor/servreg/' sur le Mac ou 'http://localhost/gexpor/servreg/' sur Vaio
  if ((strcmpstart($url, 'http://servreg.gexplor.fr/')==0) and ($_SERVER['SERVER_NAME']=='localhost')) {
    if (strcmpstart($_SERVER['SCRIPT_NAME'], '/~benoit/gexplor/servreg/')==0)
      $url2 = str_replace('http://servreg.gexplor.fr/','http://localhost/~benoit/gexplor/servreg/',$url);
    else
      $url2 = str_replace('http://servreg.gexplor.fr/','http://localhost/gexplor/servreg/',$url);
  }
  elseif (strcmpstart($url, 'http://')<>0)
// si l'url est un chemin relatif vers le fichier alors on rajoute servregPath
    $url2 = $servregPath.'servers/'.$url;
//  echo "Lecture de $url2\n";
  if (!($yaml = SpycLoad($url2)))
    throw new Exception("Erreur d'analyse de $url2");
//  echo "<pre>"; print_r($yaml['classification']);
//  echo "<pre>"; print_r($yaml); die();
  $servers = [];
  if (!isset($yaml['servers']))
    throw new Exception("yaml[servers] non défini dans ".__FILE__." ligne ".__LINE__);
  foreach ($yaml['servers'] as $serverId => $server) {
    $yaml['servers'][$serverId]['id'] = $serverId;
// lecture d'un sous-fichier de serveurs
    if (isset($server['subfile'])) {
//      echo "Traitement de l'inclusion de $server[subfile]\n";
      $subfile = $server['subfile'];
      $subyaml = servreg($serviceType, $servregPath, $subfile);
      $servers = array_merge($servers, $subyaml['servers']);
      if (isset($subyaml['classification'])) {
        if (isset($server['class']))
          $yaml['classification'] = modify_classification($yaml['classification'], $server['class'], $subyaml['classification']);
        else
          $yaml['classification'] = merge_classification($yaml['classification'], $subyaml['classification']);
      }
    }
// il s'agit bien de la définition d'un serveur, on ne prend que les serveur correspondant au serviceType
    elseif (isset($server['url']) and (!$serviceType or in_array($server['protocol'], $protocols[$serviceType]))) {
      $servers[$serverId]['id'] = $serverId;
      foreach ($server as $k=>$v)
        $servers[$serverId][$k] = $v;
      $servers[$serverId]['servregPath'] = $servregPath;
    }
  }
  $yaml['servers'] = $servers;
//  echo "<pre>yaml="; print_r($yaml); echo "</pre>";
// création de servreg.phpser
  if ($url=='default.yaml')
    file_put_contents($servregPath.'servreg.phpser', serialize($yaml));
  return $yaml;
}

/*PhpDoc: functions
name:  newServer
title: function newServer($server) - exploite le fichier Yaml pour créer un objet Server
doc: |
  Renvoie un objet Server correspondant au serveur
  Paramètres:
  - server : enregistrement issu du fichier de configuration Yaml correspondant au serveur
*/
function newServer($server) {
// Association à chaque protocol de la classe Php implémentant l'API d'exploitation du serveur correspondant
  $classes = [
    'WMTS'=>'WmtsServer',
    'WMS'=>'WmsServer', 
    'tile'=>'TileServer', 
    'OSM'=>'OsmServer', 
  ];
  if (!isset($server['protocol']))
    throw new Exception("Server's protocol undefined");
  if (!isset($classes[$server['protocol']]))
    throw new Exception("Protocol $server[protocol] unknown");
  return new $classes[$server['protocol']]($server);
}


// Tests unitaires
if (basename(__FILE__)<>basename($_SERVER['PHP_SELF'])) return;

$serverId = 'IGNFGP-WMS-R';
$serverId = 'CLC-WMS';

if (1) { // Affichage du fichier Yaml
  header('Content-type: text/plain; charset="utf-8"');
  $serviceType = 'view';
  $serviceType = 'discovery';
  $serviceType = '';
  echo Spyc::YAMLDump(servreg($serviceType), false, 100, true);
}

elseif (0) { // Simulation des fusions de classification
  $default = <<<EOT
Regions:
  title: régionales
  abstract: couches diffusées par des acteurs régionaux
EOT;

  $geoide = <<<EOT
Regions:
  title: régionales
  abstract: couches diffusées par des acteurs régionaux
  children:
    ARA:
      title: Auvergne-Rhône-Alpes
      children:
        DrealARA:
          title: DREAL Auvergne-Rhône-Alpes
        DraafARA:
          title: DRAAF Auvergne-Rhône-Alpes
        DDT01:
          title: DDT Ain (01)
EOT;

  $regions = <<<EOT
ARA:
  title: Auvergne-Rhône-Alpes
EOT;
  header('Content-type: text/plain; charset="utf-8"');
  $result = merge_classification(Spyc::YAMLLoadString($default), Spyc::YAMLLoadString($geoide));
//  echo Spyc::YAMLDump($result, false, 100, true),"\n"; die("Fin du test ligne ".__LINE__);
  $result = modify_classification($result, 'Regions', Spyc::YAMLLoadString($regions));
  echo Spyc::YAMLDump($result, false, 100, true),"\n"; die("Fin du test ligne ".__LINE__);
}

elseif (0) { // Affichage de la conf
  header('Content-type: text/plain; charset="utf-8"');
  print_r(servreg('view')['servers'][$serverId]);
}

elseif (1) { // Affichage de l'objet Server
  header('Content-type: text/plain; charset="utf-8"');
  print_r(newServer(servreg('view')['servers'][$serverId]));
}

elseif (1) { // Affichage des capacités en XML
  header('Content-Type: text/xml');
//  echo newServer(servreg()['servers']['IGNFGP-WMS-R'])->cap()->asXml();
  echo newServer(servreg('view')['servers'][$serverId])->cap()->asXml();
}
