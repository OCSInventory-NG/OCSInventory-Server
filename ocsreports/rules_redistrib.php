<?php
/*
 * Created on 23 oct. 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 require ('fichierConf.class.php');
require('req.class.php');
require_once('require/function_table_html.php');
require_once('require/function_server.php');
if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  )
	die("FORBIDDEN");
	
if ($_POST['reset'])
unset($_GET);	

if (isset($_GET['up']) or isset($_GET['down']))
{
	if (isset($_GET['up']))
	$updown=$_GET['up'];
	else
	$updown=$_GET['down'];
	
	$reqprio = "SELECT priority,rule FROM download_affect_rules WHERE ID=".$updown;
	$resprio = mysql_query( $reqprio, $_SESSION["readServer"]);
	$valprio = mysql_fetch_array( $resprio );
	if (isset($_GET['up'])){
	$idprio = $valprio['priority']+1;
	}
	elseif (isset($_GET['down'])){
	$idprio = $valprio['priority']-1;
	}
	if ($idprio>=0){
		mysql_query("update download_affect_rules set priority= ".$idprio." where ID=".$updown, $_SESSION["writeServer"]);
		$reqallprio = "SELECT priority FROM download_affect_rules WHERE ID !=".$updown." and rule=".$valprio['rule'];
		$resallprio = mysql_query( $reqallprio, $_SESSION["readServer"]);
		$val_prec="";
		while($item = mysql_fetch_object($resallprio)){			
		if ($item->priority == $idprio or isset($val_prec[$item->priority])){
			echo "<div align='center'><font color = 'red' size=4><b>".$l->g(669)."</b></font></div>";
			break;
		}
		$val_prec[$item->priority]=$item->priority;
		}
	}
		
}

//modif for rules
if (isset($_POST['Valid_modif_x']) and $_POST['WHERE'] == "MODIFRULE"){
	$reqGetId = "SELECT id FROM download_affect_rules WHERE rule_name='".$_POST['NAME']."' and id !=".$_POST["ID"];
	$resGetId = mysql_query( $reqGetId, $_SESSION["readServer"]);
	if( $valGetId = mysql_fetch_array( $resGetId ) )
		$idGroupServer = $valGetId['id'];

	if (trim($_POST['NAME']) != ""){
		if (!isset($idGroupServer))//update server's group
		{

			mysql_query("update download_affect_rules set rule_name= '".$_POST['NAME']."' where rule=".$_POST["ID"], $_SESSION["writeServer"]);
		}
		else //error
		echo "<script>alert('".$l->g(670)."');</script>";
	}
	else //error
	echo "<script>alert('".$l->g(671)."');</script>";	
}
if (isset($_GET['suppAcc']) and !isset($_POST['Add_new_rule'])){
	if ($_GET['orig'] == "rule"){
		$result = mysql_query("select rule from download_affect_rules where rule=".$_GET["suppAcc"], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$item = mysql_fetch_object($result);
		if (isset($item->rule))
		{
			//attention: QUE SE PASSE-T-IL QUAND ON SUPPRIME UNE REGLE ALORS QU ELLE EST ACTIVE SUR UN GROUPE DE SERVEUR??
			$sql="delete from download_affect_rules where rule= ".$_GET["suppAcc"];	
			mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		}
		else
		echo "<script>alert('".$l->g(672)."');</script>";
		
	}
	else{
		mysql_query("delete from download_affect_rules where id=".$_GET["suppAcc"], $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		
	}	
	
	
}


if ($_POST['Add_new_rule'])
{
	$result = mysql_query("select id from download_affect_rules where rule_name='".$_POST["RULE_NAME"]."'", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item2 = mysql_fetch_object($result);
	if (!isset($item2->id))
	{
		$sql="select max(RULE) as ID_RULE from download_affect_rules";
		$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$item = mysql_fetch_object($result);
		$id_rule=$item -> ID_RULE;
		$id_rule++;
		$i=1;
		while ($_POST['PRIORITE_'.$i]){
			if ($_POST['CFIELD_'.$i] != "")
			{
				$sql="insert into download_affect_rules (RULE,RULE_NAME,PRIORITY,CFIELD,OP,COMPTO,SERV_VALUE) 
				value (".$id_rule.",'".$_POST['RULE_NAME']."',".$_POST['PRIORITE_'.$i].",'".$_POST['CFIELD_'.$i]."','".$_POST['OP_'.$i]."','".$_POST['COMPTO_'.$i]."','".$_POST['COMPTO_TEXT_'.$i]."')";
				mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
				
			}
		$i++;
		}
	}
	else{
		echo "<script>alert('".$l->g(670)."');</script>";		
	}
	
}

?>
	<script language=javascript>
		function confirme(did,lbl,orig,get,get_value){
			if(confirm("<?php $l->g(640)?> "+lbl+" "+did+" ?"))
				window.location="index.php?multi=<?php echo $_GET["multi"]?>&suppAcc="+did+"&orig="+orig+"&"+get+"="+get_value;
		}
	</script>
<?php 
$sql="select distinct rule,rule_name from download_affect_rules";
$sql=tri($sql);
$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$i=0;
	while($colname = mysql_fetch_field($result)){
		$entete[$i++]=$colname->name;
		$lien[]=$colname->name;
	}
		$entete[$i++]="Sup";
		$entete[$i++]="Mod";
		$entete[$i]="Visu";
	$i=0;
	while($item = mysql_fetch_object($result)){
			$data[$i]['RULE']=$item ->rule;
			$data[$i]['RULE_NAME']=$item ->rule_name;
			$data[$i]['SUP']="<a href=# OnClick='confirme(\"".$item ->rule."\",\"la règle\",\"rule\",\"\",\"\");'><img src=image/supp.png></a>";
			$data[$i]['MODIF']="<a href='index.php?multi=34&modifrule=".$i."'><img src=image/modif_tab.png ></a>";
			$data[$i]['VISU']="<a href='index.php?multi=34&viewrule=".$i."'><img src=image/oeil.png  ></a>";
			$i++;
	}
	tab_entete_fixe($entete,$data,$l->g(673),"50","300",$lien);
	$tab_nom="<table align=\"center\" bgcolor=\"#C7D9F5\" style=\"border: solid thin; border-color:#A1B1F9\"><tr><td >".$l->g(674).":</td><td><input type=\"text\" name=\"RULE_NAME\" value=\"\" onFocus=\"this.style.backgroundColor=\'white\';\"></td></tr></table><br>";
	$tab="<table align=\"center\">";
	$tab.="<tr bgcolor=\"#C7D9F5\"><td><div id=\"ENTETE_1'+i+'\" style=\"display:none\">".$l->g(675)."</div></td><td><div id=\"ENTETE_2'+i+'\" style=\"display:none\">".$l->g(676)."</div></td><td><div id=\"ENTETE_3'+i+'\" style=\"display:none\">".$l->g(677)."</div></td><td><div id=\"ENTETE_4'+i+'\" style=\"display:none\">".$l->g(678)."</div></td></tr>";
	$tab.="<tr><td><input type=\"text\" name=\"PRIORITE_'+i+'\" value='+i+'></td>";
	$tab.="<td><select id=\"CFIELD\" name=\"CFIELD_'+i+'\" ><option value=\"\">".$l->g(32)."</option><option value=\"NAME\">".$l->g(679)."</option>";
	$tab.= "<option value=\"IPADDRESS\">@IP</option><option value=\"IPSUBNET\">IPSUBNET</option><option value=\"WORKGROUP\">".$l->g(680)."</option>";
	$tab.= "<option value=\"USERID\">".$l->g(681)."</option></select></td>";
	$tab.="<td><select id=\"OP\" name=\"OP_'+i+'\" ><option value=\"\">".$l->g(32)."</option><option value=\"EGAL\">=</option><option value=\"DIFF\"><></option>";
	$tab.= "<option value=\"LIKE\">LIKE</option></select></td>";
	$tab.="<td><select id=\"COMPTO\" name=\"COMPTO_'+i+'\" ><option value=\"\">".$l->g(32)."</option>";
	$tab.="<option value=\"NAME\">".$l->g(679)."</option><option value=\"IPADDRESS\">@IP</option>";
	$tab.="<option value=\"IPSUBNET\">IPSUBNET</option><option value=\"WORKGROUP\">".$l->g(680)."</option>";
    $tab.="<option value=\"USERID\">".$l->g(681)."</option>";
     $tab.="</select>";
     //"<a  onclick=\"document.getElementById(\'TEXT_VALUE_'+i+'\').style.display=\'block\';\" title=\"Cliquez ici pour entrer une valeur\">++</a></td>";
   // $tab.="<td><div id=\"TEXT_VALUE_'+i+'\" style=\"display:none\"><input type=\"text\" name=\"COMPTO_TEXT_'+i+'\" value=\"\"><a  onclick=\"document.getElementById(\'TEXT_VALUE_'+i+'\').style.display=\'none\';\" title=\"Ne plus afficher le champ valeur\">--</a></div></td>";
	$tab.= "</tr></table>";
echo "<script>
function create_champ(i)
		{
			var i2 = i + 1;
			if (i==1){
			document.getElementById('rule_name').innerHTML = '".$tab_nom."';}			
			document.getElementById('leschamps_'+i).innerHTML = '".$tab."';
			if (i==1){
			document.getElementById('ENTETE_1'+i).style.display='block';
			document.getElementById('ENTETE_2'+i).style.display='block';
			document.getElementById('ENTETE_3'+i).style.display='block';
			document.getElementById('ENTETE_4'+i).style.display='block';
			}
			document.getElementById('leschamps_'+i).innerHTML += (i <= 10) ? '<span id=\"leschamps_'+i2+'\"><a href=\"javascript:create_champ('+i2+')\"><font color=green>".$l->g(682)."</font></a>&nbsp<a href=\"\"><font color=\"red\">".$l->g(113)."</font><br></a><br><input type=\"submit\" name=\"Add_new_rule\" value=\"".$l->g(683)."\"></span>' : '';
}
</script>";
?>
<script>
function check() {
	var msg = "";
	var behing_msg = "<?php $l->g(684) ?> \n";
		if (document.ADD.RULE_NAME.value == "")	{
		document.ADD.RULE_NAME.style.backgroundColor = "RED";
		msg += "NOM REGLE.\n";
	}
	var nb_lign=ADD.getElementsByTagName("select").length /3;
 	var i=1;
 	while (i<nb_lign+1){
 
 	champs = new Array('PRIORITE_'+i,'CFIELD_'+i,'OP_'+i,'COMPTO_'+i);
 	for each (var item in champs) {
 		if (document.ADD.eval(item).value == ""){
 		document.ADD.eval(item).style.backgroundColor = "RED";
 		msg += item+"\n";
 		}
		else
		document.ADD.eval(item).style.backgroundColor = "";
	}
	i++;
 	}
	
	if (msg == "") return(true);
	else	{
		msg = behing_msg+ msg;
		alert(msg);
		return(false);
	}
}
</script>



<?
if ((!isset($_GET['modifrule']) 
	and !isset($_GET['viewrule'])) or (isset($_POST['Valid_modif_x']) or isset($_POST['Reset_modif_x']))){
echo "<form method=\"POST\" name=\"ADD\" onSubmit=\"return check()\">";
echo "<div align='center'>
<span id=\"rule_name\"></span>
<span id=\"leschamps_1\"><a href=\"javascript:create_champ(1)\"><input type='submit'  value='".$l->g(685)."'></a></span></div>";
echo "</form>";
}

if (isset($_GET['modifrule']) and !isset($_POST['Valid_modif_x']) and !isset($_POST['Reset_modif_x']) and !isset($_POST['Add_new_rule'])){
	$tab_name[0]="NAME";
	$tab_typ_champ[0]['DEFAULT_VALUE']=$data[$_GET['modifrule']]['RULE_NAME'];
	$tab_typ_champ[0]['INPUT_NAME']="NAME";
	$tab_typ_champ[0]['INPUT_TYPE']=0;
	$tab_hidden['ID']=$data[$_GET['modifrule']]['RULE'];
	$tab_hidden['WHERE']="MODIFRULE";
	tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden);	
}

if (isset($_GET['viewrule']) and !isset($_POST['Valid_modif_x']) and !isset($_POST['Reset_modif_x']) and !isset($_POST['Add_new_rule'])){
$result = mysql_query("select ID,PRIORITY,CFIELD,OP,COMPTO,SERV_VALUE from download_affect_rules where rule='".$data[$_GET['viewrule']]['RULE']."' order by PRIORITY", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$i=0;
	while($colname = mysql_fetch_field($result))
		$entete2[$i++]=$colname->name;
		$entete2[$i++]="Sup";
	//	$entete2[$i]="Mod";
	$i=0;
	while($item = mysql_fetch_object($result)){
		if (($item ->PRIORITY)>0){
		$down="<a onclick='window.location.href = \"index.php?multi=".$_GET['multi']."&viewrule=".$_GET['viewrule']."&down=".$item ->ID."\"'><font color=green>-</font></a>";	
		}else
		$down="";
		$up="<a onclick='window.location.href = \"index.php?multi=".$_GET['multi']."&viewrule=".$_GET['viewrule']."&up=".$item ->ID."\"'><font color=red>+</font></a>";
			$data2[$i]['ID']=$item ->ID;
			$data2[$i]['PRIORITY']=$up.$item ->PRIORITY.$down;
			$data2[$i]['CFIELD']=$item ->CFIELD;
			$data2[$i]['OP']=$item ->OP;
			$data2[$i]['COMPTO']=$item ->COMPTO;
			$data2[$i]['SERV_VALUE']=$item ->SERV_VALUE;
			$data2[$i]['SUP']="<a href=# OnClick='confirme(\"".$item ->ID."\",\"".$l->g(686)."\",\"condition\",\"viewrule\",\"".$_GET['viewrule']."\");'><img src=image/supp.png></a>";
			//$data2[$i]['MODIF']="<a href='index.php?multi=34&modifcondition=".$i."'><img src=image/modif_tab.png ></a>";
			$i++;
	}
	tab_entete_fixe($entete2,$data2,$l->g(641),"60","300");		
	
}

//if (isset($_GET['modifrule']) or isset($_GET['viewrule']))
//	echo "<div align='center'><input type='reset' name='reset' value='Annuler' onclick='window.location.href = \"index.php?multi=".$_GET['multi']."\";'></div>";
////if (isset($_GET['modifcondition']) and !isset($_POST['Valid_modif_x']) and !isset($_POST['Reset_modif_x']) and !isset($_POST['Add_new_rule'])){
//	$tab_name[0]="PRIORITE";
//	$tab_typ_champ[0]['DEFAULT_VALUE']=$data2[$_GET['modifcondition']]['ID'];
//	$tab_typ_champ[0]['INPUT_NAME']="PRIORITY";
//	$tab_typ_champ[0]['INPUT_TYPE']=0;
//	$tab_hidden['ID']=$data2[$_GET['modifcondition']]['ID'];
//	$tab_hidden['WHERE']="MODIFCONDITION";
//	tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden);	
//}
?>
