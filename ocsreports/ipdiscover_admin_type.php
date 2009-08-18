<?php
/*
 * Created on 7 mai 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require ('fichierConf.class.php');
$form_name='admin_type';
$ban_head='no';
$no_error='YES';
require_once("header.php");
if (!($_SESSION["lvluser"] == SADMIN or $_SESSION['TRUE_LVL'] == SADMIN))
	die("FORBIDDEN");
echo "<br><br><br>";	
echo "<form name='".$form_name."' id='".$form_name."' action='' method='post'>";

if (isset($_POST['SUP_PROF']) and $_POST['SUP_PROF'] != ''){
	$del_type=mysql_real_escape_string($_POST['SUP_PROF']);
	$sql="delete from devicetype where id='".$del_type."'";
	mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$tab_options['CACHE']='RESET';	
	
}

if (isset($_POST['Valid_modif_x'])){
	$new_type=mysql_real_escape_string($_POST['TYPE_NAME']);
	if (trim($new_type) == ''){
		$ERROR=$l->g(936);		
	}else{
		$sql="select ID from devicetype where NAME = '".$new_type."'";
		$res = mysql_query($sql, $_SESSION["readServer"] );
		$row=mysql_fetch_object($res);
		if (isset($row->ID))
		$ERROR=$l->g(937);	
	}
	if (isset($ERROR)){
		echo "<font color=red><b>".$ERROR."</b></font>";
		$_POST['ADD_TYPE']="VALID";
	}
	else{
		$sql="insert into devicetype (NAME) VALUES ('".$new_type."')";
		mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
		$tab_options['CACHE']='RESET';	
	}
}





if (isset($_POST['ADD_TYPE'])){
	$tab_typ_champ[0]['DEFAULT_VALUE']=$_POST['TYPE_NAME'];
	$tab_typ_champ[0]['INPUT_NAME']="TYPE_NAME";
	$tab_typ_champ[0]['CONFIG']['SIZE']=60;
	$tab_typ_champ[0]['CONFIG']['MAXLENGTH']=255;
	$tab_typ_champ[0]['INPUT_TYPE']=0;
	$tab_name[0]=$l->g(938).": ";
	$tab_hidden['pcparpage']=$_POST["pcparpage"];
	tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title,$comment="");	
}else{




//if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  )
//	die("FORBIDDEN");
$sql="select ID,NAME from devicetype";
$list_fields= array('ID' => 'ID',
					$l->g(49)=>'NAME',
					'SUP'=>'ID');
//$list_fields['SUP']='ID';	
$default_fields=$list_fields;
$list_col_cant_del=$list_fields;
if (!(isset($_POST["pcparpage"])))
	 $_POST["pcparpage"]=5;
$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$sql,$form_name,80,$tab_options); 

echo "<input type = submit value='".$l->g(307)."' name='ADD_TYPE'>";	
}
echo "</form>";
	
require_once($_SESSION['FOOTER_HTML']);
?>
