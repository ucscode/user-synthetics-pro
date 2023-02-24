<?php

/*
	Project Name: User Synthetics
	
	PHP Version: 7.4+
	
	Author: Uchenna Ajah (UCSCODE)
	
*/

define( "ROOT_DIR", __DIR__ );

/******************************
	Directories;
******************************/

define( "VIEW_DIR", ROOT_DIR . '/view' );
define( "ASSETS_DIR", ROOT_DIR . '/assets' );
define( "MOD_DIR", ROOT_DIR . '/modules' );
define( "CLASS_DIR", ROOT_DIR . '/class' );


/****************************
	Main Classes
****************************/

$class = array(
	"core.php",
	"events.php",
	"sQuery.php",
	"pairs.php",
	"menufy.php",
	"DOMTable.php",
	"X2Client/X2Client.php"
);

foreach( $class as $filename ) require CLASS_DIR . "/{$filename}";

/**********************************
	Main Files;
**********************************/

require ROOT_DIR . '/conn.php';
require ROOT_DIR . '/uss.php';
require MOD_DIR . '/index.php';