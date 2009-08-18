<?php
/*
 * Created on 26 mai 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

 $Directory=$_SESSION['plugin_rep'].'language/';
if (file_exists($Directory."config.txt")){
		$fd = fopen ($Directory."config.txt", "r");
		$capture='';
		while( !feof($fd) ) {				
			$line = trim( fgets( $fd, 256 ) );
			if (substr($line,0,2) == "</")
				$capture='';
			if ($capture == 'OK_ORDER')
				$list_pluggins[]=$line;
			if ($capture == 'OK_LBL'){				
				$tab_lbl=explode(":", $line);
				$list_lbl[$tab_lbl[0]]=$tab_lbl[1];
			}				
			if ($capture == 'OK_ISAVAIL'){
				$tab_isavail=explode(":", $line);
				$list_avail[$tab_isavail[0]]=$tab_isavail[1];
			}
			if ($line{0} == "<"){
				$capture = 'OK_'.substr(substr($line,1),0,-1);
			}
			//echo substr($line,0,5);
			flush();					
		}				
	fclose( $fd );
	//print_r($list_pluggins);
	}
//print_r($list_pluggins);
$i=0;
$show_lang= "<form id='language' name='language' action='' method='post'>";
while (isset($list_pluggins[$i])){
	if (file_exists($Directory.$list_pluggins[$i]."/".$list_pluggins[$i].".png"))
	$show_lang.= "<img src='pluggins/language/".$list_pluggins[$i]."/".$list_pluggins[$i].".png' width=\"20\" height=\"15\" OnClick='pag(\"".$list_pluggins[$i]."\",\"LANG\",\"language\");'>&nbsp;";
	else
	$show_lang.= "<a href=# OnClick='pag(\"".$list_pluggins[$i]."\",\"LANG\",\"language\");'>".$list_lbl[$list_pluggins[$i]]."</a>&nbsp;";
	$i++;	
}
$show_lang.= "<input type='hidden' id='LANG' name='LANG' value=''>";
$show_lang.= "</form>";
echo $show_lang;
?>
