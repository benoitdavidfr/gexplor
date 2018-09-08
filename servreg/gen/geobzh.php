<?php
/*PhpDoc:
name:  geobzh.php
title: geobzh.php - génère les serveurs GéoBretagne
doc: |
journal: |
  7/11/2016:
    première version
*/
$wmsServers = [
# réf. géo.
  'photo' => "Photographies aériennes en Bretagne",
  'geob_loc' => "données de localisation",
  'ign' => "IGN",
  'alti' => "Altimétrie",
  'cadastre' => "Cadastre en Bretagne",
  'ff' => "fichiers fonciers",
  'osm' => "OpenStreetMap",
# Organismes supra régionaux
  'onema' => "ONEMA",
  'rff' => "RFF",
#  'rte' => "RTE", # Erreur 404
  'asp' => "ASP",
  'insee' => "INSEE",
  'nasa' => "NASA",
  'fma' => "Forum des Marais Atlantique",
# Services régionaux de l'Etat
  'zoneouest' => "Zone Ouest",
  'dreal_b' => "DREAL Bretagne",
  'dir_ouest' => "Direction Interrégionale des Routes Ouest",
  'draaf' => "DRAAF Bretagne",
  'drac' => "DRAC",
#  'drjcs' => "DRJCS Bretagne", # Erreur 404
  'direccte' => "DIRECCTE",
  'rectorat' => "rectorat Bretagne",
# Autres organismes régionaux
  'otb' => "Office de Tourisme de Bretagne",
  'epfb' => "EPF Bretagne",
  'chambreagriculture' => "Chambre d'agriculture",
  'crpf' => "CRPF",
  'bretagneenvironnement' => "Bretagne Environnement",
  'bretagnevivante' => "Bretagne Vivante",
  'bretagneromantique' => "Bretagne Romantique",
# Services départementaux de l'Etat
  'ddtm22' => "DDTM 22",
  'ddtm29' => "DDTM 29",
  'ddtm35' => "DDTM 35",
  'ddtm56' => "DDTM 56",
# Autres organismes départementaux
  'cg22' => "Conseil Départemental 22",
  'cg29' => "Conseil Départemental 29",
  'cg35' => "Conseil Départemental 35",
  'cg56' => "Conseil Départemental 56",
  'sdis22' => "SDIS22",
  'sdis29' => "SDIS29",
  'sdis35' => "SDIS35",
  'sdis56' => "SDIS56",
# Organismes sub départementaux
  'smlj' => "Syndicat Mixte du bassin versant du lac de Jugon",
  'crcbs' => "Comité Régional de Conchyliculture Bretagne sud",
  'adeupa' => "Agence d'Urbanisme du Pays de Brest",
  'anfr' => "Agence Nationale des Fréquences",
  'audelor' => "Agence d'Urbanisme et de Développement Economique du Pays de Lorient (AUDELOR)",
  'audiar' => "Agence d'Urbanisme et de Développement Intercommunal de l'Agglomération Rennaise (AUDIAR)",
  'bassinelorn' => "Syndicat de Bassin de l'Elorn",
  'cciquimper' => "CCI de Quimper",
  'cocopaq' => "ComCom du Pays de Quimperlé",
  'concarneaucornouaille' => "Concarneau Cornouaille Agglomération",
  'couesnon' => "SAGE Couesnon",
  'emeraude' => "ComCom de la Côte d'Emeraude",
  'glazik' => "ComCom du pays Glazik",
  'hautecornouaille' => "ComCom de Haute Cornouaille",
  'iav' => "Institut d'Aménagement de la Vilaine",
  'lorientagglo' => "Lorient Agglomération",
  'megalis' => "Mégalis Bretagne",
  'montfortcommunaute' => "Montfort Communauté",
  'paimpolgoelo' => "ComCom de Paimpol Goëlo",
  'paysaubigne' => "ComCom du Pays d'Aubigné",
  'paysauray' => "pays d'Auray",
  'payschateaugiron' => "ComCom du pays de Chateaugiron",
  'paysfouesnantais' => "ComCom du pays Fouesnantais",
  'paysploermel' => "pays de Ploermel",
  'paysrennes' => "pays de Rennes",
  'paysstbrieuc' => "pays de Saint Brieuc",
  'paysstmalo' => "pays de Saint Malo",
  'pnra' => "Parc Naturel Régional d'Armorique",
  'pontivycommunaute' => "Pontivy Communauté",
  'presquilerhuys' => "ComCom de la Presqu'île de Rhuys",
  'qcd' => "Quimper Cornouaille Développement",
  'quimpercommunaute' => "Quimper Communauté",
  'riaetel' => "CC Ria d'Etel",
  'rochefees' => "CC de la Roche aux Fées",
  'roimorvan' => "Roi Morvan Communauté",
  'supaysvitre' => "Syndicat d'Urbanisme du Pays de Vitré",
  'valdille' => "CC du Val d'Ille",
  'vannesagglo' => "Vannes Agglomération",
  'vitrecommunaute' => "Vitré Communauté",
  'coeuremeraude' => "Coeur Emeraude",
  'paysredon' => "pays de Redon",
#  'siagm' => "Syndicat d'Aménagement du Golfe du Morbihan",
#  'lochsal' => "Loc'h et Sal",
  'paysbroceliande' => "pays de Brocéliande",
  'coglais' => "Coglais Communauté",
  'smjgb' => "Bassin Versant Jaudy-Guindy-Bizien",
#  'ccphb' => "ComCom du Haut pays Bigouden",
  'ccpbs' => "ComCom Pays Bigouden Sud",
];
header('Content-Type: text/plain; charset=UTF-8');
echo "classification:\nservers:\n";
foreach ($wmsServers as $name => $title)
  echo "  geobzh-$name:
    title: $title
    class: Bretagne
    url: http://geobretagne.fr/geoserver/$name/ows?
    protocol: WMS
";
