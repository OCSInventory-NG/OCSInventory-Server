<?php
/*
 * Created on 28 janv. 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 * 
 */
 require_once('require/function_table_html.php');
if ($_POST['ok_for_change'] == "OK"){
	$new_file=str_replace("\\", "", $_POST['modif_txt']);
	$handle = fopen ("languages/".$_POST['langue_modif'].".txt", "w");
	fwrite($handle, $new_file);
	fclose($handle);
	echo "<script>alert('".$l->g(664)."');</script>"	;
}

 
 	?>
<script language=javascript>
function confirme(){
			if(confirm("<?php echo $l->g(713)?>")){
				document.getElementById('ok_for_change').value='OK';
				document.getElementById('modif_lang').submit();
			}
		}


function reLangRef(lang){
	document.getElementById('langue_ref').value=lang;
	document.getElementById('modif_lang').submit();	
}
function reLangmodif(lang){
	document.getElementById('langue_modif').value=lang;
	document.getElementById('modif_lang').submit();	
}
</script>
<?php
echo "<form name='modif_lang' id='modif_lang' method='POST' action='index.php?multi=35'><center>";
if (isset($_POST['langue_ref']) and $_POST['langue_ref'] != "")
$val_langue_ref=$_POST['langue_ref'];
echo "<input type='hidden' id='langue_ref' name='langue_ref' value='".$val_langue_ref."'>";
if (isset($_POST['langue_modif']) and $_POST['langue_modif'] != "")
$val_langue_modif=$_POST['langue_modif'];
echo "<input type='hidden' id='langue_modif' name='langue_modif' value='".$val_langue_modif."'>";
echo "<input type='hidden' id='ok_for_change' name='ok_for_change' value=''>";
echo "<table width='60%'>
<tr><td align=center><font color='blue'><b>".$l->g(714)."</b></font>
</td><td align=center><font color='red'><b>".$l->g(715)."</b></font></td></tr>";
 if ( $dir = @opendir("languages")) {
		echo "<br><br>";
		while($filename = readdir($dir)) {
			if( strstr ( $filename, ".txt") === false)
				continue;
			$langue[] = basename ( $filename,".txt");
			
		}
		closedir($dir);
	}
if (isset($langue)){
	$i=0;
	echo "<tr><td align=center>";
	while ($langue[$i]){
		echo "<a OnClick='reLangRef(\"".$langue[$i]."\")'><img src =\"languages/".$langue[$i].".png\" width=\"20\" height=\"15\" ></a>&nbsp;";
		$i++;
	}
	echo "</td>";
}

	if (isset($langue)){
	$i=0;
	echo "<td align=center>";
	while ($langue[$i]){
		echo "<a OnClick='reLangmodif(\"".$langue[$i]."\")'><img src =\"languages/".$langue[$i].".png\" width=\"20\" height=\"15\"></a>&nbsp;";
		$i++;
	}
	echo "</td></tr>";
}




if (isset($_POST['langue_ref']) and $_POST['langue_ref'] != ""){
 $fp = fopen ("languages/".$_POST['langue_ref'].".txt", "r");  
 $i=0;
 $contenu_du_fichier_ref="";
 while (!feof($fp)){
  $value=fgets ($fp);  
  if ($value != "")
  $contenu_du_fichier_ref .= $value;
 $i++;}
 fclose ($fp); 
}
if (isset($_POST['langue_modif']) and $_POST['langue_modif'] != ""){
 $fp = fopen ("languages/".$_POST['langue_modif'].".txt", "r");  
 $i=0;
 $contenu_du_fichier_modif="";
 while (!feof($fp)){
  $value=fgets ($fp);  
  if ($value != "")
  $contenu_du_fichier_modif .= $value;
 $i++;}
 fclose ($fp); 
}  
 echo "<tr><td align=center><textarea cols=40 rows=20 name='ref_txt' readonly>".$contenu_du_fichier_ref."</textarea></td><td><textarea cols=40 rows=20 name='modif_txt'>".$contenu_du_fichier_modif."</textarea></td></tr></table>";
   echo "<input type='button' name='update_txt' value='".$l->g(716)."' onclick='confirme();'>";

echo "</center></form>";

?>
