<?php
/*PhpDoc:
name:  capsummaries.inc.php
title: capsummaries.inc.php - enregistrement d'un résumé des capacités des serveurs pour une utilisation plus rapide dans main-tree.php
functions:
doc: |
  L'enregistrement est effectué dans /gexplor/servreg/getcap.php lorsque l'on récupère les capacités
  Un fichier /gexplor/servreg/capsummaries.phpser est créé
  Ce fichier est lu et exploité dans /gexplor/gp/main-tree.php
journal: |
  26/3/2017:
    ajout de addIfAbsent($server) pour accélérer getcap() quand les capacités d'un serveur ne sont pas actualisées
  25/3/2017:
    première version
*/
class CapSummaries {
  static private $store=[]; // [serverid=> ['abstract'=>abstract, 'availableInWmOrGeo'=>availableInWmOrGeo]]

/*PhpDoc: functions
name: add
title: static function add($server) - ajoute un serveur
*/
  static function add($server) {
    try {
      $serv = newServer($server);
      self::$store[$server['id']]['abstract'] = $serv->getAbstract();
      self::$store[$server['id']]['availableInWmOrGeo'] = $serv->availableInWmOrGeo();
    } catch(Exception $e) {  }
  }
/*PhpDoc: functions
name: addIfAbsent
title: static function addIfAbsent($server) - ajoute un serveur s'il n'est pas déjà présent dans le résumé
*/
  static function addIfAbsent($server) {
    if (!isset(self::$store[$server['id']]))
      self::add($server);
  }
/*PhpDoc: functions
name: dump
title: static function dump() - affiche les résumés
*/
  static function dump() { print_r(self::$store); }
/*PhpDoc: functions
name: store
title: static function store() - enregistre les résumés dans le fichier capsummaries.phpser
*/
  static function store() { file_put_contents('capsummaries.phpser', serialize(self::$store)); }
/*PhpDoc: functions
name: load
title: static function load($dirpath='') - charge en mémoire les résumés à partir du fichier capsummaries.phpser
*/
  static function load($dirpath='') {
    if (file_exists($dirpath.'capsummaries.phpser'))
      self::$store = unserialize(file_get_contents($dirpath.'capsummaries.phpser'));
    else
      self::$store = [];
  }
/*PhpDoc: functions
name: get
title: static function get($serverId) - retourne le résumé d'un serveur ou null
*/
  static function get($serverId) { return isset(self::$store[$serverId]) ? self::$store[$serverId] : null; }
};
