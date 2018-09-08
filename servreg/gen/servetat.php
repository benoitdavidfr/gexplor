<?php
/*PhpDoc:
name:  servetat.php
title: servetat.php - génération de la liste des services de l'Etat pour le registre des serveurs
includes:
  - '../../../spyc/spyc2.inc.php'
  - '../../../georef/admin/regionmetro.yaml'
  - '../../../georef/admin/deptmetro.yaml'
  - '../../../georef/admin/dom.yaml'
  - '../../../georef/admin/com.yaml'
  - '../../../georef/admin/ddt.yaml'
  - '../../../georef/admin/dtam.yaml'
  - '../../../georef/admin/dreal.yaml'
  - '../../../georef/admin/deal.yaml'
  - '../../../georef/admin/dirm.yaml'
  - '../../../georef/admin/dmom.yaml'
  - '../../../georef/admin/dir.yaml'
  - '../../../georef/admin/minenv.yaml'
  - '../../../georef/admin/draaf.yaml'
  - '../../../georef/admin/daaf.yaml'
doc: |
  A REVOIR à la suite de restructuration du référentiel administratif
  Génération d'un fichier de classification pour:
  - les DDT(M), la DTAM, les D(r)eal, les D(r)aaf, les Dmom répartis par région de rattachement
  - les DIRM, les DIR et divers services directement rattachées au Ministère de l'environnement
  - divers services directement rattachées au Ministère de l'agriculture
  
  classification:
    FR:
      children:
        MinEnv:
          children:
            DIRM:
              title: Directions inter-régionales de la mer
              children:
                DirmNamo:
                  title: DIRM Nord-Atlantique - Manche Ouest
            DIR:
              title: Directions inter-départementales des routes
              children:
                DirEst:
                  title: DIR Est
    Regions:
      title: régionales
      abstract: couches diffusées par des acteurs régionaux
      children:
        {MnémoRégion}:
          title: Titre de la région
          children:
            {DrealMnémo}:
              title: Titre de la Dreal
            {DDTdd}:
              title: Titre de la DDT
      example:
        HautsDeFrance:
          title: Hauts-de-France
          children:
            DrealHautsDeFrance:
              title: DREAL Hauts-de-France
            DDT62:
              title: DDTM 62

journal: |
  25-26/3/2017:
    ajout de l'agriculture
  24/3/2017:
    première version
*/
require_once '../../../spyc/spyc2.inc.php';

header('Content-type: text/plain; charset="utf-8"');
if (!($decoupadm = SpycLoad('../../adminreg/decoupadm.yaml')))
  die("Erreur de lecture de decoupadm.yaml");
if (!($ddts = SpycLoad('../../adminreg/ddt.yaml')))
  die("Erreur de lecture de ddt.yaml");
if (!($minenv = SpycLoad('../../adminreg/minenv.yaml')))
  die("Erreur de lecture de minenv.yaml");
if (!($minagri = SpycLoad('../../adminreg/minagri.yaml')))
  die("Erreur de lecture de minagri.yaml");

$result = [
  'FR'=>[
    'title'=> "nationales (FR)",
    'abstract'=> "couches diffusées par des acteurs français nationaux",
    'children'=>[
      'MinEnv'=>[
        'title'=> "Ministère chargé de l'environnement",
        'children'=>[
          'DIRM'=>['title'=> "Directions inter-régionales de la mer"],
          'DIR'=>['title'=> "Directions inter-départementales des routes"],
          'divers'=>['title'=> "Services divers"],
        ],
      ],
      'MinAgri'=>[
        'title'=> "Ministère chargé de l'agriulture",
        'children'=>[
          'divers'=>['title'=> "Services divers"],
        ],
      ],
    ],
  ],
  'Regions'=>[
    'title'=> "régionales",
    'abstract'=> "couches diffusées par des acteurs régionaux",
    'children'=>[ ],
  ],
];

// Génération des DIRM et des DIR rattaché au Min env
foreach ($minenv['DIRM'] as $dirmid => $dirm)
  $result['FR']['children']['MinEnv']['children']['DIRM']['children'][$dirmid]['title'] = $dirm['title'];
foreach ($minenv['DIR'] as $dirid => $dir)
  $result['FR']['children']['MinEnv']['children']['DIR']['children'][$dirid]['title'] = $dir['title'];

// génération des services divers du Min. Env.
foreach ($minenv['divers'] as $diversid => $divers)
  $result['FR']['children']['MinEnv']['children']['divers']['children'][$diversid]['title'] = $divers['title'];

// Génération des régions et des DREAL et DRAAF
foreach ($decoupadm['regionmetro'] as $idregion => $regionmetro) {
  $mnemo = $regionmetro['mnémo'];
  $result['Regions']['children'][$mnemo]['title'] = $regionmetro['title'];
  if ($mnemo=='IleDeFrance') {
    $result['Regions']['children']['IleDeFrance']['children'] = [
      'DrieaIleDeFrance'=>[
        'title' => $minenv['DREAL']['DrieaIleDeFrance']['title'],
        'children'=>[],
      ],
      'DrieeIleDeFrance'=>['title' => $minenv['DREAL']['DrieeIleDeFrance']['title']],
      'DriaafIleDeFrance'=>['title' => $minagri['DRAAF']['DriaafIleDeFrance']['title']],
    ];
    foreach ($minenv['DREAL']['DrieaIleDeFrance']['children'] as $idut => $ut)
      $result['Regions']['children']['IleDeFrance']['children']['DrieaIleDeFrance']['children'][$idut]['title'] = $ut['title'];
  } else {
    $result['Regions']['children'][$mnemo]['children']["Dreal$mnemo"]['title'] = $minenv['DREAL']["Dreal$mnemo"]['title'];
    $result['Regions']['children'][$mnemo]['children']["Draaf$mnemo"]['title'] = $minagri['DRAAF']["Draaf$mnemo"]['title'];
  }
}

// Rattachement des DDT(M) à leur région
foreach ($decoupadm['deptmetro'] as $deptcode => $deptmetro)
  if (isset($ddts['DDT(M)'][$deptcode])) {
    $regmnemo = $decoupadm['regionmetro'][$deptmetro['région']]['mnémo'];
    $titleDttDd = $ddts['DDT(M)'][$deptcode]['title'];
    $title = substr($titleDttDd, 0, strlen($titleDttDd)-2).$deptmetro['title']." ($deptcode)";
    $result['Regions']['children'][$regmnemo]['children']["DDT$deptcode"] = ['title'=>$title];
  }
  
// génération des DOM et de leurs Deal et Daaf
foreach ($decoupadm['DOM'] as $iddom => $dom) {
  $mnemo = $dom['mnémo'];
  $result['Regions']['children'][$mnemo]['title'] = $dom['title'];
  $result['Regions']['children'][$mnemo]['children']["Deal$mnemo"]['title'] = $minenv['DEAL']["Deal$mnemo"]['title'];
  $result['Regions']['children'][$mnemo]['children']["Daaf$mnemo"]['title'] = $minagri['DAAF']["Daaf$mnemo"]['title'];
}

// Rattachement de chaque DMOM au DOM de son premier ressort
foreach ($minenv['DMOM'] as $iddmom => $dmom) {
  $dom = $dmom['ressort'][0];
  $mnemo = $decoupadm['DOM'][$dom]['mnémo'];
  $result['Regions']['children'][$mnemo]['children'][$iddmom]['title'] = $dmom['title'];
}

// Génération StP&M + DTAM
foreach ($ddts['DTAM'] as $idtom => $dtam) {
  $mnemo = $decoupadm['TOM'][$idtom]['mnémo'];
  $result['Regions']['children'][$mnemo]['title'] = $decoupadm['TOM'][$idtom]['title'];
  $result['Regions']['children'][$mnemo]['children']["Dtam$mnemo"]['title'] = $dtam['title'];
}
  
die(
  Spyc::YAMLDump(
    ['classification'=> $result],
    /*$indent=*/false, /*$wordwrap=*/130, /*$no_opening_dashes=*/true));
