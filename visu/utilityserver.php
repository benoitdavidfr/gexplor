<?php
/*PhpDoc:
name: utilityserver.php
title: utilityserver.php - service de tuiles utilitaire
includes: [ ]
classes:
doc: |
  Serveur de tuiles exposant 4 couches:
  - 3 couches correspondant respectivement à un fond blanc, noir et gris clair
  - 1 couche de déverminage affichant quelques paramètres
  Appel de la forme:
    utilityserver.php pour les capacités du service en JSON
    utilityserver.php/{layer} pour les capacités de la couche en JSON
    utilityserver.php/{layer}/{z}/{x}/{y}.[jpg|png] pour l'image demandée dans le format demandé
  Tests:
    http://localhost/gexplor/visu/utilityserver.php
    http://localhost/gexplor/visu/utilityserver.php/whiteimg
    http://localhost/gexplor/visu/utilityserver.php/whiteimg/16/33200/22552.jpg
journal: |
  19/2/2017
    ajout du format png pour les images hors debug
    mise en cache 365 jours pour les images hors debug
  12/2/2017
    passage en JSON des capacités
  13/11/2016
    première version
*/
// Liste des couches exposées
$layers = [
  'whiteimg' => [
    'title' => "Fond blanc",
    'abstract' => "Affiche un fond blanc",
    'format' => 'image/jpeg',
    'minZoom' => 0,
    'maxZoom' => 21,
  ],
  'blackimg' => [
    'title' => "Fond noir",
    'abstract' => "Affiche un fond noir",
    'format' => 'image/jpeg',
    'minZoom' => 0,
    'maxZoom' => 21,
  ],
  'lightgreyimg' => [
    'title' => "Fond gris clair",
    'abstract' => "Affiche un fond gris clair",
    'format' => 'image/jpeg',
    'minZoom' => 0,
    'maxZoom' => 21,
  ],
  'debug' => [
    'title' => "Déverminage",
    'abstract' => "Affiche certaines caractéristiques techniques pour faciliter le déverminage.",
    'format' => 'image/png',
    'minZoom' => 0,
    'maxZoom' => 21,
  ],
];

function layerdoc($layer, $name) {
  return [
    'name'=> $name,
    'title'=> $layer['title'],
    'abstract'=> $layer['abstract'],
    'url'=> "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]/$name",
    'format'=> $layer['format'],
    'minZoom'=> $layer['minZoom'],
    'maxZoom'=> $layer['maxZoom'],
  ];
}

if (!isset($_SERVER['PATH_INFO'])) {
//  print_r($_SERVER);
  $doc = [
    'title'=> "Service de tuiles utilitaire",
    'abstract'=> "Service utilitaire sous la forme de tuiles conformément au standard defacto d'OpenStreetMap utilisé par Leaflet. Cette description adhoc fournit la liste des couches servies. Les appels doivent être de la forme utilityserver.php/{layer}/{z}/{x}/{y}.[png|jpg] où {layer} est un des noms de couche listés dans layers",
    'contact'=> 'contact@geoapi.fr',
    'doc_url'=> "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]",
    'api_version'=> '2017-02-19T18:00',
    'end_points'=> [
      'utilityserver.php' => ['GET'=> "documentation de l'API en JSON"],
      'utilityserver.php/{layer}' => ['GET'=> "documentation de la couche {layer} en JSON"],
      'utilityserver.php/{layer}/{z}/{x}/{y}.[png|jpg]' => ['GET'=> "image zoom {z} colonne {x} ligne {y} de la couche {layer} en format png ou en jpg"],
    ],
    'layers'=> [],
  ];
  foreach ($layers as $name => $layer)
    $doc['layers'][] = layerdoc($layer, $name);
  header("Content-Type: text/plain; charset=utf-8");
  die(json_encode($doc, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}

/*
function geoTol93($longitude, $latitude) {
// définition des constantes
  $c= 11754255.426096; //constante de la projection
  $e= 0.0818191910428158; //première exentricité de l'ellipsoïde
  $n= 0.725607765053267; //exposant de la projection
  $xs= 700000; //coordonnées en projection du pole
  $ys= 12655612.049876; //coordonnées en projection du pole

// pré-calculs
  $lat_rad= $latitude/180*PI(); //latitude en rad
  $lat_iso= atanh(sin($lat_rad))-$e*atanh($e*sin($lat_rad)); //latitude isométrique

//calcul
  $x= (($c*exp(-$n*($lat_iso)))*sin($n*($longitude-3)/180*PI())+$xs);
  $y= ($ys-($c*exp(-$n*($lat_iso)))*cos($n*($longitude-3)/180*PI()));
  return [$x,$y];
}
  
function wm2geo($X, $Y) {
  $a = 6378137.0; // Grand axe de l'ellipsoide IAG_GRS_1980 utilisée pour WGS84
  $phi = pi()/2 - 2*atan(exp(-$Y/$a)); // (7-4)
  $lambda = $X/$a; // (7-5)
  return [ $lambda / pi() * 180.0 , $phi / pi() * 180.0 ];
}
*/

try {
// Affiche la doc d'une layer en JSON
  if (preg_match('!^/([^/]+)$!', $_SERVER['PATH_INFO'], $matches)) {
    $lyrname = $matches[1];
    if (!isset($layers[$lyrname]))
      throw new Exception("Couche $lyrname inexistante");
    header("Content-Type: text/plain; charset=utf-8");
    die(json_encode(layerdoc($layers[$lyrname], $lyrname), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
  }
  
  if (!preg_match('!^/([^/]+)/(\d+)/(\d+)/(\d+)\.(png|jpg)$!', $_SERVER['PATH_INFO'], $matches))
    throw new Exception("paramètres '$_SERVER[PATH_INFO]' incorrects");

//  print_r($matches); die();
  $layer = $matches[1];
  if (!isset($layers[$layer]))
    throw new Exception("Couche $layer inexistante");
  $zoom = $matches[2];
  $ix = $matches[3];
  $iy = $matches[4];
  $fmt = $matches[5];

  switch($layer) {
    case 'whiteimg':
    case 'blackimg':
    case 'lightgreyimg':
      $nbDaysInCache = 365;
      header('Cache-Control: max-age='.($nbDaysInCache*24*60*60)); // mise en cache pour $nbDaysInCache jours
      header('Expires: '.date('r', time() + ($nbDaysInCache*24*60*60))); // mise en cache pour $nbDaysInCache jours
      header('Last-Modified: '.date('r'));
      if ($fmt=='jpg') {
        header('Content-type: image/jpeg');
        die(file_get_contents("http://visu.gexplor.fr/images/$layer.jpg"));
      } else {
        header('Content-type: image/png');
        $img = imagecreatefromjpeg("http://visu.gexplor.fr/images/$layer.jpg");
        imagepng($img);
        imagedestroy($img);
        die();
      }
      
    case 'debug':
      $base = 20037508.3427892476320267;
      $size0 = $base * 2;
      $x0 = - $base;
      $y0 =   $base;

      $size = $size0 / pow(2, $zoom);
      $bbox = [
        $x0 + $size * $ix,
        $y0 - $size * ($iy+1),
        $x0 + $size * ($ix+1),
        $y0 - $size * $iy,
      ];
  
// Création d'une image
      if (($im = @imagecreatetruecolor(256, 256))===FALSE)
        throw new Exception("Erreur imagecreatetruecolor");
// passage en blending FALSE pour copier un fond transparent
      if (!imagealphablending($im, FALSE))
        throw new Exception("erreur sur imagealphablending(FALSE)");
// création de la couleur transparente
      if (!($transparent = imagecolorallocatealpha($im, 0xFF, 0xFF, 0xFF, 0x7F)))
        throw new Exception("erreur sur imagecolorallocatealpha");
// remplissage de la tuile par la couleur transparente
      if (!imagefilledrectangle ($im, 0, 0, 255, 255, $transparent))
        throw new Exception("Erreur sur imagefilledrectangle");
// passage en blending TRUE pour copier normalement
      if (!imagealphablending($im, TRUE))
        throw new Exception("erreur sur imagealphablending(TRUE)");
// Affichage 1 tuile sur 4
      if (($ix+$iy) % 4 ==0) {
// couleur bleu un peu transparente pour le texte
        $color = imagecolorallocatealpha($im, 0, 0, 0xFF, 0x20);
// Dessin d'un cadre délimitant la tuile
        if (!imagerectangle ($im, 0, 0, 255, 255, $color))
          throw new Exception("Erreur sur imagerectangle");
// Dessin du texte
        imagestring($im, 5, 3,  0, "ix=$ix,iy=$iy,zoom=$zoom", $color);
        imagestring($im, 5, 3, 15, "size=".sprintf('%.0f',$size), $color);
        imagestring($im, 5, 3, 30, sprintf('xmin=%.0f, ymin=%.0f',$bbox[0],$bbox[1]), $color);
        imagestring($im, 5, 3, 45, sprintf('xmax=%.0f, ymax=%.0f',$bbox[2],$bbox[3]), $color);
/*
        $swwm = wm2geo($bbox[0],$bbox[1]);
        $swl93 = geoTol93($swwm[0], $swwm[1]);
        $newm = wm2geo($bbox[2],$bbox[3]);
        $nel93 = geoTol93($newm[0], $newm[1]);
        imagestring($im, 5, 3, 60, sprintf('xmin=%.0f, xmin=%.0f',$swl93[0],$swl93[1]), $color);
        imagestring($im, 5, 3, 75, sprintf('xmax=%.0f, xmax=%.0f',$nel93[0],$nel93[1]), $color);
        $dx = $nel93[0] - $swl93[0];
        $dy = $nel93[1] - $swl93[1];
        imagestring($im, 5, 3, 90, sprintf('dx=%.0f, dy=%.0f',$dx,$dy), $color);
        imagestring($im, 5, 3, 105, sprintf('denx=%.0f, deny=%.0f',$dx/256/0.00028,$dy/256/0.00028), $color);
*/
        $string = "HTTP_REFERER=$_SERVER[HTTP_REFERER]";
        $i = 0;
        while (strlen($string)) {
          imagestring($im, 5, 3,  15*$i+60, $string, $color);
          $string = substr($string, 28);
          $i++;
        }
      }
// Affichage de l'image
      if (!imagealphablending($im, FALSE))
        throw new Exception("erreur sur imagealphablending(FALSE)");
      if (!imagesavealpha($im, TRUE))
        throw new Exception("erreur sur imagesavealpha(TRUE)");
      header('Content-type: image/png');
      imagepng($im);
      imagedestroy($im);
      die();
  }

}
catch (Exception $e) {
  header("Content-Type: text/plain; charset=utf-8");
  die(json_encode(['error'=>$e->getMessage()],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}