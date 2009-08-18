<?php
/*
 * Page de fonction communes aux détails d'une machine 
 * 
 */
 
//fonction de traitement de l'ID envoyé
function info($GET,$post_systemid){
	//traitement de l'envoi de l'id par post
	if ($post_systemid != '')
		$systemid = $_POST['systemid'];
	//ajout de la possibilité de voir une machine par son deviceid
	if (isset($GET['deviceid']) and !isset($systemid)){
		$querydeviceid = "SELECT ID FROM hardware WHERE deviceid='".strtoupper ($GET['deviceid'])."'";
		$resultdeviceid = mysql_query($querydeviceid, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
		$item = mysql_fetch_object($resultdeviceid);	
		$GET['systemid']=$item -> ID;
		//echo $GET['systemid'];
		if ($GET['systemid'] == "")
			return "Please Supply A Device ID";
	}
	//si le systemid de la machine existe
	if (isset($GET['systemid']) and !isset($systemid))
	$systemid = $GET['systemid'];
	//problème sur l'id
	//echo $systemid;
	if ($systemid == "" or !is_numeric($systemid))
		return "Please Supply A System ID";
		//recherche des infos de la machine
		$querydeviceid = "SELECT * FROM hardware h left join accountinfo a on a.hardware_id=h.id
						 WHERE h.id=".$systemid." ";
		if ($_SESSION["lvluser"] == ADMIN and isset($_SESSION['mesmachines']) and $_SESSION['mesmachines'] != '')			 
				$querydeviceid .= " and (".$_SESSION['mesmachines']." or a.tag is null or a.tag='')";
		$resultdeviceid = mysql_query($querydeviceid, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
		$item = mysql_fetch_object($resultdeviceid);
		if ( $item -> ID == ""){
			return $l->g(837);	
		}
		return $item;
	
}


function subnet_name($systemid){
	if (!is_numeric($systemid))
	return false;	
	$reqSub = "select NAME,NETID from subnet left join networks on networks.ipsubnet = subnet.netid 
				where  networks.status='Up' and hardware_id=".$systemid;
	$resSub = mysql_query($reqSub, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($valSub = mysql_fetch_object( $resSub )){
		
		$returnVal[]=$valSub->NAME."  (".$valSub->NETID.")";
	}	
	return 	$returnVal;
}

function print_item_header($text)
{
	echo "<br><br><table align=\"center\"  width='100%'  cellpadding='4'>";
	echo "<tr>";
	echo "<td align='center' width='100%'><b><font color='blue'>".strtoupper($text)."</font></b></td>";
	echo "</tr>";
	echo "</table><br>";	
}

function bandeau($data,$lbl){
	$nb_col=2;
	echo "<table width='100%' border='0' bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9' align=center ><tr><td >
			<table align=center border='0' width='95%'><tr>";
	$i=0;
	foreach ($data as $name=>$value){
		if ($i == $nb_col){
			echo "</tr><tr>";
			$i=0;			
		}
		echo "<td >&nbsp;<b>".$lbl[$name]." :</b></td><td >".$value."</td>";
		$i++;
	}
	echo "</tr></table></td></tr></table>";	
}
?>