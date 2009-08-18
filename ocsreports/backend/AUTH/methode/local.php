<?php
/*
 * connexion en local
 * 
 */
 
 
connexion_local();
mysql_select_db($db_ocs,$link_ocs);
$reqOp="SELECT id FROM operators WHERE id='".$login."' and passwd ='".md5($mdp)."'";
$resOp=mysql_query($reqOp, $link_ocs) or die(mysql_error($link_ocs));
$rowOp=mysql_fetch_object($resOp);
if (isset($rowOp -> id))
$login_successful = "OK";
else
$login_successful = $l->g(216);
$cnx_origine="LOCAL";
?>