<?php
/*PhpDoc:
name: reunion.php
title: reunion.php - téléchargement d'imagettes depuis un serveur WMTS, agrégation et visualisation
doc: |
  Réponse à la demande d'extraction des images satellites du Géoportail, extension au GIBS de la NASA
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
  24/11/2016
    ajout de BlueMarble
  23/11/2016
    nouvelle version; cette version répond à la demande
  17/11/2016
    première version
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
  'BlueMarble_ShadedRelief_Bathymetry' => [
    'urlwmts' => 'http://gibs.earthdata.nasa.gov/wmts/epsg3857/best/wmts.cgi?',
    'tilematrixSet' => 'GoogleMapsCompatible_Level8',
    'style' => 'default',
  ],
];

// définition des différents téléchargements
$dwnlddefs = [
// Image PO limitée à La Réunion
  'Reunion-ortho-z12' => [
    'title'=>"Réunion, ORTHOIMAGERY.ORTHOPHOTOS, zoom 12",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=>12,
    'colmin'=>2676, 'colmax' => 2683,
    'rowmin' => 2290, 'rowmax' => 2297,
  ],
// Image BDOrtho de La Réunion, résolution 20m
  'Reunion-ortho-z13' => [
    'title'=>"Réunion, ORTHOIMAGERY.ORTHOPHOTOS, zoom 13",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=>13,
    'colmin'=>5352, 'colmax' => 5367,
    'rowmin' => 4581, 'rowmax' => 4594,
  ],
// Image BDOrtho de La Réunion, résolution 10m
  'Reunion-ortho-z14' => [
    'title'=>"Réunion, ORTHOIMAGERY.ORTHOPHOTOS, zoom 14",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=>14,
    'colmin'=>10704, 'colmax' => 10735,
    'rowmin'=> 9162, 'rowmax' => 9189,
  ],
// Image PO globale Sud de l'Afrique/Océan Indien
  'global-ortho-z8' => [
    'title'=>"Globale, ORTHOIMAGERY.ORTHOPHOTOS, zoom 8",
    'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'zoom'=>8,
    'colmin'=> 136, 'colmax'=> 175,
    'rowmin'=> 131, 'rowmax'=> 154,
  ],
// Image BlueMarble globale Sud de l'Afrique/Océan Indien
  'global-BlueMarble-z8' => [
    'title'=>"Globale, BlueMarble_ShadedRelief_Bathymetry, zoom 8",
    'layer'=>'BlueMarble_ShadedRelief_Bathymetry',
    'zoom'=>8,
    'colmin'=> 136, 'colmax'=> 175,
    'rowmin'=> 131, 'rowmax'=> 154,
  ],
];
//$zoom = 12;
//$colmin = 2144; $colmax = 2156; $rowmin = 1504; $rowmax = 1530; // Corse

$htmlHeader = "<html><head><meta charset='UTF-8'><title>reunion.php</title></head><body>\n";
if (!isset($_GET['def'])) {
  echo "$htmlHeader<h2>Liste des définitions</h2><ul>\n";
  foreach ($dwnlddefs as $defid => $def)
    echo "<li><a href='?def=$defid'>$def[title]\n";
  die("</ul>\n");
}

$defid = $_GET['def'];
$def = $dwnlddefs[$defid];
switch (isset($_GET['action']) ? $_GET['action'] : null) {
  case null :
    die("$htmlHeader<h2>$def[title]</h2><ul>
<li><a href='?def=$defid&amp;action=dwnld'>télécharger</a>
<li><a href='?def=$defid&amp;action=view'>visualiser les imagettes</a>
<li><a href='?def=$defid&amp;action=agg'>agréger</a>
<li><a href='?def=$defid&amp;action=view_agg'>visualiser l'image agrégée</a>
<li><a href='$defid.jpg'>visualiser directement l'image agrégée</a>
</ul>\n");

  case 'dwnld' :
    echo $htmlHeader;
    $layer = $layerdefs[$def['layer']];
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
    } else
      $context = null;
    for ($col=$def['colmin']; $col<=$def['colmax']; $col++)
      for ($row=$def['rowmin']; $row<=$def['rowmax']; $row++) {
        $url = $layer['urlwmts'].'service=WMTS&version=1.0.0&request=GetTile'
              ."&layer=$def[layer]&tilematrixSet=$layer[tilematrixSet]&style=$layer[style]"
              ."&tilematrix=$def[zoom]&tilecol=$col&tilerow=$row"
              .'&height=256&width=256&format=image/jpeg';
        if ($img = @file_get_contents($url, false, $context)) {
          file_put_contents("img/$defid-$col-$row.jpg", $img);
          echo "image $col $row OK<br>\n";
        } else {
          echo "image $col $row KO $http_response_header[0]<br>\n";
        }
      }
    break;
    
  case 'view' :
    echo "$htmlHeader<table>";
    for ($row=$def['rowmin']; $row<=$def['rowmax']; $row++) {
      echo "<tr>";
      for ($col=$def['colmin']; $col<=$def['colmax']; $col++)
        echo "<td><img src='img/$defid-$col-$row.jpg'></td>";
      echo "</tr>\n";
    }
    echo "</table>\n";
    break;
    
  case 'agg' :
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
    break;
    
  case 'view_agg' :
    if (($image = @imagecreatefromjpeg("$defid.jpg"))===FALSE)
      throw new Exception("Erreur imagecreatefromjpeg ");
    header('Content-type: image/jpeg');
    if (!@imagejpeg($image))
      throw new Exception("Erreur imagejpeg");
    break;
}
  
