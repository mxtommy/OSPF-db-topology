<?php

// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

// OSPF Database visualization by Thomas St.Pierre

include("iplib.php");
include("config.php");


$debug = 1;

if (!isset($_REQUEST['ospf_network']))
{
	?>
	<form action=index.php method=post>
	show ip ospf [PROCESS] database network<br>
	<textarea rows="25" cols="80" name="ospf_network"></textarea><br>
	<table border=1>
	<tr><th colspan=3>Layout Algorithm</th></tr>
	<tr><td><input type=radio name=layout value=neato checked></input><td>neato</td><td>Generally ok. poor area support</td></tr>
	<tr><td><input type=radio name=layout value=fdp></input><td>fdp</td><td>Generally ok for small, supports areas</td></tr>
	<tr><td><input type=radio name=layout value=sfdp></input><td>sfdp</td><td>Good for large areas, no support for areas</td></tr>
	</table>
	<table border=1>
	<tr><th colspan=3>Layout Options</th></tr>
	<tr><td>Areas?</td><td>Yes:<input type=radio name=do_area value=yes> No:<input type=radio name=do_area value=no checked></input></td><td>Try and generate subgraphs for each areas</td></tr>
	<tr><td>Splines?</td><td>Yes:<input type=radio name=splines value=yes checked> No:<input type=radio name=splines value=no></input></td><td>Yes = curved splines, No = Strait splines</td></tr>
    </table>
	<input type=submit></form>	
	<?php
	exit; 	
}

if ($_REQUEST['layout'] == 'fdp')
{
	$cmd = 'fdp';
} elseif ($_REQUEST['layout'] == 'neato')
{
	$cmd = 'neato';
} elseif ($_REQUEST['layout'] == 'sfdp')
{
	$cmd = 'sfdp';
} else
{
	print ("invalid layout");
	exit;
} 


$ospf_router = explode("\n", $_REQUEST['ospf_router']);
$ospf_network = explode("\n\r", $_REQUEST['ospf_network']);
$current_link_type = "";
$links = array();
$routers = array();
$area_r = array();
$abr = array();
$cnt = 0;
$groups = array();
$current_area = 0;

foreach ($ospf_network as $group)
{
	
	$network_ip = '0.0.0.0';
	$network_mask = 32;
	if (trim($group) == "") { continue; }

	if (preg_match("/Net Link States \(Area (\d*)\)/", $group, $matches))
	{
		$current_area = $matches[1];
		continue;
	}
	if (preg_match("/Link State ID: (.*) \(address of Designated Router\)/", $group, $matches))
	{
		$network_ip = $matches[1];
	}
	if (preg_match("/Network Mask: \/(\d*)/", $group, $matches))
	{
		$network_mask = $matches[1];
	}
	
	if ($network_ip == '0.0.0.0')
	{
		continue;
	}
	
	$i = inet_aton($network_ip);
	
	$net = inet_ntoa(find_network($i, $network_mask)) . "/" . $network_mask;
	
	$area_l[$current_area][] = $net;
	
	#Connected_routers
	if (preg_match_all("/Attached Router: (.*)/", $group, $matches))
	{
		foreach ($matches[1] as $router_id)
		{
			$router_id = trim($router_id);
			if (isset($routers[$router_id]) && ($routers[$router_id] != $current_area))
			{
				$abr[$router_id] = 1;
			}
			$routers[$router_id] = $current_area;
			
			$links[$net][] = $router_id;
			$area_r[$current_area][] = $router_id;
		}
	}
	
		
	
}

################################################################################################################

$msg = "graph G {\n overlap=scale;\n";

if ($_REQUEST['splines'] == 'yes') { $msg .= "splines=true;\n"; }

$area_list = "";
$node_list = "";
$link_list = "";
$n = array();
$r = array();;
$cnt = 0;





foreach ($links as $net => $routers)
{
	if (!isset($n[$net]))
	{
		$n[$net] = $cnt;
		$node_list .= "{node [width=.1,height=.1,fontsize=8,shape=box,style=filled,color=skyblue,label=\"$net\"] $cnt}\n";
		
	}
	$cnt++;
	foreach ($routers as $router_id)
	{
		if (! isset($r[$router_id]))
		{
			$r[$router_id] = $cnt;
			if (isset($abr[$router_id]))
			{
				$node_list .= "{node [width=.1,height=.1,fontsize=8,shape=doublecircle,style=filled,color=blue,label=\"$router_id\"] $cnt}\n";
				
			} else
			{
				$node_list .= "{node [width=.1,height=.1,fontsize=8,shape=octagon,style=filled,color=darkorange,label=\"$router_id\"] $cnt}\n";
			}
			
		}
		$cnt++;
		
		$link_list .= "\"".$n[$net]."\" -- \"".$r[$router_id]."\"\n";
	}
	
}

$done = array();

if ($_REQUEST['do_area'] == 'yes')
{
  foreach ($area_r as $a => $routers)
  {
	$area_list .= "subgraph cluster_area$a {\nlabel=\"Area $a\";\n";
	foreach ($routers as $router_id)
	{
		if (!isset($abr[$router_id]))
		{
				if (!isset($done[$router_id]))
				{
					$area_list .= $r[$router_id]. ";\n";
					$done[$router_id] = 1;
				}
				
		}
	}	
	foreach($area_l[$a] as $net)
	{
		$area_list .= $n[$net] .";\n";
	}
	
	$area_list .= "}\n";
  }
}

$msg .= $node_list . $area_list . $link_list;

$msg .= "}\n";

#write to temp_file
$fh = fopen($tmpfile,'w') or die ('error opening a tmp file to write to');
fwrite($fh, $msg);
fclose($fh);

##############
#generate random name
###############

$charset = "abcdefghjkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
$fn = "";
for ($i=0; $i<8; $i++) $fn .= $charset[(mt_rand(0,(strlen($charset)-1)))];


# generate graph!
exec("$graphviz_path/$cmd -Tpng $tmpfile -o $output_dir/$fn.png");



unlink($tmpfile);


print("<a href=\"$output_html/$fn.png\"><img src=\"$output_html/$fn.png\"></a>");






?>
