<?php
/*PhpDoc:
name:  gennav.php
title: gennav.php - PERIME - génération de nav.html à partir de nav.php
doc: |
journal: |
  8/11/2016:
*/
die("PERIME");
header('Content-Type: text/plain; charset=UTF-8');
$navurl = 'http://'
          .($_SERVER['SERVER_NAME']<>'localhost' ?
            'visu.gexplor.fr'
            : (strncmp($_SERVER['SCRIPT_NAME'],'/~benoit/visu/', 14)==0 ?  'localhost/~benoit/visu' : 'localhost/visu'))
          .'/edit/nav.php';
if (!($nav = @file_get_contents($navurl)))
  die ("ERREUR: Lecture de \"$navurl\" impossible\n");
file_put_contents("edit/nav.html", $nav);
echo "Fichier nav.html généré à partir de $navurl\n";
