<?php
/*PhpDoc:
name:  check.php
title: check.php - vérifie que le contenu du registre des serveurs respecte son schema
includes: [ servreg.inc.php, '../../spyc/yamlschema.inc.php' ]
doc: |
journal: |
  11-13/3/2017:
    première version
*/
require_once 'servreg.inc.php';
require_once '../../spyc/yamlschema.inc.php';

switch (isset($_GET['action']) ? $_GET['action'] : null) {
  case null:
    echo <<<EOT
<!DOCTYPE html><html><head><meta charset='UTF-8'><title>check</title></head><body>
<h2>Menu</h2><ul>
<li><a href='?action=showYaml'>afficher le contenu du fichier du schema Yaml des serveurs</a>
<li><a href='?action=analyze'>analyse le schema Yaml des serveurs</a>
<li><a href='?action=showAsHtml'>analyse le schema Yaml des serveurs et l'affiche en HTML</a>
<li><a href='?action=check'>vérifie la conformité au schema du fichier servers.yaml</a>
<li><a href='?action=checkGlobal'>vérifie la conformité au schema du fichier global des serveurs</a>
</ul>
EOT;
    die();
  case 'showYaml':
    if (!($yaml = Spyc::YAMLLoad('servers/yamlschema.yaml')))
      die("Erreur de lecture du schema Yaml");
    header('Content-type: text/plain; charset="utf-8"');
    die(Spyc::YAMLDump($yaml, false, 100, true));
    
  case 'analyze':
    header('Content-type: text/plain; charset="utf-8"');
    if (!($yaml = Spyc::YAMLLoad('servers/yamlschema.yaml')))
      die("Erreur de lecture du schema Yaml");
    unset($yaml['phpDoc']);
    $schema = new Schema($yaml);
    $schema->show();
    die();
    
  case 'showAsHtml':
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>check</title></head><body>\n";
    if (!($yaml = Spyc::YAMLLoad('servers/yamlschema.yaml')))
      die("Erreur de lecture du schema Yaml");
    unset($yaml['phpDoc']);
    $schema = new Schema($yaml);
    $schema->showAsHtml();
    die();
    
  case 'check':
    header('Content-type: text/plain; charset="utf-8"');
    if (!($yaml = Spyc::YAMLLoad('servers/yamlschema.yaml')))
      die("Erreur de lecture du schema Yaml");
    unset($yaml['phpDoc']);
    $schema = new Schema($yaml);
    if (!($yaml = Spyc::YAMLLoad('servers/servers.yaml')))
      die("Erreur de lecture du fichier servers/servers.yaml");
    $schema->check($yaml, false);
    die("Check OK");
    
  case 'checkGlobal':
    header('Content-type: text/plain; charset="utf-8"');
    if (!($yamlschema = Spyc::YAMLLoad('servers/yamlschema.yaml')))
      die("Erreur de lecture du schema Yaml");
    unset($yamlschema['phpDoc']);
    $schema = new Schema($yamlschema);
    if (!($yaml = servreg()))
      die("Erreur sur servreg()");
//    print_r($yaml);
    $schema->check($yaml, false);
    die("Check OK");
    
  default:
    die("action $_GET[action] inconnue");
}
