-- Requêtes SQL pour trouver les services WMS dans le geocat

Je cherche:
  IGNFGP-WMS-Inspire-V:
    title: GP IGN WMS Inspire vecteur
    class: IGN
    url: http://gpp3-wxs.ign.fr/0czlrp8frromgao3lyk9eyje/inspire/v/wms?
    protocol: WMS

Base: bdavid_geocat3dev

select recordid, title.val title, attributedTo.val attributedTo, mdlocator.val url, abstract.val abstract
from harvestrecord
  join mdelement mdtype using(recordid)
  join mdelement title using(recordid)
  join mdelement mdlocator using(recordid)
  join mdelement attributedTo using(recordid)
  left join mdelement abstract using(recordid)
where harvest='geoide20170226'
  and mdtype.var='type' and mdtype.val='service'
  and title.var='title'
  and mdlocator.var='locator' and mdlocator.sval0='OGC:WMS-1.3.0-http-get-capabilities'
  and attributedTo.var='attributedTo'
  and abstract.var='abstract';


select * from mdelement where recordid='013005fe852b171c00bde1bda7a8a0de';
select * from mdelement where recordid='01423f65bfbb080acebf7d53bd0095c4';

