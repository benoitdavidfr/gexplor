var map = L.map('map').setView([48, 3], 8); // view pour la zone
map.locate({setView: true, maxZoom: 16});
L.control.scale({position:'bottomleft', metric:true, imperial:false}).addTo(map);

var base0 = new L.TileLayer(
    'https://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&tilematrix={z}&tilecol={x}&tilerow={y}&layer=ORTHOIMAGERY.ORTHOPHOTOS&format=image/jpeg&style=normal',
    {"format":"image/jpeg","minZoom":0,"maxZoom":20,"detectRetina":false,"attribution":"&copy; <a href='http://www.ign.fr'>IGN</a>"}
);

var base1 = new L.TileLayer(
    'https://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&tilematrix={z}&tilecol={x}&tilerow={y}&layer=GEOGRAPHICALGRIDSYSTEMS.MAPS&format=image/jpeg&style=normal',
    {"format":"image/jpeg","minZoom":0,"maxZoom":18,"detectRetina":false,"attribution":"&copy; <a href='http://www.ign.fr'>IGN</a>"}
);

var base2 = new L.TileLayer(
    'https://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&tilematrix={z}&tilecol={x}&tilerow={y}&layer=GEOGRAPHICALGRIDSYSTEMS.MAPS.SCAN-EXPRESS.STANDARD&format=image/jpeg&style=normal',
    {"format":"image/jpeg","minZoom":6,"maxZoom":18,"detectRetina":false,"attribution":"&copy; <a href='http://www.ign.fr'>IGN</a>"}
);

var base3 = new L.TileLayer(
    'https://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&tilematrix={z}&tilecol={x}&tilerow={y}&layer=GEOGRAPHICALGRIDSYSTEMS.ETATMAJOR40&format=image/jpeg&style=normal',
    {"format":"image/jpeg","minZoom":6,"maxZoom":15,"detectRetina":false,"attribution":"&copy; <a href='http://www.ign.fr'>IGN</a>"}
);

var base4 = new L.TileLayer(
    'https://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&tilematrix={z}&tilecol={x}&tilerow={y}&layer=GEOGRAPHICALGRIDSYSTEMS.PLANIGN&format=image/jpeg&style=normal',
    {"format":"image/jpeg","minZoom":0,"maxZoom":18,"detectRetina":false,"attribution":"&copy; <a href='http://www.ign.fr'>IGN</a>"}
);

var base5 = new L.TileLayer(
    'https://visu.gexplor.fr/utilityserver.php/whiteimg/{z}/{x}/{y}.jpg',
    { format: 'image/jpeg', minZoom: 0, maxZoom: 21, detectRetina: false,
      attribution: ""
    }
);
var overlay0 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/SUBFISCAL/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay1 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/DETAIL_TOPO/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay2 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/HYDRO/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay3 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/VOIE_COMMUNICATION/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay4 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/BU.Building/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 16, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay5 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/CP.CadastralParcel/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 16, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay6 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/BORNE_REPERE/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay7 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/CLOTURE/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay8 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/LIEUDIT/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay9 = new L.TileLayer(
    'https://cadastre.geoapi.fr/tile.php/AMORCES_CAD/{z}/{x}/{y}.png',
    { format: 'image/png', minZoom: 15, maxZoom: 21, detectRetina: false,
      attribution: "&copy; <a href='http://www.cadastre.gouv.fr'>Cadastre</a>"
    }
);
var overlay10 = new L.TileLayer(
    'https://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&tilematrix={z}&tilecol={x}&tilerow={y}&layer=TRANSPORTNETWORKS.ROADS&format=image/png&style=normal',
    {"format":"image/png","minZoom":6,"maxZoom":18,"detectRetina":false}
);

var overlay11 = new L.TileLayer(
    'https://gpp3-wxs.ign.fr/49qcg7rckqrk3og45nm5i4s3/wmts?service=WMTS&version=1.0.0&request=GetTile&tilematrixSet=PM&height=256&width=256&tilematrix={z}&tilecol={x}&tilerow={y}&layer=GEOGRAPHICALNAMES.NAMES&format=image/png&style=normal',
    {"format":"image/png","minZoom":6,"maxZoom":18,"detectRetina":false}
);

map.addLayer(base2);

<!-- ajout de l outil de sélection de couche -->
L.control.layers({
  "vue aérienne" : base0,
  "Cartes IGN classiques" : base1,
  "Cartes IGN Express" : base2,
  "Cartes d'Etat-Major" : base3,
  "Plan IGN" : base4,
  "Fond blanc" : base5
}, {
  "lim. subdi. fiscales (cadastre)" : overlay0,
  "Détails topographiques" : overlay1,
  "Eléments hydrographiques" : overlay2,
  "Petites voies de communication" : overlay3,
  "Bâtiments" : overlay4,
  "Parcelle" : overlay5,
  "Bornes et repères" : overlay6,
  "Clôture" : overlay7,
  "Lieu-dit" : overlay8,
  "Amorces cadastrales" : overlay9,
  "routes (IGN)" : overlay10,
  "toponymie (IGN)" : overlay11
}).addTo(map);

map.on('locationfound', function (e) {
    var radius = e.accuracy / 2;
    L.marker(e.latlng).addTo(map)
        .bindPopup("Ici dans un cercle de " + radius + " mètres de rayon").openPopup();
    L.circle(e.latlng, radius).addTo(map);
  }
);

map.on('locationerror', function (e) {
    alert(e.message);
  }
);
