<?php
/*
 * Created on 7 mai 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 function function_admin($size="width=650,height=450"){
 	global $l;
 	echo "<a href=# onclick=window.open(\"ipdiscover_admin_rsx.php?prov=add\",\"ADD_RSX\",\"location=0,status=0,scrollbars=1,menubar=0,resizable=0,".$size."\")><input type = button value='".$l->g(835)."'></a>";
	echo "&nbsp;";
	echo "<a href=# onclick=window.open(\"ipdiscover_admin_type.php\",\"ADD_TYPE\",\"location=0,status=0,scrollbars=1,menubar=0,resizable=0,width=800,height=500\")><input type = button value='".$l->g(836)."'></a>";				
 	
 	
 }
?>
