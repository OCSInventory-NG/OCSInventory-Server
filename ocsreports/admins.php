<?php
require_once('require/function_search.php');

 if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
if ($_POST['onglet'] == "" or !isset($_POST['onglet']))
$_POST['onglet']=3;
 //d�finition des onglets
$data_on[1]=$l->g(242);
$data_on[2]=$l->g(243);
$data_on[3]=$l->g(619);
$data_on[4]=$l->g(244);

//liste des profils
$list_profil[1]=$l->g(242);
$list_profil[2]=$l->g(243);
$list_profil[3]=$l->g(619);

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
	$sql_delete="delete from tags where login in (".$list.")";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$sql_delete="delete from operators where id in (".$list.")";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$tab_options['CACHE']='RESET';	
}


//suppression d'un user
if (isset($_POST['SUP_PROF']) and $_POST['SUP_PROF'] != ''){
	$sql_delete="delete from tags where login='".$_POST['SUP_PROF']."'";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$sql_delete="delete from operators where id= '".$_POST['SUP_PROF']."'";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$tab_options['CACHE']='RESET';
}
//ajout d'un user
if (isset($_POST['Valid_modif_x'])){
	if (trim($_POST['ID']) == "")
		$ERROR=$l->g(997);
	if (!array_key_exists($_POST['ACCESSLVL'], $list_profil))
		$ERROR=$l->g(998);
	if (!isset($ERROR)){
		$sql="select id from operators where id= '".$_POST['ID']."'";
		$res=mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$row=mysql_fetch_object($res);
		if (isset($row->id)){
			$ERROR=$l->g(999);
			echo "<script>alert('".$ERROR."')</script>";
		}else{
		
			$sql=" insert into operators (id,firstname,lastname,accesslvl,comments";
			if (isset($_POST['PASSWORD']))
				$sql.=",passwd";
			$sql.=") value ('".$_POST['ID']."',
							'".$_POST['FIRSTNAME']."',
							'".$_POST['LASTNAME']."',
							'".$_POST['ACCESSLVL']."',
							'".$_POST['COMMENTS']."'";
			if (isset($_POST['PASSWORD']))
				$sql.=",'".md5($_POST['PASSWORD'])."'";
			$sql.=")";
			//echo $sql;
			mysql_query($sql, $_SESSION["writeServer"]);
			unset($_SESSION['DATA_CACHE'],$_POST['ID'],$_POST['FIRSTNAME'],$_POST['LASTNAME'],
					$_POST['ACCESSLVL'],$_POST['COMMENTS'],$_POST['PASSWORD']);
			$msg=$l->g(373);
		}		
	}else
	echo "<script>alert('".$ERROR."')</script>";

	}

echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
//echo "<tr><td align=center><b>CREATION / SUPPRESSION DES ".$data_on[$_POST['onglet']]."</b></td></tr>";


//add user
if ($_POST['onglet'] == 4){	

	$tab_typ_champ[0]['DEFAULT_VALUE']=$_POST['ID'];
	$tab_typ_champ[0]['INPUT_NAME']="ID";
	$tab_typ_champ[0]['CONFIG']['SIZE']=60;
	$tab_typ_champ[0]['CONFIG']['MAXLENGTH']=255;
	$tab_typ_champ[0]['INPUT_TYPE']=0;
	$tab_name[0]=$l->g(995).": ";
	
	$tab_typ_champ[1]['DEFAULT_VALUE']=$_POST['FIRSTNAME'];
	$tab_typ_champ[1]['INPUT_NAME']="FIRSTNAME";
	$tab_typ_champ[1]['CONFIG']['SIZE']=60;
	$tab_typ_champ[1]['CONFIG']['MAXLENGTH']=255;
	$tab_typ_champ[1]['INPUT_TYPE']=0;
	$tab_name[1]=$l->g(49).": ";
	
	$tab_typ_champ[2]['DEFAULT_VALUE']=$_POST['LASTNAME'];
	$tab_typ_champ[2]['INPUT_NAME']="LASTNAME";
	$tab_typ_champ[2]['CONFIG']['SIZE']=60;
	$tab_typ_champ[2]['CONFIG']['MAXLENGTH']=255;
	$tab_typ_champ[2]['INPUT_TYPE']=0;
	$tab_name[2]=$l->g(996).": ";
	
	$tab_typ_champ[3]['DEFAULT_VALUE']=$_POST['COMMENTS'];
	$tab_typ_champ[3]['INPUT_NAME']="COMMENTS";
	$tab_typ_champ[3]['CONFIG']['SIZE']=60;
	$tab_typ_champ[3]['CONFIG']['MAXLENGTH']=255;
	$tab_typ_champ[3]['INPUT_TYPE']=0;
	$tab_name[3]=$l->g(51).": ";
		
	$tab_typ_champ[4]['DEFAULT_VALUE']=$list_profil;
	$tab_typ_champ[4]['INPUT_NAME']="ACCESSLVL";
	$tab_typ_champ[4]['INPUT_TYPE']=2;
	$tab_name[4]=$l->g(66).":";
	if ($_SESSION['cnx_origine'] == "LOCAL"){
		//rajouter le password si authentification locale
		$tab_typ_champ[5]['DEFAULT_VALUE']=$_POST['PASSWORD'];
		$tab_typ_champ[5]['INPUT_NAME']="PASSWORD";
		$tab_typ_champ[5]['CONFIG']['SIZE']=30;
		$tab_typ_champ[5]['INPUT_TYPE']=0;
		$tab_name[5]=$l->g(217).":";
	}
	if (isset($msg))
	echo "<font color=green>".$msg."</font>";
	tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$l->g(244),$comment="");
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
	if ($_POST['onglet'] == ADMIN){
		$tab_options['LIEN_LBL']['ID']='admin_perim.php?id=';
		$tab_options['LIEN_CHAMP']['ID']='ID';
		$tab_options['LIEN_TYPE']['ID']='POPUP';
		$tab_options['POPUP_SIZE']['ID']="width=550,height=650";
	}
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,100,$tab_options);
		//traitement par lot
	$img['image/sup_search.png']=$l->g(162);
	del_selection($form_name);
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
