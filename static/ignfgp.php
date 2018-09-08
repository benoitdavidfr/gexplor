<?php
/*PhpDoc:
name: ignfgp.php
title: ignfgp.php - carte des ressources du GP IGNF
doc: |
  essai d'un code Php auto-porteur avec une définition simple des ressources
journal: |
  29_30/5/2017:
    création
*/
$servers = [
  'WMTS'=>[
    'protocol'=>'WMTS',
    'url'=>'https://wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/geoportail/wmts?'
          .'service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&'
          .'tilematrix={z}&tilecol={x}&tilerow={y}',
    'format'=>'image/jpeg',
    'style'=>'normal',
    'detectRetina'=>false,
  ],
  'WMS'=>[
    'protocol'=>'WMS',
    'url'=>'https://wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/geoportail/r/wms?',
    'format'=>'image/jpeg',
    'detectRetina'=>false,
  ],
];

$baseLayers = [
  "Ortho-images"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS',
    'minZoom'=>0, 'maxZoom'=>20, 'attribution'=>'IGN',
  ],
  "Ortho-images HR"=>[
    'server'=>'WMS', 'layer'=>"HR.ORTHOIMAGERY.ORTHOPHOTOS",
    'format'=>'image/png', 'attribution'=>'IGN',
  ],
  "Ortho-express"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS.ORTHO-EXPRESS',
    'minZoom'=>0, 'maxZoom'=>19, 'attribution'=>'IGN',
  ],
  "Ortho-images 2006-2010"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS2006-2010',
    'minZoom'=>0, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Ortho-images 2000-2005"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS2000-2005',
    'minZoom'=>0, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Ortho-images 1950-1965"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS.1950-1965',
    'format'=>'image/png', 'minZoom'=>0, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "SPOT 6-7 - 2014"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHO-SAT.SPOT.2014',
    'minZoom'=>0, 'maxZoom'=>17, 'attribution'=>'CNES,IGN,GEOSUD',
  ],
  "SPOT 6-7 - 2015"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHO-SAT.SPOT.2015',
    'minZoom'=>0, 'maxZoom'=>17, 'attribution'=>'CIRAD,CNES,CNRS,IGN,IRD,Irstea,GEOSUD',
  ],
  "SPOT 6-7 - 2016"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHO-SAT.SPOT.2016',
    'minZoom'=>0, 'maxZoom'=>17, 'attribution'=>'CIRAD,CNES,CNRS,IGN,IRD,Irstea,GEOSUD',
  ],
  "Altitude"=>[
    'server'=>'WMTS', 'layer'=>'ELEVATION.SLOPES',
    'minZoom'=>6, 'maxZoom'=>14, 'attribution'=>'IGN',
  ],
  "Cartes IGN Express"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.STANDARD',
    'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Cartes IGN classiques"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALGRIDSYSTEMS.MAPS',
    'minZoom'=>0, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Cartes IGN Express N&amp;B"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.NIVEAUXGRIS',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Plan IGN"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALGRIDSYSTEMS.PLANIGN',
    'minZoom'=>0, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Carte littorale"=>[
    'server'=>'WMS', 'layer'=>"SCANLITTO_PYR-JPEG_WLD_WM",
    'format'=>'image/png', 'attribution'=>'IGN,Shom',
  ],
  "Cartes 1:50.000 de 1950"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN50.1950',
    'minZoom'=>3, 'maxZoom'=>15, 'attribution'=>'IGN',
  ],
  "Carte de l'état-major (1820-1866)"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALGRIDSYSTEMS.ETATMAJOR40',
    'minZoom'=>6, 'maxZoom'=>15, 'attribution'=>'IGN',
  ],
  "Carte OACI-VFR 2017"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-OACI',
    'minZoom'=>6, 'maxZoom'=>11, 'attribution'=>'DGAC/SIA',
  ],
];
$defaultLayer = "Cartes IGN Express";
$overlays = [
  "PLEIADES - 2013"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHO-SAT.PLEIADES.2013',
    'format'=>'image/png', 'minZoom'=>0, 'maxZoom'=>18,
  ],
  "PLEIADES - 2014"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHO-SAT.PLEIADES.2014',
    'format'=>'image/png', 'minZoom'=>0, 'maxZoom'=>18,
  ],
  "PLEIADES - 2015"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHO-SAT.PLEIADES.2015',
    'format'=>'image/png', 'minZoom'=>0, 'maxZoom'=>18,
  ],
  "PLEIADES - 2016"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHO-SAT.PLEIADES.2016',
    'format'=>'image/png', 'minZoom'=>0, 'maxZoom'=>18,
  ],
  "Ortholittorale 2000"=>[
    'server'=>'WMTS', 'layer'=>'ORTHOIMAGERY.ORTHOPHOTOS.COAST2000',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18,
  ],
  "OCSGE - Couverture"=>[
    'server'=>'WMTS', 'layer'=>'OCSGE.COUVERTURE',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>16, 'attribution'=>'IGN',
  ],
  "OCSGE - Usage"=>[
    'server'=>'WMTS', 'layer'=>'OCSGE.USAGE',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>16, 'attribution'=>'IGN',
  ],
  "OCSGE - Constructions"=>[
    'server'=>'WMTS', 'layer'=>'OCSGE.CONSTRUCTIONS',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>16, 'attribution'=>'IGN',
  ],
  "Aéroports"=>[
    'server'=>'WMTS', 'layer'=>'TRANSPORTNETWORKS.RUNWAYS',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Hydrographie"=>[
    'server'=>'WMTS', 'layer'=>'HYDROGRAPHY.HYDROGRAPHY',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Trait de côte Histolitt"=>[
    'server'=>'WMTS', 'layer'=>'ELEVATION.LEVEL0',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Réseau ferroviaire"=>[
    'server'=>'WMTS', 'layer'=>'TRANSPORTNETWORKS.RAILWAYS',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Bâtiments"=>[
    'server'=>'WMTS', 'layer'=>'BUILDINGS.BUILDINGS',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Routes"=>[
    'server'=>'WMTS', 'layer'=>'TRANSPORTNETWORKS.ROADS',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Lignes électriques"=>[
    'server'=>'WMTS', 'layer'=>'UTILITYANDGOVERNMENTALSERVICES.ALL',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Limites administratives"=>[
    'server'=>'WMTS', 'layer'=>'ADMINISTRATIVEUNITS.BOUNDARIES',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
  "Parcelles cadastrales (orange)"=>[
    'server'=>'WMTS', 'layer'=>'CADASTRALPARCELS.PARCELS', 'style'=>'bdparcellaire_o',
    'format'=>'image/png', 'minZoom'=>0, 'maxZoom'=>20, 'attribution'=>'Cadastre,IGN',
  ],
  "Dénominations géographiques"=>[
    'server'=>'WMTS', 'layer'=>'GEOGRAPHICALNAMES.NAMES',
    'format'=>'image/png', 'minZoom'=>6, 'maxZoom'=>18, 'attribution'=>'IGN',
  ],
];

// fabrique une chaine avec un href en fonction des sigles
function attributions($short) {
  $urls = [
    'IGN'=> 'http://www.ign.fr',
    'Shom'=>'http://data.shom.fr',
    'Cadastre'=>'http://cadastre.gouv.fr',
    'CNES'=>'http://www.cnes.fr',
    'GEOSUD'=>'http://www.http://ids.equipex-geosud.fr',
//    'CIRAD,CNES,CNRS,IGN,IRD,Irstea,GEOSUD'=>'CIRAD,CNES,CNRS,IGN,IRD,Irstea,GEOSUD',
//    'DGAC/SIA'=>'DGAC/SIA',
  ];
  $attrs = [];
  foreach (explode(',',$short) as $sigle)
    if (isset($urls[$sigle]))
      $attrs[] = "<a href='".$urls[$sigle]."' target='_blank'>$sigle</a>";
    else
      $attrs[] = $sigle;
  return '&copy; '.implode(',',$attrs);
}

function show_script($layers, $servers) {
  foreach($layers as $title=>$layer) {
    $server = $servers[$layer['server']];
    if ($server['protocol']=='WMTS') {
      $params = [
        'format'=>(isset($layer['format']) ? $layer['format'] : $server['format']),
        'minZoom'=>$layer['minZoom'],
        'maxZoom'=>$layer['maxZoom'],
        'detectRetina'=>$server['detectRetina'],
      ];
      if (isset($layer['attribution']))
        $params['attribution'] = attributions($layer['attribution']);
      echo "  \"$title\" : new L.TileLayer(\n",
           "    '",$server['url'],
           "&layer=",$layer['layer'],
           "&format=",(isset($layer['format']) ? $layer['format'] : $server['format']),
           "&style=",(isset($layer['style']) ? $layer['style'] : $server['style']),
           "',\n",
           '    ',json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"\n",
           "  ),\n";
    } else { // protocol == 'WMS'
      $format = (isset($layer['format']) ? $layer['format'] : $server['format']);
      $params = [
        'version'=>'1.3.0',
        'layers'=>$layer['layer'],
        'format'=>$format,
        'transparent'=>($format=='image/png' ? true : false),
        'detectRetina'=>$server['detectRetina'],
      ];
      if (isset($layer['attribution']))
        $params['attribution'] = attributions($layer['attribution']);
      echo "  \"$title\" : new L.tileLayer.wms(\n",
           "    '",$server['url'],"',\n",
           '    ',json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),"\n",
           "  ),\n";
    }
  }
}
?>
<html>
  <head>
    <title>carte IGN du GP</title>
    <meta charset="UTF-8">
<!-- meta nécessaire pour le mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<!-- styles nécessaires pour le mobile -->
    <link rel="stylesheet" href="https://visu.gexplor.fr/viewer.css">
<!-- styles et src de Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.0/dist/leaflet.js"></script>
<!-- Include the edgebuffer plugin -->
    <script src="https://visu.gexplor.fr/lib/leaflet.edgebuffer.js"></script>
  </head>
  <body>
    <div id="map" style="height: 100%; width: 100%"></div>
    <script>
var map = L.map('map').setView([48, 3], 8); // view pour la zone
L.control.scale({position:'bottomleft', metric:true, imperial:false}).addTo(map);
var baseLayers = {
<?php show_script($baseLayers, $servers);?>
};
map.addLayer(baseLayers["<?php echo $defaultLayer;?>"]);
var overlays = {
<?php show_script($overlays, $servers);?>
};
L.control.layers(baseLayers, overlays).addTo(map);
  </script>