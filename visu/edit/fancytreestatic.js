/*PhpDoc:
name: fancytree.js
title: fancytree.js - code JS initialisant l'arbre, recopié de http://wwwendt.de/tech/fancytree/demo/
*/
$(function(){
	// --- Initialize sample trees
	$("#tree").fancytree({
		autoActivate: false, // we use scheduleAction()
		autoCollapse: true,
//			autoFocus: true,
		autoScroll: true,
		clickFolderMode: 3, // expand with single click
		minExpandLevel: 2,
		tabindex: "-1", // we don't want the focus frame
		// scrollParent: null, // use $container
		focus: function(event, data) {
			var node = data.node;
			// Auto-activate focused node after 1 second
			if(node.data.href){
				node.scheduleAction("activate", 1000);
			}
		},
		blur: function(event, data) {
			data.node.scheduleAction("cancel");
		},
		activate: function(event, data){
			var node = data.node,
				orgEvent = data.originalEvent || {};

			if(node.data.href){
				window.open(node.data.href, (orgEvent.ctrlKey || orgEvent.metaKey) ? "_blank" : node.data.target);
			}
			if( window.parent &&  parent.history && parent.history.pushState ) {
				// Add #HREF to URL without actually loading content
				parent.history.pushState({title: node.title}, "", "#" + (node.data.href || ""));
			}
		},
		click: function(event, data){ // allow re-loads
			var node = data.node,
				orgEvent = data.originalEvent;

			if(node.isActive() && node.data.href){
				// data.tree.reactivate();
				window.open(node.data.href, (orgEvent.ctrlKey || orgEvent.metaKey) ? "_blank" : node.data.target);
			}
		}
	});
	// On page load, activate node if node.data.href matches the url#href
	var tree = $(":ui-fancytree").fancytree("getTree"),
		frameHash = window.parent && window.parent.location.hash;

	if( frameHash ) {
		frameHash = frameHash.replace("#", "");
		tree.visit(function(n) {
			if( n.data.href && n.data.href === frameHash ) {
				n.setActive();
				return false; // done: break traversal
			}
		});
	}
});
