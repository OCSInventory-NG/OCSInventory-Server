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
//Modified on $Date: 2007-07-22 18:05:44 $$Author: plemmet $($Revision: 1.6 $)

echo"<br></div><table class='headfoot'>";
echo"<tr height=25px><td align='center'>&nbsp;";
if( function_exists("getmicrotime") ) {
	$fin = getmicrotime();
	if($_SESSION["debug"]==true) {
		echo "<b>CACHE:&nbsp;<font color='".($_SESSION["usecache"]?"green'><b>ON</b>":"red'><b>OFF</b>")."</font>&nbsp;&nbsp;&nbsp;<font color='black'><b>".round($fin-$debut, 3) ." secondes</b></font>&nbsp;&nbsp;&nbsp;";
		echo "<script language='javascript'>document.getElementById(\"tps\").innerHTML=\"<font color='black'><b>".round($fin-$debut, 3)." secondes</b></font>\"</script>";
	}
}

echo"</td></tr></table>";
?>
</body>
</html>
