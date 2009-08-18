<?php
require_once('require/function_search.php');

 if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
if ($_POST['onglet'] == "" or !isset($_POST['onglet']))
$_POST['onglet']=3;
 //définition des onglets
$data_on[1]=$l->g(140);
$data_on[2]=$l->g(141);
$data_on[3]=$l->g(142);
$data_on[4]=$l->g(244);
$form_name = "admins";
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
onglet($data_on,$form_name,"onglet",4);
$table_name="TAB_ACCESSLVL".$_POST['onglet'];	
if (isset($_POST['VALID_MODIF'])){
	if ($_POST['CHANGE'] != ""){
		$sql_update="update operators set ACCESSLVL = '".$_POST['CHANGE']."' where ID='".$_POST['MODIF_ON']."'";
		mysql_query($sql_update, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));		
	$tab_options['CACHE']='RESET';
	}else
	echo "<div  align=center><font color=red size=4><b>".$l->g(909)."</b></font></div>";
	
}
//suppression d'une liste de users
if (isset($_POST['del_check']) and $_POST['del_check'] != ''){
	$list = "'".implode("','", explode(",",$_POST['del_check']))."'";
	$sql_delete="delete from codeunite where login in (".$list.")";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$sql_delete="delete from operators where id in (".$list.")";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$tab_options['CACHE']='RESET';	
}


//suppression d'un user
if (isset($_POST['SUP_PROF']) and $_POST['SUP_PROF'] != ''){
	$sql_delete="delete from codeunite where login='".$_POST['SUP_PROF']."'";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$sql_delete="delete from operators where id= '".$_POST['SUP_PROF']."'";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$tab_options['CACHE']='RESET';
}
//ajout d'un user
if (isset($_POST['Valid_modif_x'])){
	//on supprime l'utilisateur si celui-ci existe
	$sql="delete from operators where id= '".$_POST['SELECTION']."'";
	mysql_query($sql, $_SESSION["writeServer"]);
	//on ajoute l'utilisateur avec son profil et différentes infos
	$sql=" insert into operators (id,lastname,accesslvl) 
			value ('".$_POST['SELECTION']."','".$_POST['GRADE']." ".$_POST['SELECTION']."(".$_POST['ABREGE'].")','".$_POST['PROFIL']."')";
		mysql_query($sql, $_SESSION["writeServer"]);
		//regénération du cache
		$tab_options['CACHE']='RESET';
	}

echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
//echo "<tr><td align=center><b>CREATION / SUPPRESSION DES ".$data_on[$_POST['onglet']]."</b></td></tr>";


//add user
if ($_POST['onglet'] == 4){
//VERSION INTERNET A DEVELOPPER

}else{
	echo "<tr><td align=center>";
	//affichage
	$list_fields= array('ID'=>'ID',
						'FIRSTNAME'=>'FIRSTNAME',
						'LASTNAME'=>'LASTNAME',
						'ACCESSLVL'=>'ACCESSLVL',
						'COMMENTS'=>'COMMENTS',
						'SUP'=>'ID',
						'MODIF'=>'ID',
						'CHECK'=>'ID');
	$list_col_cant_del=array('ID'=>'ID','SUP'=>'SUP','MODIF'=>'MODIF','CHECK'=>'CHECK');
	$default_fields=$list_fields; 
	$queryDetails = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'MODIF' and $key != 'CHECK')
		$queryDetails .= $key.',';		
	} 
	$queryDetails=substr($queryDetails,0,-1);
	$queryDetails .= " FROM operators where ACCESSLVL=".$_POST['onglet'];
	$tab_options['FILTRE']=array('LASTNAME'=>'LASTNAME','ID'=>'ID');
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,100,$tab_options);
		//traitement par lot
	$img['image/sup_search.png']=$l->g(162);
	echo "<script language=javascript>
			function garde_check(image,id)
			 {
				var idchecked = '';
				for(i=0; i<document.".$form_name.".elements.length; i++)
				{
					if(document.".$form_name.".elements[i].name.substring(0,5) == 'check'){
				        if (document.".$form_name.".elements[i].checked)
							idchecked = idchecked + document.".$form_name.".elements[i].name.substring(5) + ',';
					}
				}
				idchecked = idchecked.substr(0,(idchecked.length -1));
				confirme('',idchecked,\"".$form_name."\",\"del_check\",\"".$l->g(900)."\");
			}
		</script>";
		echo "<table align='center' width='30%' border='0'>";
		echo "<tr><td>";
		//foreach ($img as $key=>$value){
			echo "<td align=center><a href=# onclick=garde_check(\"image/sup_search.png\",\"\")><img src='image/sup_search.png' title='".$l->g(162)."' ></a></td>";
		//}
	 echo "</tr></tr></table>";
	 echo "<input type='hidden' id='del_check' name='del_check' value=''>";
}

echo "</td></tr></table>";
if ($_POST['MODIF'] != ''){
	$choix=show_modif(array(1=>$data_on[1],2=>$data_on[2],3=>$data_on[3]),'CHANGE',2);
	echo "<tr><td align=center><b>".$l->g(911)."<font color=red> ".$_POST['MODIF']." </font></b>".$choix." <input type='submit' name='VALID_MODIF' value='".$l->g(910)."'></td></tr>";
	echo "<input type='hidden' name='MODIF_ON' value='".$_POST['MODIF']."'>";
}
echo "</table>";
echo "</form>";
?>
