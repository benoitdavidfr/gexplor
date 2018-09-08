<?php
/*PhpDoc:
name: loire.php
title: loire.php - téléchargement d'imagettes depuis un serveur WMTS, agrégation et visualisation
doc: |
  Réponse à la demande d'extraction des images satellites du Géoportail,
  Différents téléchargements sont définis dans $dwnlddefs qui référence $layerdefs
  Chaque téléchargement correspondant à un ensemble d'imagettes qui seront agrégées en une image résultante
  Ce script:
  - par défaut liste les différents téléchargements définis et permet d'en sélectionner un
  - il permet ensuite sur cette définition de téléchargement:
    - de télécharger les imagettes
    - de consulter les imagettes sous la forme d'une table (uniquement si leur nombre est limité)
    - d'agréger les imagettes téléchargées en une image agrégée
    - d'afficher l'image agrégée
journal: |
  8/9/2018
    première version par fork de La réunion
*/
// Si la définition du proxy est nécessaire
// $proxy = 'tcp://proxy-rie.ac.i2:8080';

// Définition des différentes couches dont sont issues les images
$layerdefs = [
  'ORTHOIMAGERY.ORTHOPHOTOS' => [
    'urlwmts' => 'http://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?',
    'referer' => 'http://visu.gexplor.fr/',
    'tilematrixSet' => 'PM',
    'style' => 'normal',
  ],
  'GEOGRAPHICALGRIDSYSTEMS.MAPS' => [
    'urlwmts' => 'http://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?',
    'referer' => 'http://visu.gexplor.fr/',
    'tilematrixSet' => 'PM',
    'style' => 'normal',
  ],
  'GEOGRAPHICALGRIDSYSTEMS.PLANIGN' => [
    'urlwmts' => 'http://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?',
    'referer' => 'http://visu.gexplor.fr/',
    'tilematrixSet' => 'PM',
    'style' => 'normal',
  ],
];

// définition des différents téléchargements
$dwnlddefs = [
  // Image PO limitée à La métropole
  'Metro-ortho-z7' => [
    'title'=>"Metro, ortho, zoom 7",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=> 7,
    'colmin'=> 62, 'colmax' => 67,
    'rowmin' => 42, 'rowmax' => 47,
  ],
  // Image PO limitée à La métropole
  'Metro-ortho-z8' => [
    'title'=>"Metro, ortho, zoom 8",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=> 8,
    'colmin'=> 124, 'colmax'=> 134,
    'rowmin'=>  85, 'rowmax'=>  95,
  ],
  'Loire-carte-z10' => [
    'title'=>"Loire, carte, zoom 10",
    'layer'=>'GEOGRAPHICALGRIDSYSTEMS.MAPS',
    'zoom'=> 10,
    'colmin'=> 521, 'colmax'=> 526,
    'rowmin'=> 362, 'rowmax'=> 367,
  ],
  'Loire-ortho-z10' => [
    'title'=>"Loire, ortho, zoom 10",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=> 10,
    'colmin'=> 521, 'colmax'=> 526,
    'rowmin'=> 362, 'rowmax'=> 367,
  ],
  'Loire-ortho-z11' => [
    'title'=>"Loire, ortho, zoom 11",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=> 11,
    'colmin'=> 1042, 'colmax'=> 1053,
    'rowmin'=>  724, 'rowmax'=>  735,
  ],
  'Loire-plan-z11' => [
    'title'=>"Loire, plan, zoom 11",
    'layer'=>'GEOGRAPHICALGRIDSYSTEMS.PLANIGN',
    'zoom'=> 11,
    'colmin'=> 1042, 'colmax'=> 1053,
    'rowmin'=>  724, 'rowmax'=>  735,
  ],
];

$htmlHeader = "<html><head><meta charset='UTF-8'><title>loire</title></head><body>\n";
if (!isset($_GET['def'])) {
  echo "$htmlHeader<h2>Liste des définitions</h2><ul>\n";
  foreach ($dwnlddefs as $defid => $def)
    echo "<li><a href='?def=$defid'>$def[title]\n";
  die("</ul>\n");
}

$defid = $_GET['def'];
$def = $dwnlddefs[$defid];

if (!isset($_GET['action'])) {
  die("$htmlHeader<h2>$def[title]</h2><ul>
<li><a href='?def=$defid&amp;action=dwnld'>télécharger</a>
<li><a href='?def=$defid&amp;action=view'>visualiser les imagettes</a>
<li><a href='?def=$defid&amp;action=agg'>agréger</a>
<li><a href='?def=$defid&amp;action=view_agg'>visualiser l'image agrégée</a>
<li><a href='$defid.jpg'>visualiser directement l'image agrégée</a>
</ul>\n");
}

if ($_GET['action']=='dwnld') {
  echo $htmlHeader;
  $layer = $layerdefs[$def['layer']];
  $context = null;
  if (isset($proxy) or isset($layer['referer'])) {
    $http_context_options = ['method'=>"GET"];
    if (isset($layer['referer']))
      $http_context_options['header'] = "Accept-language: en\r\n"
                                       ."referer: $layer[referer]\r\n";
    if (isset($proxy)) {
      $http_context_options['proxy'] = $proxy;
      $http_context_options['request_fulluri'] = True;
    }
    $context = stream_context_create(['http'=>$http_context_options]);
  }
  for ($col=$def['colmin']; $col<=$def['colmax']; $col++) {
    for ($row=$def['rowmin']; $row<=$def['rowmax']; $row++) {
      $filename = "img/$defid-$col-$row.jpg";
      if (is_file($filename))
        continue;
      $url = $layer['urlwmts'].'service=WMTS&version=1.0.0&request=GetTile'
            ."&layer=$def[layer]&tilematrixSet=$layer[tilematrixSet]&style=$layer[style]"
            ."&tilematrix=$def[zoom]&tilecol=$col&tilerow=$row"
            .'&height=256&width=256&format=image/jpeg';
      if ($img = @file_get_contents($url, false, $context)) {
        file_put_contents($filename, $img);
        echo "image $col $row OK<br>\n";
      } else {
        echo "image $col $row KO $http_response_header[0]<br>\n";
      }
    }
  }
  die();
}

if ($_GET['action']=='view') {
  echo "$htmlHeader<table>";
  for ($row=$def['rowmin']; $row<=$def['rowmax']; $row++) {
    echo "<tr>";
    for ($col=$def['colmin']; $col<=$def['colmax']; $col++)
      echo "<td><img src='img/$defid-$col-$row.jpg'></td>";
    echo "</tr>\n";
  }
  echo "</table>\n";
  die();
}

if ($_GET['action']=='agg') {
  $width = ($def['colmax']-$def['colmin']+1)*256;
  $height = ($def['rowmax']-$def['rowmin']+1)*256;
  if (($image = @imagecreatetruecolor($width, $height))===FALSE)
    throw new Exception("Erreur imagecreatetruecolor");
  for ($col=$def['colmin']; $col<=$def['colmax']; $col++)
    for ($row=$def['rowmin']; $row<=$def['rowmax']; $row++) {
      if (($image2 = @imagecreatefromjpeg("img/$defid-$col-$row.jpg"))===FALSE)
        throw new Exception("Erreur imagecreatefromjpeg ");
      if (0 and ($col==2678) and ($row==2292)) {
          header('Content-type: image/jpeg');
          if (!@imagejpeg($image2))
            throw new Exception("Erreur imagejpeg");
          die();
      }
      if (!imagecopy($image, $image2, ($col-$def['colmin'])*256, ($row-$def['rowmin'])*256, 0, 0, 256, 256))
        throw new Exception("Erreur sur imagecopy");
      if (!imagedestroy($image2))
        throw new Exception("Erreur sur imagedestroy");
    }
  if (!@imagejpeg($image, "$defid.jpg"))
    throw new Exception("Erreur imagejpeg");
  header('Content-type: image/jpeg');
  if (!@imagejpeg($image))
    throw new Exception("Erreur imagejpeg");
  die();
}

if ($_GET['action']=='view_agg') {
  if (($image = @imagecreatefromjpeg("$defid.jpg"))===FALSE)
    throw new Exception("Erreur imagecreatefromjpeg ");
  header('Content-type: image/jpeg');
  if (!@imagejpeg($image))
    throw new Exception("Erreur imagejpeg");
  die();
}
    
die("Aucune action");  
