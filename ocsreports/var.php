<?php
@session_start();
define("MAX_CACHED_SOFTS", 200 );		// Max number of softs that may be returned by optimizations queries
define("MAX_CACHED_REGISTRY", 200 );	// Max number of registry that may be returned by optimizations queries
define("USE_CACHE", 1 );				//Do we use cache tables ?
define("UPDATE_CHECKSUM", 1 );			// do we need to update software checksum when using dictionnary ?
define("UTF8_DEGREE", 1 );				// 0 For non utf8 database, 1 for utf8
define("GUI_VER", "5008");				// Version of the GUI
define("MAC_FILE", "files/oui.txt");	// File containing MAC database
define("SADMIN", 1);					// do NOT change
define("LADMIN", 2);   					// do NOT change
define("ADMIN", 3);						// do NOT change
define("TAG_NAME", "TAG"); 				// do NOT change
define("DEFAULT_LANGUAGE","french");
define("TAG_LBL", "Tag");				// Name of the tag information
define("PAG_INDEX","biere");            // define name in url (like multi=32)
?>
