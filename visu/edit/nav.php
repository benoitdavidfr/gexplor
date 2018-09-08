<?php
/*PhpDoc:
name:  nav.php
title: nav.php - PERIME
includes: [ '../newserver.inc.php', '../lib/jquery.js', '../lib/jquery-ui.custom.js', nav.css, fancytree.js ]
hrefs: [ edit.php, '../doc.php' ]
functions:
doc: |
  génère l'arbre des actions possibles - chaque action correspond à remplacer le frame content par une nouvelle carte
journal: |
  21/11/2016:
    script PERIME remplacé par nav.html, main-tree.php et server-tree.php
  2/11/2016:
    remplacement du theme par class
  1/11/2016:
    évolution
  31/10/2016:
    première version
*/
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <meta name="robots" content="noindex,follow">
  <script src="../lib/jquery.js" type="text/javascript"></script>
  <script src="../lib/jquery-ui.custom.js" type="text/javascript"></script>
  <link href="../lib/fancytree/skin-win8/ui.fancytree.min.css" rel="stylesheet" type="text/css">
  <script src="../lib/fancytree/jquery.fancytree-all.min.js" type="text/javascript"></script>
  <title>Navigation</title>
  <style src="nav.css" type="text/css"></style>
<!-- la fonction initialisant l'arbre -->
  <script src="fancytree.js" type="text/javascript"></script>
</head>
<body>
  <div id="tree">
  <ul>
  <li class="folder expanded"> Outils
    <ul>
    <li class="folder"> Opérations
      <ul>
        <li><a target='content' href='edit.php?action=enregistrer'>Enregistrer la carte</a>
        <li><a target='content' href='edit.php'>Réinitialiser la carte</a>
        <li><a target='content' href='edit.php?action=dump'>Afficher le code source de la carte</a>
        <li><a target='content' href='edit.php?action=none'>Afficher la carte</a>
        <li><a target='content' href='../doc.php'>Documentation</a>
        <li><a target='_blank' href='../index.php'>Accueil de visu</a>
      </ul>
    <li class="folder"> Points de vue
      <ul>
<?php
// Affichage de l'arbre des points de vue
$viewpoints = [
  ['view'=>"[48, 3], 8", 'title'=>"métropole"],
  ['view'=>"[14.61, -61], 12", 'title'=>"Martinique"],
  ['view'=>"[16.25, -61.57], 12", 'title'=>"Guadeloupe"],
  ['view'=>"[4.9, -53], 10", 'title'=>"Guyane"],
  ['view'=>"[-20.96, 55.46], 12", 'title'=>"Réunion"],
  ['view'=>"[-12.78, 45.18], 13", 'title'=>"Mayotte"],
  ['view'=>"[47.10, -56.3], 13", 'title'=>"SP&amp;M"],
  ['title'=>'Autres OM', 'children'=> [
    ['view'=>"[-13.84, -176.88], 6", 'title'=>"Wallis-et-Futuna"],
    ['view'=>"[-17, -145], 6", 'title'=>"Polynésie"],
    ['view'=>"[-46.3, 51.3], 10", 'title'=>"Crozet"],
    ['view'=>"[-49.3, 69.3], 10", 'title'=>"Kerguelen"],
    ['view'=>"[-38.3, 78], 9", 'title'=>"Iles SP&amp;A"],
    ['view'=>"[-18.9, 48.2], 6", 'title'=>"Iles éparses"],
    ['view'=>"[-21.5, 166], 9", 'title'=>"Nouvelle Calédonie"],
  ]],
];
foreach ($viewpoints as $viewpoint)
  if (isset($viewpoint['children'])) {
    echo "<li class='folder'>$viewpoint[title]<ul>\n";
    foreach ($viewpoint['children'] as $child)
      echo "<li><a target='content' href='edit.php?view=",urlencode($child['view']),"'>$child[title]</a>";
    echo "</ul>\n";
  } else
    echo "<li><a target='content' href='edit.php?view=",urlencode($viewpoint['view']),"'>$viewpoint[title]</a>";
?>
      </ul>
    </ul>
  <li class="folder expanded"> Couches
    <ul>
<?php
// Affichage de la liste des couches
require_once '../newserver.inc.php';

// Affiche récursivement une couche
function display_layer($serverId, $layer) {
  if (!isset($layer['children'])) {
    if ($layer['name']=='ERROR')
      echo "<li>Serveur en erreur : $layer[title]\n";
    else
      echo "<li><a target='content' href='edit.php?server=$serverId&amp;layer=$layer[name]'>$layer[title]</a>\n";
//    echo "<br><pre>layer="; print_r($layer); echo "</pre>";
  }
  else {
    echo "<li class='folder'>$layer[title]<ul>\n";
    foreach ($layer['children'] as $child)
      display_layer($serverId, $child);
    echo "</ul>\n";
  }
}

// Affiche le sous-arbre correspondant à un serveur WMTS/WMS
function display_server($servers, $serverId) {
//  if ($serverId <> 'Ifremer-dcsmm') return;
  $maxNbreLayersDisplayedByServer = 0; // nbre max de couches par serveur au premier niveau, 0 <=> pas de découpage
  $ogcServer = newServer($servers, $serverId, '../');
  try {
    $layerTree = $ogcServer->layerTree();
  } catch (Exception $e) {
    $layerTree = [['name'=>'ERROR', 'title'=>$e->getMessage()]];
  }
  if ($maxNbreLayersDisplayedByServer) {
  // Si le server contient plus de 100 couches, on les décompose en 2 niveaux
    if (count($layerTree) > ($maxNbreLayersDisplayedByServer * $maxNbreLayersDisplayedByServer)) {
      $newTree = [];
      $node1 = null; // noeuds des dizaines contenant les serveurs
      $node2 = null; // noeuds des centaines contenant des noeuds de dizaines
      foreach ($layerTree as $no => $layer) {
        if (($no % $maxNbreLayersDisplayedByServer) == 0) {
  // Si node1 est déjà rempli je l'ajoute à node1 et j'en construis un nouveau
          if ($node1)
            $node2['children'][] = $node1;
          if (($no % ($maxNbreLayersDisplayedByServer * $maxNbreLayersDisplayedByServer)) == 0) {
            if ($node2)
              $newTree[] = $node2;
            $node2 = [
              'title' => "Couches $no-",
              'children' => [],
            ];
          }
          $node1 = [
            'title' => "Couches $no-",
            'children' => [],
          ];
        }
        $node1['children'][] = $layer;
      }
      $node2['children'][] = $node1;
      $newTree[] = $node2;
  //    echo "<li>newTree="; print_r($newTree);
      $layerTree = $newTree;
  // ***
    }
  // Si le server contient plus de 10 couches et moins de 100, on les décompose en 1 niveau
    elseif (count($layerTree) > $maxNbreLayersDisplayedByServer) {
      $newTree = [];
      $node1 = null;
      foreach ($layerTree as $no => $layer) {
        if (($no % $maxNbreLayersDisplayedByServer) == 0) {
          if ($node1)
            $newTree[] = $node1;
          $node1 = [
            'title' => "Couches $no-",
            'children' => [],
          ];
        }
        $node1['children'][] = $layer;
      }
      $newTree[] = $node1;
      $layerTree = $newTree;
    }
  }
  foreach ($layerTree as $layer)
    display_layer($serverId, $layer);
}

// Affiche l'arbre des couches
function display_tree($tree, &$servers, $level=0) {
//  echo "<pre>tree="; print_r($tree); echo "</pre>\n";
  foreach ($tree as $classId => $class) {
    echo "<li class='folder'>",htmlspecialchars($class['title'], ENT_COMPAT|ENT_HTML401,'UTF-8'),"<ul>\n";
    foreach ($servers as $id => $server)
      if ($server['class']==$classId) {
        echo "<li class='folder'>$server[title] ($server[protocol])<ul>\n";
        display_server($servers, $id);
        echo "</ul>\n";
        unset($servers[$id]);
      }
    if (isset($class['children']))
      display_tree($class['children'], $servers, $level+1);
    echo "</ul>\n";
  }
// Liste des serveurs qui n'ont pas été affichés précédemment
  if (!$level and $servers) {
    echo "<li class='folder'>Autres<ul>\n";
    foreach ($servers as $id => $server)
      echo "<li class='folder'>$server[title] ($server[protocol])\n";
    echo "</ul>\n";
  }
}

$yaml = file_get_servers('../');
display_tree($yaml['classification'], $yaml['servers']);

?>
    </ul>
  </ul>
  </div>
</body>
</html>
