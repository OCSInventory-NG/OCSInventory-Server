<?php
$list_registry_key=array('HKEY_CLASSES_ROOT',
						 'HKEY_CURRENT_USER',
						 'HKEY_LOCAL_MACHINE',
						 'HKEY_USERS',
						 'HKEY_CURRENT_CONFIG',
						 'HKEY_DYN_DATA (Windows 9X only)');
//require_once('require/function_registry.php');
//cas d'une suppression d'une clé de registre
if (isset($_POST['SUP_PROF']) and $_POST['SUP_PROF'] != ''){	
	$sql_reg="delete from regconfig where id='".$_POST['SUP_PROF']."'";
	mysql_query($sql_reg, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	$tab_options['CACHE']='RESET';
}

//cas de ajout/modification d'une clé
if (isset($_POST['Valid_modif_x'])){
	if (trim($_POST["NAME"])!= "" and 
		trim($_POST["REGTREE"])!= "" and
		trim($_POST["REGKEY"])!= "" and
		trim($_POST["REGVALUE"])!= "")
	{
		unset($req);
		if (isset($_POST['id'])){
			$req = "UPDATE regconfig SET ".	
				"NAME='".$_POST["NAME"]."',".
				"REGTREE='".$_POST["REGTREE"]."',".
				"REGKEY='".$_POST["REGKEY"]."',".
				"REGVALUE='".$_POST["REGVALUE"]."' ".
				"where ID='".$_POST['id']."'";
		}else{
			$sql_verif="select ID from regconfig 
						where REGTREE='".$_POST["REGTREE"]."' 
							and REGKEY='".$_POST["REGKEY"]."'
							and REGVALUE='".$_POST["REGVALUE"]."'";
			$res=mysql_query($sql_verif, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			$row=mysql_fetch_object($res);
			if (!is_numeric($row->ID)){				
			$req = "INSERT INTO regconfig (NAME,REGTREE,REGKEY,REGVALUE)
					VALUES('".$_POST["NAME"]."','".$_POST["REGTREE"]."','".$_POST["REGKEY"]."','".$_POST["REGVALUE"]."')";
			}else
			$error=$l->g(987);
			
		}
		
		if (isset($req)){
			$result = mysql_query($req, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
			$tab_options['CACHE']='RESET';
		}
	}else{
		$error=$l->g(988);
		
		
	}
	
}
if (isset($error)){
	if (isset($_POST['id']))
		$_POST['MODIF']=$_POST['id'];
		$_POST['ajout']='ADD';
	echo "<font color=red><b>".$error."</b></font>";
}

//cas d'une modification d'une clé de registre

if (isset($_POST['MODIF']) and $_POST['MODIF'] != ''){	
	if (!isset($_POST['NAME'])){
		$sql="select * from regconfig where id = '".$_POST['MODIF']."'";
		//$sql="select NAME,ID,MASK from subnet where netid='".$netid."'";
		$res=mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$row=mysql_fetch_object($res);
		$_POST['NAME']=$row->NAME;
		$_POST['REGTREE']=$row->REGTREE;
		$_POST['REGKEY']=$row->REGKEY;
		$_POST['REGVALUE']=$row->REGVALUE;
	}
	$title=$l->g(108);
	$tab_hidden['id']=$row->ID;
}

if (isset($tab_hidden['id']) or isset($_POST['ajout'])){	
	$tab_typ_champ[0]['DEFAULT_VALUE']=$_POST['NAME'];
	$tab_typ_champ[0]['INPUT_NAME']="NAME";
	$tab_typ_champ[0]['CONFIG']['SIZE']=60;
	$tab_typ_champ[0]['CONFIG']['MAXLENGTH']=255;
	$tab_typ_champ[0]['INPUT_TYPE']=0;
	$tab_name[0]=$l->g(252).": ";
	$tab_typ_champ[1]['DEFAULT_VALUE']=$list_registry_key;
	$tab_typ_champ[1]['INPUT_NAME']="REGTREE";
	$tab_typ_champ[1]['INPUT_TYPE']=2;
	$tab_name[1]=$l->g(253).":";
	$tab_typ_champ[2]['DEFAULT_VALUE']=$_POST['REGKEY'];
	$tab_typ_champ[2]['INPUT_NAME']="REGKEY";
	$tab_typ_champ[2]['CONFIG']['SIZE']=60;
	$tab_typ_champ[2]['CONFIG']['MAXLENGTH']=255;
	$tab_name[2]=$l->g(254).": ";
	$tab_typ_champ[3]['DEFAULT_VALUE']=$_POST['REGVALUE'];
	$tab_typ_champ[3]['INPUT_NAME']="REGVALUE";
	$tab_typ_champ[3]['CONFIG']['SIZE']=60;
	$tab_typ_champ[3]['CONFIG']['MAXLENGTH']=255;
	$tab_typ_champ[3]['INPUT_TYPE']=0;
	$tab_name[3]=$l->g(255).": ";
	$tab_hidden['FILTRE']=$_POST['FILTRE'];
	$tab_hidden['pcparpage']=$_POST['pcparpage'];
	$tab_hidden['page']=$_POST['page'];
	$tab_hidden['old_pcparpage']=$_POST['old_pcparpage'];
	$tab_hidden['tri2']=$_POST['tri2'];
	tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title,$comment="");
}






	$form_name="registry";
	$table_name="registry";
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields = array ( 'ID'   => "id", 
						   $l->g(49) => "name", 
						   'REGTREE' => "REGTREE",
						   'REGKEY' => "REGKEY",
						   'REGVALUE' => "REGVALUE",
						   'SUP' => "id",
						   'MODIF' => "id");
	
	$list_col_cant_del=array('SUP'=>'SUP','MODIF' => 'MODIF',$l->g(49)=> $l->g(49));
	$default_fields= array($l->g(49) => "name", 
						   'REGTREE' => "REGTREE",
						   'REGKEY' => "REGKEY",
						   'REGVALUE' => "REGVALUE",
						   'SUP' => "id",
						   'MODIF' => "id");
	$queryDetails  = "SELECT  ";
	foreach ($list_fields as $lbl=>$value){
		if ($lbl != 'SUP' and $lbl != 'MODIF'){
			$queryDetails .= $value;
				$queryDetails .=",";		
		}
	}
	$queryDetails  = substr($queryDetails,0,-1)." from regconfig ";
	$tab_options['FILTRE']['name']=$l->g(49);
	$tab_options['FILTRE']['REGKEY']='REGKEY';
	$tab_options['FILTRE']['REGVALUE']='REGVALUE';
	$tab_options['REPLACE_VALUE']['REGTREE']=$list_registry_key;
	$tab_options['LBL_POPUP']['SUP']='name';
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,95,$tab_options);
	echo "<br><input  class='bouton' name='ajout' type='submit' value='".$l->g(116)."' >";

	echo "</form>";

?>