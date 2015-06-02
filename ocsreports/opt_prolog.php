<?php
/*
 * Created on 25 janv. 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'70%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));

if(!isset($optvalue['PROLOG_FREQ']))
$optvalueselected='SERVER DEFAULT';
else
$optvalueselected='CUSTOM';
$champ_value['VALUE']=$optvalueselected;
$champ_value['CUSTOM']=$l->g(487);
$champ_value['SERVER DEFAULT']=$l->g(488);
if (!isset($_POST['origine'])){	
	$champ_value['IGNORED']=$l->g(718);
	$champ_value['VALUE']='IGNORED';	
}
ligne("PROLOG_FREQ",$l->g(724),'radio',$champ_value,array('HIDDEN'=>'CUSTOM','HIDDEN_VALUE'=>$optvalue['PROLOG_FREQ'],'END'=>$l->g(730),'JAVASCRIPT'=>$numeric));
fin_tab($form_name);
 
 
?>
