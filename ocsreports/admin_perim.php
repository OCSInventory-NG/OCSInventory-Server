<?php
/*
 * Add tags for users
 * 
 */
 
require ('fichierConf.class.php');
$form_name='taguser';
$ban_head='no';
$no_error='YES';
require_once("header.php");
if (!($_SESSION["lvluser"] == SADMIN or $_SESSION['TRUE_LVL'] == SADMIN))
	die("FORBIDDEN");
printEnTete($l->g(616)." ".$_GET["id"] );
if( $_POST['ADD_TAG'] != "" ) {
	$tab_options['CACHE']='RESET';
	$tbi = $_POST["newtag"] ;
	@mysql_query( "INSERT INTO tags(tag,login) VALUES('".$tbi."','".$_GET["id"]."')", $_SESSION["writeServer"]  );
}
//suppression d'une liste de tag
if (isset($_POST['del_check']) and $_POST['del_check'] != ''){
	$list = "'".implode("','", explode(",",$_POST['del_check']))."'";
	$sql_delete="DELETE FROM tags WHERE tag in (".$list.") AND login='".$_GET["id"]."'";
	mysql_query($sql_delete, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	$tab_options['CACHE']='RESET';	
}

if(isset($_POST['SUP_PROF'])) {
	//$tbd = $_GET["supptag"];
	@mysql_query( "DELETE FROM tags WHERE tag='".$_POST['SUP_PROF']."' AND login='".$_GET["id"]."'", $_SESSION["writeServer"]  );
}
echo "<br><form name='".$form_name."' id='".$form_name."' method='POST'>";
$reqTags ="select tag from tags where login='".$_GET['id']."'";
$resTags = mysql_query( $reqTags, $_SESSION["readServer"] );
$valTags = mysql_fetch_array( $resTags );
if (isset($valTags['tag'])){
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	if (!(isset($_POST["pcparpage"])))
		 $_POST["pcparpage"]=5;
	$list_fields= array(TAG_LBL=>'tag',
						'SUP'=>'tag',
						'CHECK'=>'tag');
	$list_col_cant_del=array('ID'=>'ID','SUP'=>'SUP','CHECK'=>'CHECK');
	$default_fields=$list_fields; 
	$queryDetails = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK')
		$queryDetails .= $value.',';		
	} 
	$queryDetails=substr($queryDetails,0,-1);
	$queryDetails .= " FROM tags where login='".$_GET['id']."'";
	$tab_options['FILTRE']=array(TAG_LBL=>TAG_LBL);
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
//
echo "<FONT FACE='tahoma' SIZE=2>";
echo $l->g(617)." ".TAG_LBL.": <input type='text' id='newtag' name='newtag' value='".$_POST['newtag']."'>
		<input type='submit' name='ADD_TAG' value='envoyer'>";
echo "</form>";
?>

