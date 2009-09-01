<?php


//Creating array for icons (this is not really a function, juste for code reading)

{
        $icons_list['all_computers']=create_icon($l->g(2), $pages_refs['all_computers']);
        $icons_list['repart_tag']=create_icon($l->g(178), $pages_refs['repart_tag']);
        $icons_list['groups']=create_icon($l->g(583), $pages_refs['groups']);
        $icons_list['all_soft']=create_icon($l->g(765), $pages_refs['all_soft']);
        $icons_list['multi_search']=create_icon($l->g(9), $pages_refs['multi_search']);
        $icons_list['dict']=create_icon($l->g(380), $pages_refs['dict']);
        $icons_list['upload_file']=create_icon($l->g(17) , $pages_refs['upload_file']);
        $icons_list['regconfig']=create_icon($l->g(211), $pages_refs['regconfig']);
        $icons_list['logs']=create_icon($l->g(928), $pages_refs['logs']);
        $icons_list['admininfo']=create_icon($l->g(225), $pages_refs['admininfo']);
        $icons_list['ipdiscover']=create_icon($l->g(174), $pages_refs['ipdiscover']);
        $icons_list['doubles']=create_icon($l->g(175), $pages_refs['doubles']);
        $icons_list['label']=create_icon($l->g(263), $pages_refs['label']);
        $icons_list['users']=create_icon($l->g(243), $pages_refs['users']);
        $icons_list['local']=create_icon($l->g(287), $pages_refs['local']);
        $icons_list['help']=create_icon($l->g(570), $pages_refs['help']);
}


function getmicrotime() {
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}


function create_icon( $label, $biere ) {
	
	global $pages_refs;
        $llink = "?".PAG_INDEX."=$biere";

        switch($biere) {

                case $pages_refs['codes']: $img = "codes"; break;
                case $pages_refs['ipdiscover']: $img = "securite"; break;
                case $pages_refs['configuration']: $img = "configuration"; break;
                case $pages_refs['regconfig']: $img = "regconfig"; break;
                case $pages_refs['doubles']: $img = "doublons"; break;
                case $pages_refs['upload_file']: $img = "agent"; break;
                case $pages_refs['admininfo']: $img = "administration"; break;
                case $pages_refs['label']: $img = "label"; break;
                case $pages_refs['local']: $img = "local"; break;
                case $pages_refs['dict']: $img = "dictionnaire"; break;
                case $pages_refs['help']: $img = "aide";$llink = "http://wiki.ocsinventory-ng.org"; break;
                case $pages_refs['all_soft']: $img = "ttlogiciels"; break;
                case $pages_refs['groups']: $img = "groups"; break;
                case $pages_refs['logs']: $img = "log"; break;
                case $pages_refs['multi_search']: $img = "recherche"; break;
                case $pages_refs['stats']: $img = "statistiques"; break;
                case $pages_refs['all_computers']: $img = "ttmachines"; break;
                case $pages_refs['repart_tag']: $img = "repartition"; break;
                case $pages_refs['users']: $img = "utilisateurs"; break;
        }
        if($_GET[PAG_INDEX] == $biere && $biere != "" ) {
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

?>
