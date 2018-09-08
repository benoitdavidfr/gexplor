<?php
/*PhpDoc:
name:  index.php
title: index.php - script d'accueil de l'appli edit, définit 2 frames nav:nav.html &amp; content:edit.php
uses: [ nav.html, edit.php ]
doc: |
  Définit 2 frames:
  - nav qui contiendra l'arbre des actions défini dans nav.html
  - content qui contiendra le contenu (généralement la carte modifiée) généré par edit.php
  et appellent nav.html et edit.php
journal: |
  31/10/2016:
    première version
*/
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta charset='UTF-8'>
	<script src="../lib/jquery.js" type="text/javascript"></script>
	<title>Map edit</title>
</head>
<frameset cols="200,1*" >
  <frame src="nav.html" name="nav">
  <frame src="edit.php<?php echo (isset($_GET['url']) ? "?url=".urlencode($_GET['url']) : ''); ?>" name="content">
</frameset>

<noframes>
	<body>
		<p>This page requires frames.</p>
	</body>
</noframes>
</html>
