<?php
 require_once('require/function_table_html.php');
 if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
 //définition des onglets
//$data_on['GUI_LOGS']="Logs de l'interface";
$_POST['onglet'] == "";
$form_name = "logs";
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
//onglet($data_on,$form_name,"onglet",2);
echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td colspan=10></td></tr>";
echo "<tr><td align=center>".$l->g(950)."</td><td align=center>".$l->g(951)."</td><td align=center>".$l->g(952)."</td><td align=center>".$l->g(953)."</td></tr>";
if ($_POST['onglet'] == 'GUI_LOGS' or $_POST['onglet'] == ""){
//	if ($_SESSION['LOG_DIR'] == '')
//	$Directory="";
//	else
	$Directory=$_SESSION['LOG_DIR']."/";
	ScanDirectory($Directory,"csv");
}
echo "</td></tr></table>";
echo "</tr></td></form>";

function ScanDirectory($Directory,$Filetype){

  $MyDirectory = opendir($Directory) or die('Erreur');
	while($Entry = @readdir($MyDirectory)) {

		if (substr($Entry,-strlen($Filetype)) == $Filetype){
			echo "<tr BGCOLOR='#f2f2f2'>";
			echo "<td align=center><a href='cvs.php?log=".$Entry."&rep=".$Directory."'>".$Entry."</td>";
			echo "<td align=center>".date ("d M Y H:i:s.", filectime($Directory.$Entry))."</td>";
			echo "<td align=center>".date ("d M Y H:i:s.", filemtime($Directory.$Entry))."</td>";
			echo "<td align=center>".filesize($Directory.$Entry)." ko</td>";
			echo "</tr>";
		}
		
	}
  closedir($MyDirectory);
}

?>
