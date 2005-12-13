<?
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on 7/7/2005
if(!class_exists("Req"))
{ 
/**
 * \brief Classe Req
 *
 * Cette classe contient un objet requete pour l'application
 */
class Req
{		
	var 	$label,   /// Nom et description de la requete      
		$sql,     /// Commande SQL de la requête	
		$sqlCount,     /// Commande SQL de la requete de comptage des résultats
		$labelChamps,  /// Tableau contenant le texte de tous les paramètres de la requete
		$sqlChamps,    /// Tableau contenant toutes les requetes de préremplissage des ComboBox de choix des parametres
		$typeChamps,   /// Tableau contenant les types de champs de saisie des parametres de la requete:
					   /// COMBO: combobox préremplie par les requetes de $sqlChamps ou
					   /// FREE : champ texte libre)					   
		$isNumber,     /// Tableau indiquant si un nombre est attendu pour le parametre 
		$columnEdit;   /// Indique si les colonnes sont éditables
		
	function Req($label,$sql,$sqlCount,$labelChamps,$sqlChamps,$typeChamps,$isNumber=NULL,$columnEdit=false) // constructeur
	{
		$this->label=$label;
		$this->sql=$sql;
		$this->sqlCount=$sqlCount;
		$this->labelChamps=$labelChamps;
		$this->sqlChamps=$sqlChamps;
		$this->typeChamps=$typeChamps;
		$this->isNumber=$isNumber;
		$this->columnEdit=$columnEdit;
	}
	
	function toHtml($link) // renvoie la page html présentant la requete
	{
		$result=NULL;
		$html="<br><table border=1 class= \"Fenetre\" WIDTH = '62%' ALIGN = 'Center' CELLPADDING='5'><th height=40px class=\"Fenetre\" colspan=2><b>".$this->label."</b>\n";
		$i=0;
		$html.="</th><form name=\"req2\" method=\"POST\" action=\"index.php\">\n";
		$html.="<input type=hidden name=lareq value=\"$this->label\">";
		if(isset($this->labelChamps[0]))
		foreach($this->labelChamps as $lbl) // On parcourt le tableau des parametres
		{
			$fond=($x == 1 ? "#FFFFFF" : "#F2F2F2");	// on alterne les couleurs de ligne
			$x = ($x == 1 ? 0 : 1) ;	
			
			if($lbl==NULL) break;
			
			$html.="<tr bgcolor=$fond height=40px>";
			if($this->typeChamps[$i]!="FREE"&&substr($this->sqlChamps[$i],0,6)=="SELECT") // Si c'est une combo
			{				
					$result = mysql_query( $this->sqlChamps[$i], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"])); // on execute la requete remplissant la combo
					//echo  $this->sqlChamps[$i];
					$nomColonne= mysql_fetch_field($result);					
			}
   			   	
			$html.="<td width=50% align=\"center\">".$lbl."</td><td width=50%>\n";
			
			switch($this->typeChamps[$i])
			{
				case "COMBO": $html.="<p align=\"left\"><select class=\"bouton\" name=option$i>";
							  $varr="option".$i;
							  $vall="";
							  if(isset($_POST[$varr]))
							  {
							  	$vall=$_POST[$varr];
							  	$html.="<option selected>".utf8_decode($vall)."</option>\n";
								$select="";
							  }
						  	  else
							  {
							  	$select="selected";
							  }
							  break;
							  
				case "FREE": $html.="<p align=\"left\"><input class=bouton type=\"text\" size=\"15\" maxlength=\"256\" ";
							 $varr="option".$i;
							 $vall=isset($_POST[$varr])?$_POST[$varr]:"";				
							 $html.="name=\"option$i\" value=\"".$vall."\"></p>\n";break;

			}
			
			if($this->typeChamps[$i]=="COMBO")
			{
				if(substr($this->sqlChamps[$i],0,6)=="SELECT")
					while($item = mysql_fetch_object($result))
					{
							// Ajouter $item dans la combo	
							$cl=$nomColonne->name;
							if((isset($_POST[$varr])&&$item->$cl!=$vall)  || !isset($_POST[$varr]))
								$html.="<option>".$item->$cl."</option>\n"; // on met l'objet trouvé dans la combo
					}
				else
				{
					$bouts = explode(",", $this->sqlChamps[$i]);
					foreach($bouts as $le)
						if($le!=$vall)
							$html.="<option>$le</option>\n"; // on met l'objet trouvé dans la combo
				}
			}
			if($this->typeChamps[$i]=="COMBO")
				$html.="</p></select>\n";
			$i++;
			$html.="</td></tr>";
		}
		if(isset($this->labelChamps[0]))
			$html.="<tr bgcolor=white height=40px><td colspan=2>
			<p align=\"right\"><input type=\"hidden\" name=\"sub\" value=\"Envoyer\"><input onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\" type=button class=\"bouton\" value=Envoyer OnClick='req2.submit()'>\n";
				
		$html.="</tr></FORM></table><br>\n";
		return $html;
	}
}
}
?>