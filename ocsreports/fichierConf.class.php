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
		//looking for if languages table exist
		$table_exist=false;
		$sql="SHOW TABLES";
		$result = @mysql_query($sql, $_SESSION["readServer"]);
		while ($table = @mysql_fetch_object($result)){
			foreach ($table as $value){
				if ($value == "languages")
				$table_exist=true;
			}
			
		}
		if ($table_exist){
			$sql="select json_value from languages where name ='".$language."'";
			$result = @mysql_query($sql, $_SESSION["readServer"]);
			$item = @mysql_fetch_object($result);
		}
		if (!isset($_SESSION['plugin_rep']) or $_SESSION['plugin_rep'] == "")
		$_SESSION['plugin_rep']="pluggins/";
		$language_file=$_SESSION['plugin_rep']."language/".$language."/".$language.".txt";
		if (file_exists ( $language_file) 
		and !isset($item->json_value)
		){		
			$file=fopen($language_file,"r");		
			if ($file) {	
				while (!feof($file)) {
					$val = fgets($file, 1024);
					$tok1   =  rtrim(strtok($val," "));
					$tok2   =  rtrim(strtok(""));
					$this->tableauMots[$tok1] = $tok2;
				}
				fclose($file);	
				$toto=$this->tableauMots;
				if (!isset($item->json_value) and $table_exist){
					$sql="insert into languages (name,json_value) values ('".$language."','".mysql_real_escape_string(json_encode($toto))."')"; 
					@mysql_query( $sql, $_SESSION["writeServer"] );
				}
			
			} 
		}
		else{
			$this->tableauMots=json_decode($item->json_value,true);
		}
	}		
	function g($i)
	{
		//print_r($this);
		//If word doesn't exist for language, return default english word 
		if ($this->tableauMots[$i] == NULL) {
			$defword = new language(english);
			$word= $defword->tableauMots[$i];
		}else
			$word=$this->tableauMots[$i]; 
		//language mode
		if ($_SESSION['MODE_LANGUAGE']=="ON"){
			$_SESSION['EDIT_LANGUAGE'][$i]=$word;
			$word.="{<i><b>".$i."</b></i>}";		
		}
		return stripslashes($word);
	}

}		

?>
