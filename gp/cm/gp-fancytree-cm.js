/*PhpDoc:
name: gp-fancytree.js
title: gp-fancytree.js - code JS initialisant l'arbre
hrefs: [main-tree.php, server-tree.php]
doc: |
  Quand on click une couche dans Fancytree, elle est ajoutée à la carte et son nom passe en gras
  Si on la click à nouveau, elle est supprimée et son nom repasse en normal
  Pour une couche tuilée (tile ou WMTS)
    Si la couche est au format jpeg alors elle est ajoutée en couche de base sinon en couche superposable
  Pour une couche WMS
    Si la touche control est enfoncée alors elle est ajoutée comme couche de base sinon en couche superposable
  
journal: |
  26/2/2017:
    Ajout du context menu
  24/2/2017:
    Ajout de l'utilisation de la touche controle pour l'ajout d'une couche WMS en couche de base
  15/2/2017:
    Utilisation de l'événement click pour activer/désactiver une couche
    Utilisation de l'événement activate pour afficher une page liée
  14/2/2017:
    Utilisation de l'événement click au lieu de activate qui permet de désactiver facilement une couche
  13/2/2017:
    Gestion de l'ajout/suppression de couches
*/
$(function(){
// Create the tree inside the <div id="tree"> element.
  $("#tree").fancytree({
    source: {
      url: "main-tree.php",
      cache: false
    },
    lazyLoad: function(event, data) {
      var node = data.node;
      // Issue an ajax request to load child nodes
      data.result = {
        url: "server-tree.php",
        data: {server: node.data.server}
      }
    }
  })
  .contextmenu({
//    delegate: "span.fancytree-title",
//      menu: "#options",
    menu: [
        {title: "Cut", cmd: "cut", uiIcon: "ui-icon-scissors"},
        {title: "Copy", cmd: "copy", uiIcon: "ui-icon-copy"},
        {title: "Paste", cmd: "paste", uiIcon: "ui-icon-clipboard", disabled: false },
        {title: "----"},
        {title: "Edit", cmd: "edit", uiIcon: "ui-icon-pencil", disabled: true },
        {title: "Delete", cmd: "delete", uiIcon: "ui-icon-trash", disabled: true },
        {title: "More", children: [
          {title: "Sub 1", cmd: "sub1"},
          {title: "Sub 2", cmd: "sub1"}
          ]}
        ],
    beforeOpen: function(event, ui) {
      var node = $.ui.fancytree.getNode(ui.target);
      // Modify menu entries depending on node status
      $("#tree").contextmenu("enableEntry", "paste", node.isFolder());
      // Show/hide single entries
//            $("#tree").contextmenu("showEntry", "cut", false);

      // Activate node on right-click
      node.setActive();
    },
    select: function(event, ui) {
      var node = $.ui.fancytree.getNode(ui.target);
      alert("select " + ui.cmd + " on " + node);
    }
  });
});
