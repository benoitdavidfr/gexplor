<?php
/*PhpDoc:
name:  wmslayer.inc.php
title: wmslayer.inc.php - définition de la classe pour exploiter les capacités d'une couche WMS
classes:
doc: |
journal: |
  28/3/2017 :
    gestion d'un paramètre éventuel de la requête dans WmsLayer::showWmsLayers()
  18-19/3/2017 :
    Remplacement de WmsLayer::availableInWM() en WmsLayer::availableInWmOrGeo() et WmsLayer::availableInCrs()
    Les CRS acceptés pour définir des coord. géo. sont: 'EPSG:4326', 'EPSG:4171' et 'EPSG:4258'
    Ces CRS sont utilisés dans cet ordre dans leafletJS()
    Prise en compte des serveurs en version 1.1.1
  8/3/2017:
    fork pour adaptation à viuserv
    écriture partielle
  4/11/2016:
    première version
*/
/*PhpDoc: classes
name:  WmsLayer
methods:
title: class WmsLayer - exploite les capacités d'une couche WMS
doc: |
*/
class WmsLayer implements Layer {
  private $server; // référence vers le serveur WMS
  private $cap; // SimpleXmlElement des capacités de la couche
  private $conf; // Tableau Php éventuel de la configuration ajoutée dans servers.yaml
  private $inherited; // Tableau Php du paramètre CRS hérité dans la hiérarchie de couches
  private $crs; // liste des CRS/SRS définis pour cette couche
  
/*PhpDoc: methods
name:  __construct
title: function __construct($server, $cap, $inherited) - création d'une couche
*/
  function __construct($server, $cap, $inherited) {
//    echo "WmsLayer::__construct(server, cap=<pre>"; print_r($cap); echo "</pre>, inherited=<pre>"; print_r($inherited); echo "</pre>)<br>\n";
//    print_r($server);
    if (!$cap)
      throw new Exception("Erreur cap null dans ".__FILE__." ligne ".__LINE__);
    if ($inherited===null)
      throw new Exception("Erreur inherited null dans ".__FILE__." ligne ".__LINE__);
    $this->server = $server;
    $this->cap = $cap;
    $this->conf = $server->getLayerConf($cap->Name);
    $this->inherited = $inherited;
    if (!isset($this->inherited['CRS']))
      $this->inherited['CRS'] = [];
    $this->crs = [];
    if ($server->wmsversion() == '1.3.0') {
      if ($cap->CRS)
        foreach ($cap->CRS as $crs)
          if (!in_array((string)$crs, $this->crs))
            $this->crs[] = (string)$crs;
    } else {
      if ($cap->SRS)
        foreach ($cap->SRS as $crs)
          if (!in_array((string)$crs, $this->crs))
            $this->crs[] = (string)$crs;
    }
  }
  
// Si le titre est défini dans la conf ajouté alors je le prend, sinon celui des capacités
  function title() {
    return (isset($this->conf['title']) ? $this->conf['title'] :
      ($this->cap->Title ? (string)$this->cap->Title : '{titre absent}'));
    }
  
  function name() { return (string)$this->cap->Name; }
  function getAbstract() { return (string)$this->cap->Abstract; }
  function cap() { return $this->cap; }

/*PhpDoc: methods
name:  availableInWmOrGeo
title: function availableInWmOrGeo($layer=null) - La couche this ou au moins une de ses sous-couches est-elle proposée en WM ou en géo ?
doc: |
  On considère géo comme EPSG:4326, EPSG:4171 ou EPSG:4258
  La sémantique n'est valable que pour layer==nul et appel récursif. sur les objets SimpleXml layer
  En cas d'appel sur une sous-couche la méthode ne prend pas en compte les CRS définis dans les couches intermédiaires entre
  this et layer
*/
  function availableInWmOrGeo($layer=null) {
    $wmOrGeoCrsIds = ['EPSG:3857','EPSG:4326','EPSG:4171','EPSG:4258'];
//    echo "appel de availableInWmOrGeo() sur layer ",($layer?$layer->Title:'null'),"<br>\n";
    if (!$layer) {
      if (array_intersect($wmOrGeoCrsIds, $this->inherited['CRS']))
        return true;
      $layer = $this->cap;
    }
// Test dans la liste des CRS de la couche layer qui peut être différente de this
    if ($this->server->wmsVersion()=='1.3.0') {
      if ($layer->CRS)
        foreach ($layer->CRS as $crs)
          if (in_array((string)$crs, $wmOrGeoCrsIds))
            return true;
    } else {
      if ($layer->SRS)
        foreach ($layer->SRS as $crs)
          if (in_array((string)$crs, $wmOrGeoCrsIds))
            return true;
    }
// Sinon test récursif de chaque sous-couche
    if ($layer->Layer)
      foreach ($layer->Layer as $sublayer)
        if ($this->availableInWmOrGeo($sublayer))
          return true;
    return false;
  }
  
/*PhpDoc: methods
name:  availableInCrs
title: function availableInCrs($crs) - La couche est-elle disponible dans le CRS ?
doc: |
*/
  function availableInCrs($crs) {
    return (in_array($crs, $this->inherited['CRS']) or in_array($crs, $this->crs));
  }
  
// children() - renvoie un tableau Php [id=>Layer] / id est un numéro d'ordre
  function children() {
    $layers = [];
    $inherited = ['CRS' => array_merge($this->inherited['CRS'], $this->crs)];
    foreach ($this->cap->Layer as $layer)
      $layers[] = new WmsLayer($this->server, $layer, $inherited);
    return $layers;
  }
  
/*PhpDoc: methods
name:  showInHtml
title: function showInHtml() - affiche en HTML les capacités de la couche
doc: |
*/
  function showInHtml() {
    $url = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]$_SERVER[PATH_INFO]";
    $params = ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
    $urlserver = 'http://'.$_SERVER['SERVER_NAME'].dirname("$_SERVER[SCRIPT_NAME]$_SERVER[PATH_INFO]").$params;
    echo "<h2>Couche \"",$this->title(),'"',($this->cap->Name ? " (".$this->cap->Name.')' : ''),"</h2>\n",
         "<table border=1>\n",
         "<tr><td><i>Serveur :</i></td><td><a href='$urlserver'>",$this->server->title(),"</a></td></tr>\n",
         "<tr><td><i>Titre :</i></td><td>",$this->cap->Title,"</td></tr>\n",
         "<tr><td><i>Nom :</i></td><td>",$this->cap->Name,"</td></tr>\n",
         "<tr><td><i>Résumé :</i></td><td>",$this->cap->Abstract,"</td></tr>\n",
         "<tr><td><i>inh. CRS/SRS :</i></td><td>",implode(', ',$this->inherited['CRS']),"</td></tr>\n",
         "<tr><td><i>CRS/SRS :</i></td><td>",implode(', ',$this->crs),"</td></tr>\n",
         "<tr><td><i>Attribution :</i></td><td>",$this->cap->Attribution->Title,"</td></tr>\n",
         "<tr><td><i>Capacités de la couche :</i></td>",
           "<td><a href='$url",($params?"$params&amp;":'?'),"request=GetCap'>XML simplifié</a>",
         "</td></tr>\n";
    if ($this->conf)
      echo "<tr><td><i>Capacités suppl. :</i></td>",
             "<td><a href='?action=showLayerConf&amp;server=",$this->server->id(),"&amp;layer=$layerIds'>configuration</a></td>",
           "</tr>\n";
    echo "</table>\n";
    echo ($this->availableInWmOrGeo() ? 'availableInWmOrGeo' : '<s>availableInWmOrGeo</s>');
    foreach(['EPSG:3857','EPSG:4326','EPSG:4171','EPSG:4258'] as $crs)
      echo ($this->availableInCrs($crs) ? " $crs" : " <s>$crs</s>");
    echo "<br>\n";
    
    if ($this->cap->Layer) {
      echo "<h3>A REVOIR Sous-couches</h3>\n<ul>\n";
      foreach ($this->cap->Layer as $sublayer)
        $this->showWmsLayers($sublayer, false, $urlserver);
      echo "</ul>\n";
    }
  
    if ($this->cap->Style) {
      echo "<h3>Styles</h3>\n",
           "<table border=1><th>Name</th><th>Title</th><th>w</th><th>h</th><th>Format</th><th>image</th>\n";
      foreach ($this->cap->Style as $style) {
        $width = $style->LegendURL['width'];
        $height = $style->LegendURL['height'];
        $href = $style->LegendURL->OnlineResource['xlink_href'];
        echo "<tr><td>",$style->Name,"</td><td>",$style->Title,"</td>",
             "<td>$width</td><td>$height</td>",
             "<td>",$style->LegendURL->Format,"</td>",
             "<td><img src='$href' alt='$href' height='$height' width='$width'</td></tr>\n";
      }
      echo "</table>\n";
    }
//    echo "<pre>"; print_r($this); echo "</pre>\n";
    die();
  }
  
/*PhpDoc: methods
name:  showWmsLayers
title: function showWmsLayers($layer=null, $urlserver=null) - Affichage récursif des couches
doc: |
  Appelé par WmsServer::showInHtml() sur la couche racine sans paramètre
  et par WmsLayer::showInHtml() sur une couche quelconque avec les 3 paramètres
  Si urlserver vaut null alors c'est l'URL courante
*/
  function showWmsLayers($layer=null, $parentInWmOrGeo=false, $urlserver=null) {
    if (!$urlserver)
//      $urlserver = "http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
      $urlserver = "http://$_SERVER[SERVER_NAME]$_SERVER[SCRIPT_NAME]$_SERVER[PATH_INFO]";
    $params = ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
//    echo "urlserver=$urlserver<br>\n";
//    echo "<pre>_SERVER="; print_r($_SERVER); die();
    if (!$layer)
      $layer = $this->cap;
    $lyrName = ($layer->Name ? $layer->Name : '');
    $layerInWmOrGeo = ($parentInWmOrGeo || $this->availableInWmOrGeo($layer));
//    echo "layerInWmOrGeo=",($layerInWmOrGeo?'true':'false'),"<br>\n";
    $listOfCrs = [];
    if ($this->server->wmsVersion()=='1.3.0')
      foreach ($layer->CRS as $crs)
        $listOfCrs[] = (string)$crs;
    else
      foreach ($layer->SRS as $crs)
        $listOfCrs[] = (string)$crs;
    echo "<li>",
         ($lyrName ? "<a href=\"$urlserver/$lyrName$params\">".$layer->Title."</a> ($lyrName)" : $layer->Title),
         ' ',($layerInWmOrGeo ? '' : '<s>WM/Geo</s>'),
//         "<br>listOfCrs=",implode(', ',$listOfCrs),"\n",
         '<br>',$layer->Abstract,
         "\n";
    echo "<ul>\n";
    foreach ($layer->Layer as $sublayer)
      $this->showWmsLayers($sublayer, $layerInWmOrGeo, $urlserver);
    echo "</ul>\n";
  }

/*PhpDoc: methods
name:  showWmsLayersWithLegend
title: function showWmsLayersWithLegend($layer=null) - Affichage récursif des couches avec légende
doc: |
  Appelé par WmsServer::showInHtml()
*/
  function showWmsLayersWithLegend($layer=null) {
// URL d'appel
    $url = "http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]";
    if (!$layer)
      $layer = $this->cap;
    $lyrName = ($layer->Name ? $layer->Name : '');
    echo "<tr><td>",($lyrName ? "<a href='$url/$lyrName'>".$layer->Title."</a>" : $layer->Title),"</td><td>";
    if ($layer->Style) {
      echo "<table border=1>";
// affichage d'un style par ligne
      foreach ($layer->Style as $style) {
        $width = $style->LegendURL['width'];
        $height = $style->LegendURL['height'];
        $href = $style->LegendURL->OnlineResource['xlink_href'];
        echo "<tr><td>",$style->Name,"</td><td>",$style->Title,"</td>",
             "<td><img src='$href' alt='$href' height='$height' width='$width'</td></tr>\n";
      }
      echo "</table>\n";
    }
    echo "</td></tr>\n";
    $no = 0;
    foreach ($layer->Layer as $sublayer)
      $this->showWmsLayersWithLegend($sublayer);
  }
  
/*PhpDoc: methods
name:  leafletJS
title: function leafletJS($options=[]) - renvoie un tableau Php destiné à la génération d'une commande JS pour insérer la couche dans Leaflet
doc: |
  ['title'=>titre de la couche, 'lfunc'=> classe Leaflet, 'url'=>URL d'appel de la couche, 'options'=>options]
*/
  function leafletJS($options=[]) {
    $options['version'] = $this->server->wmsVersion();
    if (!$this->availableInCrs('EPSG:3857')) {
      if ($this->availableInCrs('EPSG:4326'))
        $options['crs'] = 'L.CRS.EPSG4326';
      elseif ($this->availableInCrs('EPSG:4171'))
        $options['crs'] = 'L.CRS.EPSG4171';
      elseif ($this->availableInCrs('EPSG:4258'))
        $options['crs'] = 'L.CRS.EPSG4258';
    }
    $options['layers'] = $this->name();
    $options['format'] = 'image/png';
    $options['transparent'] = true;
    return [ 'title'=> $this->title(),
             'lfunc'=> 'L.tileLayer.wms',
             'url'=> $this->server->conf()['url'],
             'options'=> $options,
           ];
  }
  
/*PhpDoc: methods
name:  genLegend
title: function genLegend($style=null) - retourne le code HTML affichant la légende de la couche
doc: |
*/
  function genLegend($style=null) {
    $layer = $this->cap;
    if ($layer->Style) {
      echo "<table border=1>";
// affichage d'un style par ligne
      foreach ($layer->Style as $style) {
        $width = $style->LegendURL['width'];
        $height = $style->LegendURL['height'];
        $href = $style->LegendURL->OnlineResource['xlink_href'];
        echo "<tr><td>",$style->Name,"</td><td>",$style->Title,"</td>",
             "<td><img src='$href' alt='$href' height='$height' width='$width'</td></tr>\n";
      }
      echo "</table>\n";
    }
  }
};