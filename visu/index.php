<?php
/*PhpDoc:
name: index.php
title: index.php - accueil de visu.GExplor.fr
journal: |
  19/4/2017
    modif. du chemin de servreg pour fonctionner sur visu.gexplor.fr
*/
?>
<html><head><title>visu</title><meta charset='UTF-8'></head><body>
<h2>visu.GExplor.fr</h2>
visu est un outil de création et de visualisation de cartes définies par des couches WMTS/WMS
développé de manière itérative par Benoît DAVID (MEDDE/CGDD/DRI/MIG).
<h3>Actualités</h3><ul>
<li><b>17/11/2016</b> :<ul>
  <li>Amélioration de l'éditeur de carte
  </ul>
<li><b>12/11/2016</b> :<ul>
  <li>Ajout d'un proxy du Cadastre à titre expérimental
  </ul>
<li><b>9/11/2016</b> :<ul>
  <li>Ajout de la possibilité d'ajouter un fond blanc (voir carte exemple)
  </ul>
<li><b>8/11/2016</b> :<ul>
  <li>Ajout de divers serveurs dont celui de la NASA
  </ul>
<li><b>6/11/2016</b> :<ul>
  <li>Ajout du <a href='server.php?action=showResources&amp;server=gpu'>serveur WMS du géoportail de l'urbanisme (GpU)</a>
      et d'une <a href='viewer.php?url=http%3A%2F%2Fvisu.gexplor.fr%2Fgen%2Fgpu.php%3Faction%3DgenMap'>carte des principales couches du GpU</a>
  <li>Affichage de la légende associée à une carte
  <li>Définition d'un mécanisme de capacités améliorées lorsque les capacités initiales sont insuffisantes
  <li>Ajout de divers serveurs
  </ul>
</ul>

<h3>Galerie de cartes</h3>
<ul>
<?php
foreach([
      'mapex.yaml' => "Carte exemple",
      'carte.yaml' => "Carte simple",
      'ignfgp.yaml' => "Carte des principales ressources IGN du Géoportail",
      'ignfgpwtools.yaml' => "Carte des principales ressources IGN du Géoportail avec les outils du Géoportail",
    ] as $yaml => $title)
  echo "<li><a href='viewer.php/$yaml' target='_blank'>$title</a> ",
       "(<a href='legend.php?url=",urlencode("maps/$yaml"),"' target='_blank'>légende</a>, ",
       "<a href='maps/$yaml'>source</a>)\n";
  foreach([
      'http://visu.gexplor.fr/gen/gpu.php?action=genMap' => "Carte du GpU",
      'http://cadastre.geoapi.fr/carte.yaml' => "Cadastre (très expérimental)",
    ] as $url => $title)
  echo "<li><a href='viewer.php?url=",urlencode($url),"' target='_blank'>$title</a> ",
       "(<a href='legend.php?url=",urlencode($url),"' target='_blank'>légende</a>, <a href='$url'>source</a>)\n";
?>
</ul>

<h3>Vous pouvez aussi :</h3>
<ul>
<?php
//echo "<pre>"; print_r($_SERVER); echo "</pre>\n";
$servregpath = ($_SERVER['SERVER_NAME'] == 'visu.gexplor.fr' ? 'http://gexplor.fr/servreg' : '../servreg');
echo "<li><a href='$servregpath/servreg.php' target='_blank'>Consulter les capacités des serveurs WMTS/WMS</a>\n";
?>
<li><a href='doc.php' target='_blank'>Consulter la doc</a>
</ul>
--<br>
Page mise à jour le 17/11/2016
</body></html>