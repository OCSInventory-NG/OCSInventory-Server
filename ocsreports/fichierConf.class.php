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
//Modified on $Date: 2006-12-21 18:13:46 $$Author: plemmet $($Revision: 1.4 $)

if(!class_exists("FichierConf"))
{ 
/**
 * \brief Classe FichierConf
 *
 * Cette classe contient un object FichierConf qui contient toutes les données du fichier conf
 */
class FichierConf
{		
	var  	$tableauMots;    // tableau contenant tous les mots du fichier 			
	
	function FichierConf($language) // constructeur
	{
		if( !isset($_SESSION["langueFich"])) {
			$_SESSION["langueFich"] = "languages/$language.txt";
		}
		
		$file=@fopen($_SESSION["langueFich"],"r");
		
		if (!$file) {
			$_SESSION["langueFich"] = "languages/".DEFAULT_LANGUAGE.".txt";
			$file=@fopen($_SESSION["langueFich"],"r");
		}
		
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
		return $this->tableauMots[$i];
	}
}		
}
?>