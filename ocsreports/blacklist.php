<?php
/*
 * this page makes it possible to seize the MAC addresses for blacklist
 */

//for delete 
if (isset($_GET['choise'])) $_POST['choose_blacklist']= $_GET['choise'];
//javascript 
?>
	<script language=javascript>
		function confirme(did){
			if(confirm("<?php echo $l->g(640)?> id "+did+" ?"))
				window.location="index.php?multi=<?php echo $_GET["multi"]?>&choise=<?php echo $_POST['choose_blacklist']?>&suppAcc="+did;
		}
		function convertToUpper(v_string){
         v_string.value=v_string.value.toUpperCase();
		}
		
		function codeTouche(evenement) {
        for (prop in evenement) {
                if(prop == 'which') return(evenement.which);
        }
        return(evenement.keyCode);
        }
		
		function pressePapierNS6(evenement,touche)
		{
        var rePressePapierNS = /[cvxz]/i;

        for (prop in evenement) if (prop == 'ctrlKey') isModifiers = true;
        if (isModifiers) return evenement.ctrlKey && rePressePapierNS.test(touche);
        else return false;
		}
			
		function scanTouche(evenement,exReguliere) {
        var reCarSpeciaux = /[\x00\x08\x0D\x03\x16\x18\x1A]/;
        var reCarValides = exReguliere;
        var codeDecimal  = codeTouche(evenement);
        var car = String.fromCharCode(codeDecimal);
        var autorisation = reCarValides.test(car) || reCarSpeciaux.test(car) || pressePapierNS6(evenement,car);
        var toto = autorisation;
        return autorisation;
        }		
	</script>
<?php 


//if choise is blacklist mac
if ($_POST['choose_blacklist'] == 'mac'){
	$table="blacklist_macaddresses";
	$champ="MACADDRESS";
	 //@ip = 6
	$nbr_champs=6;
	$print_entete=$l->g(653);	
	//js for control mac address
	$maj="onKeyPress='return scanTouche(event,/[0-9 a-f A-F]/)' 
		  onkeydown='convertToUpper(this)'
		  onkeyup='convertToUpper(this)' 
		  onblur='convertToUpper(this)'
		  onclick='convertToUpper(this)'";
	
	//max length input
	$maxlength=2;
	//text for input
	$text_enter=$l->g(654);
//if choise is blacklist serial
}elseif ($_POST['choose_blacklist'] == 'serial'){
	$table="blacklist_serials";
	$champ="SERIAL";
	$nbr_champs=1;
	$print_entete=$l->g(701);
	$maxlength=50;
	$text_enter=$l->g(702);
}
//cas of delete mac address or serial
if($_GET["suppAcc"]){
	mysql_query("delete from ".$table." where id=".$_GET["suppAcc"], $_SESSION["writeServer"]);
}
//cas of add mac address or serial
if($_POST["enre"]){	
	//var to cross the table of $_POST['champ_']
	$i=1;
	$mac_serial="";	
	while ($i<=$nbr_champs){
		//looking for error
		if (strlen($_POST['champ_'.$i]) != 2 and $_POST['choose_blacklist'] == 'mac')
		$erreur['champ_'.$i] = "erreur";	
		else //on crée l'adresse mac avec les ':'
		$mac_serial.=$_POST['champ_'.$i];
		//si on n'est pas dans le dernier champ, on rajoute entre les champs les ':'
		if ($i != $nbr_champs)
		$mac_serial.=":";
		$i++;			
	}
	
	//if no error
	if (!isset($erreur)){
		//insert into table the new mac address
		$sql="insert into ".$table." (".$champ.") value ('".$mac_serial."')";
		//no error
		if (mysql_query($sql, $_SESSION["writeServer"]))
		//message OK
		echo "<br><br><center><font face='Verdana' size=-1 color='green'><b>".$l->g(655)."</b></font></center><br>";
		else //message KO
		echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>".$l->g(656)."</b></font></center><br>";
		
	}else{//if error
	    //message KO
		echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>".$l->g(657)."</b></font></center><br>";
	}	
}

//include javascript file for control mac address
$direction=explode('/',$_SERVER['HTTP_REFERER']);
unset($direction[count($direction)-1]);
echo '<script type="text/javascript" src="'.implode("/",$direction).'/js/function.js"></script>';
$data_list_black['mac']=$l->g(95);
$data_list_black['serial']=$l->g(36);
//choise of blacklist: mac adress or serial
$toto=show_modif($data_list_black,"choose_blacklist",2,"black");
	
echo "<form name='black' method='POST' action='index.php?multi=32'>
<center>
<table width='60%'>
<tr><td align=right>".$l->g(700).":</td><td>".$toto."</td></tr></table></form>";



if (isset($print_entete)){
	printEnTete($print_entete);
	echo "<br> <form name='add_black_list' method='POST' action='index.php?multi=32'>
	<center>
	<table width='60%'>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$text_enter.":&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'>";
	$i=1;
	while ($i<=$nbr_champs)	
	{
		echo "<input size=".$maxlength." maxlength=".$maxlength." name='champ_".$i."' ".$maj." value='".$_POST['champ_'.$i]."' ".(isset($erreur['champ_'.$i])? "style='color:white; background-color:red;'" : "").">";
		if ($i != $nbr_champs)
		echo ":";
	$i++;
	}
	echo "</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
		<tr>
		<td colspan='2' align='center'>
			<input class='bouton' name='enre' type='submit' value=".$l->g(114)."> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
	</tr>
	
	</table></center>";
	$sql_list_black="select ID,".$champ." FROM ".$table." order by 1";
	$result = mysql_query($sql_list_black, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$title=$l->g(233);
	$i=0;
	while($colname = mysql_fetch_field($result))
		$entete[$i++]=$colname->name;
		$entete[$i]="Sup";
	$i=0;
	while($item = mysql_fetch_object($result)){
			$data[$i][$entete[0]]=$item ->$entete[0];
			$data[$i][$entete[1]]=$item ->$entete[1];
			$data[$i]['SUP']="<a href=# OnClick='confirme(\"".$data[$i][$entete[0]]."\");'><img src=image/supp.png></a>";
			$i++;
	}

	tab_entete_fixe($entete,$data,$title,"50","300");
	
	echo "<input type='hidden' name='choose_blacklist' value='".$_POST['choose_blacklist']."'>";
		echo "</form><br>";
}
 
?>
