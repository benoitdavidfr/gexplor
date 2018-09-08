<?php
/*PhpDoc:
name: gp-fancytree-js.php
title: gp-fancytree-js.php - code JS (généré par Php) initialisant l'arbre Fancytree
hrefs: [main-tree.php, server-tree.php]
doc: |
  Ce script JS doit être généré en Php afin de transmettre les paramètres de signet à server-tree.php
  Quand on click une couche dans Fancytree, elle est ajoutée à la carte et son nom passe en gras
  Si on la click à nouveau, elle est supprimée et son nom repasse en normal
  Pour une couche tuilée (tile ou WMTS)
    Si la couche est au format jpeg alors elle est ajoutée en couche de base sinon en couche superposable
    Si la touche shift est enfoncée, c'est l'inverse
  Pour une couche WMS:
    Si la touche shift est enfoncée alors elle est ajoutée comme couche de base en jpeg sinon en couche superposable en png
  
journal: |
  24/3/2017
    ajout de la possibilité d'utiliser un registre différent
  11/3/2017:
    passage à servreg
  4/3/2017:
    Passage en php pour passer les paramètres d'initialisation à server-tree.php
  2/3/2017:
    Gestion de l'état de la carte avec l'objet mapContext
  27/2/2017:
    Utilisation de la touche shift à la place de la touche control
  26/2/2017:
    Ajout de l'utilisation de la touche controle pour l'ajout d'une couche Tile en couche de base ou superposable
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
header('Content-type: text/plain; charset="utf8"');
?>
function boleanXOR(a,b) {
  return ( a || b ) && !( a && b );
}

$(function(){
// Create the tree inside the <div id="tree"> element.
  $("#mytree").fancytree({
    source: {
      url: "main-tree.php<?php echo (isset($_GET['register']) ? "?register=".urlencode($_GET['register']) : '');?>",
      cache: false
    },
    lazyLoad: function(event, data) {
      var node = data.node;
      // Issue an ajax request to load child nodes
<?php
// construction des paramètres de server-tree.php
// il y a premièrement l'id du serveur puis le register éventuel puis la liste baseLayers et overlays du contexte initial
$data = "server: node.data.server";
foreach (['register','baseLayers','overlays'] as $k)
  if (isset($_GET[$k]))
    $data .= ", $k: \"".$_GET[$k]."\"";
?>
      data.result = {
        url: "server-tree.php",
        data: {<?php echo $data;?>}
      }
    },
    
// L'événement activate permet d'afficher une page liée éventuelle (node.data.href)
// ou d'afficher la page bookmark (node.data.bookmark)
    activate: function(event, data){
      var node = data.node,
          orgEvent = data.originalEvent || {};
//      alert("node="+node);
//      alert("orgEvent.ctrlKey="+orgEvent.ctrlKey+", orgEvent.metaKey="+orgEvent.metaKey);
      if (node.data.tools) {
        context = JSON.stringify(mapContext.getContext());
        window.open('tools.php?context='+encodeURIComponent(context),'tools');
      }
      else if (node.data.href) {
        window.open(node.data.href, (orgEvent.ctrlKey || orgEvent.metaKey) ? "_blank" : node.data.target);
      }
    },
    
// L'événement click permet d'activer/désactiver une couche
// L'activation se fait sur les noeuds dont node.data.protocol vaut 'tile' ou 'WMS'
//   node.data.lyrid contient alors l'identifiant renvoyé par mapContext et node.data.origTitle contient le titre d'origine
// La désactivation se fait sur les noeuds dont le node.data.lyrid est défini et non null
// Si shiftKey est enfoncée alors l'action est modifiée
// Si ctrlKey est enfoncée alors l'action n'est ni une activation/désactivation mais un pan/zoom si node.data.center est défini
    click: function(event, data){
//      alert("click on"+data.node);
//      alert("event.ctrlKey="+event.ctrlKey+" event.shiftKey="+event.shiftKey);
      var node = data.node,
          orgEvent = data.originalEvent || {};

      if (event.ctrlKey && event.shiftKey && node.data.lfunc) {
        alert("page de diagnostic");
        if (node.data.lfunc==='L.tileLayer.wms') {
          window.open("showlayer.php?protocol=WMS&url="+encodeURIComponent(node.data.url)+
                                   "&layer="+node.data.options.layers+
                                   "&center="+map.getCenter().lat+','+map.getCenter().lng+
                                   "&zoom="+map.getZoom(),
                     'diagnostic');
        }
        else if (node.data.lfunc==='L.tileLayer') {
          window.open("showlayer.php?protocol=tile&url="+encodeURIComponent(node.data.url)+
                                   "&center="+map.getCenter().lat+','+map.getCenter().lng+
                                   "&zoom="+map.getZoom(),
                     'diagnostic');
        }
      }
// node.data.lyrid indique que la couche a été fabriquée et est affichée
      else if (!event.ctrlKey && node.data.lyrid) {
//        alert("Suppression de la couche "+node.title);
        mapContext.removeLayer(node.data.lyrid);
        node.data.lyrid = null;
        node.setTitle(node.data.origTitle);
      }
// Cas d'ajout d'une couche
      else if (!event.ctrlKey && node.data.lfunc) {
        layer = {
            title: node.title,
            server: node.data.server,
            lfunc: node.data.lfunc,
            lyrname: node.data.lyrname,
            url: node.data.url,
            options: node.data.options
        };
// Cas d'ajout d'une couche L.tileLayer
        if (node.data.lfunc==='L.tileLayer') {
//          alert("Ajout de la couche L.tileLayer "+node.title);
          if (boleanXOR(node.data.options.format=='image/jpeg', event.shiftKey))
            node.data.lyrid = mapContext.addBaseLayer(layer);
          else
            node.data.lyrid = mapContext.addOverlay(layer);
        }
// Cas d'ajout d'une couche L.tileLayer.wms
        else if (node.data.lfunc==='L.tileLayer.wms') {
//          alert("Ajout de la couche L.tileLayer.wms "+node.title);
// Si shiftKey alors format jpeg en couche de base
          if (event.shiftKey) {
            node.data.options.format = 'image/jpeg';
            node.data.lyrid = mapContext.addBaseLayer(layer);
          } else {
// Si PAS shiftKey alors format png en couche superposée, éventuellement partiellement transparente
            node.data.options.format = 'image/png';
            node.data.lyrid = mapContext.addOverlay(layer);
          }
        }
        node.data.origTitle = node.title;
        node.setTitle('<b>'+node.title+'</b>');
      }
// Cas de zoom/pan vers un center et un zoom associé à l'objet
      else if (node.data.center) {
        map.setView(L.latLng(node.data.center[0], node.data.center[1]), node.data.zoom);
//        map.flyTo(L.latLng(node.data.center[0], node.data.center[1]), node.data.zoom);
      }
    }
  });
  
// On page load, activate node if node.data.href matches the url#href
  var tree = $(":ui-fancytree").fancytree("getTree"),
      frameHash = window.parent && window.parent.location.hash;

  if (frameHash) {
    frameHash = frameHash.replace("#", "");
    tree.visit(function(n) {
      if( n.data.href && n.data.href === frameHash ) {
        n.setActive();
        return false; // done: break traversal
      }
    });
  }
});
