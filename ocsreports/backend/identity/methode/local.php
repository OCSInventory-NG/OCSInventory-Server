<?php
/* page de r�cup�ration en local des droits
 * et des tags sur lesquels l'utilisateur
 * a des droits
 * 
 * on doit renvoyer un tableau array('accesslvl'=>%%,'tag_show'=>array(%,%,%,%,%...))
 * si une erreur est rencontr�e, on retourne un code erreur
 * 
 */
	


//nom de la page
$name="local.php";
connexion_local();
mysql_select_db($db_ocs,$link_ocs);
//recherche du niveau de droit de l'utilisateur
$reqOp="SELECT accesslvl FROM operators WHERE id='".$_SESSION["loggeduser"]."'";
$resOp=mysql_query($reqOp, $link_ocs) or die(mysql_error($link_ocs));
$rowOp=mysql_fetch_object($resOp);
if (isset($rowOp -> accesslvl)){
	$lvluser=$rowOp -> accesslvl;
	//Si l'utilisateur a des droits limit�s
	//on va rechercher les tags sur lesquels il a des droits
	if ($lvluser == 3){
		$sql="select tag from tags where login='".$_SESSION["loggeduser"]."'";
		$res=mysql_query($sql, $link_ocs) or die(mysql_error($link_ocs));
		while ($row=mysql_fetch_object($res)){	
			$list_tag[$row->code]=$row->code;
		}
		if (!isset($list_tag))
			$ERROR=$l->g(893);
	}
}else
	$ERROR=$l->g(894);

////on revoie les valeurs
//if (isset($ERROR))
//	return $ERROR;
//else
//	return array('accesslvl'=>$lvluser,'tag_show'=>$list_tag);


?>