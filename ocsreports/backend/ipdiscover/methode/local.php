<?php

//nom de la page
$name="local.php";
connexion_local();
mysql_select_db($db_ocs,$link_ocs);
 
$req="select distinct ipsubnet,s.name,s.id 
			from networks n left join subnet s on s.netid=n.ipsubnet
			,accountinfo a
		where a.hardware_id=n.HARDWARE_ID 
			and n.status='Up'";
if (isset($_SESSION["mesmachines"]) and $_SESSION["mesmachines"] != '')
		$req.="	and ".$_SESSION["mesmachines"]." order by ipsubnet";
$res=mysql_query($req, $link_ocs) or die(mysql_error($link_ocs));
while ($row=mysql_fetch_object($res)){
	$list_ip[$row->id][$row->ipsubnet]=$row->name;		
}
$id_subnet="ID";
if (!isset($list_ip))
$INFO="NO_IPDICOVER";

?>
