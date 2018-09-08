<?php
/*PhpDoc:
name:  pigma.php
title: pigma.php - génère les serveurs Pigma
doc: |
journal: |
  21/11/2016:
    première version
*/
$wmsServers = [
# Organismes supra régionaux
  'ign_r' => "IGN-R",
  'medde' => "MEDDE",
  'erdf' => "ERDF",
  'rte' => "RTE",
# Services régionaux de l'Etat
  'dira' => "DIRA",
  'draaf' => "DRAAF",
  'drac' => "DRAC",
  'drjscs' => "DRJSCS",
  'rect' => "Rectorat",
  'ars' => "ARS",
# Autres organismes régionaux
  'craquitaine' => "CR Aquitaine",
  'crt' => "CR Tourisme",
  'crpf' => "CRPF",
  'pnrlg' => "PNR LG",
  'giplit' => "GIP Littoral",
  'tigf' => "Transport Infrastructures Gaz France",
  'aurba' => "Agence d'urbanisme de la métropole bordelaise et de l'Aquitaine",
  'audap' => "Agence d'Urbanisme Atlantique et Pyrénées",
  'agrn' => "Atelier de Géographie Rurale Numérique",
  'apem' => "apem",
  'airaq' => "airaq",
  'sdis' => "sdis",
# Services départementaux de l'Etat
  'ddtm33' => "DDTM 33",
  'ddtm40' => "DDTM 40",
# Autres organismes départementaux
  'ca24' => "ca24",
  'ce24' => "ce24",
  'cg33' => "Conseil départemental 33",
  'girnum' => "girnum",
  'chag33' => "Chambre d'agriculture 33",
  'fdc33' => "fdc33",
  'fdp33' => "fdp33",
  'caue33' => "caue33",
  'cg40' => "Conseil départemental 40",
  'adacl' => "Agence Départementale d'Aide aux Collectivités Locales (ADACL)",
  'cg47' => "Conseil départemental 47",
  'sdee47' => "sdee47",
  'cg64' => "Conseil départemental 64",
  'chag64' => "Chambre d'agriculture 64",
# Organismes sub départementaux
  'gpmb' => "Grand Port Maritime de Bordeaux",
/*
http://ids.pigma.org/geoserver/sysdau/wms
http://ids.pigma.org/geoserver/smeag/wms
http://ids.pigma.org/geoserver/smgeolandes/wms
http://ids.pigma.org/geoserver/ca_cote_basque_adour/wms
http://ids.pigma.org/geoserver/cenaq/wms
http://ids.pigma.org/geoserver/cdc_val_albret/wms
http://ids.pigma.org/geoserver/cdcmtq/wms
http://ids.pigma.org/geoserver/cobas/wms
http://ids.pigma.org/geoserver/cdc_bourg_en_gironde/wms
http://ids.pigma.org/geoserver/cdcpiemontoloronais/wms
http://ids.pigma.org/geoserver/siba/wms
http://ids.pigma.org/geoserver/smavlot/wms
http://ids.pigma.org/geoserver/siaep_lalinde/wms
http://ids.pigma.org/geoserver/epidropt/wms
http://ids.pigma.org/geoserver/ausonius/wms
http://ids.pigma.org/geoserver/cdc_grands_lacs/wms
http://ids.pigma.org/geoserver/cdcsudpaysbasque/wms
http://ids.pigma.org/geoserver/smeap/wms
http://ids.pigma.org/geoserver/cdc_penne_agenais/wms
http://ids.pigma.org/geoserver/c_mezin/wms
http://ids.pigma.org/geoserver/cdc_estuaire/wms
http://ids.pigma.org/geoserver/geotransfert/wms
http://ids.pigma.org/geoserver/ca_gd_villeneuvois/wms
http://ids.pigma.org/geoserver/euratl/wms
http://ids.pigma.org/geoserver/cdc_mezinais/wms
http://ids.pigma.org/geoserver/epidor/wms
http://ids.pigma.org/geoserver/cdc_prayssas/wms
*/
];
header('Content-Type: text/plain; charset=UTF-8');
echo "classification:\nservers:\n";
foreach ($wmsServers as $name => $title)
  echo "  pigma-$name:
    title: $title
    class: Pigma
    url: https://ids.pigma.org/geoserver/$name/ows?
    protocol: WMS
";
