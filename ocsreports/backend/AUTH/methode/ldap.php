<?php

connexion_local();
mysql_select_db($db_ocs,$link_ocs);
$sql="select substr(NAME,7) as NAME,TVALUE from config where NAME like '%CONEX%'";
$res=mysql_query($sql, $link_ocs) or die(mysql_error($link_ocs));
while($item = mysql_fetch_object($res)){
	$name[$item->NAME]=$item->TVALUE;
			define ($item->NAME,$item->TVALUE);
}

$login_successful=verif_pw_ldap($login, $mdp);
 $cnx_origine="LDAP";
 
function verif_pw_ldap($login, $pw) { 
    $info = search_on_loginnt($login); 
    if ($info["nbResultats"]!=1) 
        return ("BAD LOGIN OR PASSWORD"); // login does't exist
    return (ldap_test_pw($info[0]["dn"], $pw) ? "OK" : "BAD LOGIN OR PASSWORD"); 
} 
 
function search_on_loginnt($login) { 
	 $ds = ldap_connect(LDAP_SERVEUR,LDAP_PORT); 

      $attributs = array("dn"); 
      $filtre = "(".LOGIN_FIELD."={$login})"; 
      $sr = @ldap_search($ds,DN_BASE_LDAP,$filtre,$attributs); 
      $lce = @ldap_count_entries($ds,$sr); 
      $info = @ldap_get_entries($ds,$sr); 
    @ldap_close($ds); 
    $info["nbResultats"] = $lce; 
    return $info; 
} 
 
 
function ldap_test_pw($dn, $pw) { 
    $ds = ldap_connect(LDAP_SERVEUR,LDAP_PORT); 
    if (!$ds) { // avec ldap 2.x.x, ldap_connect est tjrs ok. La connection n'est ouverte qu'au bind 
      $r = false; 
    } else { 
      @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, LDAP_PROTOCOL_VERSION); 
      $r = @ldap_bind($ds, $dn, $pw); 
     @ldap_close($ds); 
      return $r; 
    } 
} 

?>