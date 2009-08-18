<script>
function check() {
	var msg = '';
	if (document.getElementById('RULE_NAME').value == "")	{
		document.getElementById('RULE_NAME').style.backgroundColor = "RED";
		msg='NULL';
	}
	var nb_lign=(document.getElementsByTagName('select').length -2) /3;
	var i=1;
	while (i < (nb_lign+1)){
		champs = new Array('PRIORITE_'+i,'CFIELD_'+i,'OP_'+i,'COMPTO_'+i);
		for (var n = 0; n < champs.length; n++)
		{
			if (document.getElementById(champs[n]).value == ""){
			 document.getElementById(champs[n]).style.backgroundColor = "RED";
			 msg='NULL';
			 }
			else
			 document.getElementById(champs[n]).style.backgroundColor = "";
		}
		i++;
	
	}
	if (msg == "") return(true);
 	else	{
		return(false);
	}
}
</script>


<?php
/*
 * Created on 26 sept. 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
function verify_name($RULE_NAME,$condition=''){
	//verify this rule name exist
	$sql_exist="select id from download_affect_rules where rule_name='".trim($RULE_NAME)."' ";
	if ($condition != "")
	$sql_exist.= $condition;
	$result_rule_exist = mysql_query($sql_exist, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$rule_exist = mysql_fetch_object($result_rule_exist);
	if ($rule_exist->id)
	return 'NAME_EXIST';
	else
	return 'NAME_NOT_EXIST';	
}

function verify_rule($rule_or_condition,$ID){
	$result_id = mysql_query("select id from download_affect_rules where ".$rule_or_condition."='".$ID."'", 
				$_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$id_exist = mysql_fetch_object($result_id);
	if ($id_exist->id)
	return 'RULE_EXIST';
	else
	return 'RULE_NOT_EXIST';		
}


function delete_rule($ID_RULE){
	global $l;
	$id_exist=verify_rule('rule',$ID_RULE);
	if ($id_exist == "RULE_EXIST"){
		$sql_del_rule="delete from download_affect_rules where rule='".$ID_RULE."'";
		mysql_query($sql_del_rule, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	}else
	echo "<script>alert('".$l->g(672)."');</script>";	
}

function delete_condition_rule($ID){
	global $l;
	$id_exist=verify_rule('id',$ID);
	if ($id_exist == "RULE_EXIST"){
		$sql_del_rule="delete from download_affect_rules where id='".$ID."'";
		mysql_query($sql_del_rule, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	}else
	echo "<script>alert('".$l->g(672)."');</script>";
	
}

/*
 * Function for add new rule for redistribution server
 * 
 * $RULE_NAME= Name of the rule
 * $RULE_VALUES = array with condition values
 * 		=> ex: $RULE_VALUES['PRIORITE_1'],$RULE_VALUES['CFIELD_1'],
 * 			   $RULE_VALUES['OP_1'],$RULE_VALUES['COMPTO_1'],$RULE_VALUES['COMPTO_TEXT_1'],
 * 			   $RULE_VALUES['PRIORITE_2'],$RULE_VALUES['CFIELD_2'],
 * 			   $RULE_VALUES['OP_2'],$RULE_VALUES['COMPTO_2'],$RULE_VALUES['COMPTO_TEXT_2']
 * $ID_RULE= Id of the rule. It can't exist before
 * 
 */
function add_rule($RULE_NAME,$RULE_VALUES,$ID_RULE=''){
	global $l;
	$rule_exist=verify_name($RULE_NAME);
	if ($rule_exist == 'NAME_NOT_EXIST'){
		//verify this id is new
		$result_id = mysql_query("select id from download_affect_rules where id='".$ID_RULE."'", 
				$_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$id_exist = mysql_fetch_object($result_id);
		//generate id
		if (!is_numeric($ID_RULE) or $ID_RULE == '' or isset($id_exist->id)){	
			$sql_new_id="select max(RULE) as ID_RULE from download_affect_rules";
			$result_new_id = mysql_query($sql_new_id, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			$new_id = mysql_fetch_object($result_new_id);
			$ID_RULE=$new_id -> ID_RULE;
			$ID_RULE++;
		}
		//insert new rule
		$i=1;
		while ($RULE_VALUES['PRIORITE_'.$i]){
			if ($RULE_VALUES['CFIELD_'.$i] != "")
			{
				$sql_insert_rule="insert into download_affect_rules (RULE,RULE_NAME,PRIORITY,CFIELD,OP,COMPTO,SERV_VALUE) 
				value (".$ID_RULE.",'".$_POST['RULE_NAME']."',".$RULE_VALUES['PRIORITE_'.$i].",'".$RULE_VALUES['CFIELD_'.$i]."','".$RULE_VALUES['OP_'.$i]."','".$RULE_VALUES['COMPTO_'.$i]."','".$RULE_VALUES['COMPTO_TEXT_'.$i]."')";
				mysql_query($sql_insert_rule, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
				
			}
		$i++;
		}
	}
	else{
		echo "<script>alert('".$l->g(670)."');</script>";		
	}	
	
}
/*
 * HTML fields for condition of rule 
 * 
 */

function fields_conditions_rules($num,$entete='NO'){
	global $l;
	if ($entete != 'NO')
	$tab.="<tr bgcolor='#C7D9F5'><td>".$l->g(675)."</td><td>".$l->g(676)."</td><td>".$l->g(677)."</td><td>".$l->g(678)."</td></tr>";	
	$CFIELD=array('NAME'=>$l->g(679),'IPADDRESS'=>'@IP','IPSUBNET'=>'IPSUBNET','WORKGROUP'=>$l->g(680),'USERID'=>$l->g(681));
	$OP=array('EGAL'=>"=",'DIFF'=>"<>",'LIKE'=>'LIKE');
	if (!isset($_POST["PRIORITE_".$num]))
	$_POST["PRIORITE_".$num]=$num;
	$tab.="<tr><td>".show_modif($_POST["PRIORITE_".$num],"PRIORITE_".$num,'0')."</td>";
	$tab.="<td>".show_modif($CFIELD,"CFIELD_".$num,'2')."</td>";
	$tab.="<td>".show_modif($OP,"OP_".$num,'2')."</td>";
	$tab.="<td>".show_modif($CFIELD,"COMPTO_".$num,'2')."</td>";
	return $tab;
}
?>
