<?php
/*PhpDoc:
name: doc.php
title: doc.php - doc de visu
includes: [ newserver.inc.php ]
*/
if (isset($_GET['action']) and ($_GET['action']=='servers')) {
  require_once 'newserver.inc.php';
  $yaml = file_get_servers();
  header('Content-Type: text/plain; charset=UTF-8');
  echo str_replace(
          ['\xE0','\xE1','\xE7','\xE8','\xE9','\xEA','\xED','\xF1'],
          ['à',   'á',   'ç',   'è',   'é',   'ê',   'í',   'ñ'],
          yaml_emit($yaml));
  die();
}
?>
<html><head><meta charset='UTF-8'><title>doc visu</title></head></body>
<h2>Doc de visu.GExplor.fr</h2>

<h3>Généralités</h3>
L'objectif de cet outil est de visualiser des cartes définies comme un empilement de couches WMTS/WMS
en s'appuyant sur Leaflet.<br>
Une liste de serveurs WMTS/WMS est définie (<a href='?action=servers'>disponible ici codée en Yaml</a>).<br> 
Une carte est définie principalement par une liste de couches de base et une autre liste de couches superposées.
Chaque couche est définie par son serveur (identifié par son identifiant dans la liste)
et par l'identifiant de la couche dans le serveur.
Un <a href='maps/mapex.yaml'>exemple de définition de carte est fourni ici</a>.
</p>

Le fichier décrivant une carte peut être construit à l'aide d'un éditeur de texte.<br>
visu propose 3 outils :<ol>
<li><a href='server.php' target='_blank'>server</a> permet de consulter les capacités des serveurs afin de connaitre la liste des couches proposées par chacun.
<li>Le <a href='viewer.php' target='_blank'>visualisateur ce carte (viewer)</a> permet soit de visualiser une carte en indiquant son URL,
    soit d'éditer interactivement une carte et de la visualiser.
<li>L'<a href='edit' target='_blank'>éditeur de carte</a> propose de construire interactivement une carte en y ajoutant interactivement
    des couches des seveurs WMTS/WMS tout en la visualisant.
    Cet un outil basic et peu convivial.
</ol>

Actuellement, toutes les couches doivent être disponibles en projection "Web Mercator" (EPSG:3857).

<h3>L'éditeur</h3>
L'<a href='edit' target='_blank'>éditeur</a> propose de construire une carte interactivement.
Par défaut, une nouvelle carte est initialisée à partir de la <a href='edit/defaultmap.yaml'>carte par défaut</a>.
Il est aussi possible de repartir d'une carte précédemment éditée.<br>

L'éditeur est composée de 2 cadres (frame) qui peuvent être dans la même fenêtre ou dans 2 fenêtres séparées.
Dans le premier cadre (qui est la partie gauche de la fenêtre unique),
l'arbre des Couches présente une liste de serveurs WMTS/WMS et permet d'accéder à leurs couches.
En cliquant sur une couche qui n'appartient pas déjà à la carte on la rajoute ; si la couche existe déjà elle est supprimée de la carte.<br>

Au-dessus de l'arbre des couches un sous-arbre "Outils" propose :<ul>
<li>Tout d'abord dans le sous-arbre "Opérations" les opérations suivantes :<ul>
  <li>Enregistrer la carte : enregistre la carte dans un fichier sur le serveur ;
      après l'enregistrement 3 URL sont fournies :<ol>
      <li>une URL d'affichage du texte Yaml correspondant à la carte,
      <li>une URL de visualisation de la carte avec Leaflet,
      <li>une URL pour reprendre l'édition de la carte.
      </ol>
      Un mécanisme de sécurité évite qu'une carte soit modifiée malencontreusement.
      Il est fondé sur l'existence de 2 identifiants : l'identifiant de consultation et l'identifiant d'édition.
      Pour partager sa carte en visualisation tant en la protégeant en modification, vous pouvez communiquer les 2 premiers URL mais pas le dernier.
  <li>Réinitialiser la carte : efface la carte courante en la remplacant par la carte par défaut ;
  <li>Faire un dump du contexte : affiche la carte en mémoire sous la forme Yaml ou Php ;
  <li>Documentation : affiche cette documentation ;
  <li>Accueil de visu : ouvre une nouvelle fenêtre avec la page d'accueil du site.
  </ul>
<li>Ensuite un sous-arbre avec différents points de vue qui permet de changer le point de départ de la carte.
</ul>

Les fichiers de carte sont conservés sans garantie de pérennité.
J'envisage a priori de les conserver un an et supprimer régulièrement les fichiers plus vieux qui n'ont pas été modifiés.
Si vous souhaitez conserver un fichier finalisé, le mieux est de le recopier sur votre propre serveur
et de fournir son URL en paramètre dans le visualisateur viewer.

<h3>Sources</h3>
<a href='visudiff20161113.7z'>Les codes sources du 13/11/2016 sont disponibles ici</a>.

<h3>Roadmap</h3>
<pre>
A FAIRE (31/10/2016):
- obtenir une clef Météo-France
  https://donneespubliques.meteofrance.fr/?fond=geoservices&id_dossier=14
- ajouter des serveurs initiaux: PF régionales, Géo-IDE, ...
- permettre aux internautes d'ajouter leur propre serveur WMTS/WMS
- permettre de définir des cartes en projection <> WM
</pre>
--<br>
Dernière mise à jour: 13/11/2016