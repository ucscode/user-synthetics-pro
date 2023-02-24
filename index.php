<?php require __DIR__ . '/config.php';

/*

	* If no `uss::view()` method was previously called
	
	* And no `uss::focus()` method is pointing to the current URL Query
	
	* An no module has called on `exit()` or `die()` method.
	
	* Then! error 404 page will be displayed
	
*/

if( is_null(uss::get_focus()) ):
	
	uss::view(function() {
		
		require VIEW_DIR . '/error-404.php';
		
	});

endif;

// close database connection;

if( uss::$global['mysqli'] instanceOf MYSQLI ) uss::$global['mysqli']->close();

