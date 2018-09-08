<?php
/*PhpDoc:
name:  viewer.php
title: viewer.php - génère le code JavaScript affichant la carte
includes: [ '../../phplib/yaml.inc.php', newserver.inc.php, genmapjs.inc.php ]
doc: |
  Prend en paramètre:
  - soit l'URL d'un fichier texte contenant la description Yaml d'une carte
  - soit en paramètre POST le texte lui-même
  et génère le code JavaScript affichant la carte   
  Si aucun des 2 paramètres n'est présent, génère un formulaire permettant de coller le texte et de l'envoyer
journal: |
  19/4/2017:
    pour simplifier l'URL d'appel ajout de la possibilité de passer le nom d'une carte dans maps en PATH_INFO
  6/3/2017:
    ajout d'une exception pour OSM
  3/11/2016:
    utilisation de file_get_servers()
  29/10/2016:
    première version
*/
require_once '../../phplib/yaml.inc.php';
require_once 'newserver.inc.php';
require_once 'genmapjs.inc.php';

$servers = []; // Tableau d'objets OgcServer identifiés par leur id
$serversYaml = file_get_servers();
foreach (array_keys($serversYaml['servers']) as $id)
  if ($id<>'OSM')
    $servers[$id] = newServer($serversYaml['servers'], $id);
  
//echo "<pre>_SERVER="; print_r($_SERVER); echo "</pre>\n";
if (isset($_SERVER['PATH_INFO']) and ($_SERVER['PATH_INFO'])) {
//  echo "PATH_INFO=$_SERVER[PATH_INFO]";
  if (!is_file("maps$_SERVER[PATH_INFO]"))
    die("Erreur la carte maps$_SERVER[PATH_INFO] n'existe pas");
  try {
    genmapjsFromYamlFile("maps$_SERVER[PATH_INFO]", $servers);
  } catch (Exception $e) {
    echo "\n</script>\n",$e->getMessage();
    die();
  }
  die();
} elseif (isset($_GET['url'])) {
  try {
    genmapjsFromYamlFile($_GET['url'], $servers);
  } catch (Exception $e) {
    echo "\n</script>\n",$e->getMessage();
    die();
  }
  die();
} elseif (isset($_POST['yaml'])) {
  $yamlSrce = $_POST['yaml'];
  try {
    genmapjsFromYaml($yamlSrce, $servers, ['mapstyle'=>'height: 80%; width: 95%']);
  } catch (Exception $e) {
    echo "\n</script>\n",$e->getMessage();
    die();
  }
  echo "
<table border=1><form method='post'>
<tr><td><center><input type='submit' value='Envoi'></center></td></tr>
<tr><td><textarea rows=40 cols=130 name='yaml'>",
htmlspecialchars($yamlSrce, ENT_COMPAT | ENT_HTML401,'UTF-8'),
"</textarea></td></tr>
</form></table>
</body></html>
";
} else
  echo <<<EOT
<html><head><title>map</title><meta charset='UTF-8'></head><body>
<table border=1><form><tr>
<td>url</td>
<td><input type='text' size=120 name='url' value=""/></td>
<td><center><input type='submit' value='Envoi'></center></td>
</tr></form></table>
ou texte source:
<table border=1><form method='post'>
<tr><td><textarea rows=40 cols=90 name='yaml'></textarea></td></tr>
<tr><td><center><input type='submit' value='Envoi'></center></td></tr>
</form></table>
</body></html>
EOT;
?>