<?php
/*PhpDoc:
name:  wmslayer.inc.php
title: wmslayer.inc.php - définition de la classe pour exploiter les capacités d'une couche WMS
classes:
doc: |
journal: |
  4/11/2016:
    première version
*/
/*PhpDoc: classes
name:  WmsLayer
methods:
title: class WmsLayer - exploite les capacités d'une couche WMS
doc: |
*/
class WmsLayer {
  private $server; // référence vers le serveur WMS
  private $cap; // SimpleXmlElement des capacités de la couche
  private $conf; // Tableau Php éventuel de la configuration ajoutée dans servers.yaml
  
// Si le titre est défini dans la conf ajouté alors je le prend, sinon celui des capacités
  function title() {
    return (isset($this->conf['title']) ?
      $this->conf['title'] :
      ($this->cap->Title ? (string)$this->cap->Title : '{titre absent}'));
    }
  
  function getAbstract() { return (string)$this->cap->Abstract; }
  
  function __construct($server, $cap) {
//    print_r($server); print_r($cap);
    if (!$cap)
      throw new Exception("Erreur cap null dans ".__FILE__." ligne ".__LINE__);
    $this->server = $server;
    $this->cap = $cap;
    $this->conf = $server->getLayerConf($cap->Name);
  }
  
// Teste si au moins une ressource est exposée en projection WM
  function availableInWM() {
//    echo "cap"; print_r($this->cap);
    foreach ($this->cap->CRS as $crs)
      if (((string)$crs)=='EPSG:3857')
        return true;
    foreach ($this->cap->Layer as $layer) {
      $lyr = new WmsLayer($this->server, $layer);
      if ($lyr->availableInWM())
        return true;
    }
    return false;
  }
  
// retourne les CRS associés à la couche (indépendamment des CRS associés à ses parents)
// sous la forme d'un tableau dont les clés sont les chaines des CRS
  function tabOfCrs() {
//    echo "cap="; print_r($this->cap);
    $tabOfCrs = [];
    foreach($this->cap->CRS as $crs)
      $tabOfCrs[(string)$crs] = 1;
//    echo "tabOfCrs="; print_r($tabOfCrs);
    return $tabOfCrs;
  }
  
  function showWmsLayers($level=0, $ids=[]) {
    echo "<li><a href='?action=showLayerInHtml&amp;server=".$this->server->id()."&amp;layer=",implode(',',$ids),"'>",
         $this->title(),"</a>",
         ($this->cap->Name ? ' ('.$this->cap->Name.')' : ''),
         '<br>',$this->cap->Abstract,
         "\n";
    $no = 0;
    echo "<ul>\n";
    foreach ($this->cap->Layer as $sublayer) {
      $sublayer = new WmsLayer($this->server, $sublayer);
      $sublayer->showWmsLayers($level+1, array_merge($ids,[$no++]));
    }
    echo "</ul>\n";
  }

  function showWmsLayersWithLegend($level=0, $ids=[]) {
    echo "<tr><td><a href='?action=showLayerInHtml&amp;server=".$this->server->id()."&amp;layer=",implode(',',$ids),"'>",
         $this->title(),"</a></td><td>";
    if ($this->cap->Style) {
      echo "<table border=1>";
// affichage d'un style par ligne
      foreach ($this->cap->Style as $style) {
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
    foreach ($this->cap->Layer as $sublayer) {
      $sublayer = new WmsLayer($this->server, $sublayer);
      $sublayer->showWmsLayersWithLegend($level+1, array_merge($ids,[$no++]));
    }
  }
  
/*PhpDoc: methods
name:  showLayerInHtml
title: function showLayerInHtml($layerIds) - affiche en HTML des capacités d'une couche identifiée par un index
doc: |
  L'index layerIds qui est une liste de numéros encodée dans une chaine avec une ',' comme séparateur
*/
  function showLayerInHtml($layerIds) {
    $layerCrs = [];
    foreach ($this->cap->CRS as $crs) $layerCrs[] = $crs;
    echo "<h2>Couche \"",$this->title(),'"',($this->cap->Name ? " (".$this->cap->Name.')' : ''),"</h2>\n",
         "<table border=1>\n",
         "<tr><td><i>Titre :</i></td><td>",$this->cap->Title,"</td></tr>\n",
         "<tr><td><i>Nom :</i></td><td>",$this->cap->Name,"</td></tr>\n",
         "<tr><td><i>Résumé :</i></td><td>",$this->cap->Abstract,"</td></tr>\n",
         "<tr><td><i>CRS :</i></td><td>",implode(', ',$layerCrs),"</td></tr>\n",
         "<tr><td><i>Attribution :</i></td><td>",$this->cap->Attribution->Title,"</td></tr>\n",
         "<tr><td><i>Capacités de la couche :</i></td>",
           "<td><a href='?action=showLayerInXml&amp;server=",$this->server->id(),"&amp;layer=$layerIds'>XML simplifié</a>",
         "</td></tr>\n";
    if ($this->conf)
      echo "<tr><td><i>Capacités suppl. :</i></td>",
             "<td><a href='?action=showLayerConf&amp;server=",$this->server->id(),"&amp;layer=$layerIds'>configuration</a></td>",
           "</tr>\n";
    echo "</table>\n";
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
//    echo "<pre>"; print_r($layer); echo "</pre>\n";
    die();
  }
  
/*PhpDoc: methods
name:  showLayerXml
title: function showLayerXml() - transmet en XML les capacités de la couche
doc: |
*/
  function showLayerInXml() {
    header('Content-Type: text/xml');
    echo $this->cap->asXml();
    die();
  }
  
/*PhpDoc: methods
name:  showLayerConf
title: function showLayerConf() - afiche les infos de configuration de la couche
*/
  function showLayerConf() {
    echo "<pre>"; print_r($this->conf); echo "</pre>\n";
  }
  
/*PhpDoc: methods
name:  firstStyle
title: function firstStyle() - retourne le premier style s'il en existe, sinon null
doc: |
*/
  function firstStyle() {
    if (count($this->cap->Style)==0)
      return null;
    foreach ($this->cap->Style as $style)
      return $style;
  }
  
/*PhpDoc: methods
name:  layer
title: function layer() - retourne qqs caractéristiques de la couche sous la forme d'un tableau Php
doc: |
  retourne ['title'=>title, 'style'=> nom du style par défaut ou null ]
*/
  function layer() {
    if ($firstStyle = $this->firstStyle())
      $styleName = (string)$firstStyle->Name;
    else
      $styleName = null;
    return [
      'title'=>$this->title(),
      'style'=>$styleName,
    ];
  }
  
/*PhpDoc: methods
name:  baseLayer
title: function baseLayer() - une couche WMS n'est jamais une couche de base
doc: |
*/
  function baseLayer() { return false; }
  
/*PhpDoc: methods
name:  genLegend
title: function genLegend($styleOfTheMap=null) - retourne le code HTML affichant la légende de la couche
doc: |
  styleOfTheMap est le nom éventuel du style choisi dans la carte, sinon null.
  Si le styleOfTheMap est défini alors je le cherche dans les capacités; si je ne le trouve pas je lève une exception
  Si le styleOfTheMap n'est pas défini, je prend le premier style de la couche
*/
  function genLegend($styleOfTheMap=null) {
    if (isset($this->conf['styles'])) {
// Je ne traite que le cas où les capacités n'ont pas défini de style et la configuration n'en définit qu'un
      $href = $this->conf['styles'][0]['legend']['url'];
      return "<img src='$href' alt='$href'>";
    }
    if (count($this->cap->Style)==0)
      return "Pas de légende";
    if ($styleOfTheMap) {
      $style = null;
      foreach ($this->cap->Style as $s)
        if ((string)$s->Name == $styleOfTheMap) {
          $style = $s;
          break;
        }
      if (!$style)
        throw new Exception("Style $styleOfTheMap non trouve pour la couche "
                            .$this->cap->Name." du serveur ".$this->server->id());
    } else
      $style = $this->firstStyle();
    $width = $style->LegendURL['width'];
    $height = $style->LegendURL['height'];
    $href = $style->LegendURL->OnlineResource['xlink_href'];
    return "<img src='$href' alt='$href'>";
    return "<img src='$href' alt='$href' height='$height' width='$width'>";
  }
}