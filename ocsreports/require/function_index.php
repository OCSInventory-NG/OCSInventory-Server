<?php
function getmicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}
function create_icon( $label, $index) {
        $llink = "?".PAG_INDEX."=$index";

        switch($index) {

                case 'ciney': $img = "codes"; break;
                case 'kro': $img = "securite"; break;
                case 1664: $img = "configuration"; break;
                case 'kwak': $img = "regconfig"; break;
                case 'tripel': $img = "doublons"; break;
                case 'cuvee_troll': $img = "agent"; break;
                case 'calsberg': $img = "administration"; break;
                case 'guinness': $img = "label"; break;
                case 'gouden': $img = "local"; break;
                case 'livinus': $img = "dictionnaire"; break;
                case 'duvel': $img = "aide";$llink = "http://wiki.ocsinventory-ng.org"; break;
                case 'delirium': $img = "ttlogiciels"; break;
                case 'gueuze': $img = "groups"; break;
                case 'duchesse_ane': $img = "log"; break;
                case 'gauloise': $img = "recherche"; break;
                case 'julius': $img = "statistiques"; break;
                case 'hoegaarden': $img = "ttmachines"; break;
                case 'chti': $img = "repartition"; break;
                case 'corsendonk': $img = "utilisateurs"; break;
        }
        if($_GET[PAG_INDEX] == $index && $index != "" ) {
                $img .= "_a";
        }

        //si on clic sur l'icone, on charge le formulaire
        //pour obliger le cache des tableaux a se vider
        return "<td onmouseover=\"javascript:montre();\"><a onclick='clic(\"".$llink."\");'><img title=\"".htmlspecialchars($label)."\" src='image/$img.png'></a></td>";
}


function menu_list($name_menu,$packAct,$nam_img,$title,$data_list)
{
        global $_GET;

        echo "<td onmouseover=\"javascript:montre('".$name_menu."');\">
        <dl id=\"menu\">
                <dt onmouseover=\"javascript:montre('".$name_menu."');\">
                <a href='javascript:void(0);'>
        <img src='image/".$nam_img;
        if( in_array($_GET[PAG_INDEX],$packAct) )
                echo "_a";
                echo ".png'></a></dt>
                        <dd id=\"".$name_menu."\" onmouseover=\"javascript:montre('".$name_menu."');\" onmouseout=\"javascript:montre();\">
                                <ul>
                                        <li><b>".$title."</b></li>";
                                        foreach ($data_list as $key=>$values){
                                                echo "<li><a href=\"index.php?".PAG_INDEX."=".$key."\">".$values."</a></li>";
                                        }
                echo "</ul>
                        </dd>
        </dl>
        </td> ";

}


//Creating array for icons (this is not really a function, juste for code reading)

{
	$icons_list['all_computers']=create_icon($l->g(2), 'hoegaarden');
	$icons_list['repart_tag']=create_icon($l->g(178), 'chti');	
	$icons_list['groups']=create_icon($l->g(583), 'gueuze');	
	$icons_list['all_soft']=create_icon($l->g(765), 'delirium');	
	$icons_list['multi_search']=create_icon($l->g(9), 'gauloise');	
	$icons_list['dict']=create_icon($l->g(380), 'livinus');	
	$icons_list['upload_file']=create_icon($l->g(17) , 'cuvee_troll');	
	$icons_list['regconfig']=create_icon($l->g(211), 'kwak');	
	$icons_list['logs']=create_icon($l->g(928), 'duchesse_ane');	
	$icons_list['admininfo']=create_icon($l->g(225), 'calsberg');	
	$icons_list['ipdiscover']=create_icon($l->g(174), 'kro');	
	$icons_list['doubles']=create_icon($l->g(175), 'tripel');	
	$icons_list['label']=create_icon($l->g(263), 'guinness');	
	$icons_list['users']=create_icon($l->g(243), 'corsendonk');	
	$icons_list['local']=create_icon($l->g(287), 'gouden');	
	$icons_list['help']=create_icon($l->g(570), 'duvel');	
}




?>
