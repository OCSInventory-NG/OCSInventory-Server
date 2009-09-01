<?php
@session_start();
define("MAX_CACHED_SOFTS", 200 );		// Max number of softs that may be returned by optimizations queries
define("MAX_CACHED_REGISTRY", 200 );	// Max number of registry that may be returned by optimizations queries
define("USE_CACHE", 1 );				//Do we use cache tables ?
define("UPDATE_CHECKSUM", 1 );			// do we need to update software checksum when using dictionnary ?
define("UTF8_DEGREE", 1 );				// 0 For non utf8 database, 1 for utf8
define("GUI_VER", "5009");				// Version of the GUI
define("MAC_FILE", "files/oui.txt");	// File containing MAC database
define("SADMIN", 1);					// do NOT change
define("LADMIN", 2);   					// do NOT change
define("ADMIN", 3);						// do NOT change
define("TAG_NAME", "TAG"); 				// do NOT change
define("DEFAULT_LANGUAGE","french");
define("TAG_LBL", "Tag");				// Name of the tag information
define("PAG_INDEX","biere");            // define name in url (like multi=32)
//Creating array for page references (this is not really a function, juste for code reading)
$pages_refs['all_computers']='hoegaarden';
$pages_refs['configuration']='1664';
$pages_refs['repart_tag']='chti';
$pages_refs['groups']='gueuze';
$pages_refs['all_soft']='delirium';
$pages_refs['multi_search']='gauloise';
$pages_refs['dict']='livinus';
$pages_refs['upload_file']='cuvee_troll';
$pages_refs['regconfig']='kwak';
$pages_refs['logs']='duchesse_ane';
$pages_refs['admininfo']='calsberg';
$pages_refs['ipdiscover']='kro';
$pages_refs['doubles']='tripel';
$pages_refs['label']='guinness';
$pages_refs['users']='corsendonk';
$pages_refs['local']='gouden';
$pages_refs['help']='duvel';
$pages_refs['stats']='julius';
$pages_refs['codes']='ciney';
$pages_refs['blacklist']='westmalle';
$pages_refs['console']='malheur';
$pages_refs['components']='stella';
$pages_refs['tele_package']='mere_noel';
$pages_refs['tele_activate']='grimbergen';
$pages_refs['tele_affect']='grottenbier';
$pages_refs['tele_stats']='foster';
$pages_refs['tele_actives']='petasse';
$pages_refs['tele_massaffect']='bourgogne_des_flandres';
$pages_refs['rules_redistrib']='leffe';
$pages_refs['opt_param']='brigand';
$pages_refs['opt_ipdiscover']='becasse';
$pages_refs['opt_opt_suppr']='bonnet_rouge';
$pages_refs['admin_attrib']='moinette';
$pages_refs['group_show']='chapeau_faro';
$pages_refs['show_detail']='vondel';
?>
