/*PhpDoc:
name: mapcontext.js
title: mapcontext.js - gestion du contexte de carte qui mémorise les couches de la carte afin de générer un contexte ou un géosignet
doc: |
  L'objet mapContext mémorise les couches de la carte afin de générer un contexte ou un géosignet
  Il utilise la carte map comme variable globale.
  Il contient et pilote le contrôle de couches de Leaflet.
  Chaque couche est définie dans l'interface de l'objet :
  - soit quand la couche est a priori inconnue par un objet {title, server, lyrname, lfunc, url, options, visible?}
    où:
    - title est le nom affiché de la couche
    - server est le code du serveur dans Yaml
    - lyrname est le nom de la couche dans le serveur
    - lfunc vaut 'L.tileLayer' ou 'L.tileLayer.wms'
    - url et options sont les paramètres pour créer l'objet Leaflet
      options contient éventuellement un paramètre crs avec la valeur 'L.CRS.EPSG4326' qu'il faut transformer
      en L.CRS.EPSG4326 à la création de la couche dans Leaflet
    - visible (optionel) utilisé uniquement dans init()
  - soit par un identifiant de la couche noté lyrid qui est la concaténation de server et lyrname séparés par /
  Les opérations sont les suivantes:
  - init()
    ajoute un tableau de couches définies chacune par un objet 
    Les couches pour lesquelles visible est défini et vrai sont affichées dans la carte
  - addBaseLayer()
    ajoute une couche de base dans le contrôle et l'affiche dans la carte, la couche de base préc. affichée est désaffichée
    renvoie le lyrid
  - addOverlay()
    ajoute une couche superposable dans le contrôle et l'affiche dans la carte si visible est non défini ou vrai
    renvoie le lyrid
  - removeLayer()
    prend en paramètre le lyrid ; supprime la couche du controle de couches, si elle était affichée elle est désaffichée
    si c'était la couche de base affichée dans la carte, la couche de base 0 est affichée à la place
  - getContext()
    génère un objet JS décrivant l'état du contexte qui peut être exporté en JSON
journal: |
  29/3/2017
    Ajout du registre de serveurs indispensable pour les géo-signets
  18-19/3/2017
    Ajout de la possibilité pour le paramètre options d'avoir un champ crs valant 'L.CRS.EPSG4326'
    pour créer des couches WMS appelées en EPSG:4326
    Ajout des CRS EPSG:4171 (RGF93) + EPSG:4258 (ETRS89)
  11/3/2017
    Passage à servreg
  3-4/3/2017
    Evolution pour gérer les signets
  2/3/2017
    premiere version
    Il serait préférable de gérer l'affichage en gras des couches 
*/

// Sys. de coord. EPSG:4171 (RGF93)
L.CRS.EPSG4171 = L.extend({}, L.CRS.EPSG4326, {code: 'EPSG:4171'});
// Sys. de coord. EPSG:4258 (ETRS89)
L.CRS.EPSG4258 = L.extend({}, L.CRS.EPSG4326, {code: 'EPSG:4258'});

// L'objet mapContext mémorise l'état du controlLayers de manière à pouvoir générer un géosignet (bookmark)
var mapContext = {  
// Dans baseLayers et overlays, chaque couche est définie par un objet:
//  {title, server, lyrname, lfunc, url, options, lyrid, llyr}
// où llyr est l'objet Leaflet
  register: null, // le nom du registre de serveurs
  baseLayers: [], // la liste des couches de base définies dans le contrôle de couches
  overlays: [], // la liste des couches superposables définies dans le contrôle de couches
  controlLayers: null, // Le controlLayers

// ajoute un tableau de couches de base chacune définie par un objet:
//   {title, server, lyrname, lfunc, url, options, visible}
// Les couches pour lesquelles visible est défini et vrai sont affichées dans la carte
  init: function(layers, register) {
    this.register = register;
    this.controlLayers = L.control.layers().addTo(map);
    for (var i in layers) {
      layer = layers[i];
      layer.llyr = this.createLayer(layer);
      layer.lyrid = layer.server+'/'+layer.lyrname;
      this.baseLayers.push(layer);
      this.controlLayers.addBaseLayer(layer.llyr, layer.title);
      if ((typeof(layer.visible)!='undefined') && layer.visible)
        map.addLayer(layer.llyr);
    }
  },
    
// ajoute une couche de base dans le contrôle et l'affiche, la couche de base préc. affichée est désaffichée
// renvoie le lyrid
  addBaseLayer: function(layer) {
//    alert("addBaseLayer "+layer.title);
// désaffichage de la couche de base courante
    for (var i in this.baseLayers)
      if (map.hasLayer(this.baseLayers[i].llyr))
        map.removeLayer(this.baseLayers[i].llyr);
    layer.llyr = this.createLayer(layer);
    layer.lyrid = layer.server+'/'+layer.lyrname;
    this.baseLayers.push(layer);
    this.controlLayers.addBaseLayer(layer.llyr, layer.title);
    map.addLayer(layer.llyr);
    return layer.lyrid;
  },
  
// ajoute une couche superposable dans le contrôle et l'affiche dans la carte si visible est non défini ou vrai
// renvoie le lyrid
  addOverlay: function(layer) {
//    alert("addOverlay "+layer.title);
    layer.llyr = this.createLayer(layer);
    layer.lyrid = layer.server+'/'+layer.lyrname;
    this.overlays.push(layer);
    this.controlLayers.addOverlay(layer.llyr, layer.title);
    if ((typeof(layer.visible)=='undefined') || layer.visible)
      map.addLayer(layer.llyr);
    return layer.lyrid;
  },
  
// supprime une couche du controle de couches, si elle était affichée elle est désaffichée
// si c'était la couche de base affichée, la couche 0 est affichée à la place
  removeLayer: function(lyrid) {
//    alert("removeLayer(lyrid="+lyrid+")");
    var nbl = [];
    baseLayerRemoved = false; // indique si la couche supprimée est la couche de base affichée
    for (var i in this.baseLayers) {
      if (this.baseLayers[i].lyrid != lyrid)
        nbl.push(this.baseLayers[i]);
      else {
        this.controlLayers.removeLayer(this.baseLayers[i].llyr);
        if (map.hasLayer(this.baseLayers[i].llyr)) {
          map.removeLayer(this.baseLayers[i].llyr);
          baseLayerRemoved = true;
        }
      }
    }
    this.baseLayers = nbl;
    if (baseLayerRemoved && this.baseLayers.length)
      map.addLayer(this.baseLayers[0].llyr);
    var nol = [];
    for (var i in this.overlays) {
      if (this.overlays[i].lyrid != lyrid)
        nol.push(this.overlays[i]);
      else {
        this.controlLayers.removeLayer(this.overlays[i].llyr);
        if (map.hasLayer(this.overlays[i].llyr))
          map.removeLayer(this.overlays[i].llyr);
      }
    }
    this.overlays = nol;
  },
  
// méthode commune à init(), addBaseLayer() et addOverlay()
  createLayer: function(layer) {
    layer.options.detectRetina = true;
		if (layer.lfunc=='L.tileLayer') {
      return L.tileLayer(layer.url, layer.options);
    }
    else if (layer.lfunc=='L.tileLayer.wms') {
      if (layer.options.crs=='L.CRS.EPSG4326')
        layer.options.crs = L.CRS.EPSG4326;
      else if (layer.options.crs=='L.CRS.EPSG4171')
        layer.options.crs = L.CRS.EPSG4171;
      else if (layer.options.crs=='L.CRS.EPSG4258')
        layer.options.crs = L.CRS.EPSG4258;
      return L.tileLayer.wms(layer.url, layer.options);
    }
    else {
      window.alert("Erreur lfunc "+layer.lfunc+" non defini dans mapContext");
    }
  },
  
// fabrique un objet pour représentant le contexte
  getContext: function() {
    var context = {
      register: this.register,
      baseLayers: [],
      overlays: [],
      center: map.getCenter(),
      zoom: map.getZoom(),
      minZoom: map.getMinZoom(),
      maxZoom: map.getMaxZoom()
    };
    for (var i in this.baseLayers) {
      lyr = this.baseLayers[i];
      if (lyr.options.crs==L.CRS.EPSG4326)
        lyr.options.crs = 'L.CRS.EPSG4326';
      if (lyr.options.crs==L.CRS.EPSG4171)
        lyr.options.crs = 'L.CRS.EPSG4171';
      if (lyr.options.crs==L.CRS.EPSG4258)
        lyr.options.crs = 'L.CRS.EPSG4258';
      context.baseLayers.push({
          title: lyr.title,
          server: lyr.server,
          lfunc: lyr.lfunc,
          lyrname: lyr.lyrname,
          url: lyr.url,
          options: lyr.options,
          visible: map.hasLayer(lyr.llyr)
      });
    }
    for (var i in this.overlays) {
      lyr = this.overlays[i];
      if (lyr.options.crs==L.CRS.EPSG4326)
        lyr.options.crs = 'L.CRS.EPSG4326';
      context.overlays.push({
          title: lyr.title,
          server: lyr.server,
          lfunc: lyr.lfunc,
          lyrname: lyr.lyrname,
          url: lyr.url,
          options: lyr.options,
          visible: map.hasLayer(lyr.llyr)
      });
    }
    return context;
  }
};
