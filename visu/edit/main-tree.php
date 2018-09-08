<?php
/*PhpDoc:
name:  main-tree.php
title: main-tree.php - génère l'arbre principal pour le menu FancyTree
includes: [ '../newserver.inc.php' ]
hrefs: [ edit.php, '../doc.php' ]
functions:
doc: |
  Génère l'arbre en JSON conformément aux specs de FancyTree
journal: |
  21/11/2016:
    première version
*/
require_once '../newserver.inc.php';

/*PhpDoc: functions
name:  actions
title: function actions() - génère l'arbre des opérations
doc: |
  Génère un tableau Php qui traduit en JSON sera conforme aux specs de FancyTree
*/
function actions() {
  return [
    'title'=>"Opérations", 'folder'=>true, 'children'=> [
      ['title'=>"Enregistrer la carte", 'href'=>"edit.php?action=enregistrer", 'target'=> "content"],
      ['title'=>"Réinitialiser la carte", 'href'=>"edit.php", 'target'=> "content"],
      ['title'=>"Afficher le code source de la carte", 'href'=>"edit.php?action=dump", 'target'=> "content"],
      ['title'=>"Afficher la carte", 'href'=>"edit.php?action=none", 'target'=> "content"],
//      ['title'=>"Afficher les capacités", 'href'=>"edit.php?action=capabilities", 'target'=> "content"],
      ['title'=>"Documentation", 'href'=>"../doc.php", 'target'=> "content"],
      ['title'=>"Accueil de visu", 'href'=>"../index.php", 'target'=> "_blank"],
    ]];
}

/*PhpDoc: functions
name:  viewpoints
title: function viewpoints() - génère l'arbre des points de vue
doc: |
  Génère un tableau Php qui traduit en JSON sera conforme aux specs de FancyTree
*/
function viewpoints() {
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
  $json = [];
  foreach ($viewpoints as $viewpoint)
    if (isset($viewpoint['children'])) {
      $children = [];
      foreach ($viewpoint['children'] as $child)
        $children[] = ['title'=>$child['title'], 'href'=>'edit.php?view='.urlencode($child['view']), 'target'=>'content'];
      $json[] = ['title'=>$viewpoint['title'], 'folder'=>'true', 'children'=>$children];
    } else
  //    echo "<li><a target='content' href='edit.php?view=",urlencode($viewpoint['view']),"'>$viewpoint[title]</a>";
  //    echo '{"title": "Enregistrer la carte", "href": "edit.php?action=enregistrer", "target": "content"},'
      $json[] = ['title'=>$viewpoint['title'], 'href'=>'edit.php?view='.urlencode($viewpoint['view']), 'target'=>'content'];
  return ['title'=> "Points de vue", 'folder'=> true, 'children'=> $json];
}

/*PhpDoc: functions
name:  servers
title: function servers($classification, &$servers, $level=0) - génère l'arbre des serveurs de couches
doc: |
  Génère un tableau Php qui traduit en JSON sera conforme aux specs de FancyTree
*/
function servers($classification, &$servers, $level=0) {
//  echo "servers: classification="; print_r($classification);
  $json = [];
  foreach ($classification as $classId => $class) {
    $children = [];
    foreach ($servers as $serverId => $server)
      if ($server['class']==$classId) {
        $children[] = ['title'=>"$server[title] ($server[protocol])",
                       'folder'=>true, 'lazy'=> true, 'data'=> ['server'=> "$serverId"]
                      ];
        unset($servers[$serverId]);
      }
    if (isset($class['children']) and $class['children'])
      $children = array_merge($children, servers($class['children'], $servers, $level+1));
    if (isset($class['layers']) and $class['layers'])
      foreach ($class['layers'] as $layer)
        $children[] = [
            'title'=>$layer['title'],
            'href'=>"edit.php?server=$layer[server]&layer=$layer[layer]",
            'target'=>'content'
        ];
    $json[] = ['title'=>$class['title'], 'folder'=>true, 'children'=>$children];
  }
  
// Liste des serveurs qui n'ont pas été affichés précédemment
  if (!$level and $servers) {
    $children = [];
    foreach ($servers as $serverId => $server)
      $children[] = ['title'=>"$server[title] ($server[protocol])",
                     'folder'=>true, 'lazy'=> true, 'data'=> ['server'=> "$serverId"]
                    ];
    $json[] = ['title'=>"Autres", 'folder'=>true, 'children'=>$children];
  }
  return $json;
}

header('Content-type: text/xml; charset="utf8"');
$yaml = file_get_servers('../');
$json = [
  ['title'=>"Outils", 'folder'=>true, 'children'=> [
    actions(),
    viewpoints(),
  ]],
  ['title'=>"Couches", 'folder'=>true, 'children'=>servers($yaml['classification'], $yaml['servers'])]
];
echo json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);