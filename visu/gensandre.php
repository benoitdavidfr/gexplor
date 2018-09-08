<?php
/*PhpDoc:
name:  gensandre.php
title: gensandre.php - génération du catalogue des services du Sandre
doc: |
journal: |
  1/11/2016:
    reprise du résultat à la main - ce script est donc périmé
  30/10/2016:
    première version
*/
header('Content-Type: text/plain; charset=UTF-8');
$codes = [
  'com_FRA' => "Référentiel administratif France entière",
  'eth' => "Référentiel hydrographique",
  'hyd' => "Hydrométrie",
  'mdo' => "Référentiel des Masses d'eau",
  'mdo_VEDL' => "Référentiel des Masses d'eau - Version 2013 Interne (Etat des lieux 2013)",
  'obs' => "Référentiel des Obstacles à l'Ecoulement",
  'odp_FRA' => "Référentiel Ouvrages de dépollution France entière",
  'saq' => "Référentiel Hydrogéologique National",
  'sel' => "Lieux de surveillance",
  'stq' => "Stations de mesure de la qualité des eaux de surface",
  'vic' => "Vigilance Crues",
  'zon' => "Zonages",
];
foreach ([
'com_FRA',
'eth_FXX',
'eth_GLP',
'eth_GUF',
'eth_MTQ',
'eth_MYT',
'eth_REU',
'hyd_FXX',
'hyd_MTQ',
'mdo_FRA',
'mdo_FXX',
'mdo_GLP',
'mdo_GUF',
'mdo_MTQ',
'mdo_REU',
'mdo_VEDL_FXX',
'mdo_VEDL_GLP',
'mdo_VEDL_GUF',
'mdo_VEDL_MTQ',
'mdo_VEDL_MYT',
'mdo_VEDL_REU',
'obs_FXX',
'obs_GLP',
'obs_MTQ',
'obs_REU',
'odp_FRA',
'saq_FXX',
'sel_FXX',
'sel_GLP',
'sel_MTQ',
'sel_MYT',
'sel_REU',
'stq_FXX',
'stq_GLP',
'stq_GUF',
'stq_MTQ',
'stq_MYT',
'stq_REU',
'vic_FXX',
'zon_FXX',
'zon_GLP',
'zon_MTQ',
'zon_REU',
] as $code) {
  preg_match('!^(.*)_(...)$!', $code, $matches);
//  print_r($matches);
  if (isset($codes[$code]))
    $title = $codes[$code];
  elseif (isset($codes[$matches[1]]))
    $title = $codes[$matches[1]]." ($matches[2])";
  else
    $title = $matches[1]." ($matches[2])";
  echo "  Sandre-$code:
    title: $title
    class: Sandre
    url: http://services.sandre.eaufrance.fr/geo/$code?
    protocol: WMS
";
}