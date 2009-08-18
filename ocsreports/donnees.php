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
//Modified on $Date: 2007/02/08 15:53:24 $$Author: plemmet $($Revision: 1.7 $)

$user=$_SESSION["loggeduser"];
if( isset( $_GET["fuser"] ) ) {
	unset($_SESSION["mesmachines"]);
}
if($_SESSION["lvluser"]==SADMIN||$_SESSION["lvluser"]==LADMIN) {

	$mesMachines="";
	$_SESSION["mesmachines"] = "";
}
else
 if( ! isset($_SESSION["mesmachines"] )) {
	$mesMachines = "a.".TAG_NAME." IN ('".@implode("','",$list_Supportcu)."') ";	
	$_SESSION["mesmachines"] = $mesMachines;
}
else {
	$mesMachines = $_SESSION["mesmachines"];
}

?>