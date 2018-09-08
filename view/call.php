<?php
file_get_contents('http://view.gexplor.fr/sigloire/l_zonesactioncomplementaire_085');
header('Content-type: text/plain; charset="utf-8"');
echo "http_response_header="; print_r($http_response_header);