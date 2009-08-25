<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2006/12/21 18:13:46 $$Author: plemmet $($Revision: 1.4 $)

class language
{		
	var  	$tableauMots;    // tableau contenant tous les mots du fichier 			
	function language($language) // constructeur
	{
		if (!isset($_SESSION['plugin_rep']) or $_SESSION['plugin_rep'] == "")
		$_SESSION['plugin_rep']="pluggins/";
		$file=fopen($_SESSION['plugin_rep']."language/".$language."/".$language.".txt","r");
		
		if ($file) {	
			while (!feof($file)) {
				$val = fgets($file, 1024);
				$tok1   =  rtrim(strtok($val," "));
				$tok2   =  rtrim(strtok(""));
				$this->tableauMots[$tok1] = $tok2;
			}
			fclose($file);	
		} 
	}		
	function g($i)
	{
		//If word doesn't exist for language, return default english word 
		if ($this->tableauMots[$i] == NULL) {
			$defword = new language(english);
			return $defword->tableauMots[$i];
		}
		return $this->tableauMots[$i]; 
	}

}		

?>
