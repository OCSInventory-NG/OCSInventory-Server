<?php 
	$list_fields=array();
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	print_item_header($l->g(896));
	$form_name="affich_notes";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	
	//suppression en masse
	if ($_POST['del_check'] != ''){
			$sql="update itmgmt_comments set visible=0 where id in (".$_POST['del_check'].")";
			mysql_query($sql, $_SESSION["writeServer"]);
		 	//regénération du cache
			$tab_options['CACHE']='RESET';	 	
	 }	

	if ($_POST['SUP_PROF'] != '' and isset($_POST['SUP_PROF'])){
		$sql="update itmgmt_comments set visible=0 where id=".$_POST['SUP_PROF'];
		mysql_query($sql, $_SESSION["writeServer"]);
		//regénération du cache
		$tab_options['CACHE']='RESET';
		addLog($l->g(896), " DEL => ".$_POST['SUP_PROF']);

	}
	
	if ($_POST['Valid_modif_x'] != '' and isset($_POST['Valid_modif_x'])){
		
		//ajout de note
		if (trim($_POST['NOTE']) != '' and isset($_POST['NOTE'])){
			$sql="insert into itmgmt_comments (HARDWARE_ID,DATE_INSERT,USER_INSERT,COMMENTS,ACTION) 
					value (".$systemid.",sysdate(),'".$_SESSION["loggeduser"]."','".xml_encode($_POST['NOTE'])."','ADD_NOTE_BY_USER')";
			mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["readServer"]));
			//regénération du cache
			$tab_options['CACHE']='RESET';			
		}elseif (trim($_POST['NOTE_MODIF']) != '' and isset($_POST['NOTE_MODIF'])){
			$sql="update itmgmt_comments set COMMENTS='".xml_encode($_POST['NOTE_MODIF'])."'";
			if (!strstr($_POST['USER_INSERT'], $_SESSION["loggeduser"]))
			$sql.=" , USER_INSERT = '".$_POST['USER_INSERT']."/".$_SESSION["loggeduser"]."'";
			$sql.=" where id=".$_POST['ID_MODIF'];
			mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["readServer"]));
			//regénération du cache
			$tab_options['CACHE']='RESET';				
			addLog($l->g(896), " UPDATE ".$_POST['ID_MODIF'].". => ".$_POST['OLD_COMMENTS'] );

		}		
		
	}
	if ($_POST['ADD_NOTE']){
		$tab_name[1]=$l->g(897)." : ";
		$tab_name[2]=$l->g(898)." : ";
		$tab_name[3]=$l->g(896)." : ";
		$tab_typ_champ[1]['DEFAULT_VALUE']=date("d/m/Y");
		$tab_typ_champ[2]['DEFAULT_VALUE']=$_SESSION["loggeduser"];
		$tab_typ_champ[1]['INPUT_TYPE']=3;
		$tab_typ_champ[2]['INPUT_TYPE']=3;
		$tab_typ_champ[3]['INPUT_NAME']='NOTE';
		$tab_typ_champ[3]['INPUT_TYPE']=1;
	    tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title,$comment);		
	}
	
	$queryDetails = "SELECT ID,DATE_INSERT,USER_INSERT,COMMENTS,ACTION FROM itmgmt_comments WHERE (visible is null or visible =1) and hardware_id=$systemid";
	$list_fields=array($l->g(897) => 'DATE_INSERT',
					   $l->g(899) => 'USER_INSERT',
					   $l->g(51) => 'COMMENTS',
					   $l->g(443)=>'ACTION',
					   'MODIF'=>'ID',
					   'SUP'=>'ID',
					   'CHECK'=>'ID');

	$list_col_cant_del=$list_fields;
	$default_fields= $list_fields;

	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);

	
	echo "<input type='hidden' id='del_check' name='del_check' value=''>";
	echo "<br><input type='submit' name='ADD_NOTE' id='ADD_NOTE' value='Ajouter une annotation'>";
	
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
		echo "<td align=center><a href=# onclick=garde_check(\"image/sup_search.png\",\"\")><img src='image/sup_search.png' title='".$l->g(162)."' ></a></td>";

	 echo "</tr></tr></table>";
	if (isset($_POST['MODIF']) and $_POST['MODIF'] != ''){
		$queryDetails = "SELECT ID,DATE_INSERT,USER_INSERT,COMMENTS,ACTION FROM itmgmt_comments WHERE id=".$_POST['MODIF'];
		$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$item=mysql_fetch_array($resultDetails,MYSQL_ASSOC);
		$tab_name[1]=" Date de la note : ";
		$tab_name[2]=" Auteur de la note : ";
		$tab_name[3]=" Note : ";
		$tab_typ_champ[1]['DEFAULT_VALUE']=$item['DATE_INSERT'];
		$tab_typ_champ[2]['DEFAULT_VALUE']=$item['USER_INSERT'];
		$tab_typ_champ[3]['DEFAULT_VALUE']=$item['COMMENTS'];
		$tab_typ_champ[1]['INPUT_TYPE']=3;
		$tab_typ_champ[2]['INPUT_TYPE']=3;
		$tab_typ_champ[3]['INPUT_NAME']='NOTE_MODIF';
		$tab_typ_champ[3]['INPUT_TYPE']=1;
		$tab_hidden['USER_INSERT']=$item['USER_INSERT'];
		$tab_hidden['ID_MODIF']=$_POST['MODIF'];
		$tab_hidden['OLD_COMMENTS']=$item['COMMENTS'];
	    tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title,$comment);
	
	}
	echo "</form>";
?>