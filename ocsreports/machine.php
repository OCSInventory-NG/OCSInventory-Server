<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2007/02/08 16:59:15 $$Author: plemmet $($Revision: 1.15 $)
@session_start();
unset($_SESSION['LANGUAGE']);
$ban_head='no';
require_once("header.php");
require('require/function_opt_param.php');
require('require/function_graphic.php');
require_once('require/function_machine.php');

//recherche des infos de la machine
$item=info($_GET,$_POST['systemid']);
if (!is_object($item)){
	echo "<center><B><font color=red size=4>".$item."</font></B></center>";
	die();
}
//you can't view groups'detail by this way
if ( $item->DEVICEID == "_DOWNLOADGROUP_"
	or $item->DEVICEID == "_SYSTEMGROUP_"){
	die('FORBIDDEN');	
}
$systemid=$item -> ID;

// COMPUTER SUMMARY
$lbl_affich=array('NAME'=>$l->g(49),'WORKGROUP'=>$l->g(33),'USERDOMAIN'=>$l->g(557),'IPADDR'=>$l->g(34),
					'USERID'=>$l->g(24),'SWAP'=>$l->g(50),'OSNAME'=>$l->g(274),'OSVERSION'=>$l->g(275),
					'OSCOMMENTS'=>$l->g(286),'WINCOMPANY'=>$l->g(51),'WINOWNER'=>$l->g(348),
					'WINPRODID'=>$l->g(111),'WINPRODKEY'=>$l->g(553),'USERAGENT'=>$l->g(357),
					'MEMORY'=>$l->g(26),'LASTDATE'=>$l->g(46),'LASTCOME'=>$l->g(820),'DESCRIPTION'=>$l->g(636),
					'NAME_RZ'=>$l->g(304)
					);					
foreach ($lbl_affich as $key=>$lbl){
	if ($key == "MEMORY"){
				$sqlMem = "SELECT SUM(capacity) AS 'capa' FROM memories WHERE hardware_id=$systemid";
		$resMem = mysql_query($sqlMem, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$valMem = mysql_fetch_array( $resMem );
		if( $valMem["capa"] > 0 )
			$memory = $valMem["capa"];
		else
			$memory = $item->$key;
		$data[$key]=$memory;
	}elseif ($key == "LASTDATE" or $key == "LASTCOME"){
		$data[$key]=dateTimeFromMysql(textDecode($item->$key));
	}
	elseif ($key == "NAME_RZ"){
		$data[$key]="";
		$data_RZ=subnet_name($systemid);
		$nb_val=count($data_RZ);
		if ($nb_val == 1){
			$data[$key]=$data_RZ[0];
		}elseif(isset($data_RZ)){	
			foreach($data_RZ as $index=>$value){
				$data[$key].=$index." => ".$value."<br>";			
			}	
		}	
	}
	elseif ($item->$key != '')
		$data[$key]=$item->$key;
}

$bandeau=bandeau($data,$lbl_affich);

//récupération des pluggins existants
$Directory=$_SESSION['plugin_rep']."computor_detail/";
if (file_exists($Directory."config.txt")){
		$fd = fopen ($Directory."config.txt", "r");
		$capture='';
		while( !feof($fd) ) {				
			$line = trim( fgets( $fd, 256 ) );
			if (substr($line,0,2) == "</")
				$capture='';
			if ($capture == 'OK_ORDER')
				$list_pluggins[]=$line;
			if ($capture == 'OK_LBL'){				
				$tab_lbl=explode(":", $line);
				$list_lbl[$tab_lbl[0]]=$tab_lbl[1];
			}				
			if ($capture == 'OK_ISAVAIL'){
				$tab_isavail=explode(":", $line);
				$list_avail[$tab_isavail[0]]=$tab_isavail[1];
			}
			if ($line{0} == "<"){
				$capture = 'OK_'.substr(substr($line,1),0,-1);
			}
			//echo substr($line,0,5);
			flush();					
		}				
	fclose( $fd );
	//print_r($list_pluggins);
	}

//par défaut, on affiche les données admininfo
if (!isset($_GET['option'])){
	$_GET['option']="cd_admininfo";
}
$i=0;
echo "<br><br><table width='90%' border=0 align='center'><tr align=center>";
$nb_col=array(10,13,13);
$j=0;
$index_tab=0;
//intitialisation du tableau de plugins
$show_all=array();
while ($list_pluggins[$i]){
	unset($valavail);
	//vérification de l'existance des données
	if (isset($list_avail[$list_pluggins[$i]])){
		$sql_avail="select count(*) from ".$list_avail[$list_pluggins[$i]]." where hardware_id=".$systemid;
		$resavail = mysql_query( $sql_avail, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$valavail = mysql_fetch_array($resavail);
	}
	if ($j == $nb_col[$index_tab]){
		echo "</tr></table><table width='90%' border=0 align='center'><tr align=center>";
		$index_tab++;
		$j=0;
	}
	//echo substr(substr($list_lbl[$list_pluggins[$i]],2),0,-1);
	echo "<td align=center>";
	if (!isset($valavail[0]) or $valavail[0] != 0){
		//liste de toutes les infos de la machine
		$show_all[]=$list_pluggins[$i];
		$href = "<a href='machine.php?systemid=".$systemid."&option=".$list_pluggins[$i]."'>";
		$fhref = "</a>";
	}else{
		$href = "";
		$fhref = "";
	}
	echo $href."<img title=\"";
	
	if (substr($list_lbl[$list_pluggins[$i]],0,2) == 'g(')
	echo $l->g(substr(substr($list_lbl[$list_pluggins[$i]],2),0,-1));
	else
	echo $list_lbl[$i];
	echo "\" src='pluggins/computor_detail/img/";
	$list_pluggins[$i];
	if (isset($valavail[0]) and $valavail[0] == 0){
		if (file_exists($Directory."/img/".$list_pluggins[$i]."_d.png"))
			echo $list_pluggins[$i]."_d.png";
		else
			echo "cd_default_d.png";
	}
	elseif ($_GET['option'] == $list_pluggins[$i]){
		if (file_exists($Directory."/img/".$list_pluggins[$i]."_a.png"))
			echo $list_pluggins[$i]."_a.png";
		else
			echo "cd_default_a.png";		
	}
	else{
		if (file_exists($Directory."/img/".$list_pluggins[$i].".png"))
			echo $list_pluggins[$i].".png";
		else
			echo "cd_default.png";
		
	}
	echo "'/>".$fhref."</td>";
	$j++;
 	$i++;	
}
echo "</tr></table><br><br>";
if ($_GET['tout'] == 1){
	$list_plugins_4_all=0;
	while (isset($show_all[$list_plugins_4_all])){
		include ($Directory."/".$show_all[$list_plugins_4_all]."/".$show_all[$list_plugins_4_all].".php");	
		$list_plugins_4_all++;
	}
	
}else{
	if (file_exists($Directory."/".$_GET['option']."/".$_GET['option'].".php"))
		include ($Directory."/".$_GET['option']."/".$_GET['option'].".php");
}

echo "<br><table align='center'> <tr><td width =50%>";
echo "<a style=\"text-decoration:underline\" onClick=print()><img src='image/imprimer.png' title='".$l->g(214)."'></a></td>";


if(!isset($_GET["tout"]))
		echo"<td width=50%><a style=\"text-decoration:underline\" href=\"machine.php?systemid=".urlencode(stripslashes($systemid))."&tout=1\"><img width='60px' src='image/ttaff.png' title='".$l->g(215)."'></a></td>";
		
echo "</tr></table></body>";
echo "</html>";
exit;


?>