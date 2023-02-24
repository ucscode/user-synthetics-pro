<?php 

defined( "ROOT_DIR" ) OR DIE('GREAT! &mdash; GLAD TO SEE YOU ACCESS THIS PAGE ILLEGALLY!');


// Iterate This Directory;

foreach( (new FileSystemIterator( __DIR__ )) as $sysIter ) {
	
	// Avoid local variable override by wrapping module inside function;
	
	call_user_func(function() use($sysIter) {
		
		/*
			check if the content is a folder;
			else skip;
		*/
		
		if( !$sysIter->isDir() ) return;
		
		// Get the index.php file;
		
		$modIndex = $sysIter->getPathname() . "/index.php";
		
		// Require the index.php file only if it exists;
		
		if( file_exists($modIndex) ) require_once $modIndex;
	
	});
	
};


/*	
	
	- An initialization phase for modules;
	
	- Modules that depend on other modules should run the on the "modules-loaded" event
	
	- The execution phase for modules
	
*/

events::exec("modules-loaded");


