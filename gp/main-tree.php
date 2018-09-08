<?php
/*PhpDoc:
name:  main-tree.php
title: main-tree.php - génère l'arbre principal pour le menu FancyTree, appelé en Ajax par gp-fancytree-js.php
includes: [ '../servreg/servreg.inc.php', '../servreg/capsummaries.inc.php' ]
functions:
doc: |
  Génère l'arbre en JSON conformément aux specs de FancyTree
  Peut être utilisé soit en SAPI CLI ou non
  \xampp\php\php.exe main-tree.php [{register}]
    ex:
      P:\xampp\htdocs\gexplor\gp>\xampp\php\php.exe main-tree.php serv-geoide.yaml
  php main-tree.php [{register}]
  http://localhost/gexplor/gp/main-tree.php
  http://localhost/gexplor/gp/main-tree.php?register=serv-geoide.yaml
journal: |
  28/3/2017
    utilisation du paramètre éventuel register dans l'URL href associée au serveur
  25/3/2017
    utilisation du fichier capsummaries.phpser
    supression du fichier json de bufferisation qui devient inutile
  24/3/2017
    ajout de la possibilité d'utiliser un registre différent
    ajout de la possibilité d'avoir une classification vide
  21/3/2017
    correction d'un bug
  18/3/2017
    Utilisation de Server::availableInWmOrGeo() à la place de Server::availableInWM()
  9-11/3/2017
    Migration vers viuserv puis servreg
  5/3/2017:
    Les titres des serveurs WMS sont différents en local et sur Alwaysdata !!!
  4/3/2017:
    Ajout d'une exception pour traiter le serveur OSM
  2/3/2017:
    Ajout d'une feuille bookmark pour générer le bookmark
  28/2/2017:
    si main-tree.json existe on se contente de l'envoyer, sinon il est généré puis envoyé
  26/2/2017:
    Modfication de l'affectation des serveurs affectés à aucune classe:
    1) le champ class d'un serveur peut être absent
    2) les serveurs qui n'ont pas de classe ou dont la classe est absente de la classification sont affectés à la dernière
       classe de la classification qui est normalement Autres
  19-20/2/2017:
    ajout d'un test de compatibilité de chaque serveur avec WM et s'il ne l'est pas affichage barré du titre
  15/2/2017:
    ajout pour chaque serveur d'un lien vers l'affichage des capacités du serveur
    ajout pour les classes associées à un lien du lien correspondant
    ajout du résumé du serveur comme bulle d'aide (tooltip)
  11/2/2017:
    première version
*/

// définition du paramètre register en fonction du sapi
//echo "php_sapi_name=",php_sapi_name(),"\n"; die();
$register = 'default.yaml';
if (php_sapi_name()=='cli') {
//  echo "argc=$argc\nargv="; print_r($argv);
  if ($argc > 1)
    $register = $argv[1];
} else {
  header('Content-type: text/plain; charset="utf8"');
  if (isset($_GET['register']))
    $register = $_GET['register'];
}

require_once '../servreg/servreg.inc.php';
require_once '../servreg/capsummaries.inc.php';
CapSummaries::load('../servreg/');

/*PhpDoc: functions
name:  viewpoints
title: function viewpoints() - génère l'arbre des points de vue
doc: |
  Génère un tableau Php qui traduit en JSON sera conforme aux specs de FancyTree
*/
function viewpoints() {
  $viewpoints = [
    "métropole" => ['center'=>[47.3, 2.4], 'zoom'=> 7],
    "Martinique" => ['center'=>[14.67, -61], 'zoom'=>11],
    "Guadeloupe" => ['center'=>[16.25, -61.57], 'zoom'=>11],
    "Guyane" => ['center'=>[4.9, -53], 'zoom'=>9],
    "Réunion" => ['center'=>[-21.1, 55.6], 'zoom'=>11],
    "Mayotte" => ['center'=>[-12.78, 45.18], 'zoom'=>12],
    "St. P. &amp; M." => ['center'=>[46.94, -56.3], 'zoom'=>11],
    "Autres OM" => [
      "St. Barth. &amp; St. Martin" => ['center'=>[18.0, -62.95], 'zoom'=>12],
      "Wallis-et-Futuna" => ['center'=>[-13.84, -176.88], 'zoom'=>6],
      "Polynésie" => ['center'=>[-17, -145], 'zoom'=>6],
      "Iles Crozet" => ['center'=>[-46.3, 51.4], 'zoom'=>9],
      "Iles Kerguelen" => ['center'=>[-49.3, 69.7], 'zoom'=>9],
      "Iles SP&amp;A" => ['center'=>[-38.3, 78], 'zoom'=>9],
      "Iles éparses" => ['center'=>[-18.9, 48.2], 'zoom'=>6],
      "Nouvelle Calédonie" => ['center'=>[-21.5, 166], 'zoom'=>9],
    ],
  ];
  $json = [];
  foreach ($viewpoints as $title => $viewpoint)
    if (isset($viewpoint['center']))
      $json[] = ['title'=>$title, 'data'=>['center'=>$viewpoint['center'], 'zoom'=>$viewpoint['zoom']]];
    else {
      $children = [];
      foreach ($viewpoint as $childTitle => $child)
        $children[] = ['title'=>$childTitle, 'data'=>['center'=>$child['center'], 'zoom'=>$child['zoom']]];
      $json[] = ['title'=>$title, 'folder'=>'true', 'children'=>$children];
    }
  return [
      'title'=> "Points de vue",
      'tooltip'=> "Accès aux principaux espaces du territoire français",
      'folder'=> true,
      'children'=> $json
  ];
}

/*PhpDoc: functions
name:  serverDefs
title: function serverDefs($classification, &$servers, $level=0) - génère l'arbre des serveurs de couches
doc: |
  Génère un tableau Php qui, traduit en JSON, sera conforme aux specs de FancyTree
  Balaye récursivement l'arbre de classification, pour chaque classe:
    pour tous les serveurs
      si le serveur est classé selon la classe
        ajoute le serveur au noeud courant
        supprime le serveur
      fin_si
  pour tous les serveurs restants
    ajoute le serveur au noeud courant
  Chaque élément du tableau est soit une classe soit un serveur.
  Chaque classe correspond à la structure:
    [ 'title' => titre de la classe,
      'folder' => true,
      {'tooltip' => résumé éventuel associé à la classe,}?
      {'data' => [ 'href' => URL associé à la classe, 'target' => 'capacites' ],}?
      'children' => [ ELT ]
    ]
  Chaque serveur correspond à la structure:
    [ 'title'=> titre du serveur (protocole)
      'folder'=> true, 'lazy'=> true,
      'tooltip'=> résumé associé au serveur,
      'data'=> [
        'server'=> identifiant du serveur,
        'href'=> lien vers un affichage des capacités du serveur,
        'target'=> 'capacites',
      ]
    ]
*/
// génère l'enregistrement correspondant à un serveur
function serverDef($servers, $server, $href0, $register) {
//  echo "$server[title] ($server[protocol])\n";
  $capsum = CapSummaries::get($server['id']);
  $title = "$server[title] ($server[protocol])";
  if (!$capsum or !$capsum['availableInWmOrGeo'])
    $title = '<s>'.$title.'</s>';
  $register_param = ($register<>'default.yaml' ? "?register=$register" : '');
  return  [ 'title'=>$title,
            'folder'=> true, 'lazy'=> true,
            'tooltip'=> ($capsum ? $capsum['abstract'] : ''),
            'data'=> [
              'server'=> $server['id'],
              'href'=> $href0.$server['id'].$register_param,
              'target'=> 'capacites',
            ]
          ];
}

function serverDefs($classification, &$servers, $register, $level=0) {
//  echo "servers: classification="; print_r($classification);
  $json = [];
  $href0 = ((isset($_SERVER['SERVER_NAME']) and in_array($_SERVER['SERVER_NAME'], ['localhost','bdavid.alwaysdata.net'])) ?
              "http://$_SERVER[SERVER_NAME]/gexplor"
            : 'http://gexplor.fr')
         . '/servreg/servreg.php/';
//  echo "href0=$href0\n"; die();
  foreach ($classification as $classId => $class) {
    $children = [];
    foreach ($servers as $serverId => $server)
      if (isset($server['class']) and ($server['class']==$classId)) {
//        echo "ajout du serveur $serverId\n";
//        die("ajout du serveur $serverId\n");
        $children[] = serverDef($servers, $server, $href0, $register);
        unset($servers[$serverId]);
      }
    if (isset($class['children']) and $class['children'])
      $children = array_merge($children, serverDefs($class['children'], $servers, $register, $level+1));
    $node = [
        'title'=> (isset($class['title']) ? $class['title'] : 'Non défini'),
        'folder'=> true,
    ];
// Ajout éventuelle de l'URL associée à une classe
    if (isset($class['abstract']))
      $node['tooltip'] = $class['abstract'];
    if (isset($class['url'])) {
      $node['data'] = [ 'href'=> $class['url'], 'target'=> 'capacites' ];
      if (!isset($node['tooltip']))
        $node['tooltip'] = "Cliquez pour plus d'infos";
    }
    $node['children'] = $children;
    $json[] = $node;
  }

// Les serveurs qui n'ont pas été affectés à une classe sont ajoutés à la dernière classe qui est normalement Autres
// Je gère aussi le cas particulier où la classification est vide et tous les serveurs sont au premier niveau de l'arbre
  if (!$level and $servers) {
    if (count($json)==0) // cas particulier: à ce stade le résultat est vide
      foreach ($servers as $serverId => $server)
        $json[] = serverDef($servers, $server, $href0, $register);
    else // cas normal: j'ai déjà créé des classes dans le résultat
      foreach ($servers as $serverId => $server)
        $json[count($json)-1]['children'][] = serverDef($servers, $server, $href0, $register);
  }
  
// Si cette dernière classe existe bien et ne contient aucun enfant, elle est supprimée
  if (isset($json[count($json)-1]['children']) and (count($json[count($json)-1]['children'])==0))
    array_pop($json);
  return $json;
}

// travaille sur le FancyTree, supprime les noeuds qui n'ont pas d'enfant
function cleanTree($tree) {
  $cleanedTree = [];
  foreach ($tree as $elt)
    if (!isset($elt['children']) or (count($elt['children'])<>0)) {
      $newelt = [];
// recopie des champs élémentaires
      foreach ($elt as $key=>$value)
        if ($key=='children')
          $newelt['children'] = cleanTree($elt['children']);
        else
          $newelt[$key] = $value;
      $cleanedTree[] = $newelt;
    }
  return $cleanedTree;
}

if (!($servreg = servreg('view','../servreg/', $register)))
  die("Erreur d'ouverture du registre $register ligne ".__LINE__);
if (!isset($servreg['servers']) or (count($servreg['servers'])==0))
  die("Erreur: le registre ne contient aucun serveur");

$classification = ((isset($servreg['classification']) and $servreg['classification']) ? $servreg['classification'] : []);
die(
  json_encode(
    [
      [ 'title'=> "Outils",
        'data'=> [
          'tools'=>true,
        ],
      ],
      viewpoints(),
      [ 'title'=> "Couches",
        'tooltip'=> "Arbre des couches organisé selon une classification hiérarchique des diffuseurs "
                    ."puis pour chacun par serveur et selon l'aborescence de couches qu'il définit",
        'folder'=> true,
        'expanded'=> true,
        'children'=> cleanTree(serverDefs($classification, $servreg['servers'], $register)),
      ]
    ],
    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE
  )
);
