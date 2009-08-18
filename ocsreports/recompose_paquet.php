<?php
@session_start();
if($_SESSION["lvluser"]==SADMIN){
	$valid='OK';
	$document_root = $_SERVER["DOCUMENT_ROOT"]."/download/";
		$rep = $document_root = $_SERVER["DOCUMENT_ROOT"]."/download/".$_GET['id_pack'];
		$dir = opendir($rep);
		while($f = readdir($dir)){
			if ($_GET['id_pack'] == ''){
				if ($f != '.' and $f != '..')
		 		  echo "<a href='recompose_paquet.php?id_pack=".$f."'>".$f."</a><br>";
			}else{
				if ($f == "info"){
					//récupération du fichier info
					$filename = $rep.'/'.$f;
					$handle = fopen ($filename, "r");
					$info = fread ($handle, filesize ($filename));
					fclose ($handle);
					//surpression des balises
					$info=substr($info, 1);   
					$info=substr($info,0, -1);
					//récupration par catégories du fichier
					$info_traite=explode(" ",$info);
					//récupération du nom du fichier
					$name=$info_traite[10];
					if (substr($name,0,4) != 'NAME'){
						"<font color=red>PROBLEME AVEC LE NOM DU FICHIER</font><br>";
						$valid='KO';
					}
					if (substr($info_traite[6],0,5) != 'FRAGS'){
						"<font color=red>PROBLEME AVEC LE NOMBRE DE FRAGMENT</font><br>";
						$valid='KO';
					}
					$name=substr($name,6);
					$name=substr($name,0, -1);
					$name=str_replace(".", "_", $name).".zip";
					//récupération du nombre de fragments
					$nb_frag=$info_traite[6];
					$nb_frag=substr($nb_frag,7);
					$nb_frag=substr($nb_frag,0,-1);
				}			
			}
		}
		closedir($dir);
		
		if ($_GET['id_pack'] != '' and $valid == 'OK'){
			$temp="";
			$i=1;
			$filename = $rep.'/'.$_GET['id_pack'];
			$handfich_final = fopen( $rep.'/'.$name, "a+b" );
			while ($i <= $nb_frag){
				echo "Lecture du fichier ".$filename."-".$i." en cours...<br>";
				$handlefrag = fopen ($filename."-".$i, "r+b");
				$temp = fread ($handlefrag, filesize ($filename."-".$i));
				fclose ($handlefrag);			
				fwrite( $handfich_final, $temp );			
				flush();
				$i++;
			}
			fclose( $handfich_final );
			echo "<br><font color=green>FICHIER CREE</font>";
		}
		
}else
echo "PAGE INDISPONIBLE";


?>