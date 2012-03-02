<?php


#tmpfile is file used for generating graph data. it's gets deleted once the graph is created
$tmpfile = "/tmp/ospf_g.txt";

# The filesystem localtion and it's corrisponding URL for the output files
$output_dir = "/var/www/html/ospf_db_topology/graph/";
$output_html = "http://localhost/ospf_db_topology/graph";

#filesystem path to the graphviz binaries
$graphviz_path = "/usr/bin";

?>
